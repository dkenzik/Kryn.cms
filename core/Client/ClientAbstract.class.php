<?php

/**
 * krynAuth - class to handle the sessions and authentication.
 *
 * @author MArc Schmidt <marc@Kryn.org>
 */

namespace Core\Client;

use Core\Kryn;
use Core\Utils;

/**
 * Client class.
 *
 * Handles authentification and sessions.
 * 
 */
abstract class ClientAbstract {

    /**
     * The auth token. (which is basically stored as cookie on the client side)
     */
    private $token = false;

    /**
     * Token id (cookie id)
     */
    private $tokenId = 'session_id';

    /**
     * Current session instance.
     *
     * @var \Session
     */
    private $session;

    /**
     * Contains the config.
     *
     * items:
     *   passwdHashCompat = false,
     *   passwdHashKey = <diggets>
     *   tokenId = "cookieName"
     *   timeout = <seconds>
     *   cookieDomain = '' (default is Domain->getDomain())
     *   cookiePath = '' (default is Domain->getPath())
     *   autoLoginLogout = false
     *   loginTrigger = auth-login
     *   logoutTrigger = auth-logout
     *   refreshing = false
     *   ipCheck = false
     *   garbageCollector = false
     *   store = array(
     *       class  = "\Core\Cache\Files",
     *       config = array(
     *       )
     *   )
     */
    private $config = array();

    /**
     * Instance of Cache class
     */
    private $cache;

    /**
     * Constructor
     *
     * @see $config
     */
    function __construct($pConfig) {

        if (!$pConfig['store']['class'])
            throw new \Exception('The storage class has not been defined.');
    
        $this->config = $pConfig;
        if ($this->config['tokenId'])
            $this->tokenId = $this->config['tokenId'];

        if (!$this->config['timeout'])
            $this->config['timeout'] = 3600;

        if (!$this->config['cookieDomain'] && Kryn::getDomain())
            $this->config['cookieDomain'] = Kryn::getDomain()->getDomain();

        if (!$this->config['cookiePath'] && Kryn::getDomain())
            $this->config['cookiePath'] = Kryn::getDomain()->getPath();

        if ($this->config['store']['class'] != 'database')
            $this->cache = new \Core\Cache\Controller($pConfig['store']['class'], $pConfig['store']['config']);

    }

    public function start() {

        $this->token = $this->getClientToken();
        error_log('sessionid: '.$this->token);
        $this->session = $this->fetchSession();

        if (!$this->session) {

            //no session found, create new one
            $this->session = $this->createSession();

        } else {

            //maybe we wanna check the ip ?
            if ($this->config['ipCheck']) {
                $ip = $this->get('ip');

                if ($ip != $_SERVER['REMOTE_ADDR']) {
                    $this->logout(); //force down to guest
                }
            }

            if ($this->refreshing)
                $this->updateSession();

        }

        if ($this->config['autoLoginLogout'])
            $this->handleClientLoginLogout();

        if ($this->config['garbageCollector'] )
            $this->removeExpiredSessions();
    }

    /**
     * Updates the time and refreshed-counter of a session.
     * 
     */
    public function updateSession() {

        $this->session->setTime(time());
        $this->session->setRefreshed( $this->session->getRefreshed()+1 );
        $this->session->setPage(Kryn::getRequestedPath(true));
        
    }

    /**
     * Handles the input (login/logout) of the client.
     */
    public function handleClientLoginLogout() {

        if (getArgv($this->config['loginTrigger'])) {

            $login = getArgv('username');

            if (getArgv('login'))
                $login = getArgv('login');

            $passwd = getArgv('passwd') ? getArgv('passwd') : getArgv('password');

            $userId = $this->login($login, $passwd);

            if (!$userId) {

                klog('authentication', str_replace("%s", getArgv('username'), "SECURITY Login failed for '%s'"));
                if (getArgv(1) == 'admin') {
                    json(0);
                }

            } else {

                if (getArgv(1) == 'admin') {

                    if (!Kryn::checkUrlAccess('admin/backend/', $this)) {
                        json(0);
                    }

                    klog('authentication', 'Successfully login to administration for user ' .
                        $this->getSession()->getUser()->getUsername());

                    $lastLogin = $this->getSession()->getUser()->getLastlogin();
                    if ($userId > 0) {
                        $this->getSession()->getUser()->setLastlogin(time());
                        $this->getSession()->getUser()->save();
                    }
                    json(array('user_id' => $userId, 'sessionid' => $this->token,
                        'username' => getArgv('username'), 'lastlogin' => $lastLogin));
                }

            }
        }

        if (getArgv($this->config['logoutTrigger'])) {
            $this->logout();
            $this->syncStore();
            if (getArgv(1) == 'admin') {
                json(true);
            }
        }
    }

    /**
     * Returns the user from current session.
     *
     * @return \User
     */
    public function getUser(){
        return $this->getSession()->getUser();
    }

    /**
     * Returns the user from current session.
     *
     * @return \User
     */
    public function getUserId(){
        return $this->getSession()->getUser()->getId();
    }

    /**
     * Auth against the internal user table.
     *
     * @param $pLogin
     * @param $pPassword
     * @return bool
     */
    protected function internalLogin($pLogin, $pPassword) {
        $state = $this->checkCredentialsDatabase($pLogin, $pPassword);
        return $state;
    }

    /**
     * Check credentials and set user_id to the session.
     * 
     * @param string $pLogin
     * @param string $pPassword
     * @return bool returns false, if someting went wrong.
     */
    public function login($pLogin, $pPassword) {

        if ($pLogin == 'admin')
            $state = $this->internalLogin($pLogin, $pPassword);
        else
            $state = $this->checkCredentials($pLogin, $pPassword);

        if ($state == false) {
            return false;
        }

        $this->setUser($state);
        $this->syncStore();

        return true;
    }

    /**
     * If the user has not been found in the system_user table, we've created it and
     * maybe this class want to map some groups to this new user.
     * 
     * Don't forget to clear the cache after updating.
     *
     * The default of this function searches 'default_group' in the auth_params
     * and maps the user automatically to the defined groups.
     * 
     * 'defaultGroups' => array(
     *    array('login' => 'LoginOrRegex', 'group' => 'group_id')
     * );
     * 
     * You can perfectly use the following ka.Field definition in your client properties:
     *
     *    "defaultGroup": {
     *        "label": "Group mapping",
     *        "desc": "Regular expression are possible in the login field. The group will be attached after the first login.",
     *        "type": "array",
     *        "columns": [
     *            {"label": "Login"},
     *            {"label": "Group", "width": "65%"}
     *        ],
     *        "fields": {
     *            "login": {
     *                "type": "text"
     *            },
     *            "group": {
     *                "type": "textlist",
     *                "multi": true,
     *                "store": "admin/backend/stores/groups"
     *            }
     *        }
     *    }
     *    
     *
     * @param \User $pUser The newly created user.
     */
    public function firstLogin($pUser) {

        if (is_array($this->config['defaultGroup'])) {
            foreach ($this->config['defaultGroup'] as $item) {

                if (preg_match('/' . $item['login'] . '/', $pUser['username']) == 1) {
                    dbInsert('system_user_group', array(
                        'group_id' => $item['group'],
                        'user_id' => $pUser['id']
                    ));
                }
            }
        }

    }


    /**
     * Setter for current user
     *
     * @param int $pUserId
     *
     * @return \Core\Client\ClientAbstract $this
     * @throws \Exception
     */
    public function setUser($pUserId = null) {

        if ($pUserId){
            $user = \UserQuery::create()->findPk($pUserId);

            if (!$user){
                throw new \Exception('User not found '.$pUserId);
            }

            $this->session->setUser($user);
        } else {
            $this->session->setUserId(null);
        }

        return $this;
    }


    /**
     * Change the user_id in the session object to 0. Means: is logged out then
     */
    public function logout() {
        $this->setUser();
        $this->syncStore();
    }

    /**
     * Removes all expired sessions.
     * 
     */
    public function removeExpiredSessions() {
        if ($this->config['store']['class'] == 'database'){
            //todo
        }
    }

    /**
     * When the scripts ends, we need to sync the session data to the backend.
     * 
     */
    public function syncStore() {
        if ($this->config['store']['class'] == 'database'){
            $this->getSession()->save();
        } else {
            $this->cache->set($this->tokenId . '_' . $this->token, serialize($this->getSession()), $this->config['timeout']);
        }
    }

    /**
     * Create new session in the backend and stores the newly created session id
     * into $this->token. 
     * 
     * @return bool|\Session The session object
     */
    public function createSession() {

        $session = false;

        for ($i = 1; $i <= 25; $i++) {

            $session = $this->createSessionById($this->generateSessionId());

            if ($session) {

                if ($this->config['store']['class'] == 'database')
                    $session->setIsStoredInDatabase(false);

                $this->token = $session->getId();

                setCookie($this->tokenId, $this->token, time() + $this->config['timeout'],
                    $this->config['path'], $this->config['domain']);
                return $session;
            }
        }

        //after 25 tries, we stop and log it.
        klog('session', t("The system just tried to create a session 25 times, but can't generate a new free session id.'.
            'Maybe the caching server is full or you forgot to setup a cronjob for the garbage collector."));

        return false;
    }


    /**
     * Creates a \Session object and store it in the current backend.
     * 
     * @param  string $pId
     * @return bool|\Session Returns false, if something went wrong otherwise a \Session object.
     */
    public function createSessionById($pId){

        $cacheKey = $this->tokenId . '_' . $pId;

        //this is a critical section, since between checking whether a session exists
        //and setting the session object, another thread or another server (in the cluster)
        //can write the cache key.
        //So we LOCK all kryn php instances, like in multithreaded apps, but with all
        //cluster buddies too.
        Utils::appLock('ClientCreateSession');

        //session id already used?
        $session = $this->fetchSession($cacheKey);
        if ($session) return false;

        $session = new \Session();
        $session->setId($pId)
            ->setTime(time())
            ->setIp($_SERVER['REMOTE_ADDR'])
            ->setPage(Kryn::getRequestedPath(true))
            ->setUseragent($_SERVER['HTTP_USER_AGENT']);

        if ($this->config['store']['class'] == 'database'){
            try {
                $session->save();
            } catch (\Exception $e){
                Utils::appRelease('ClientCreateSession');
                return false;
            }
        } else {
            $session->setIsStoredInDatabase(false);
            if (!$this->cache->set(cacheKey, $session, $expired)){
                Utils::appRelease('ClientCreateSession');
                return false;
            }

            $this->store->set($cacheKey, $this->getSession(), $this->config['timeout']);
        }

        Utils::appRelease('ClientCreateSession');
        return $session;
    }

    /**
     * Defined whether or not the class should process the client login/logout.
     *
     * @param boolean $pEnabled
     * @return ClientAbstract $this
     */
    public function setAutoLoginLogout($pEnabled){
        $this->autoLoginLogout = $pEnabled;
        return $this;
    }

    public function getToken(){
        return $this->token;
    }

    public function getTokenId(){
        return $this->tokenId;
    }


    public function setToken($pToken){
        $this->token = $pToken;
        return $this;
    }


    public function setTokenId($pTokenId){
        $this->tokenId = $pTokenId;
        return $this;
    }

    /**
     * Generates a new token/session id.
     * 
     * @return string The session id
     */
    public function generateSessionId() {
        return md5(microtime(true) . mt_rand() . mt_rand(50, 60 * 100));
    }

    /**
     * Trys to load a session based on current token or pToken from the cache or database backend.
     *
     * @return bool|\Session false if the session does not exist, and Session object, if found.
     */
    protected function fetchSession($pToken = null) {

        $token = $this->token;
        if ($pToken) $token = $pToken;

        if (!$token) return false;

        if ($this->config['store']['class'] == 'database'){
            return $this->loadSessionDatabase($token);
        } else {
            return $this->loadSessionCache($token);
        }
    }


    /**
     * Trys to load a session based on pToken from the database backend.
     *
     * @return bool|\Session false if the session does not exist, and Session object, if found.
     */
    protected function loadSessionDatabase($pToken) {

        $session = \SessionQuery::create()->findPK($pToken);

        if (!$session) return false;

        if ($session->getTime() + $this->config['timeout'] < time()) {
            $session->delete();
            return false;
        }

        /*if ($session->getExtra()) {
            $extra = @json_decode($session->getExtra(), true);
            if (is_array($extra))
                $row = array_merge($session->asArray(), $extra);
            $this->session->setExtra($extra);
        }*/

        return $session;
    }

    /**
     * Trys to load a session based on current pToken from the cache backend.
     *
     * @return bool|\Session false if the session does not exist, and Session object, if found.
     */
    public function loadSessionCache($pToken) {

        $cacheKey = $this->tokenId.'_'.$pToken;
        $session = $this->cache->get($cacheKey);

        if ($session && $session->getTime() + $this->config['session_timeout'] < time()) {
            $this->cache->delete($cacheKey);
            return false;
        }

        if (!$session) return false;

        return $session;
    }

    /**
     * Returns the token from the client
     *
     * @return string
     */
    public function getClientToken() {

        if ($_COOKIE[$this->tokenId]) return $_COOKIE[$this->tokenId];
        if ($_GET[$this->tokenId]) return $_GET[$this->tokenId];
        if ($_POST[$this->tokenId]) return $_POST[$this->tokenId];

        return false;
    }

    /**
     * Checks the given credentials.
     *
     * @param string $pLogin
     * @param string $pPassword
     *
     * @return bool|integer Returns false if credentials are wrong and returns the user id, if credentials are correct.
     */
    abstract public function checkCredentials($pLogin, $pPassword);


    /**
     * Generates a salt for a hashed password
     *
     * @param int $pLenth
     */
    public static function getSalt($pLength = 12) {

        $salt = 'a';

        for ($i = 0; $i < $pLength; $i++) {
            $salt[$i] = chr(mt_rand(33, 122));
        }

        return $salt;
    }

    /**
     * Returns a hashed password with salt through some rounds.
     */
    public static function getHashedPassword($pPassword, $pSalt) {

        $hash = md5(($pPassword . $pSalt) . $pSalt);

        for ($i = 0; $i < 5000; $i++) {
            for ($j = 0; $j < 32; $j++) {
                $hash[$j] = chr(ord($hash[$j]) + ord(Kryn::$config['passwdHashKey'][$j]));
            }
            $hash = md5($hash);
        }

        return $hash;
    }

    /**
     * @param string $loginTrigger
     * @return Auth $this
     */
    public function setLoginTrigger($loginTrigger){
        $this->loginTrigger = $loginTrigger;
        return $this;
    }

    /**
     * @return string
     */
    public function getLoginTrigger(){
        return $this->loginTrigger;
    }

    /**
     * @param string $logoutTrigger
     * @return Kryn\Auth $this
     */
    public function setLogoutTrigger($logoutTrigger){
        $this->logoutTrigger = $logoutTrigger;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogoutTrigger(){
        return $this->logoutTrigger;
    }

    /**
     * @param \Session $session
     */
    public function setSession($session){
        $this->session = $session;
    }

    /**
     * @return \Session
     */
    public function getSession(){
        return $this->session;
    }

    /**
     * @param array $config
     */
    public function setConfig($config){
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig(){
        return $this->config;
    }
}

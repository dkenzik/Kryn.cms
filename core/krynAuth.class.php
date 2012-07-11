<?php

/**
 * krynAuth - class to handle the sessions and authentication.
 *
 * @author MArc Schmidt <marc@kryn.org>
 */

class krynAuth {

    /**
     * The auth token. (which is basically stored as cookie on the client side)
     */
    public $token = false;

    /**
     * The token id (the name of the cookie on the client side)
     */
    public $tokenid = 'krynsessionid';


    /**
     * Some session informations
     * Modified by set() and get()
     * The system uses following items, so your should't override it:
     *    language, time, refreshed, ip, user_id, page, useragent
     */
    private $session;

    /**
     * For backwards compatibility the user_id from $this->user['id']
     */
    public $user_id = 0;

    /**
     * Same value as $user_id or $user['id']
     *
     * @var int
     */
    public $id = 0;

    /**
     * Some user informations from the system_user table.
     */
    public $user;

    /**
     * Defines whether set() was called and changed $session and therefore
     * we need at the end of the script a sync to the backend (database/memcache)
     * Idea behind this: We get more speed, when only saving the combined data at the end,
     * instead of saving as far as it has been changed.
     */
    private $needSync = false;


    /**
     * Contains the config. Items: 'session_timeout', 'session_storage', 'auth_class', 'auth_params' => array('<auth_class>' => array())
     */
    public $config = array();

    /**
     * Object of krynCache.class
     */
    public $cache;

    /**
     * Defines whether processHandler() is called initially
     * @var bool
     */
    public $autoLoginLogout = false;

    /**
     * The HTTP GET/POST key which triggers the login.
     * admin?users-login=1
     * @var string
     */
    public $loginTrigger = 'users-login';

    /**
     * The HTTP GET/POST key which triggers the logout.
     * admin?users-logout=1
     * @var string
     */
    public $logoutTrigger = 'users-logout';


    /**
     * Increases for each valid request the 'refreshed' value in session values
     *
     * @var bool
     */
    public $refreshing = true;

    /**
     * Constructor
     */
    function __construct($pConfig, $pWithRefreshing = true) {

        $this->config = $pConfig;

        if ($pConfig['session_tokenid']) {
            $this->tokenid = $pConfig['session_tokenid'];
        }

        $this->refreshing = $pWithRefreshing;

        if (!$this->config['session_storage'])
            $this->config['session_storage'] = 'database';

        if (!$this->config['session_timeout'])
            $this->config['session_timeout'] = 3600 * 12;

        if ($pConfig['session_storage'] != 'database') {
            $this->cache = new krynCache($pConfig['session_storage'], $pConfig['session_storage_config']);
        }

    }

    public function start() {

        $this->token = $this->getToken();
        $this->session = $this->loadSession();

        error_log('sessionid: '.$this->token);

        $this->startSession = $this->session;

        if (!$this->session) {

            //no session found, create new one
            $this->session = $this->newSession();

        } else {

            //maybe we wanna check the ip ?
            if ($this->config['session_ipcheck'] == 1) {
                $ip = $this->get('ip');

                if ($ip != $_SERVER['REMOTE_ADDR']) {
                    $this->setUser(0); //force down to guest
                }
            }

            if ($this->refreshing)
                $this->updateSession();

        }

        $this->loadUser($this->session['user_id']);

        if( $this->autoLoginLogout )
            $this->handleClientLoginLogout();

        if ($this->config['session_autoremove'] == 1)
            $this->removeExpiredSessions();
    }

    /**
     * Updates the time and refreshed-counter of a session.
     */
    public function updateSession() {

        $this->set('time', time());
        $this->set('refreshed', $this->get('refreshed') + 1);

    }

    /**
     * Handles the input (login/logout) of the client.
     */
    public function handleClientLoginLogout() {

        if (getArgv($this->loginTrigger)) {

            $login = getArgv('username');

            if (getArgv('login'))
                $login = getArgv('login');

            $passwd = getArgv('passwd') ? getArgv('passwd') : getArgv('password');

            $user = $this->login($login, $passwd);

            if (!$user) {

                klog('authentication', str_replace("%s", getArgv('username'), "SECURITY Login failed for '%s'"));
                if (getArgv(1) == 'admin') {
                    json(0);
                }

            } else {

                $this->user = $user;
                $this->user_id = $user['id'];

                if (getArgv(1) == 'admin') {

                    if (!kryn::checkUrlAccess('admin/backend/', $this)) {
                        json(0);
                    }

                    klog('authentication', 'Successfully login to administration for user ' . $this->user['username']);

                    if ($user['id'] > 0) {
                        dbUpdate('system_user', 'rsn = ' . $user['id'], array('lastlogin' => time()));
                        $this->clearCache();
                    }
                    json(array('user_id' => $this->user_id, 'sessionid' => $this->token,
                        'username' => getArgv('username'), 'lastlogin' => $this->user['lastlogin'],
                        'lang' => $this->user['settings']['adminLanguage']));
                }

            }
        }

        if (getArgv($this->logoutTrigger)) {
            $this->logout();
            $this->syncStore();
            if (getArgv(1) == 'admin') {
                json(true);
            }
        }
    }

    /**
     * Set the current user of the session.
     */
    public function setUser($pUserRsn, $pLoadUser = true) {

        $this->set('user_id', $pUserRsn); //will be saved at shutdown

        if ($pLoadUser)
            $this->loadUser($pUserRsn);
    }

    /**
     * Auth against the internal user table.
     */
    protected function internalLogin($pLogin, $pPassword) {
        $state = $this->checkCredentialsDatabase($pLogin, $pPassword);
        return $state;
    }

    /**
     * Do the authentication against the defined backend and return the new user if login was sucessful
     */
    public function &login($pLogin, $pPassword) {

        if ($pLogin == 'admin')
            $state = $this->internalLogin($pLogin, $pPassword);
        else
            $state = $this->checkCredentials($pLogin, $pPassword);

        if ($state == false) {
            return false;
        }

        //Search user in the system_user table. If not exist, create it
        $user = $this->getOrCreateUser($pLogin);
        $this->setUser($user['id']);
        $this->syncStore();

        return $user;
    }

    /**
     * Checks whether a valid logins exists in our system_user database.
     */
    public function &getOrCreateUser($pLogin) {


        if ($this->credentials_row) {
            //since we have already the data with checkCredentialsDatabase we just use this
            //information instead of fetching it new
            $user =& $this->credentials_row;
        } else {
            $where = "username = '" . esc($pLogin) . "'";
            if (!$this->config['auth_class'] || $this->config['auth_class'] == 'kryn')
                $where .= " AND (auth_class IS NULL OR auth_class = 'kryn')";
            else
                $where .= " AND auth_class = '" . $this->config['auth_class'] . "'";

            $user = dbExfetch('
            SELECT rsn FROM %pfx%system_user
            WHERE ' . $where,
                1);
        }

        if (!$user) {
            $rsn = dbInsert('system_user', array('username' => $pLogin, 'auth_class' => $this->config['auth_class']));
            $user = dbTableFetch('system_user', 'rsn = ' . $rsn, 1);
            $this->firstLogin($user);
        }

        return $this->getUser($user['id']);
    }


    /**
     * If the user was not found in the system_user table, we've created it and
     * maybe the auth class want to map some groups to this new user.
     * Don't forget to clear the cache after updating.
     *
     * The default of this function searches 'default_group' in the auth_params
     * and maps the user automatically to the defined groups.
     * 'default_groups' => array(
     *    array('login' => 'LoginOrRegex', 'group' => 'group_rsn')
     * );
     * You can perfectly use the following ka.Field in your auth properties:
     *
     *    "default_group": {
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
     * @param array $pUser The newly created user (scheme as in system_user table)
     */
    public function firstLogin($pUser) {

        if (is_array($this->config['default_group'])) {
            foreach ($this->config['default_group'] as $item) {

                if (preg_match('/' . $item['login'] . '/', $pUser['username']) == 1) {
                    dbInsert('system_groupaccess', array(
                        'group_rsn' => $item['group'],
                        'user_id' => $pUser['id']
                    ));
                    $this->clearCache($pUser['id']);
                }

            }
        }

    }

    /**
     * Clears the cache of the current user.
     *
     * @param boolean $pUserRsn
     * @internal
     */
    private function clearCache($pUserRsn = false) {
        if (!$pUserRsn) $this->user_id;
        $this->getUser($this->user_id, true);
    }

    /**
     * @param $pUserRsn
     */
    public function loadUser($pUserRsn) {

        $this->user =& $this->getUser($pUserRsn);
        $this->user_id = $this->user['id'];
        $this->id = $this->user['id'];

        tAssign('user', $this->user);
    }

    /**
     * Returns user information
     *
     * @param int $pUserId The rsn of the system_user table
     * @param bool $pForceReload to reload the cache
     * @return array|bool returns false if not found
     */
    public function &getUser($pUserId, $pForceReload = false) {

        $pUserId += 0;

        $cacheCode = 'system-users-' . $pUserId;
        $result =& kryn::getCache($cacheCode);

        if ($pUserId == 0){

            return array(
                'id' => 0,
                'username' => 'Guest',
                'groups' => array(0),
                'inGroups' => '0'
            );
        }

        if ($result == false || $pForceReload) {
            $result = dbExfetch("SELECT * FROM %pfx%system_user WHERE rsn = " . $pUserId, 1);

            if ($result['id'] <= 0) return false;

            $result['settings'] = unserialize($result['settings']);

            if ($result['settings']['userBg'] == '')
                $result['settings']['userBg'] = '/admin/images/userBgs/defaultImages/1.jpg';


            $result['groups'] = array();
            $statement = dbExec(
                'SELECT group_rsn FROM %pfx%system_groupaccess
    		  WHERE user_id = ' . $pUserId);

            while ($row = dbFetch($statement)) {
                $result['groups'][] = $row['group_rsn'];
            }

            $result['inGroups'] = '0';
            if (count($result['groups']) > 0)
                foreach ($result['groups'] as $group)
                    $result['inGroups'] .= ',' . $group;

            kryn::setCache($cacheCode, $result);
            $result =& kryn::getCache($cacheCode);

        }

        return $result;
    }

    /**
     * Change the user_id in the session object. Means: is logged out then
     */
    public function logout() {
        $this->setUser(0, true);
        $this->syncStore(true);
    }

    /**
     * Removes all expired sessions.
     * If the user configured no 'session_autoremove', then this method
     * is called through a cronjob. Last method is basically better regarding
     * the performance.
     */
    public function removeExpiredSessions() {
        $lastTime = time() - $this->config['session_timeout'];
        dbDelete('system_sessions', 'time < ' . $lastTime);

    }

    /**
     * Sets the language of the current session
     */
    public function setLang($pLang) {
        if ($this->getLang() != $pLang)
            $this->set('language', $pLang);
    }

    /**
     * Gets the language of the current session
     */
    public function getLang() {
        return $this->get('language');
    }

    /**
     * When the scripts ends, we need to sync the stored data ($this->session, which has been changed with set())
     * to the backend.
     */
    public function syncStore( $pForce = false ) {

        if (!$pForce && $this->needSync != true) return;
        $session['user_id'] = $this->user['id'];

        if ($this->config['session_storage'] == 'database') {

            $session['language'] = $this->session['language'];
            $session['time'] = $this->session['time'];
            $session['refreshed'] = $this->session['refreshed'];
            $session['ip'] = $this->session['ip'];

            $sessionExtra = $this->session;
            $notInExtra = array('language', 'time', 'refreshed', 'ip', 'user_id', 'page', 'useragent', 'extra');
            foreach ($notInExtra as $temp)
                unset($sessionExtra[$temp]);

            $session['extra'] = json_encode($sessionExtra);
            dbUpdate('system_sessions', "id = '" . esc($this->token) . "'", $session);

        } else {
            $expired = $this->config['session_timeout'];
            $this->cache->set($this->tokenid . '_' . $this->token, $this->session, $expired);

        }

    }

    /**
     * Gets values of the current session
     * @return mixed
     */
    public function &get($pCode) {
        return $this->session[$pCode];
    }

    /**
     * Stores additional information into the current session.
     * The system uses following codes, so your should't override it:
     *    language, time, refreshed, ip, user_id, page, useragent
     */
    public function set($pCode, $pValue) {

        if ($this->session[$pCode] == $pValue) return;

        $this->needSync = true;
        $this->session[$pCode] = $pValue;
    }

    /**
     * Creates a new token and session in the backend
     * @return array The session object
     */
    public function newSession() {

        $session = false;

        for ($i = 1; $i <= 25; $i++) {
            if ($this->config['session_storage'] == 'database') {
                $session = $this->newSessionDatabase();
            } else {
                $session = $this->newSessionCache();
            }
            if ($session) {
                setCookie($this->tokenid, $this->token, time() + 3600 * 24 * 7, kryn::$config['path']); //7 Days
                return $session;
            }
        }

        //after 25 tries, we stop and log it.
        klog('session', _l("The system just tried to create a session 25 times, but can't generate a new free session id. Maybe the caching server is full or you forgot to setup a cronjob for the garbage collector."));
        return false;
    }


    /**
     * Creates a new token and session
     */
    public function newSessionCache() {

        $token = $this->generateSessionId();

        $exist = $this->cache->get($this->tokenid . '_' . $token);

        if ($exist !== false) {
            return false;
        }

        $session = array(
            'user_id' => 0,
            'time' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'page' => kryn::getRequestPageUrl(true),
            'useragent' => $_SERVER['HTTP_USER_AGENT'],
            'refreshed' => 0
        );

        $expired = $this->config['session_timeout'];

        if (!$this->cache->set($this->tokenid . '_' . $token, $session, $expired))
            return false;

        $this->token = $token;
        return $session;
    }


    /**
     * Creates a new token and session in the database
     * @return array The session object
     */
    public function newSessionDatabase() {

        $token = $this->generateSessionId();
        $row = dbExfetch("SELECT id FROM %pfx%system_sessions WHERE id = '$token'", 1);
        if ($row['id']) {
            //another session with this id exists
            return false;
        }

        $session = array(
            'id' => $token,
            'user_id' => 0,
            'time' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'page' => kryn::getRequestPageUrl(true),
            'useragent' => $_SERVER['HTTP_USER_AGENT'],
            'refreshed' => 0
        );

        dbInsert('system_sessions', $session);
        $this->token = $token;
        unset($session['id']);
        return $session;
    }

    /**
     * Generates a new token/session id
     * @return string The session id
     */
    public function generateSessionId() {
        return md5(microtime(true) . mt_rand() . mt_rand(50, 60 * 100));
    }

    /**
     * Loads the session based on the given token from the client
     *
     * @return array Session object
     */
    public function loadSession() {

        if (!$this->token) return false;

        if ($this->config['session_storage'] == 'database')
            return $this->loadSessionDatabase();
        else
            return $this->loadSessionCache();
    }


    /**
     * Loads the session based on the given token from the client in the database
     */
    public function loadSessionDatabase() {

        $row = dbExfetch('SELECT * FROM %pfx%system_sessions WHERE id = \'' . esc($this->token) . '\'', 1);

        if (!$row) return false;
        if ($row['time'] + $this->config['session_timeout'] < time()) {
            dbExec('DELETE FROM %pfx%system_sessions WHERE id = \'' . esc($this->token) . '\'');
            return false;
        }

        unset($row['created']);
        unset($row['id']);

        if ($row['extra']) {
            $extra = @json_decode($row['extra'], true);
            if (is_array($extra))
                $row = array_merge($row, $extra);
        }

        return $row;
    }

    /**
     * Loads the session based on the given token from the client
     */
    public function loadSessionCache() {

        $session = $this->cache->get($this->tokenid . '_' . $this->token);

        if ($session && $session['time'] + $this->config['session_timeout'] < time()) {
            $this->cache->delete($this->tokenid . '_' . $this->token);
            return false;
        }

        if (!$session) return false;

        return $session;
    }

    /**
     * Returns the token from the client
     * @return string
     */
    public function getToken() {

        if ($_GET[$this->tokenid]) return $_GET[$this->tokenid];
        if ($_POST[$this->tokenid]) return $_POST[$this->tokenid];
        if ($_COOKIE[$this->tokenid]) return $_COOKIE[$this->tokenid];

        return false;
    }

    /**
     * Checks the given credentials.
     *
     * @param $pLogin    string
     * @param $pPassword string
     *
     * @return bool
     */
    public function checkCredentials($pLogin, $pPassword) {

        return $this->checkCredentialsDatabase($pLogin, $pPassword);
    }

    /**
     * Checks the given credentials in the database
     */
    protected function checkCredentialsDatabase($pLogin, $pPassword) {

        $login = esc($pLogin);

        $userColumn = 'username';
        if ($this->config['auth_params']['email_login'] && strpos($pLogin, '@') !== false && strpos($pLogin, '.') !== false)
            $userColumn = 'email';

        $saltField = ', passwd_salt';
        $columns = database::getOptions('system_user');
        if (!$columns['passwd_salt'])
            $saltField = '';

        $row = dbExfetch("
            SELECT id, passwd $saltField
            FROM %pfx%system_user
            WHERE 
                    id > 0
                AND $userColumn = '$login'
                AND (auth_class IS NULL OR auth_class = 'kryn')",
            1);

        if ($row['id'] > 0) {

            if ($row['passwd_salt']) {
                $hash = self::getHashedPassword($pPassword, $row['passwd_salt']);
            } else {
                if (kryn::$config['passwd_hash_compatibility'] != 1) return false;
                //compatibility
                $hash = md5($pPassword);
            }

            if ($hash != $row['passwd']) return false;

            $this->credentials_row = $row;
            return true;
        }
        return false;
    }

    /**
     *
     * Generates a salt for a hashed password
     */
    public static function getSalt($pLength = 12) {

        $salt = 'a';

        for ($i = 0; $i < $pLength; $i++) {
            $salt[$i] = chr(mt_rand(33, 122));
        }

        return $salt;
    }

    /**
     *
     * Returns a hashed password with salt through some rounds.
     */
    public static function getHashedPassword($pPassword, $pSalt) {

        $hash = md5(($pPassword . $pSalt) . $pSalt);

        for ($i = 0; $i < 5000; $i++) {
            for ($j = 0; $j < 32; $j++) {
                $hash[$j] = chr(ord($hash[$j]) + ord(kryn::$config['passwd_hash_key'][$j]));
            }
            $hash = md5($hash);
        }

        return $hash;
    }
}

?>
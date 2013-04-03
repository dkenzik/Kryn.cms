<?php

/*
 * This file is part of Kryn.cms.
 *
 * (c) Kryn.labs, MArc Schmidt <marc@kryn.org>
 *
 * To get the full copyright and license informations, please view the
 * LICENSE file, that was distributed with this source code.
 *
 */

namespace Admin;

use \Core\Kryn;
use \Core\Object;

class AdminController
{
    /**
     * Checks the access to the administration URLs and redirect to administration login if no access.
     *
     * @internal
     * @static
     */
    public static function checkAccess($pUrl)
    {
        return true;

        if (substr($pUrl, 0, 9) == 'admin/ui/') {
            return true;
        }

        if ($pUrl == 'admin/login') {
            return true;
        }

        //todo, use Permission class

        //if (Kryn::checkUrlAccess($pUrl))
        //    throw new \AccessDeniedException(tf('Access denied.'));
    }

    public function exceptionHandler($pException)
    {
        if (get_class($pException) != 'AccessDeniedException')
            \Core\Utils::exceptionHandler($pException);
    }

    public function run()
    {
        @header('Expires:');

        if (Kryn::$config['displayDetailedRestErrors']) {
            $exceptionHandler = array($this, 'exceptionHandler');
            $debugMode = true;
        }

        $url = Kryn::getRequest()->getPathInfo();

        //checkAccess
        $this->checkAccess($url);

        $blackListForEntryPoints = array('backend', 'login', 'logged-in', 'logout', 'ui', 'system', 'file', 'object',
                                         'object-by-url');

        if (array_search(getArgv(1), $blackListForEntryPoints) === false) {

            $url =  substr($url, strlen('/admin/'));
            $entryPoint = Utils::getEntryPoint($url);

            if ($entryPoint) {

                //is window entry point?
                $objectWindowTypes = array('list', 'edit', 'add', 'combine');

                if (in_array($entryPoint['type'], $objectWindowTypes)) {
                    $epc = new ObjectCrudController(($entryPoint['_module'] == 'admin' && getArgv(2) != 'admin' ? '': 'admin/') . $entryPoint['_url']);
                    $epc->setExceptionHandler($exceptionHandler);
                    $epc->getClient()->setUrl(substr(Kryn::getRequest()->getPathInfo(), 1));
                    $epc->setDebugMode($debugMode);
                    $epc->setEntryPoint($entryPoint);
                    die($epc->run());
                } elseif ($entryPoint['type'] == 'store') {

                    $clazz = $entryPoint['class'];
                    if (!$clazz) throw new \ClassNotFoundException(sprintf('The property `class` is not defined in entry point `%s`', $entryPoint['_url']));
                    if (!class_exists($clazz)) throw new \ClassNotFoundException(sprintf('The class `%s` does not exist in entry point `%s`', $clazz, $entryPoint['_url']));

                    $obj = new $clazz($entryPoint['_url']);
                    $obj->setEntryPoint($entryPoint);
                    die($obj->run());

                }

            }
        }

        if (Kryn::isActiveModule(getArgv(2)) && getArgv(2) != 'admin') {

            $clazz = '\\'.ucfirst(getArgv(2)).'\\AdminController';

            if (get_parent_class($clazz) == 'RestService\Server') {
                $obj = new $clazz('admin/'.getArgv(2));
                $obj->getClient()->setUrl(substr(Kryn::getRequest()->getPathInfo(), 1));
                $obj->setExceptionHandler($exceptionHandler);
                $obj->setDebugMode($debugMode);
            } else {
                $obj = new $clazz();
            }

            die($obj->run());

        } else {

            if (getArgv(1) == 'admin' && getArgv(2) == 'object') {

                $entryPoint = array(
                    '_url' => 'object/'.getArgv(3).'/',
                    'type' => 'combine'
                );

                $objectKey = rawurldecode(getArgv(3));
                $definition = \Core\Object::getDefinition($objectKey);

                if (!$definition)
                    throw new \ObjectNotFoundException(sprintf('Object `%s` not found.', $objectKey));

                $object = new ObjectCrud();
                $object->setObject($objectKey);
                $object->setAllowCustomSelectFields(true);

                $autoFields = array();
                foreach ($definition['fields'] as $key => $field)
                    if ($field['type'] != 'object') $autoFields[$key] = $field;

                $object->setFields($autoFields);
                $object->initialize();

                $epc = new ObjectCrudController(($entryPoint['_module'] == 'admin' ? '': 'admin/') . $entryPoint['_url']);
                $epc->setObj($object);
                $epc->getClient()->setUrl(substr(Kryn::getRequest()->getPathInfo(), 1));
                $epc->setExceptionHandler($exceptionHandler);
                $epc->setDebugMode($debugMode);

                die($epc->run($entryPoint));

            }

            \RestService\Server::create('admin', $this)

                ->getClient()->setUrl(substr(Kryn::getRequest()->getPathInfo(), 1))->getController()
                ->setCheckAccess(array($this, 'checkAccess'))
                ->setExceptionHandler($exceptionHandler)
                ->setDebugMode($debugMode)

                ->addGetRoute('', 'showLogin')

                ->addGetRoute('css/style.css', 'loadCss')
                ->addGetRoute('login', 'loginUser')
                ->addGetRoute('logged-in', 'loggedIn')
                ->addGetRoute('logout', 'logoutUser')

                ->addSubController('ui', '\Admin\UIAssets')
                    ->addGetRoute('possibleLangs', 'getPossibleLangs')
                    ->addGetRoute('languagePluralForm', 'getLanguagePluralForm')
                    ->addGetRoute('language', 'getLanguage')
                ->done()

                //admin/backend
                ->addSubController('backend', '\Admin\Backend')
                    ->addGetRoute('js/script.js', 'loadJs')

                    ->addGetRoute('settings', 'getSettings')

                    ->addGetRoute('desktop', 'getDesktop')
                    ->addPostRoute('desktop', 'saveDesktop')

                    ->addGetRoute('widgets', 'getWidgets')
                    ->addPostRoute('widgets', 'saveWidgets')

                    ->addGetRoute('menus', 'getMenus')
                    ->addGetRoute('custom-js', 'getCustomJs')
                    ->addPostRoute('user-settings', 'saveUserSettings')

                    ->addDeleteRoute('cache', 'clearCache')

                    ->addGetRoute('search', 'getSearch')
                    ->addGetRoute('content/template', 'getContentTemplate')
                    ->addPutRoute('content', 'saveContents')

                ->done()

                //->addGetRoute('editor', 'getKEditor')

                ->addSubController('', '\Admin\Object\Controller')

                    ->addGetRoute('objects', 'getItemsByUrl')
                    ->addGetRoute('object', 'getItemPerUrl')
                    ->addGetRoute('object-version', 'getVersionsPerUrl')

                    /*
                    ->addGetRoute('field-object/([a-zA-Z-_]+)/([^/]+)', 'getFieldItem')
                    ->addGetRoute('field-object-count/([a-zA-Z-_]+)', 'getFieldItemsCount')
                    ->addGetRoute('field-object/([a-zA-Z-_]+)', 'getFieldItems')
                    */

                    ->addGetRoute('object-browser/([a-zA-Z-_\.\\\\]+)', 'getBrowserItems')
                    ->addGetRoute('object-browser-count/([a-zA-Z-_\.\\\\]+)', 'getBrowserItemsCount')
                ->done()

                //admin/system
                ->addSubController('system', '\Admin\System')

                    ->addGetRoute('', 'getSystemInformation')

                    ->addSubController('config', '\Admin\Config')
                        ->addGetRoute('', 'getConfig')
                        ->addGetRoute('labels', 'getLabels')
                        ->addPostRoute('', 'saveConfig')
                    ->done()

                    //admin/system/module/manager
                    ->addSubController('module/manager', '\Admin\Module\Manager')
                        ->addGetRoute('install/pre', 'installPre')
                        ->addGetRoute('install/extract', 'installExtract')
                        ->addGetRoute('install/database', 'installDatabase')
                        ->addGetRoute('install/post', 'installPost')
                        ->addGetRoute('check-updates', 'check4updates')
                        ->addGetRoute('local', 'getLocal')
                        ->addGetRoute('installed', 'getInstalled')
                        ->addGetRoute('activate', 'activate')
                        ->addGetRoute('deactivate', 'deactivate')
                    ->done()

                    ->addSubController('languages', '\Admin\Languages')
                    ->done()

                    //admin/system/orm
                    ->addSubController('orm', '\Admin\ORM')
                        ->addGetRoute('environment', 'buildEnvironment')
                        ->addGetRoute('models', 'writeModels')
                        ->addGetRoute('update', 'updateScheme')
                        ->addGetRoute('check', 'checkScheme')
                    ->done()

                    //admin/system/module/editor
                    ->addSubController('module/editor', '\Admin\Module\Editor')
                        ->addGetRoute('config', 'getConfig')

                        ->addGetRoute('windows', 'getWindows')
                        ->addGetRoute('window', 'getWindowDefinition')
                        ->addPostRoute('window', 'saveWindowDefinition')
                        ->addPutRoute('window', 'newWindow')

                        ->addGetRoute('objects', 'getObjects')
                        ->addPostRoute('objects', 'saveObjects')

                        ->addGetRoute('plugins', 'getPlugins')
                        ->addPostRoute('plugins', 'savePlugins')

                        ->addPostRoute('model/from-object', 'setModelFromObject')
                        ->addPostRoute('model/from-objects', 'setModelFromObjects')

                        ->addPostRoute('model', 'saveModel')
                        ->addGetRoute('model', 'getModel')

                        ->addPostRoute('language', 'saveLanguage')
                        ->addGetRoute('language', 'getLanguage')
                        ->addGetRoute('language/extract', 'getExtractedLanguage')

                        ->addPostRoute('general', 'saveGeneral')
                        ->addPostRoute('entryPoints', 'saveEntryPoints')

                    ->done()

                ->done()

                ->addSubController('file', '\Admin\File')
                    ->addGetRoute('', 'getFiles')

                    ->addPostRoute('', 'createFile')
                    ->addDeleteRoute('', 'deleteFile')
                    ->addPostRoute('folder', 'createFolder')

                    ->addGetRoute('single', 'getFile')
                    ->addGetRoute('preview', 'showPreview')
                    ->addPostRoute('upload', 'doUpload')
                    ->addPostRoute('upload/prepare', 'prepareUpload')
                ->done()

            ->run();

            exit;

        }
    }

    public function getContentTemplate($pTemplate)
    {
        $pTemplate = str_replace('..', '', $pTemplate);

        $html = file_get_contents(tPath($pTemplate));

        $html = str_replace('{$content}', '<div class="ka-content-container"></div>', $html);

        return $html;
    }

    public static function addSessionScripts()
    {
        $response = Kryn::getResponse();

        $client = Kryn::getAdminClient();
        if (!$client)
            $client = Kryn::getClient();

        $session = array();
        $session['user_id'] = $client->getUserId();
        $session['sessionid'] = $client->getToken();
        $session['tokenid'] = $client->getTokenId();
        $session['lang'] = $client->getSession()->getLanguage();
        if ($client->getUserId()) {
            $session['username'] = $client->getUser()->getUsername();
            $session['lastlogin'] = $client->getUser()->getLastlogin();
        }

        $css = 'window._session = '.json_encode($session).';';
        $response->addJs($css);
    }

    public static function handleKEditor()
    {
        self::addMainResources();
        self::addSessionScripts();
        $response = Kryn::getResponse();

        $response->addJs('window.currentNode = '.
            json_encode(Kryn::$page->toArray(\BasePeer::TYPE_STUDLYPHPNAME)).';', 'bottom');

        $response->addJs('ka = parent.ka;');
        $response->addJsFile('admin/js/globals.js', 'bottom');

        $response->addCssFile('admin/css/style.css');
        $response->addJs('window.editor = new ka.Editor(document.body, {nodePk: '.Kryn::$page->getId().'});', 'bottom');

    }

    public static function addMainResources()
    {
        $response = Kryn::getResponse();

        $response->addJs('window._path = window._baseUrl = '.json_encode(Kryn::getBaseUrl()));
        $response->addJsFile('core/mootools-core.js');
        $response->addJsFile('core/mootools-more.js');
        $response->addJsFile('core/mowla.min.js');

        $response->addJsFile('admin/js/mootools-extras/String.sprintf.js');
        $response->addJsFile('core/ckeditor/ckeditor.js');

        self::addCssResources();

        $response->addJs("
        CKEDITOR.disableAutoInline = true;
        CodeMirror.modeURL = 'core/codemirror/mode/%N/%N.js';
        ", "bottom");

        $response->setResourceCompression(false);
    }

    public static function addLanguageResources(){
        $response = Kryn::getResponse();

        $response->addJsFile('admin/ui/possibleLangs?noCache=978699877');
        $response->addJsFile('admin/ui/language?lang=en&javascript=1');
        $response->addJsFile('admin/ui/languagePluralForm?lang=en');
    }

    public static function addBackendResources(){
        $response = Kryn::getResponse();

        $response->addJsFile('admin/js/ka.js');
        $response->addJsFile('admin/js/ka/AdminInterface.js');
        $response->addJsFile('admin/js/ka/Button.js');
    }

    public static function addCssResources(){
        $response = Kryn::getResponse();

        $response->addCssFile('admin/icons/style.css');
        $response->addCssFile('admin/css/ai.css');
        $response->addCssFile('admin/css/ka.wm.css');
        $response->addJsFile('core/codemirror/lib/codemirror.js');
        $response->addJsFile('core/codemirror/addon/mode/loadmode.js');
        $response->addCssFile('core/codemirror/lib/codemirror.css');
        $response->addCssFile('admin/icons/style.css');
        $response->addHeader('<meta name="viewport" content="initial-scale=1.0" >');
        $response->addHeader('<meta name="apple-mobile-web-app-capable" content="yes">');
    }

    public static function showLogin()
    {
        self::addMainResources();
        self::addBackendResources();
        self::addLanguageResources();

        $response = Kryn::getResponse();
        $response->addJsFile('admin/js/globals.js');
        $response->addJsFile('admin/js/ka/Select.js');
        $response->addJsFile('admin/js/ka/Checkbox.js');
        self::addSessionScripts();

        $response->setFavicon('admin/images/favicon.ico');

        $response->addJs("
        window.addEvent('domready', function(){
            ka.adminInterface = new ka.AdminInterface();
        });
        ");

        $response->setResourceCompression(false);
        $response->setDomainHandling(false);

        $response->setTitle(Kryn::$config['systemTitle'].' | Kryn.cms Administration');

        $response->addCssFile('admin/css/ai.css');
        $response->addCssFile('admin/css/ka/Login.css');
        $response->addCssFile('admin/css/ka/Select.css');
        $response->addCssFile('admin/css/ka/Checkbox.css');

        $response->send();
        exit;
    }


    public function loginUser($pUsername, $pPassword)
    {
        $status = Kryn::getAdminClient()->login($pUsername, $pPassword);

        return !$status ? false :
            array(
                'token' => Kryn::getAdminClient()->getToken(),
                'userId' => Kryn::getAdminClient()->getUserId(),
                'lastLogin' => Kryn::getAdminClient()->getUser()->getLastLogin()
            );
    }

    public function loggedIn()
    {
        return Kryn::getAdminClient()->getUserId() > 0;
    }

    public function logoutUser()
    {
        Kryn::getAdminClient()->logout();

        return true;
    }

    public function searchAdmin($pQuery)
    {
        $res = array();

        $lang = getArgv('lang');

        //pages
        $nodes = \Core\NodeQuery::create()->filterByTitle('%'.$pQuery.'%', \Criteria::LIKE)->find();

        if (count($nodes) > 0) {
            foreach ($nodes as $node)
                $respages[] =
                    array($node->getTitle(), 'admin/pages', array('id' => $node->getId(), 'lang' => $node->getDomain()->getLang()));
            $res[t('Pages')] = $respages;
        }

        //help
        $helps = array();
        foreach (Kryn::$configs as $key => $mod) {
            $helpFile = PATH_MODULE . "$key/lang/help_$lang.json";
            if (!file_exists($helpFile)) continue;
            if (count($helps) > 10) continue;

            $json = json_decode(file_get_contents($helpFile), 1);
            if (is_array($json) && count($json) > 0) {
                foreach ($json as $help) {

                    if (count($helps) > 10) continue;
                    $found = false;

                    if (preg_match("/$pQuery/i", $help['title']))
                        $found = true;

                    if (preg_match("/$pQuery/i", $help['tags']))
                        $found = true;

                    if (preg_match("/$pQuery/i", $help['help']))
                        $found = true;

                    if ($found)
                        $helps[] = array($help['title'], 'admin/help', array('id' => $key . '/' . $help['id']));
                }
            }
        }
        if (count($helps) > 0) {
            $res[t('Help')] = $helps;
        }

        return $res;
    }

    public function loadCss()
    {
        return Utils::loadCss();
    }

}

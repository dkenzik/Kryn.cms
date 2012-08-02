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

use \Core\Kryn;

class admin {


    public function __construct(){

    }

    public function content() {

        @header('Expires:');

        $code = Core\Kryn::getRequestedPath();
        $pEntryPoint = self::getPathItem($code); //admin entry point

        if (!$pEntryPoint) {
            $pEntryPoint = self::getPathItem(substr($code, 6)); //extensions
        }

        if ($pEntryPoint) {
            $epc = new adminRestEntryPoint('admin');
            $epc->run($pEntryPoint);
        }

        if (Core\Kryn::$modules[getArgv(2)] && getArgv(2) != 'admin') {

            die(Core\Kryn::$modules[getArgv(2)]->admin());

        } else {

            if (php_sapi_name() !== 'cli' && !getArgv(2))
                admin::showLogin();

            RestServer::create('admin', $this)

                ->addRoute('get:loadCss/style.css', 'loadCss')
                ->addRoute('get:ui/possibleLangs', 'getPossibleLangs')
                ->addRoute('get:ui/languagePluralForm', 'getLanguagePluralForm', array('lang'))
                ->addRoute('get:ui/language', 'getLanguage', array('lang'))

                //admin/backend
                ->addSubController('backend', '\Admin\Backend')
                    ->addRoute('get:loadJs/script.js', 'loadJs')
                    ->addRoute('get:settings', 'getSettings')

                    ->addRoute('get:desktop', 'getDesktop')
                    ->addRoute('post:desktop', 'saveDesktop', array('icons'))

                    ->addRoute('get:widgets', 'getWidgets')
                    ->addRoute('post:widgets', 'saveWidgets', array('widgets'))

                    ->addRoute('get:menus', 'getMenus')
                    ->addRoute('get:customJs', 'getCustomJs')
                    ->addRoute('post:userSettings', 'saveUserSettings', array('settings'))




                    //admin/backend/object
                    ->addSubController('object', '\Admin\Object')
                        ->addRoute('get:([a-zA-Z-_]+)/(.+)', 'getItem', null, array(
                            'fields'
                        ))
                        ->addRoute('get:([a-zA-Z-_]+)', 'getItems', null, array(
                            'fields', 'limit', 'offset', 'order'
                        ))
                    ->done()

                    ->addSubController('objectLabel', '\Admin\Object')
                        ->addRoute('get:([a-zA-Z-_]+)/(.*)', 'getItemLabel', null, array(
                            'fields'
                        ))
                    ->done()

                ->done()

                //admin/system
                ->addSubController('system', '\Admin\System')

                    ->addRoute('get:', 'getSystemInformation')

                    //admin/system/module/manager
                    ->addSubController('module/manager', '\Admin\Module\Manager')
                        ->addRoute('get:install/pre', 'installPre', array('name'))
                        ->addRoute('get:install/extract', 'installExtract', array('name'))
                        ->addRoute('get:install/database', 'installDatabase', array('name'))
                        ->addRoute('get:install/post', 'installPost', array('name'))
                        ->addRoute('get:check4updates', 'check4updates')
                        ->addRoute('get:local', 'getLocal')
                        ->addRoute('get:installed', 'getInstalled')
                    ->done()


                    //admin/system/orm
                    ->addSubController('orm', '\Admin\ORM')
                        ->addRoute('get:environment', 'buildEnvironment')
                        ->addRoute('get:models', 'writeModels')
                        ->addRoute('get:update', 'updateScheme')
                        ->addRoute('get:check', 'checkScheme')
                    ->done()

                    //admin/system/module/manager
                    ->addSubController('module/editor', '\Admin\Module\Editor')
                        ->addRoute('get:config', 'getConfig', array('name'))

                        ->addRoute('get:windows', 'getWindows', array('name'))

                        ->addRoute('get:objects', 'getObjects', array('name'))
                        ->addRoute('post:objects', 'saveObjects', array('name'))

                        ->addRoute('post:model/fromObject', 'setModelFromObject', array('name', 'object'))

                        ->addRoute('post:model', 'saveModel', array('name', 'model'))
                        ->addRoute('get:model', 'getModel', array('name'))

                        ->addRoute('post:general', 'saveGeneral', array('name'))
                        ->addRoute('post:entryPoints', 'saveEntryPoints', array('name', 'entryPoints'))


                    ->done()

                ->done()

                //->addSubController('file', '\Admin\File')

            ->run();

            //todo, rewrite all following to the new RestServer
            exit;


            $content = null;
            switch (getArgv(2)) {
                case 'mini-search':
                    return self::miniSearch(getArgv('q', 1));
                case 'loadCss':
                    return self::loadCss();
                case 'widgets':
                    require(PATH_MODULE . "admin/adminWidgets.class.php");
                    return adminWidgets::init();
                case 'pages':
                    json(adminPages::init());
                    break;
                case 'backend':
                    switch (getArgv(3)) {
                        case 'help':
                            switch (getArgv(4)) {
                                case 'load':
                                    return self::loadHelp();
                                case 'loadTree':
                                    return json(self::loadHelpTree(getArgv('lang')));
                            }
                            break;
                        case 'nothing':
                            die("");


                        case 'objectGetLabel':
                            $content = self::objectGetLabel(getArgv('object'));
                            break;
                        case 'objectGetItems':
                            $content = self::objectGetItems(getArgv('object'));
                            break;
                        case 'objectTree':
                            $content = self::getObjectTree(getArgv('object'), getArgv('depth')+0);
                            break;
                        case 'objectTreeRoot':
                            $content = self::getObjectTreeRoot(getArgv('object'), getArgv('rootId'));
                            break;

                        case 'objectParents':
                            $content = self::getObjectParents(getArgv('object'));
                            break;
                        case 'moveObject':
                            $content = self::moveObject(getArgv('source'), getArgv('target'), getArgv('mode', 2));
                            break;

                        case 'autoChooser':
                            $content = self::autoChooser(getArgv('object', 2), getArgv('page'));
                            break;
                        case 'getPluginElements':
                            $content = self::getPluginElements(getArgv('object', 2));
                            break;


                        case 'clearCache':
                            json(admin::clearCache());
                        case 'loadJs':
                            return self::loadJs();
                        case 'loadCustomJs':
                            return self::loadCustomJs();
                        case 'loadLayoutElementFile':
                            return self::loadLayoutElementFile(getArgv('template'));
                        case 'getContentTemplate':
                            return self::loadContentLayout();
                        case 'fixDb':
                            return self::fixDb();
                        case 'saveDesktop':
                            self::saveDesktop(getArgv('icons'));

                        case 'getMenus':
                            return admin::getMenus();
                        case 'saveUserSettings':
                            $content = self::saveUserSettings();
                            break;
                        case 'getDefaultImages':
                            self::getDefaultImages();
                            break;
                        case 'objects':
                            self::sendObjectStore();

                        case 'imageThumb':
                            $content = adminFilemanager::imageThumb(getArgv('path'),getArgv('width'),getArgv('height'));
                            break;
                        case 'showImage':
                            $content = adminFilemanager::showImage(getArgv('path'));
                            break;

                        case 'stream':
                            $content = self::stream();
                            break;
                        case 'navigationPreview':
                            return admin::navigationPreview(getArgv('content'));
                        case 'pointerPreview':
                            return admin::pointerPreview(getArgv('content'));
                        case 'plugins':
                            require(PATH_MODULE . "admin/adminPlugins.class.php");
                            return adminPlugins::init();
                        case 'window':
                            if (getArgv(4) == 'sessionbasedFileUpload') {
                                require(PATH_MODULE . 'admin/adminWindow.class.php');
                                $_REQUEST['cmd'] = 'sessionbasedFileUpload';
                                $content = adminWindow::handle();
                            }
                            break;
                        case 'searchIndexer' :
                            adminSearchIndexer::init();
                            break;
                    }
                    break;
                case 'files':
                    $content = adminFilemanager::init();
                    break;
                case 'filebrowser':
                    require(PATH_MODULE . 'admin/filebrowser.class.php');
                    $content = filebrowser::init();
                    break;
                case 'system':


                    switch (getArgv(3)) {
                        case 'tools':
                            switch (getArgv(4)) {
                                case 'database':
                                    return self::database();
                                case 'logs':
                                    return json(self::getLogs());
                            }
                            break;
                        case 'module':

                            RestServer::create('admin/system/module', 'adminModule')
                            ->collectRoutes()
                            ->run();

                        case 'settings':
                            $content = adminSettings::init();
                            break;
                        case 'backup':
                            $content = adminBackup::init();
                            break;
                        case 'languages':
                            require(PATH_MODULE . "admin/adminLanguages.class.php");
                            $content = adminLanguages::init();
                            break;
                        case 'layout':
                            adminLayout::init();
                            break;
                        default:
                            $content = self::systemInfo();
                            break;
                    }
                    break;
            }
            if ($content !== null)
                json($content);
        }

        if (php_sapi_name() !== 'cli' && !getArgv(2))
            admin::showLogin();

        json(false);
    }



    public static function getObjectParents($pObjectUrl){
        return krynObjects::getParentsFromUri($pObjectUrl);
    }

    public static function getObjectTree($pObjectUrl, $pDepth = 0){
        return krynObjects::getTreeFromUri($pObjectUrl, $pDepth);
    }

    public static function getObjectTreeRoot($pObjectUrl, $pRootId){
        return krynObjects::getTreeRoot($pObjectUrl, $pRootId);
    }

    public static function moveObject($pSourceObjectUrl, $pTargetObjectUrl, $pMode){
        return krynObjects::move($pSourceObjectUrl, $pTargetObjectUrl, $pMode);
    }


    /**
     * Returns all plugin elements for specified object
     *
     * @static
     * @param $pObjectKey
     * @return array
     */
    public static function getPluginElements($pObjectKey){

        if (!Core\Kryn::$objects[$pObjectKey]) return array('error' => 'object_not_found');

        $definition = Core\Kryn::$objects[$pObjectKey];

        $cachedPluginRelations =& Core\Kryn::getCache('kryn_pluginrelations');
        if (true || !$cachedPluginRelations || count($cachedPluginRelations) == 0) {
            self::cachePluginsRelations();
            $cachedPluginRelations =& Core\Kryn::getCache('kryn_pluginrelations');
        }

        $module = $definition['_extension'];

        $previewPluginPages = array();

        if (!$definition['plugins']) return array('error' => 'no_plugins_defined');
        $plugins = explode(',', str_replace(' ', '', $definition['plugins']));

        foreach ($plugins as $plugin) {

            $moduleToUse = $module;
            $pluginToUse = $plugin;

            if (strpos($plugin, '/') !== false) {
                $ex = explode('/', $plugin);
                $moduleToUse = $ex[0];
                $pluginToUse = $ex[1];
            }

            $pages =& $cachedPluginRelations[$moduleToUse][$pluginToUse];
            if (count($pages) > 0) {
                foreach ($pages as &$page) {
                    $previewPluginPages[$moduleToUse . '/' . $pluginToUse][$page['domain_id']][$page['id']] =
                        array(
                            'title' => $page['title'],
                            'path' => Core\Kryn::getPagePath($page['id'])
                        );
                }
            }
        }

        return $previewPluginPages;
    }




    /**
     * Loads all plugins from system_contents to a indexed cached array
     */
    private static function cachePluginsRelations() {

        $res = dbExec('
        SELECT p.domain_id, p.id, c.content, p.title
        FROM
            %pfx%system_contents c,
            %pfx%system_page_version v,
            %pfx%system_page p
        WHERE 1=1
            AND c.type = \'plugin\'
            AND c.hide = 0
            AND v.id = c.version_id
            AND p.id = v.page_id
            AND (p.access_denied = \'0\' OR p.access_denied IS NULL)
            AND v.active = 1
        ');

        if (!$res) {
            Core\Kryn::setCache('kryn_pluginrelations', array());
            return;
        }

        $pluginRelations = array();

        while ($row = dbFetch($res)) {

            preg_match('/([a-zA-Z0-9_-]*)::([a-zA-Z0-9_-]*)::(.*)/', $row['content'], $matches);
            $pluginRelations[$matches[1]][$matches[2]][] = $row;

        }
        Core\Kryn::setCache('kryn_pluginrelations', $pluginRelations);
    }


    public static function objectGetItems($pUrl){

        if (is_numeric($pUrl)){
            //compatibility
            $object_key = '';
        } else {
            list($object_key, $object_ids, $params) = krynObjects::parseUri($pUrl);
        }

        $definition = Core\Kryn::$objects[$object_key];
        if (!$definition) return array('error' => 'object_not_found');

        //todo check here access

        if ($definition['chooserFieldDataModel'] == 'custom'){

            $class = $definition['chooserFieldDataModel'];
            $classFile = PATH_MODULE.'/'.$definition['_extension'].'/'.$class.'.class.php';
            if (!file_exists($classFile)) return array('error' => 'classfile_not_found');

            require_once($classFile);
            $dataModel = new $class($object_key);

            $items = $dataModel->getItems($object_ids);

        } else {

            $primaryKeys = krynObjects::getPrimaries($object_key);

            $fields = array_keys($primaryKeys);

            foreach ($definition['chooserFieldDataModelFields'] as $key => $val){
                $fields[] = $key;
            }

            $items = krynObjects::getList($object_key, $object_ids, array(
                'fields' => $fields,
                'condition' => $definition['chooserFieldDataModelCondition']
            ));
        }

        $res = array();
        if (is_array($items)){
            foreach ($items as &$item){

                $keys = array();
                foreach($primaryKeys as $key => &$field){
                    $keys[] = rawurlencode($item[$key]);
                }
                $res[ implode(',', $keys) ] = $item;
            }
        }

        return $res;
    }

    public static function objectGetLabel($pUrl){

        if (is_numeric($pUrl)){
            //compatibility
            $object_key = '';
        } else {
            list($object_key, $object_id, $params) = krynObjects::parseUri($pUrl);
        }

        $definition = Core\Kryn::$objects[$object_key];
        if (!$definition) return array('error' => 'object_not_found');

        //todo check here access

        if ($definition['chooserFieldDataModel'] == 'custom'){

            $class = $definition['chooserFieldDataModelClass'];
            $classFile = PATH_MODULE.'/'.$definition['_extension'].'/'.$class.'.class.php';
            if (!file_exists($classFile)) return array('error' => 'classfile_not_found');

            require_once($classFile);
            $dataModel = new $class($object_key);

            $item = $dataModel->getItem($object_id[0]);
            return array(
                'object' => $object_key,
                'values' => $item
            );

        } else {

            $fields = array();
            foreach ($definition['fields'] as $key => $field){
                if ($field['primaryKey'])
                    $fields[] = $key;
            }

            $fields[] = $definition['chooserFieldDataModelField'];

            $item = krynObjects::get($object_key, $object_id[0], array(
                'fields' => $fields,
                'condition' => $definition['chooserFieldDataModelCondition']
            ));

            return array(
                'object' => $object_key,
                'values' => $item
            );

        }
    }

    /**
     * @static
     * @param $pObjectKey
     * @param int $pPage
     * @return array
     */
    public static function autoChooser($pObjectKey, $pPage = 1){

        //todo, check permissions

        $definition = Core\Kryn::$objects[$pObjectKey];

        if ($definition['chooserBrowserDataModel'] == 'none')
            return;

        $order = false; //todo

        if ($definition['chooserBrowserDataModel'] == 'custom' && $definition['chooserBrowserDataModelClass']){

            $class = $definition['chooserBrowserDataModelClass'];
            $classFile = PATH_MODULE.'/'.$definition['_extension'].'/'.$class.'.class.php';
            if (!file_exists($classFile)) return array('error' => 'classfile_not_found');

            require_once($classFile);
            $dataModel = new $class($pObjectKey);

            $itemsCount = $dataModel->getCount();
            if (is_array($itemsCount) && $itemsCount['error'])
                return $itemsCount;

            $itemsPerPage = 15;
            $start = ($itemsPerPage*$pPage)-$itemsPerPage;
            $pages = ceil($itemsCount/$itemsPerPage);

            $items = $dataModel->getItems(
                $definition['chooserBrowserDataModelCondition'], $start, $itemsPerPage, null, $order
            );

            return array(
                'items' => count($items)>0?$items:false,
                'count' => $itemsCount,
                'pages' => $pages
            );
        }

        $fields = array();

        foreach ($definition['fields'] as $key => $field){
            if ($field['primaryKey'])
                $fields[] = $key;
        }

        if ($definition['chooserBrowserAutoColumns']){
            foreach ($definition['chooserBrowserAutoColumns'] as $key => $column){
                $fields[] = $key;
            }
        } else {
            if ($definition['chooserBrowserDataModelFields']){
                $tempFields = explode(',', str_replace(' ', '', $definition['chooserBrowserDataModelFields']));
                if (is_array($tempFields)){
                    foreach ($tempFields as $field){
                        $fields[] = $field;
                    }
                }
            }
        }

        $itemsCount = krynObjects::getCount($pObjectKey, $definition['chooserCondition']);
        if (is_array($itemsCount) && $itemsCount['error'])
            return $itemsCount;

        $itemsPerPage = 15;
        $start = ($itemsPerPage*$pPage)-$itemsPerPage;
        $pages = ceil($itemsCount/$itemsPerPage);

        $items = krynObjects::getList($pObjectKey, false, array(
            'fields' => implode(',', $fields),
            'limit'  => $itemsPerPage,
            'offset' => $start,
            'condition' => $definition['chooserBrowserDataModelCondition']
        ));

        return array(
            'items' => count($items)>0?$items:false,
            'count' => $itemsCount,
            'pages' => $pages
        );

    }

    /**
     *
     * Gets the item from the 'admin' entry points defined in the config.json, by the given code
     *
     * @static
     * @param $pCode <extKey>/news/foo/bar/edit
     * @return array|bool
     */
    public static function getPathItem($pCode) {

        $codes = explode('/', $pCode);

        if (Core\Kryn::$configs['admin']['admin'][$codes[1]]) {
            //inside admin extension
            $adminInfo = Core\Kryn::$configs['admin']['admin'];
            $start = 1;
            $module = 'admin';
            $code = substr($pCode, 6);
        } else if (Core\Kryn::$configs[$codes[1]]['admin']) {
            //inside other extension
            $adminInfo = Core\Kryn::$configs[$codes[1]]['admin'];
            $start = 2;
            $module = $codes[1];
            $code = substr($pCode, 6 + strlen($codes[1]) + 1);
        }

        $_info = $adminInfo[$codes[$start]];
        $path = array();
        $path[] = $_info['title'];

        $count = count($codes);
        for ($i = $start + 1; $i <= $count; $i++) {
            if ($codes[$i] != "") {
                $_info = $_info['childs'][$codes[$i]];
                $path[] = $_info['title'];
            }
        }

        unset($path[count($path) - 1]);
        unset($_info['childs']);

        if (!$_info) {
            return false;
        }

        $_info['_path'] = $path;
        $_info['_module'] = $module;
        $_info['_code'] = $code;

        if ($code) {
            $css = PATH . PATH_MEDIA . $module . '/' . (($module != 'admin') ? 'admin/' : '') . 'css/' .
                   str_replace('/', '_', $code) . '.css';
            if (file_exists($css) && $mtime = filemtime($css)) {
                $_info['cssmdate'] = $mtime;
            }
        }


        return $_info;
    }

    public static function loadContentLayout() {

        $content = array();

        $vars = array('title', 'type', 'template');

        foreach ($vars as $p) {
            $content[$p] = $_GET[$p];
        }

        tAssign('content', $content);

        $content['template'] = str_replace('..', '', $content['template']);
        $tpl = Core\Kryn::fileRead(PATH_MEDIA . $content['template']);

        $tpl =
            str_replace('{$content.title}', '<span class="ka-layoutelement-content-title">{$content.title}</span>', $tpl);
        $tpl = str_replace('{$content.content}', '<div class="ka-layoutelement-content-content"></div>', $tpl);

        json(tFetch('string:' . $tpl));
    }

    public static function loadLayoutElementFile($pFile) {

        $pFile = str_replace('..', '', $pFile);

        $found = false;
        foreach (Core\Kryn::$configs as $config) {
            if ($config['themes']) {
                foreach ($config['themes'] as $themeTitle => $layouts) {
                    if ($layouts['layoutElement']) {
                        foreach ($layouts['layoutElement'] as $layoutTiel => $layoutFile) {
                            if ($pFile == $layoutFile)
                                $found = true;
                        }
                    }
                }
            }
        }

        $res = false;
        if ($found) {
            $res['layout'] = tFetch($pFile);
        }
        json($res);
    }

    public static function logs() {


    }

    public static function database() {

        $res = array('fetchtime' => 0);

        $sql = getArgv('sql');

        $startExec = microtime(true);
        $execRes = dbExec($sql);
        $res['exectime'] = microtime(true) - $startExec;

        if (!$execRes) {
            $res['error'] = dbError();
        } else {
            $startFetch = microtime(true);
            $res['items'] = dbFetch($execRes, -1);
            $res['fetchtime'] = microtime(true) - $startFetch;
        }

        json($res);
    }

    public static function miniSearch($pQ) {

        $res = array();
        foreach (Core\Kryn::$modules as &$mod) {
            if (method_exists($mod, 'searchAdmin')) {
                $res = array_merge($res, $mod->searchAdmin($pQ));
            }
        }

        json($res);

    }

    public static function getLogs() {

        if (getArgv(5) == 'clear') {
            dbDelete('system_log');
            json(1);
        }

        $page = 1;
        if (getArgv('page') + 0 > 1) {
            $page = getArgv('page') + 0;
        }


        $perPage = 40;
        $where = "WHERE ";

        switch (getArgv('area')) {

            case '404':
                $where .= "code = '404'";
                break;

            case 'database':
                $where .= "code = 'database'";
                break;

            case 'authentication':
                $where .= "code = 'authentication'";
                break;

            case 'system':
                $where .= "code = 2 OR code = 2048";
                break;

            case 'all':
            default:
                $where = "";

        }


        $from = ($perPage * $page) - $perPage;
        $count = $perPage;

        $return = array('items', 'count');

        $sql = "SELECT date, ip, username, code, message FROM %pfx%system_log $where";

        if ($sql == "") return $return;

        $limit = ' ORDER BY date DESC LIMIT ' . $count . ' OFFSET ' . $from;
        $res = dbExec($sql . $limit, -1);

        $count =
            dbExfetch(preg_replace('/SELECT(.*)FROM/mi', 'SELECT count(id) as ctn FROM', str_replace("\n", " ", $sql)));
        $return['count'] = $count['ctn'];

        $maxPages = 1;
        if ($return['count'] > 0) {
            $maxPages = ceil($return['count'] / $perPage);
        }
        $return['maxPages'] = $maxPages;
        $return['items'] = dbExfetch($sql . $limit, -1);

        foreach ($return['items'] as &$item) {
            $item[0] = date('d M H:i:s', $item['date']);
            $item[1] = $item['ip'];
            $item[2] = $item['username'];
            $item[3] = $item['code'];
            $item[4] = $item['message'];
        }

        return $return;
    }

    public static function clearCache() {

        clearfolder('cache/object/');
        clearfolder(Core\Kryn::$config['media_cache']);

        foreach (Core\Kryn::$configs as $extKey => $config){
            if ($config['caches']){
                foreach ($config['caches'] as $cache){
                    if ($m = $cache['method']){
                        if (method_exists(Core\Kryn::$modules[$extKey], $m))
                            try {
                                Core\Kryn::$modules[$extKey]->$m();
                            } catch (Exception $e){
                                klog('admin', 'Error during the clearCache function: '.$e);
                            }
                    } else {
                        Core\Kryn::deleteCache($cache['key']);
                    }
                }
            }
            if ($config['cacheInvalidation']){
                foreach ($config['cacheInvalidation'] as $cache){
                    Core\Kryn::invalidateCache($cache['key']);
                }
            }
        }


        return true;
    }

    public function searchAdmin($pQuery) {

        $res = array();

        $lang = getArgv('lang');

        //pages
        $pages = dbExfetch("SELECT p.id, p.title, d.lang
            FROM %pfx%system_page p, %pfx%system_domains d
            WHERE d.id = p.domain_id AND p.title LIKE '%$pQuery%' LIMIT 10 OFFSET 0", -1);

        if (count($pages) > 0) {
            foreach ($pages as $page)
                $respages[] =
                    array($page['title'], 'admin/pages/', array('id' => $page['id'], 'lang' => $page['lang']));
            $res[_l('Pages')] = $respages;
        }

        //help
        $helps = array();
        foreach (Core\Kryn::$configs as $key => $mod) {
            $helpFile = PATH_MODULE . "$key/lang/help_$lang.json";
            if (!file_exists($helpFile)) continue;
            if (count($helps) > 10) continue;

            $json = json_decode(Core\Kryn::fileRead($helpFile), 1);
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
            $res[_l('Help')] = $helps;
        }

        return $res;
    }

    public static function loadHelp() {
        $id = getArgv('id');

        $temp = explode("/", $id);
        $module = $temp[0];
        $helpId = $temp[1];
        $lang = getArgv('lang');

        $helpFile = PATH_MODULE . "$module/lang/help_$lang.json";
        $json = Core\Kryn::fileRead($helpFile);
        $langs = json_decode($json, 1);
        $res = false;
        foreach ($langs as &$help) {
            if ($help['id'] == $helpId)
                $res = $help;
        }
        if (!$res && $lang != 'en') {
            $_REQUEST['lang'] = 'en';
            self::loadHelp();

        } else {
            if (!$res)
                json(array('title' => 'Not found'));
            else
                json($res);
        }
    }


    public static function loadHelpTree($pLang = 'en') {

        $res = array();
        foreach (Core\Kryn::$configs as $modCode => &$config) {

            $langFile = PATH_MODULE . "$modCode/lang/help_$pLang.json";
            if (!file_exists($langFile))
                $langFile = PATH_MODULE . "$modCode/lang/help_en.json";
            if (!file_exists($langFile))
                continue;

            $modTitle = $config['title'][$pLang] ? $config['title'][$pLang] : $config['title']['en'];

            $help = Core\Kryn::fileRead($langFile);
            $help = json_decode($help, true);

            if (count($help) > 0) {
                foreach ($help as &$item) {

                    $item['open'] = $modCode . '/' . $item['id'];
                    $res[$modTitle][] = $item;

                }
            }

        }

        return $res;

    }

    public static function fixDb() {


    }

    public static function showLogin() {

        $language = Core\Kryn::$adminClient->getSession()->getLanguage();
        if (!$language) $language = 'en';

        if (getArgv('setLang') != '')
            $language = getArgv('setLang', 2);

        tAssign('adminLanguage', $language);

        print tFetch('admin/index.tpl');
        exit;
    }

    public function getPossibleLangs() {

        $files = Core\Kryn::readFolder(PATH_MODULE . 'admin/lang/', false);
        $where = "code = 'en' ";
        foreach ($files as $file)
            $where .= " OR code = '$file'";
        $langs = dbExFetchAll("SELECT * FROM %pfx%system_langs WHERE $where");

        $json = json_encode($langs);
        header('Content-Type: text/javascript');
        print "if( typeof(ka)=='undefined') window.ka = {}; ka.possibleLangs = " . $json;
        exit;
    }

    public function getLanguagePluralForm($pLang){

        $lang = esc($pLang, 2);
        header('Content-Type: text/javascript');
        print "/* Kryn plural function */\n";
        print Core\Kryn::fileRead(Core\Kryn::$config['media_cache'].'gettext_plural_fn_'.$lang.'.js')."\n";
        exit;
    }

    public function getLanguage($pLang) {

        $lang = esc($pLang, 2);

        Core\Kryn::$adminClient->getSession()->setLanguage($lang);
        Core\Kryn::$adminClient->syncStore();

        Core\Kryn::loadLanguage($lang);
        $json = json_encode(Core\Kryn::$lang);

        if (getArgv('javascript') == 1) {
            header('Content-Type: text/javascript');
            print "if( typeof(ka)=='undefined') window.ka = {}; ka.lang = " . $json;
            if (!$json) {
                print "\nLocale.define('en-US', 'Date', " . tFetch('admin/mootools-locale.tpl') . ");";
            }
        } else {
            $json = json_decode($json, true);
            $json['mootools'] = json_decode(tFetch('admin/mootools-locale.tpl'), true);
            json($json);
        }

        exit;
    }

    public static function stream() {

        $res['time'] = date('H:i');
        $res['last'] = time();

        $sessionCount = dbExfetch('SELECT count(*) as mcount FROM %pfx%system_sessions', 1);
        $res['sessions_count'] = $sessionCount['mcount'];

        $res['hasCrawlPermission'] = adminSearchIndexer::hasPermission();

        foreach (Core\Kryn::$configs as $key => $conf) {

            if ($conf['_corruptConfig']) {

                $res['corruptJson'][] = $key;
            }
            $stream = $conf['stream'];

            if ($stream && method_exists(Core\Kryn::$modules[$key], $stream)) {

                $res[$key] = Core\Kryn::$modules[$key]->$stream();
            }
        }


        json($res);
    }

    public static function systemInfo() {

        $res['version'] = Core\Kryn::$configs['kryn']['version'];

        json($res);
    }

    public static function collectFiles($pArray, &$pFiles){

        foreach ($pArray as $jsFile) {
            if (strpos($jsFile, '*') !== -1){
                $folderFiles = find(PATH_MEDIA . $jsFile, false);
                foreach ($folderFiles as $file){
                    if (!array_search($file, $pFiles))
                        $pFiles[] = $file;
                }
            } else {
                if (file_exists(PATH_MEDIA . $jsFile))
                    $pFiles[] = PATH_MEDIA . $jsFile;
            }
        }

    }

    public static function loadCss() {

        header('Content-Type: text/css');

        $from = array(
            "-moz-border-radius-topleft",
            "-moz-border-radius-topright",
            "-moz-border-radius-bottomleft",
            "-moz-border-radius-bottomright",
            "-moz-border-radius",
        );

        $toSafari = array(
            "-webkit-border-top-left-radius",
            "-webkit-border-top-right-radius",
            "-webkit-border-bottom-left-radius",
            "-webkit-border-bottom-right-radius",
            "-webkit-border-radius",
        );
        $toCss3 = array(
            "border-top-left-radius",
            "border-top-right-radius",
            "border-bottom-left-radius",
            "border-bottom-right-radius",
            "border-radius",
        );

        $md5Hash = '';
        $cssFiles = array();

        foreach (Core\Kryn::$configs as &$config) {
            if ($config['adminCss'])
                self::collectFiles($config['adminCss'], $cssFiles);
        }

        foreach ($cssFiles as $cssFile)
            $md5Hash .= filemtime($cssFile) . '.';

        $md5Hash = md5($md5Hash);

        print "/* Kryn.cms combined admin css file: $md5Hash */\n\n";

        if (file_exists('cache/media/cachedAdminCss_' . $md5Hash . '.css')) {
            readFile('cache/media/cachedAdminCss_' . $md5Hash . '.css');
        } else {
            $content = '';
            foreach ($cssFiles as $cssFile) {
                $content .= "\n\n/* file: $cssFile */\n\n";

                $dir = '../../'.dirname($cssFile).'/';
                $h = fopen($cssFile, "r");
                if ($h) {
                    while (!feof($h) && $h) {
                        $buffer = fgets($h, 4096);

                        $buffer = preg_replace('/url\(\'([^\/].*)\'\)/', 'url(\''.$dir.'$1\')', $buffer);
                        $buffer = preg_replace('/url\(([^\/\'].*)\)/', 'url('.$dir.'$1)', $buffer);

                        $content .= $buffer;
                        $newLine = str_replace($from, $toSafari, $buffer);
                        if ($newLine != $buffer)
                            $content .= $newLine;
                        $newLine = str_replace($from, $toCss3, $buffer);
                        if ($newLine != $buffer)
                            $content .= $newLine;
                    }
                    fclose($h);
                }
            }

            foreach (glob('cache/media/cachedAdminCss_*.css') as $cache)
                @unlink($cache);

            Core\Kryn::fileWrite('cache/media/cachedAdminCss_' . $md5Hash . '.css', $content);
            print $content;
        }
        exit;
    }



    public static function addVersion($pTable, $pPrimary) {

        foreach ($pPrimary as $fieldName => $fieldValue) {
            if ($fieldValue+0 > 0)
                $sql = " AND $fieldName = ".($fieldValue+0);
            else
                $sql = " AND $fieldName = '" . esc($fieldValue) . "'";
        }

        $row = dbTableFetch($pTable, "1=1 $sql", 1);

        return self::addVersionRow($pTable, $pPrimary, $row);
    }

    public static function addVersionRow($pTable, $pPrimary, $pRow) {

        $code = $pTable;
        foreach ($pPrimary as $fieldName => $fieldValue) {
            $code .= '_' . $fieldName . '=' . $fieldValue;
        }

        $content = json_encode($pRow);

        $currentVersion =
            dbTableFetch('system_frameworkversion', "code = '" . esc($code) . "' ORDER BY version DESC", 1);

        $version = $currentVersion['version'] + 1;
        $new = array(
            'code' => $code,
            'content' => $content,
            'version' => $version,
            'cdate' => time(),
            'user_id' => Core\Kryn::$adminClient->user_id
        );

        dbInsert('system_frameworkversion', $new);
        return $version;
    }


    public static function getVersion($pTable, $pPrimary, $pVersion) {

        $code = $pTable;
        foreach ($pPrimary as $fieldName => $fieldValue) {
            $code .= '_' . $fieldName . '=' . $fieldValue;
        }
        $version = $pVersion + 0;

        $version = dbTableFetch('system_frameworkversion', "code = '$code' AND version = $version", 1);

        return json_decode($version['content'], true);
    }


    /*
    *
    * WIDGET STUFF
    *
    */

    public function widgetLastLogins($pConf) {
        $res['title'] = "Letzte Sessions";

        $sessions = dbExFetch('SELECT s.*, u.username
                    FROM ' . pfx . 'system_sessions s, ' . pfx . 'system_user u
                    WHERE s.user_id = u.id AND u.id > 0
                    ORDER BY time DESC
                    LIMIT 10 OFFSET 0', DB_FETCH_ALL);
        tAssign('sessions', $sessions);
        $res['content'] = tFetch('admin/overview.widget.sessions.tpl');

        return $res;
    }

    public function widgetVersion() {

        $res['title'] = 'Kryn ' . Core\Kryn::$configs['kryn']['version'];
        $res['content'] = '
            <span style="color: green;">Sie benutzen die aktuellste Version.</span>    
        ';

        return $res;

    }

    public function widgetWaitingContent($pConf) {

        $pages = dbExFetch('SELECT u.username, p.*, v.modified
            FROM %pfx%system_user u, %pfx%system_page p, %pfx%system_page_version v
            WHERE draft_exist = 1
            AND v.page_id = p.id
            AND u.id = v.owner_id
            AND v.active = 1
            AND ( p.type = 0 OR p.type = 3)
            ', -1);

        $res['title'] = _l('Unpulished contents') . ' (' . count($pages) . ')';

        $html = '<table width="100%">';
        if (count($pages) > 0) {
            foreach ($pages as $page) {
                $html .= '<tr><td width="90">' . date("d. M H:i:s", $page['modified']) . '</td>';
                $html .= '<td>' . $page['username'] . '</td>';
                $html .= '<td>' . $page['title'] . '</td>';
                $html .=
                    '<td width="20"><a href="javascript:;" onclick="ka.wm.open(\'admin/pages\', {id: ' . $page['id'] .
                    '});"><img src="' . Core\Kryn::$domain['path'] . 'admin/images/icons/bullet_go.png" /></a></td>';
                $html .= '</tr>';
            }
        }
        $html .= '</table>';
        $res['content'] = $html;

        return $res;

    }

    public function manipulateUnpublishedContentsRow($pRow) {
        $domain = Core\Kryn::getDomain($pRow[4]);
        $pRow[2] = '<a href="javascript:;" onclick="ka.wm.open(\'admin/pages\', {id: ' . $pRow[2] . '});">' .
                   Core\Kryn::getPagePath($pRow[2] + 0) . '</a>';
        return $pRow;
    }

    public function manipulateLastChangesRow($pRow) {
        //$domain = Core\Kryn::getDomain( $pRow[4] );
        $pRow[3] = '<a href="javascript:;" onclick="ka.wm.open(\'admin/pages\', {id: ' . $pRow[3] . '});">' .
                   Core\Kryn::getPagePath($pRow[3] + 0) . '</a>';
        return $pRow;
    }

    public function cacheDeleteSystemUrls(){

        $domains = krynObjects::getList('domain');
        foreach ($domains as $domain)
            Core\Kryn::deleteCache('systemUrls-'.$domain['id']);

    }

    public function cacheDeleteDomain(){

        $domains = krynObjects::getList('domain');
        foreach ($domains as $domain)
            Core\Kryn::deleteCache('systemDomain-'.$domain['id']);
    }
}

<?php

class adminModule {


    private static $migrationCurrentPos = 0;

    public static function init() {
        global $cfg;


        if (!$cfg['repoServer']) {
            $cfg['repoServer'] = 'http://download.kryn.org';
        }

        switch (getArgv(4)) {
            case 'deactivate':
                return self::deactivate($_REQUEST['name']);
            case 'activate':
                return self::activate($_REQUEST['name']);

            /* not in use
            case 'dev-install':
                return self::devInfstall(getArgv(5));
            case 'dev-update':
                return self::devUpdate(getArgv(5));
            case 'dev-remove':
                return self::devRemove(getArgv(5));
            case 'new':
                return self::listTopModules();
            */

            case 'convertPagesTo1.0':
                return self::convertPagesTo10();

            case 'managerSearch':
                return self::managerSearch(getArgv('q'));
            case 'managerGetCategoryItems':
                return self::getCategoryItems(getArgv('category') + 0, getArgv('lang'));
            case 'managerGetBox':
                return self::getBox(getArgv('code'));

            // for pluginchooser
            case 'getModules':
                return self::getModules();


            case 'check4updates':
                return self::check4updates();


            case 'getInstallInfo': #step 1
                return self::getInstallInfo(getArgv('name', 2), getArgv('type'));
            case 'getPrepareInstall': #step 2
                return self::getPrepareInstall(getArgv('name', 2), getArgv('type'));

            case 'getDependExtension':
                json(self::getDependExtension(getArgv('name', 2), getArgv('file'), getArgv('version')));

            case 'installModule': # step 3
                return json(self::installModule(getArgv('name', 2), getArgv('type')));

            case 'loadLocal':
                return self::loadLocal();
            case 'loadInstalled':
                return self::loadInstalled();

            case 'getPublishInfo':
                return self::getPublishInfo(getArgv('name', 2));
            case 'publish':
                return self::publish(getArgv('pw'), getArgv('name', 2), getArgv('message'));
            case 'getVersion':
                return self::getVersion(getArgv('name', 2));
            case 'getPackage':
                return self::getPackage(getArgv('name', 2));

            case 'getChangedFiles':
                return self::getChangedFiles(getArgv('name', 2));
            case 'remove':
                json(self::removeModule(getArgv('name', 2)));

            case 'dbInit':
                return self::dbInit(getArgv('name', 2));

            case 'dbRemove':
                return self::dbRemove(getArgv('name', 2));

            //edit module
            case 'extractLanguage':
                json(krynLanguage::extractLanguage(getArgv('name', 2)));
            case 'getLanguage':
                return krynLanguage::getLanguage(getArgv('name', 2), getArgv('lang', 2));
            case 'saveLanguage':
                json(krynLanguage::saveLanguage(getArgv('name', 2), getArgv('lang', 2), getArgv('langs')));

            case 'getConfig':
                return self::loadInfo(getArgv('name', 2));

            case 'getHelp':
                return self::getHelp(getArgv('name', 2), getArgv('lang', 2));
            case 'saveHelp':
                json(self::saveHelp(getArgv('name', 2), getArgv('lang', 2), getArgv('help')));

            case 'saveLayouts':
                json(self::saveLayouts());
            case 'saveGeneral':
                json(self::saveGeneral());
            case 'saveExtras':
                json(self::saveExtras());
            case 'saveLinks':
                json(self::saveLinks());
            case 'saveDb':
                json(self::saveDb());

            case 'saveDocu':
                json(self::saveDocu());
            case 'getDocu':
                return self::getDocu();

            case 'savePlugins':
                json(self::savePlugins());
            case 'getPlugins':
                return self::getPlugins(getArgv('name', 2));


            case 'getObjects':
                return self::getObjects(getArgv('name', 2));
            case 'saveObjects':
                return self::saveObjects();

            case 'addCheckCode':
                return self::addCheckCode(getArgv('name', 2));


            case 'getWindowDefinition':
                return self::getWindowDefinition(getArgv('name', 2), getArgv('class', 2));
            case 'saveWindowClass':
                return self::saveWindowClass(getArgv('name', 2), getArgv('class', 2), array(
                    'general' => getArgv('general'),
                    'fields' => getArgv('fields'),
                    'columns' => getArgv('columns'),
                    'methods' => getArgv('methods'),
                ));
            case 'getWindows':
                return self::getWindows(getArgv('name', 2));
            case 'windowsExists':
                return self::windowsExists(getArgv('name', 2), getArgv('className', 2));
            case 'createWindows':
                return self::createWindows(getArgv('name', 2), getArgv('object', 2), getArgv('values', 2));
            case 'newWindow':
                return self::newWindow(getArgv('name', 2), getArgv('className'), getArgv('class'));
        }
    }

    /**
     * Converts system_pages to the new nested architecture (lft, rgt columns)
     *
     * @static
     * @return bool
     */
    public static function convertPagesTo10(){

        $res = dbExec('SELECT rsn FROM '.pfx.'system_domains');
        while ($row = dbFetch($res)){
            self::convertPagesTo10Domain($row['rsn']);
        }

        return true;
    }

    public static function convertPagesTo10Domain($pDomainRsn){

        $res = dbExec('SELECT rsn, prsn FROM '.pfx.'system_pages WHERE (prsn = 0 OR prsn IS NULL) AND domain_rsn = '.($pDomainRsn+0).
                      ' ORDER BY sort');

        self::$migrationCurrentPos = 0;
        while ($row = dbFetch($res)){

            self::convertPagesTo10Node($row['rsn']);

        }

    }
    public static function convertPagesTo10Node($pNodeRsn, $pDepth = 1){

        $node = dbExFetch('SELECT rsn, prsn, title FROM '.pfx.'system_pages WHERE rsn = '.($pNodeRsn+0));

        self::$migrationCurrentPos++;
        $newPosOfNode = array('lft' => self::$migrationCurrentPos);

        $res = dbExec('SELECT rsn, prsn FROM '.pfx.'system_pages WHERE prsn = '.($pNodeRsn+0).' ORDER BY sort ASC');

        echo str_repeat('  ', $pDepth)."#".$node['rsn'].' - '.$node['title'].' => '.$newPosOfNode['lft']."\n";

        if ($res){
            while ($row = dbFetch($res)){
                $newPos = self::convertPagesTo10Node($row['rsn'], $pDepth+1);
            }
        }

        self::$migrationCurrentPos++;
        $newPosOfNode['rgt'] = self::$migrationCurrentPos;
        echo str_repeat('  ', $pDepth)."#".$node['rsn'].' - '.$newPosOfNode['rgt']."\n";

        dbUpdate('system_pages', array('rsn' => $pNodeRsn), array(
            'lft' => $newPosOfNode['lft'],
            'rgt' => $newPosOfNode['rgt']
        ));

        return $newPosOfNode;

    }

    public static function windowsExists($pName, $pClassName){

        if (!$pName) return false;

        $path = PATH_MODULE.$pName.'/'.$pClassName.'.class.php';
        return file_exists($path);

    }

    public static function createWindows($pName, $pObjectKey, $pValues){

        $columns = array();

        $config = self::loadConfig($pName);
        $definition = $config['objects'][$pObjectKey];

        foreach ($pValues['windowListColumns'] as $column){
            if ($column['usage']){
                $columns[$column['key']] = array(
                    'label' => $column['label'],
                    'type' => 'predefined',
                    'object' => $pObjectKey,
                    'field' => $column['key'],
                    'width' => $column['width']
                );
            }
        }

        $list = array(
            'general' => array(
                'class' => 'adminWindowList',
                'dataModel' => 'object',
                'object' => $pObjectKey,
                'add' => true,
                'edit' => true,
                'remove' => true,
                'domainDepended' => $definition['domainDepended'],
                'multiLanguage' => $definition['multiLanguage'],
                'workspace' => $definition['workspace']
            ),
            'columns' => $columns
        );
        self::saveWindowClass($pName, trim($pValues['windowListName']), $list);


        $fields = array();

        foreach ($pValues['windowEditFields'] as $field){
            $fields[$field] = array(
                'label' => $definition['fields'][$field]['label']?$definition['fields'][$field]['label']:$field,
                'type' => 'predefined',
                'object' => $pObjectKey,
                'field' => $field
            );
        }

        $edit = array(
            'general' => array(
                'class' => 'adminWindowEdit',
                'dataModel' => 'object',
                'object' => $pObjectKey,
                'add' => true,
                'edit' => true,
                'remove' => true,
                'domainDepended' => $definition['domainDepended'],
                'multiLanguage' => $definition['multiLanguage'],
                'workspace' => $definition['workspace']
            ),
            'fields' => $fields
        );
        self::saveWindowClass($pName, trim($pValues['windowEditName']), $edit);

        $fields = array();

        foreach ($pValues['windowAddFields'] as $field){
            $fields[$field] = array(
                'label' => $definition['fields'][$field]['label']?$definition['fields'][$field]['label']:$field,
                'type' => 'predefined',
                'object' => $pObjectKey,
                'field' => $field
            );
        }

        $add = array(
            'general' => array(
                'class' => 'adminWindowAdd',
                'dataModel' => 'object',
                'object' => $pObjectKey,
                'add' => true,
                'edit' => true,
                'remove' => true,
                'domainDepended' => $definition['domainDepended'],
                'multiLanguage' => $definition['multiLanguage'],
                'workspace' => $definition['workspace']
            ),
            'fields' => $fields
        );
        self::saveWindowClass($pName, trim($pValues['windowAddName']), $add);


        if ($pValues['addEntrypoints']){

            //$config = self::loadConfig($pName);
            $config['admin'][$pObjectKey] = array(
                'title' => $pObjectKey.' list',
                'type'  => 'list',
                'isLink' =>  1,
                'class' => trim($pValues['windowListName']),
                'childs' => array(
                    'add' => array(
                        'title' => 'Add',
                        'type'  => 'add',
                        'isLink' =>  0,
                        'class' => trim($pValues['windowAddName']),
                    ),
                    'edit' => array(
                        'title' => 'Edit',
                        'type'  => 'edit',
                        'isLink' =>  0,
                        'class' => trim($pValues['windowEditName']),
                    )
                )
            );

            self::writeConfig($pName, $config);

        }

        return true;
    }

    public static function newWindow($pName, $pClassName, $pClass){

        $path = PATH_MODULE.$pName.'/'.$pClassName.'.class.php';
        if (file_exists($path)) return array('error' => 'file_already_exists');

        $content = "<?php

class $pClassName extends $pClass {

}

?>";

        kryn::fileWrite($path, $content);

        return true;
    }

    public static function saveWindowClass($pName, $pClass, $pValues){

        $path = PATH_MODULE.$pName.'/'.$pClass.'.class.php';

        if (file_exists($path) && !is_writeable($path)){
            return array('error' => 'no_writeaccess', 'error_file' => $path);
        }

        if (!file_exists($path) && !is_writeable(dirname($path))){
            return array('error' => 'no_writeaccess', 'error_file' => dirname($path));
        }

        if (!file_exists($path)){
            touch($path);
        }

        $general = $pValues['general'];

        $php = "<?php\n\n";

        $php .= "class $pClass extends ".$general['class']." {\n\n";

        foreach ($general as $key => $val){
            if ($key == 'class') continue;
            if ($key == 'object') continue;
            if ($key == 'primary') continue;
            if ($key == 'table') continue;
            if ($key == 'filter' && is_string($val)) {
                $val = explode(',', str_replace(' ', '', $val));
            };

            if ($key == 'dataModel'){
                if ($val == 'object')
                    $php .= "    public \$object = '".$general['object']."';\n\n";
                else {
                    $php .= "    public \$table = '".$general['table']."';\n\n";
                    $php .= "    public \$primary = '".$general['primary']."';\n\n";
                }
            } else {
                if ($val === 'true') $val = true;
                if ($val === 'false') $val = false;
                if (preg_match('/[0-9.]+/', $val)) $val += 0;
                $php .= "    public $".$key." = ".var_export($val, true).";\n\n";
            }
        }

        if ($pValues['columns']){
            $fields = $pValues['columns'];
            $var = 'columns';
        } else {
            $fields = $pValues['fields'];
            $var = 'fields';
        }

        if ($fields){
            self::parseFieldValues($fields);

            $fieldsCode = var_export($fields, true);
            $fieldsCode = preg_replace("/=>\s*array/", "=> array", $fieldsCode);
            $fieldsCode = str_replace("\n", "\n      ", $fieldsCode);

            $php .= "\n    public \$".$var." = ".$fieldsCode.";\n\n";
        }


        $methods = $pValues['methods'];

        if (is_array($methods)){
            foreach ($methods as $key => $val){

                if (substr($val, 0, 5) == '<?php'){
                    $val = substr($val, 6, -3);
                }

                $php .= $val;

            }
        }

        $php .= "}\n ?>";

        return kryn::fileWrite($path, $php);

    }

    public static function parseFieldValues(&$pFields){

        if (is_array($pFields)){
            foreach ($pFields as &$value){
                if (is_numeric($value)) $value += 0;
                if (is_array($value)) self::parseFieldValues($value);
            }
        }

    }


    public static function getWindowDefinition($pName, $pClass){

        $path = PATH_MODULE.$pName.'/'.$pClass.'.class.php';

        require_once(PATH_MODULE.'admin/adminWindowEdit.class.php');
        require_once(PATH_MODULE.'admin/adminWindowAdd.class.php');
        require_once(PATH_MODULE.'admin/adminWindowList.class.php');
        require_once(PATH_MODULE.'admin/adminWindowCombine.class.php');

        $content = explode("\n", kryn::fileRead($path));

        if (!file_exists($path)) return array('error' => 'class_file_not_found');

        require_once($path);

        $res = array(
            'properties' => array(
                '__file__' => $path
            )
        );

        $obj = new $pClass();
        foreach ($obj as $k => $v)
            $res['properties'][$k] = $v;

        $reflection = new ReflectionClass($pClass);
        $parent = $reflection->getParentClass();
        $parentClass = $parent->name;

        if ($parentClass == 'windowEdit')
            $parentClass = 'adminWindowEdit';

        if ($parentClass == 'windowAdd')
            $parentClass = 'adminWindowAdd';

        if ($parentClass == 'windowList')
            $parentClass = 'adminWindowList';

        if ($parentClass == 'windowCombine')
            $parentClass = 'adminWindowCombine';

        $res['class'] = $parentClass;

        $methods = $reflection->getMethods();

        foreach ($methods as $method){
            if ($method->class == $pClass){

                $code = '';
                for ($i = $method->getStartLine()-1; $i < $method->getEndLine(); $i++){
                    $code .= $content[$i]."\n";
                }

                $res['methods'][$method->name] = $code;
            }
        }

        if (getArgv('parentClass')){
            $parentClass = getArgv('parentClass', 2);
        }

        self::extractParentClassInformation($parentClass, $res['parentMethods']);

        return $res;
    }

    public static function extractParentClassInformation($pParentClass, &$pMethods){

        preg_match('/[A-Z]/', $pParentClass, $matches, PREG_OFFSET_CAPTURE);

        $firstCapLetter = $matches[0][1];
        $extKey = substr($pParentClass, 0, $firstCapLetter);

        $parentPath = PATH_MODULE.'admin/'.$pParentClass.'.class.php';

        if (file_exists($parentPath)){
            require_once($parentPath);
        } else {
            $parentPath = PATH_MODULE.$extKey.'/'.$pParentClass.'.class.php';
            if (file_exists($parentPath)){
                require_once($parentPath);
            }
        }

        $parentContent = explode("\n",kryn::fileRead($parentPath));
        $parentReflection = new ReflectionClass($pParentClass);

        $methods = $parentReflection->getMethods();
        foreach ($methods as $method){
            if ($method->class == $pParentClass){

                $code = '';
                for ($i = $method->getStartLine()-1; $i < $method->getEndLine(); $i++){

                    $code .= $parentContent[$i]."\n";
                    if (strpos($parentContent[$i], '{'))
                        break;

                }

                $pMethods[$method->name] = $code;
            }
        }

        $parent = $parentReflection->getParentClass();
        if ($parent){
            $parentClass = $parent->name;

            if ($parentClass == 'windowEdit')
                $parentClass = 'adminWindowEdit';

            if ($parentClass == 'windowAdd')
                $parentClass = 'adminWindowAdd';

            if ($parentClass == 'windowList')
                $parentClass = 'adminWindowList';

            if ($parentClass == 'windowCombine')
                $parentClass = 'adminWindowCombine';


            self::extractParentClassInformation($parentClass, $pMethods);
        }

    }

    public static function getWindows($pName) {

        $classes = find(PATH_MODULE.$pName.'/*.class.php');
        $windows = array();
        $whiteList = array('windowlist', 'windowadd', 'windowedit', 'windowcombine');

        foreach ($classes as $class){

            $content = kryn::fileRead($class);

            if (preg_match('/class ([a-zA-Z0-9_]*) extends (admin|)([a-zA-Z0-9_]*)\s*{/', $content, $matches)){
                if (in_array(strtolower($matches[3]), $whiteList))
                    $windows[] = $matches[1];
            }

        }

        return $windows;
    }



    public static function getDependExtension($pName, $pFile, $pNeedVersion) {
        $res = array('ok' => false);


        $del = false;
        $del = (strpos($pNeedVersion, '>') === false) ? $del : '>';
        $del = (strpos($pNeedVersion, '=') === false) ? $del : '=';
        $del = (strpos($pNeedVersion, '=>') === false) ? $del : '=>';
        $del = (strpos($pNeedVersion, '>=') === false) ? $del : '>=';
        $del = (strpos($pNeedVersion, '<') === false) ? $del : '<';
        $del = (strpos($pNeedVersion, '<=') === false) ? $del : '<=';
        $del = (strpos($pNeedVersion, '=<') === false) ? $del : '=<';

        $needVersion = str_replace($del, '', $pNeedVersion);

        $pFile = str_replace('..', '', $pFile);

        if (!file_exists($pFile)) {

            $info = self::loadInfo($pName, $pFile);
            if (!$info['noConfig'] && $info['extensionCode'] == getArgv('name')) {

                if (kryn::compareVersion($info['version'], $del, $needVersion)) {

                    $res = array('ok' => true);

                }

            }

        }

        return $res;
    }

    public static function getPlugins($pName) {
        $config = self::loadConfig($pName);
        return $config['plugins'];
    }

    public static function getObjects($pName) {
        $config = self::loadConfig($pName);
        return $config['objects'];
    }

    public static function getCategoryItems($pId, $pLang) {
        global $cfg;
        $res = wget($cfg['repoServer'] . "/?exGetCategoryList=1&id=$pId&lang=" . $pLang);
        print $res;
        exit;
    }

    public static function getBox($pCode) {
        global $cfg;
        $res = wget($cfg['repoServer'] . "/?exGetBox=1&code=$pCode");
        print $res;
        exit;
    }

    public static function managerSearch($q) {
        global $cfg;
        $res = wget($cfg['repoServer'] . "/?exSearch=$q");
        print $res;
        exit;
    }

    function getHelp($pName, $pLang) {
        $helpFile = PATH_MODULE . "$pName/lang/help_$pLang.json";
        $res = array();
        if (!file_exists($helpFile))
            json($res);
        else {
            $json = kryn::fileRead($helpFile);
            $help = json_decode($json, 1);
            json($help);
        }
    }

    public static function saveHelp($pName, $pLang, $pHelp) {
        $helpFile = PATH_MODULE . "$pName/lang/help_$pLang.json";
        $json = json_format($pHelp);
        kryn::fileWrite($helpFile, $json);
        json(1);
    }

    public static function getDocu() {
        $lang = getArgv('lang', 2);
        $name = getArgv('name', 2);
        $text = kryn::fileRead(PATH_MODULE . "$name/docu/$lang.html");
        json($text);
    }

    public static function saveDocu() {
        $lang = getArgv('lang', 2);
        $text = getArgv('text');
        $name = getArgv('name', 2);
        if (!is_dir(PATH_MODULE . "$name/docu/"))
            mkdir(PATH_MODULE . "$name/docu/");
        kryn::fileWrite(PATH_MODULE . "$name/docu/$lang.html", $text);
        json(1);
    }

    public static function addCheckCode($pName) {
        global $cfg;

        if (file_exists(PATH_MODULE . '' . $pName)) {
            $res['status'] = 'exist';
        } else {
            $res = wget('http://www.kryn.org/rpc?t=checkExtensionCode&code=' . $pName);
            $res = json_decode($res, 1);
        }

        if ($res['status'] == 'ok') {
            @mkdir(PATH_MODULE . "$pName");
            @mkdir(PATH_MEDIA.$pName);
            $config = array(
                'version' => '0.0.1',
                'owner' => $cfg['communityId'],
                'community' => 0,
                'category' => 0,
                'writableFiles' => PATH_MEDIA . $pName . '/*',
                'title' => array(
                    'en' => 'Enter here a title for ' . $pName
                ),
                'desc' => array(
                    'en' => 'Enter here a description about your extension'
                )
            );
            self::writeConfig($pName, $config);
        }

        return $res;

    }

    public static function saveDb() {

        $name = getArgv('name', 2);

        $config = self::loadConfig($name);

        if (!getArgv('tables')) return array('error' => 'empty_param');

        $db = json_decode(getArgv('tables'), true);
        $config['db'] = $db;

        $res = self::writeConfig($name, $config);
        return $res === true?true:$res;
    }

    public static function saveObjects() {

        $name = getArgv('name', 2);

        $config = self::loadConfig($name);

        $objects = json_decode(getArgv('objects'), true);
        $config['objects'] = $objects;

        $res = self::writeConfig($name, $config);
        return $res === true?true:$res;
    }

    public static function savePlugins() {

        $name = getArgv('name', 2);

        $config = self::loadConfig($name);

        $plugins = json_decode(getArgv('plugins'), true);
        $config['plugins'] = $plugins;

        $res = self::writeConfig($name, $config);
        return $res === true?true:$res;
    }

    public static function saveLinks() {

        $name = getArgv('name', 2);

        $config = self::loadConfig($name);

        $admin = json_decode(getArgv('admin'), true);
        $config['admin'] = $admin;

        $res = self::writeConfig($name, $config);
        return $res === true?true:$res;
    }

    public static function saveGeneral() {

        $name = getArgv('name', 2);

        $config = self::loadConfig($name);

        if (getArgv('owner') > 0)
            $config['owner'] = getArgv('owner');

        $config['title'][getArgv('lang')] = getArgv('title');
        $config['desc'][getArgv('lang')] = getArgv('desc');
        $config['tags'][getArgv('lang')] = getArgv('tags');

        $config['version'] = getArgv('version');
        $config['community'] = getArgv('community');
        $config['writableFiles'] = getArgv('writableFiles');
        $config['category'] = getArgv('category');
        $config['depends'] = getArgv('depends');

        $res = self::writeConfig($name, $config);
        return $res === true?true:$res;
    }

    public static function saveExtras() {

        $vars = array('attachEvents', 'events', 'caches', 'cacheInvalidation', 'adminJavascript', 'adminCss');

        $name = getArgv('name', 2);
        $config = self::loadConfig($name);

        foreach ($vars as $var){
            if (getArgv($var) !== '')
                $config[$var] = getArgv($var);
            else
                unset($config[$var]);
        }

        $res = self::writeConfig($name, $config);
        return $res === true?true:$res;
    }

    public static function saveLayouts() {

        $themes = json_decode(getArgv('themes'), true);
        $name = getArgv('name', 2);

        $config = self::loadConfig($name);
        $config['themes'] = $themes;

        $res = self::writeConfig($name, $config);
        return $res === true?true:$res;
    }

    public static function writeConfig($pName, $pConfig) {
        $json = json_format(json_encode($pConfig));

        $path = "core/config.json";

        if ($pName != 'kryn')
            $path = PATH_MODULE . "$pName/config.json";

        if (!is_writeable($path)){
            return array('error' => 'file_not_writeable');
        }

        return kryn::fileWrite(PATH_MODULE . "$pName/config.json", $json);
    }

    public static function removeModule($pModuleName) {

        $files = json_decode($_REQUEST['files'], true);
        $pModuleName = esc(str_replace("..", "", $pModuleName));

        $h = fopen(PATH_MODULE . '' . $pModuleName . '/files.md5', 'r');
        mkdirr('data/packages/modules/removeMod/');
        $id = time() . $pModuleName;
        $folders = array();
        $copyBack = array();

        if ($h) {
            while ($line = @fgets($h)) {
                $temp = explode(" ", $line);
                $md5 = substr($temp[1], 0, -1);
                $filename = $temp[0];

                $save = false;

                if (is_array($files)) {
                    foreach ($files as $file => $delete) {
                        //if not checked
                        if ($file == $filename && $delete != 1)
                            $save = true;
                    }
                }


                if ($save) {
                    mkdirr("data/packages/modules/removeMod/$id/" . dirname($filename));
                    rename($filename, "data/packages/modules/removeMod/$id/" . $filename);
                    $copyBack[] = $filename;
                } else {
                    unlink($filename);
                }

                $folders[dirname($filename)] = 1;
            }
        }

        unlink(PATH_MODULE . '' . $pModuleName . '/files.md5');

        foreach ($folders as $folder => $dummy) {
            @rmdir($folder); //only remove if empty folder
        }

        if (count($copyBack) > 0)
            foreach ($copyBack as $file) {
                mkdirr(dirname($file));
                rename("data/packages/modules/removeMod/$id/" . $file, $file);
            }


        delDir("data/packages/modules/removeMod/$id/");
        adminDb::remove(kryn::$configs[$pModuleName]);
        dbDelete('system_modules', "name = '$pModuleName'");

        kryn::clearLanguageCache();
        return true;
    }

    public static function getChangedFiles($pModuleName) {

        $res = array();
        $res['modifiedFiles'] = array();

        $pModuleName = str_replace("..", "", $pModuleName);
        $config = kryn::getModuleConfig($pModuleName);
        $writableFiles = explode("\n", $config['writableFiles']);

        if (is_array($writableFiles)) {

            $h = fopen(PATH_MODULE . '' . $pModuleName . '/files.md5', 'r');

            if (!$h) return $res;
            $md5s = array();
            while ($line = @fgets($h)) {
                $temp = explode(" ", $line);
                $temp[1] = substr($temp[1], 0, -1);
                $md5s[$temp[0]] = $temp[1];
            }

            foreach ($md5s as $file => $md5) {
                foreach ($writableFiles as $path) {
                    if ($path != "" && preg_match('/' . str_replace('/', '\/', $path) . '/', $file) != 0) {
                        if (file_exists($file) && $md5 != md5(kryn::fileRead($file)))
                            $res['modifiedFiles'][] = $file;
                    }
                }
            }
        }

        return $res;
    }

    public static function getVersion($pName) {
        global $cfg;
        return wget($cfg['repoServer'] . '/?version=' . $pName);
    }

    public static function getPackage($pModuleName) {
        $res['file'] = self::createArchive($pModuleName);
        json($res);
    }

    public static function publish($pPw, $pModuleName, $pMessage) {
        global $cfg;
        $res = wget($cfg['repoServer'] . '/?checkPw=1&id=' . $cfg['communityId'] . "&pw=$pPw");
        if ($res != "1")
            json(0);
        $file = self::createArchive($pModuleName);
        $res = array();
        $status = wget('http://www.kryn.org/rpc?t=publish&id=' . $cfg['communityId'] . "&pw=$pPw&message=" .
                       urlencode($pMessage), null, $file);
        $res['file'] = $file;
        $res['status'] = $status;
        json($res);
    }

    public static function createArchive($pModuleName) {

        $config = self::loadInfo($pModuleName);

        $temp = 'data/packages/modules/createArchive_' . $pModuleName . '/';
        if (file_exists($temp))
            delDir($temp);

        mkdirr($temp);

        if ($pModuleName != 'kryn') {
            mkdirr($temp . PATH_MODULE . $pModuleName);
            copyr(PATH_MODULE . '' . $pModuleName, $temp . PATH_MODULE . $pModuleName);
        }

        $template = PATH_MEDIA . $pModuleName;
        if (file_exists($template)) {
            mkdirr($temp . $template);
            copyr($template, $temp . $template);
        }

        if ($config['extraFiles']) {
            foreach ($config['extraFiles'] as $item) {
                mkdirr(dirname($temp . $item));
                copyr($item, $temp . $item);
            }
        }

        include_once('File/Archive.php');
        #generate md5 of each file

        chdir($temp);
        $files = find('./*');
        chdir('../../../../'); //to PATH
        $md5s = "";

        if ($pModuleName == 'kryn')
            $files[] = './.htaccess';

        $files2Compress = array();
        foreach ($files as $file) {
            if (is_dir($temp . $file) && is_dir($temp . $file . '/.svn')) {
                delDir($temp . $file . '/.svn');

            } else if (!is_dir($temp . $file) && strpos($file, 'files.md5') === false) {
                $file = substr($file, 2);
                $md5s .= $file . ' ' . md5(kryn::fileRead($temp . $file)) . "\n";

                if (!class_exists('ZipArchive')){
                    $reads[] = File_Archive::read($file, $file);
                }
            }
        }

        if ($pModuleName == 'kryn')
            $md5File = PATH_CORE.'files.md5';
        else
            $md5File = PATH_MODULE . $pModuleName . '/files.md5';

        kryn::fileWrite($temp . $md5File, $md5s);

        $archive = "data/packages/modules/$pModuleName-" . $config['version'] . '_' . date("ymdhis") . ".zip";

        if (!is_writeable('data/packages/modules/')){
            klog('core', 'data/packages/modules is not writeable. Can not create the extension archive.');
            return;
        }

        if (class_exists('ZipArchive')){
            $zip = new ZipArchive();
            $zip->open($archive, ZIPARCHIVE::CREATE);

            foreach ($files as $file)
                if (!is_dir($file)){
                    $zip->addFile($file, $file);
                }
            $zip->addFile($temp . $md5File, $md5File);
            $zip->close();
        } else {

            $reads[] = File_Archive::read($temp . $md5File, $md5File);

            File_Archive::setOption('zipCompressionLevel', 9);
            //        File_Archive::setOption('appendRemoveDuplicates', true);

            $source = File_Archive::readMulti(
                $reads
            );

            File_Archive::extract(
                $source,
                $archive
            );
        }

        return $archive;

    }

    public static function dbRemove($pName) {


        if (!$pName){

            $res = (php_sapi_name() === 'cli' )?"Remove all tables from all extensions\n\n":array();

            foreach (kryn::$configs as $key => $config){
                if (php_sapi_name() === 'cli' ){
                    $res .= self::dbRemove($key)."\n";
                } else {
                    $res = array_merge($res, self::dbRemove($key));
                }
            }

            return $res;
        }

        $res = 'Remove tables in extension '.$pName.":\n\n";

        $config = kryn::getModuleConfig($pName);
        $removedTables = adminDb::remove($config);

        if (php_sapi_name() === 'cli' ){
            if (is_array($removedTables) && count($removedTables) > 0){
                foreach ($removedTables as $table){
                    $res .= "\t$table removed.\n";
                }
            } else {
                $res .= "\tno tables removed.\n";
            }
            return $res;
        } else {
            return array($pName =>$removedTables);
        }
    }

    public static function dbInit($pName, $pModel = '') {
/*
        if (!$pName){
            if (php_sapi_name() === 'cli') print "Sync all models from all extensions.\n\n";

            $res = array();
            foreach (kryn::$configs as $key => $config){
                if ($key != 'kryn')
                    $res[$key] = self::dbInit($key)."\n";
            }

            return $res;
        }

        if (php_sapi_name() === 'cli') print 'Sync models in extension '.$pName.":\n\n";


        if (!$pModel){

            $res = array();
            $path = PATH_MODULE.$pName.'/models/*.dcm.xml';
            $models = find($path, 1);
            $path = str_replace('*', '(.*)', $path);
            print_r($models);
            foreach ($models as $model){
                $model = preg_replace("#$path#", '$1', $model);
                print "$model\n";
                $res[] = self::dbInit($pName, $model);
                print "$model done\n";
            }
            return $res;
        }


        if (php_sapi_name() === 'cli') print "Sync $pModel in $pName:\n\n";
*/

        $cmf = new Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
        $cmf->setEntityManager(kryn::$em);
        $metaData = $cmf->getAllMetadata();

        $entityGenerator = new Doctrine\ORM\Tools\EntityGenerator();
        $entityGenerator->setGenerateStubMethods(true);
        $entityGenerator->setRegenerateEntityIfExists(true);
        $entityGenerator->setUpdateEntityIfExists(true);
        $entityGenerator->generate($metaData, PATH_CORE.'entities/');

        $schemaTool = new Doctrine\ORM\Tools\SchemaTool(kryn::$em);
        $schemaTool->updateSchema($metaData, true);
        return;

        print "hi\n";
/*
        $cmf = new Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
        $cmf->setEntityManager(kryn::$em);

        try {
            $metaData = $cmf->getAllMetadata();
        } catch (Doctrine\ORM\Mapping\MappingException $e){
            $error = $e;
        }
        $entityGenerator = new Doctrine\ORM\Tools\EntityGenerator();
        $entityGenerator->setGenerateStubMethods(true);
        $entityGenerator->setRegenerateEntityIfExists(true);
        $entityGenerator->setUpdateEntityIfExists(true);
        $entityGenerator->generate($metaData, PATH_MODULE.$pName.'/');

        $schemaTool = new Doctrine\ORM\Tools\SchemaTool(kryn::$em);
        $res = $schemaTool->updateSchema($metaData, true);*/

        //if (php_sapi_name() === 'cli') print $res;
        return true;

    }

    public static function getPublishInfo($pName) {
        $config = kryn::getModuleConfig($pName);
        $res['config'] = $config;
        $res['serverVersion'] = self::getVersion($pName);

        $files = array();
        if (count($config['extraFiles']) > 0) {
            foreach ($config['extraFiles'] as $extraFile) {
                foreach (glob($extraFile) as $file) {
                    $files[$file] = is_dir($file) ? readFolder($file) : $file;
                }
            }
        }
        if ($pName != 'kryn')
            $files[PATH_MODULE . '' . $pName . '/'] = readFolder(PATH_MODULE . '' . $pName . '/');

        $files[PATH_MEDIA . $pName . '/'] = readFolder(PATH_MEDIA . $pName . '/');

        $res['files'] = $files;
        json($res);
    }

    public static function loadInstalled() {
        global $cfg;

        $res = array();
        $mods = dbTableFetch("system_modules", -1);
        $installed = array('kryn', 'admin', 'users');
        foreach ($mods as $mod) {
            $installed[] = $mod['name'];
        }
        foreach ($installed as $mod) {
            $config = self::loadInfo($mod);
            $res[$mod] = $config;
            $res[$mod]['activated'] = (kryn::$configs[$mod]) ? 1 : 0;
            $res[$mod]['serverVersion'] = wget($cfg['repoServer'] . "/?version=" . $mod);
            $res[$mod]['serverCompare'] =
                self::versionCompareToServer($res[$mod]['version'], $res[$mod]['serverVersion']);
        }

        json($res);
    }

    static public function loadLocal() {

        $modules = kryn::readFolder(PATH_MODULE);
        $modules[] = 'kryn';
        $res = array();

        foreach ($modules as $module) {
            $config = self::loadInfo($module);
            unset($config['db']);
            unset($config['admin']);
            unset($config['objects']);
            unset($config['plugins']);
            unset($config['widgetsLayout']);
            unset($config['widgets']);
            unset($config['adminJavascript']);
            unset($config['adminCss']);
            $res[$module] = $config;
            $res[$module]['activated'] = (kryn::$configs[$module]) ? 1 : 0;
        }

        json($res);

    }

    static public function loadConfig($pModuleName) {
        if ($pModuleName == 'kryn')
            $configFile = "core/config.json";
        else
            $configFile = PATH_MODULE . "$pModuleName/config.json";
        $json = kryn::fileRead($configFile);
        $config = json_decode($json, true);
        return $config;
    }

    static public function loadInfo($pModuleName, $pType = false, $pExtract = false) {
        global $cfg;

        /*
        * pType: false => load from local (dev) PATH_MODULE/$pModuleName
        * pType: path  => load from zip (module upload)
        * pType: true =>  load from inet
        */

        $pModuleName = str_replace(".", "", $pModuleName);
        $configFile = PATH_MODULE . "$pModuleName/config.json";

        if ($pModuleName == 'kryn')
            $configFile = "core/config.json";

        $extract = false;

        // inet
        if ($pType === true || $pType == 1) {

            $res = wget($cfg['repoServer'] . "/?install=$pModuleName");
            if ($res === false)
                return array('cannotConnect' => 1);

            $info = json_decode($res, 1);

            if (!$info['rsn'] > 0) {
                return array('notExist' => 1);
            }

            if (!@file_exists('data/upload'))
                if (!@mkdir('data/upload'))
                    klog('core', t('FATAL ERROR: Can not create folder data/upload.'));

            if (!@file_exists('data/packages/modules'))
                if (!@mkdir('data/packages/modules'))
                    klog('core', _l('FATAL ERROR: Can not create folder data/packages/modules.'));

            $configFile = "data/packages/modules/$pModuleName.config.json";
            @unlink($configFile);
            wget($cfg['repoServer'] . "/modules/$pModuleName/config.json", $configFile);
            if ($pExtract) {
                $extract = true;
                $zipFile = 'data/packages/modules/' . $info['filename'];
                wget($cfg['repoServer'] . "/modules/$pModuleName/" . $info['filename'], $zipFile);
            }
        }

        //local zip 
        if (($pType !== false && $pType != "0") && ($pType !== true && $pType != "1")) {
            if (file_exists(PATH_MEDIA . $pType)) {
                $pType = PATH_MEDIA . $pType;
            }
            $zipFile = $pType;
            $bname = basename($pType);
            $t = explode("-", $bname);
            $pModuleName = $t[0];
            $extract = true;
        }

        if ($extract) {
            @mkdir("data/packages/modules/$pModuleName");
            include_once('File/Archive.php');
            $toDir = "data/packages/modules/$pModuleName/";
            $zipFile .= "/";
            $res = File_Archive::extract($zipFile, $toDir);
            $configFile = "data/packages/modules/$pModuleName/module/$pModuleName/config.json";
            if ($pModuleName == 'kryn')
                $configFile = "data/packages/modules/kryn/core/config.json";
        }

        if ($configFile) {
            if (!file_exists($configFile)) {
                return array('noConfig' => 1);
            }
            $json = kryn::fileRead($configFile);
            $config = json_decode($json, true);

            if (!$pExtract) {
                @rmDir("data/packages/modules/$pModuleName");
                @unlink($zipFile);
            }

            //if locale
            if ($pType == false) {
                if (is_dir(PATH_MEDIA."$pModuleName/_screenshots")) {
                    $config['screenshots'] = kryn::readFolder(PATH_MEDIA."$pModuleName/_screenshots");
                }
            }

            $config['__path'] = dirname($configFile);
            if (is_array(kryn::$configs) && array_key_exists($pModuleName, kryn::$configs))
                $config['installed'] = true;

            $config['extensionCode'] = $pModuleName;

            if (kryn::$configs)
                foreach (kryn::$configs as $extender => &$modConfig) {
                    if (is_array($modConfig['extendConfig'])) {
                        foreach ($modConfig['extendConfig'] as $extendModule => $extendConfig) {
                            if ($extendModule == $pModuleName) {
                                $config['extendedFrom'][$extender] = $extendConfig;
                            }
                        }
                    }
                }

            return $config;
        }

    }

    public static function getChangedFilesForUpdate($pConfig) {

        $writableFiles = explode("\n", $pConfig['writableFiles']);

        $modFiles = array();
        if (is_array($writableFiles)) {

            $filename = $pConfig['__path'] . '/files.md5';

            $h = fopen($filename, 'r');
            $md5s = array();
            while ($line = @fgets($h)) {
                $temp = explode(' ', $line);
                $temp[1] = substr($temp[1], 0, -1);
                $md5s[$temp[0]] = $temp[1];
            }

            foreach ($md5s as $file => $md5) {
                foreach ($writableFiles as $path) {
                    if ($path != "" && preg_match('/' . str_replace('/', '\/', $path) . '/', $file) != 0) {
                        if (file_exists($file) && $md5 != md5(kryn::fileRead($file)))
                            $modFiles[] = $file;
                    }
                }
            }
        }
        return $modFiles;
    }

    public static function getPrepareInstall($pModuleName, $pType) {
        global $cfg;

        if ($pType != "0" && $pType != "1") {
            $temp = explode("-", basename($pType));
            $pModuleName = preg_replace('/\W/', '', $temp[0]);
        }

        $info = self::loadInfo($pModuleName, $pType, true);
        $res['module'] = $info;

        $modFiles = self::getChangedFilesForUpdate($info);

        if ($info['depends']) {
            $res['depends_ext'] = array();

            $depends = explode(',', str_replace(' ', '', $info['depends']));
            foreach ($depends as $depend) {


                $del = false;
                $del = (strpos($depend, '=') === false) ? $del : '=';
                $del = (strpos($depend, '>') === false) ? $del : '>';
                $del = (strpos($depend, '=>') === false) ? $del : '=>';
                $del = (strpos($depend, '>=') === false) ? $del : '>=';
                $del = (strpos($depend, '<') === false) ? $del : '<';
                $del = (strpos($depend, '<=') === false) ? $del : '<=';
                $del = (strpos($depend, '=<') === false) ? $del : '=<';

                $dependInfo = explode($del, $depend);
                $dependKey = $dependInfo[0];

                $res['depends_ext'][$dependKey]['installed'] = false;
                $res['depends_ext'][$dependKey]['needVersion'] = $del . $dependInfo[1];

                if (!kryn::$configs[$dependInfo[0]]) {

                    $res['needPackages'] = true;

                } else {

                    $dependConfig = kryn::$configs[$dependInfo[0]];
                    $res['depends_ext'][$dependKey]['installedVersion'] = $dependConfig['version'];
                    $res['depends_ext'][$dependKey]['toVersion'] = $dependInfo[1];

                    if (kryn::compareVersion($dependConfig['version'], $del, $dependInfo[1])) {
                        $res['depends_ext'][$dependKey]['installed'] = true;
                    } else {
                        $res['depends_ext'][$dependKey]['needUpdate'] = true;

                        //todo here we need files.md5 ...
                        $res['depends_ext'][$dependKey]['modifiedFiles'] = self::getChangedFilesForUpdate($dependKey);
                    }

                }

                if (!$res['depends_ext'][$dependKey]['installed'] || $res['depends_ext'][$dependKey]['needUpdate']) {
                    $res['needPackages'] = true;

                    $res['depends_ext'][$dependKey]['server_version'] = false;


                    $serverRes = wget($cfg['repoServer'] . '/?version=' . $dependKey);
                    if ($serverRes && $serverRes != '') {
                        $res['depends_ext'][$dependKey]['server_version'] = true;
                        if (!kryn::compareVersion($serverRes, $del, $dependInfo[1])) {
                            $res['depends_ext'][$dependKey]['server_version_not_ok_version'] = $serverRes;
                            $res['depends_ext'][$dependKey]['server_version_not_ok'] = true;
                        }
                    }


                }


            }
        }

        $newFiles = array();

        $res['modifiedFiles'] = $modFiles;
        $res['newFiles'] = $newFiles;

        json($res);
    }

    private static function versionCompareToServer($local, $server) {
        list($major, $minor, $patch) = explode(".", $local);
        $lversion = $major * 1000 * 1000 + $minor * 1000 + $patch;

        list($major, $minor, $patch) = explode(".", $server);
        $sversion = $major * 1000 * 1000 + $minor * 1000 + $patch;

        if ($lversion == $sversion)
            return '='; // Same version
        else if ($lversion < $sversion)
            return '<'; // Local older
        else
            return '>'; // Local newer
    }

    public static function getInstallInfo($pModuleName, $pType) {
        global $cfg;

        if ($pType != "0" && $pType != "1") {
            $temp = explode("-", basename($pType));
            $pModuleName = preg_replace('/\W/', '', $temp[0]);
        }

        $info = self::loadInfo($pModuleName, $pType);
        if ($info['cannotConnect'])
            json($info);


        $res = json_decode(wget($cfg['repoServer'] . "/?getAdditionalInfo=$pModuleName"), 1);

        $res['installed'] = false;

        //$serverVersion = wget("http://download.kryn.org/?version=$pModuleName");
        //$res['serverVersion'] = $serverVersion;

        $res['module'] = $info;
        $res['serverCompare'] = self::versionCompareToServer($info['version'], $res['serverVersion']);

        if (kryn::$configs[$pModuleName] || $pModuleName == 'kryn-core') {
            $res['installed'] = true;
            $res['installedModule'] = self::loadInfo($pModuleName); //fetch local installed module infos
            $res['activated'] = (kryn::$configs[$pModuleName]) ? 1 : 0;
        }
        json($res);
    }

    public static function installModule($pModuleName, $pType) {
        global $cfg;
        if ($pType != "0" && $pType != "1") {
            $temp = explode("-", basename($pType));
            $pModuleName = preg_replace('/\W/', '', $temp[0]);
        }

        $res = wget($cfg['repoServer'] . "/?install2=$pModuleName");
        $info = self::loadInfo($pModuleName, $pType, true);

        $files = json_decode($_REQUEST['files'], true);
        foreach ($files as $file => $delete) {
            if ($delete != 1) { //delete new file, so the old file won't overwrite
                unlink("data/packages/modules/$pModuleName/$file");
            }
        }

        if ($pModuleName == 'kryn')
            @unlink("data/packages/modules/$pModuleName/install.php");

        $oldInfo = self::loadInfo($pModuleName);

        # copy files
        copyr("data/packages/modules/$pModuleName/.", ".");
        deldir("data/packages/modules/$pModuleName");

        //update script
        $updateScript = PATH_MODULE . $pModuleName . '/update.php';
        if (file_exists($updateScript)) {
            $GLOBALS['oldVersion'][$pModuleName] = $oldInfo;
            include($updateScript);
        }

        @rename('install.php', 'install.php.' . rand(123, 5123) . rand(585, 2319293) . rand(9384394, 313213133));

        # db install
        if ($pModuleName != 'kryn') {
            dbDelete('system_modules', array('name' => $pModuleName));
            dbInsert('system_modules', array('name' => $pModuleName, 'activated' => 1));
            adminDb::install($info);
        }

        if ($info['extendConfig']) {
            foreach ($info['extendConfig'] as $extendConfig) {
                if ($extendConfig['db']) {
                    adminDb::install($extendConfig);
                }
            }
        }

        if (file_exists(PATH_MODULE . "$pModuleName/$pModuleName.class.php")) {
            require_once(PATH_MODULE . "$pModuleName/$pModuleName.class.php");
            $m = new $pModuleName();
            $m->install();
        }

        require_once(PATH_MODULE . 'admin/admin.class.php');
        admin::clearCache();

        return true;
    }

    //list all modules which have plugins -> for pluginChoooser
    public static function getModules() {
        global $user;

        $lang = $user->user['settings']['adminLanguage'] ? $user->user['settings']['adminLanguage'] : 'en';

        foreach (kryn::$configs as $key => $config) {
            if (!$config['plugins']) continue;
            $config['title'] = $config['title'][$lang] ? $config['title'][$lang] : $config['title']['en'];
            $config['name'] = $key;

            $res[] = $config;
        }

        json($res);
    }

    public static function check4updates() {
        global $cfg;

        $res['found'] = false;

        # add kryn-core
        $tmodules = kryn::$configs;

        foreach ($tmodules as $key => $config) {
            $version = '0';
            $name = $key;
            $version = wget($cfg['repoServer'] . "/?version=$name");
            if ($version && $version != '' && self::versionCompareToServer($config['version'], $version) == '<') {
                $res['found'] = true;
                $temp = array();
                $temp['newVersion'] = $version;
                $temp['name'] = $name;
                $res['modules'][] = $temp;
            }
        }

        json($res);
    }

    /*
    function devRemove( $pModule ){
        dbExec( "DELETE FROM %pfx%system_modules WHERE name = '$pModule'" );
        adminDb::remove( $pModule );
        return 'Ok';
    }

    function devUpdate( $pModule ){
        self::create2ndClass( $pModule );
        require( PATH_MODULE."$pModule/$pModule.class.php.new" );
        $module = new $pModule();
        $module->update();
        return 'ok';
    }

    function devInstall( $pModule ){
        dbExec( "INSERT INTO %pfx%system_modules VALUES('$pModule', 1)" );
        return '<pre>'.adminDb::install( $pModule ).'</pre>';
    }
    */

    public static function deactivate($pName) {
        dbUpdate('system_modules', array('name' => $pName), array('activated' => 0));
        kryn::clearLanguageCache();
        kryn::deleteCache('activeModules');
        json(1);
    }

    public static function exists($pModule) {
        if (kryn::$configs[$pModule])
            return true;
        return false;
    }

    public static function activate($pName) {
        $row = dbTableFetch('system_modules', 1, "name = '" . esc($pName) . "'");

        if ($row['name'] == '')
            dbInsert('system_modules', array('name' => $pName, 'activated' => 1));
        else
            dbUpdate('system_modules', array('name' => $pName), array('activated' => 1));

        kryn::clearLanguageCache();
        kryn::deleteCache('activeModules');
        json(1);
    }

    /*

function deinstall($pName, $pLinks = array()){

   $info = self::loadInfo( $pName );
   $filename = $info['__path'].'/files.md5';

   $h = @fopen($filename, 'r');
   $md5s = array();
   while($line = @fgets($h)) {
       $temp = explode( '  ', $line );
       $temp[1] = substr( $temp[1], 0, -1);
       @unlink( $temp[1] );
   }

   dbDelete('system_modules',"`name` = '".strtolower($pName)."'");
   delDir(PATH_MODULE."$pName/");
   delDir(PATH_MEDIA.$pName/");
   json(1);
}
    */

    public static function readDirRekursiv($pDir) {
        global $step2;
        $res = array();

        $file = $pDir;
        if (!is_dir($file)) {
            $res[] = $file;
        }
        if (is_dir($file) === TRUE) {
            $dir = opendir($file);
            while (($_file = readdir($dir)) !== false) {
                if ($_file != '..' && $_file != '.' && $_file != '.svn') {
                    $res = array_merge($res, self::readDirRekursiv($file . '/' . $_file));
                }
            }
        }
        return $res;
    }

}

?>
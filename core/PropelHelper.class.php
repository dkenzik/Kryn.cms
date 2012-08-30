<?php

namespace Core;

class PropelHelper {

    public static $objectsToExtension = array();
    public static $classDefinition = array();

    private static $tempFolder = '';

    public static function init(){

        try {
            $result = self::fullGenerator();
        } catch(Exception $e){
            self::cleanup();
            Kryn::internalError('Propel initialization Error', is_array($e)?print_r($e,true):$e);
        }

        self::cleanup();
        Kryn::internalMessage('Propel initialization', $result);
    }

    public static function getTempFolder(){

        if (self::$tempFolder) return self::$tempFolder;

        self::$tempFolder = Kryn::getTempFolder();

        return self::$tempFolder;
    }

    public static function callGen($pCmd){

        $errors = self::checkModelXml();
        if ($errors)
            return array('errors' => $errors);

        self::writeXmlConfig();
        self::writeBuildPorperties();
        self::collectSchemas();

        switch($pCmd){
            case 'models':
                $result = self::generateClasses(); break;
            case 'update':
                $result = self::updateSchema(); break;
            case 'environment': return true;
        }

        self::cleanup();

        return $result;
    }

    public static function cleanup(){

        $tmp = self::getTempFolder();
        delDir($tmp . 'propel');

    }

    public static function checkModelXml(){
        foreach (Kryn::$extensions as $extension){

            if ($extension == 'kryn') continue;

            if (file_exists($schema = PATH_MODULE.$extension.'/model.xml')){

                simplexml_load_file($schema);
                if ($errors = libxml_get_errors())
                    $errors[$schema] = $errors;

            }
        }

        return $errors;
    }

    public static function fullGenerator(){

        self::writeXmlConfig();
        self::writeBuildPorperties();
        self::collectSchemas();

        $content = '';

        $content .= self::generateClasses();
        $content .= self::updateSchema();

        self::cleanup();

        $content .= "\n\n<b style='color: green'>Done.</b>";

        return $content;
    }

    public static function generateClasses(){

        //delete old map/om folders

        $content  = self::execute('om');

        if (is_array($content)){
            throw new \Exception("Propel generateClasses failed: \n". $content[0]);
        }
        $content .= self::moveClasses();

        return $content;
    }

    public static function collectClassDefinition(){

        self::collectObjectToExtension();

        foreach (self::$objectsToExtension as $name => $extension){

            if (!$name) continue;

            $files = array(
                'om/Base'.$name.'Peer.php',
                'om/Base'.$name.'.php',
                'map/'.$name.'TableMap.php',
                'om/Base'.$name.'Query.php',
                'x' => $name.'.php',
                'y' => $name.'Peer.php',
                'z' => $name.'Query.php'
            );

            foreach ($files as $key => $file){
                $target    = PATH_MODULE.$extension.'/model/'.$file;
                $targetDir = dirname(PATH_MODULE.$extension.'/model/'.$file);
                self::$classDefinition[basename($file)] = $target;
            }

        }

    }

    public static function moveClasses(){

        $tmp = self::getTempFolder();

        self::collectObjectToExtension();
        
        foreach (Kryn::$extensions as $extension){
            delDir(PATH_MODULE.$extension.'/model/map/');
            delDir(PATH_MODULE.$extension.'/model/om');
        }

        $content = "\nMove class files<div style='color: gray;'>";

        foreach (self::$objectsToExtension as $name => $extension){

            if (!$name) continue;

            $files = array(
                'om/Base'.$name.'Peer.php',
                'om/Base'.$name.'.php',
                'map/'.$name.'TableMap.php',
                'om/Base'.$name.'Query.php',

                'x' => $name.'.php',
                'y' => $name.'Peer.php',
                'z' => $name.'Query.php'
            );

            foreach ($files as $key => $file){

                $target    = PATH_MODULE.$extension.'/model/'.$file;

                self::$classDefinition[basename($file)] = $target;

                if (!is_numeric($key) ){
                    $target = PATH_MODULE.$extension.'/model/'.$file;
                    //do not remove the class files which we can edit
                    if (file_exists($target)) continue;
                } else {
                    $target = $tmp.'propel-classes/'.basename($file);
                }


                $targetDir = dirname($target);
                if (!is_dir($targetDir)) if(!mkdirr($targetDir)) die('Can not create folder '.$targetDir);

                $source = $tmp . 'propel/build/classes/kryn/'.$file;

                if (!file_exists($source)){
                    $content .= "[move][$extension] ERROR can not find $source.\n";
                } else {

                    if (!rename($source, $target)){
                        die('Can not move file '.$source.' to '.$target);
                    }
                    $content .= "[move][$extension] Class moved $file to $targetDir\n";
                }
            }

        }

        return $content."</div>";

    }

    /**
     * Returns a array of propel config's value. We do not save it as .php file, instead
     * we create it dynamicaly out of our own config.php.
     * 
     * @return array The config array for Propel::init() (only in kryn's version of propel, no official)
     */
    public static function getConfig(){
        
        $adapter = Kryn::$config['database']['type'];
        if ($adapter == 'postgresql') $adapter = 'pgsql';


        $dsn = $adapter.':host='.Kryn::$config['database']['server'].';dbname='.Kryn::$config['database']['name'];

        $persistent = Kryn::$config['database']['persistent'] ? true:false;

        $emulatePrepares = Kryn::$config['database']['type'] == 'mysql';

        $config = array();
        $config['datasources']['kryn'] = array(
            'adapter' => $adapter,
            'connection' => array(
                'dsn' => $dsn,
                'user' => Kryn::$config['database']['user'],
                'password' => Kryn::$config['database']['password'],
                'options' => array(
                    'ATTR_PERSISTENT' => array('value' => $persistent)
                ),
                'settings' => array(
                    'charset' => array('value' => 'utf8')
                ),
                'attributes' => array(
                    'ATTR_EMULATE_PREPARES' => array('value' => $emulatePrepares)
                )
            )
        );
        $config['datasources']['default'] = 'kryn';


        return $config;
    }


    public static function writeXmlConfig(){

        $tmp = self::getTempFolder();

        if (!mkdirr($folder = $tmp.'propel/build/conf/'))
            throw new Exception('Can not create propel folder in '.$folder);

        $adapter = Kryn::$config['database']['type'];
        if ($adapter == 'postgresql') $adapter = 'pgsql';

        $dsn = $adapter.':host='.Kryn::$config['database']['server'].';dbname='.Kryn::$config['database']['name'];

        $persistent = Kryn::$config['database']['persistent'] ? true:false;

        $xml = '<?xml version="1.0"?>
<config>
    <propel>
        <datasources default="kryn">
            <datasource id="kryn">
                <adapter>'.$adapter.'</adapter>
                <connection>
                    <classname>PropelPDO</classname>
                    <dsn>'.$dsn.'</dsn>
                    <user>'.Kryn::$config['database']['user'].'</user>
                    <password>'.Kryn::$config['database']['password'].'</password>
                    <options>
                        <option id="ATTR_PERSISTENT">'.$persistent.'</option>
                    </options>';

        if (Kryn::$config['database']['type'] == 'mysql'){
            $xml .= '
                    <attributes>
                        <option id="ATTR_EMULATE_PREPARES">true</option>
                    </attributes>
                    ';
        }

        $xml .= '
                    <settings>
                        <setting id="charset">utf8</setting>
                    </settings>
                </connection>
            </datasource>
        </datasources>
    </propel>
</config>';
    
        file_put_contents($tmp . 'propel/runtime-conf.xml', $xml);
        file_put_contents($tmp . 'propel/buildtime-conf.xml', $xml);
        return true;
    }






    public static function updateSchema(){

        $file = 'propel/build/conf/kryn-conf.php';

        if (!file_exists($file)){
            self::writeXmlConfig();
            self::writeBuildPorperties();
            self::collectSchemas();
        }


        if (!\Propel::isInit()){
            \Propel::init(self::getConfig());
        }

        $sql = self::getSqlDiff();
        if (is_array($sql)){
            throw new \Exception("Propel updateSchema failed: \n". $sql[0]);
        }

        if (!$sql){
            return "Schema up 2 date.";
        }

        $sql = explode(";\n", $sql."\n");

        $result = '';

        foreach ($sql as $query){
            if (!trim($query)) continue;
            try {
                dbExec($query);
            } catch (Exception $e){
                $result .= "[error] $query -> $e\n";
            }
        }

        return $result?$result:'ok';
    }

    public static function getSqlDiff(){

        $tmp = self::getTempFolder();
        //remove all migration files
        $files = find($tmp . 'propel/build/migrations/PropelMigration_*.php');
        if ($files[0]) unlink($files[0]);


        $content = self::execute('diff');
        if (is_array($content)) return $content;

        $files = find($tmp . 'propel/build/migrations/PropelMigration_*.php');
        $lastMigrationFile = $files[0];

        if (!$lastMigrationFile) return '';

        preg_match('/(.*)\/PropelMigration_([0-9]*)\.php/', $lastMigrationFile, $matches);
        $clazz = 'PropelMigration_'.$matches[2];

        require($lastMigrationFile);
        $obj = new $clazz;

        $sql = $obj->getUpSQL();

        $sql = $sql['kryn'];
        unlink($lastMigrationFile);

        $sql = preg_replace('/^DROP TABLE .*$/im', '', $sql);
        $sql = preg_replace('/^#.*$/im', '', $sql);

        return trim($sql);

    }

    public static function collectObjectToExtension(){

        foreach (Kryn::$extensions as $extension){

            if ($extension == 'kryn') continue;

            if (file_exists($schema = PATH_MODULE.$extension.'/model.xml')){

                $tables = simplexml_load_file ($schema);

                foreach ($tables->table as $table){
                    $attributes = $table->attributes();
                    $clazz = (string)$attributes['phpName'];

                    self::$objectsToExtension[$clazz] = $extension;

                }

            }
        }
    }

    public static function collectSchemas(){

        $tmp = self::getTempFolder();

        $schemeData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n  <database name=\"kryn\" defaultIdMethod=\"native\">";

        foreach (Kryn::$extensions as $extension){

            if ($extension == 'kryn') continue;

            if (file_exists($schema = PATH_MODULE.$extension.'/model.xml')){

                $tables = simplexml_load_file($schema);
                $newSchema = $schemeData;

                foreach ($tables->table as $table){ 
                    $newSchema .= $table->asXML()."\n    ";
                }

                $newSchema .= "</database>";

                $file = $extension.'.schema.xml';
                file_put_contents($tmp . 'propel/'.$file, $newSchema);
            }

        }

        return true;
    }

    public static function execute(){

        $chdir = getcwd();
        chdir('lib/propel/generator/');

        $oldIncludePath = get_include_path();
        set_include_path("./lib" . PATH_SEPARATOR . get_include_path());

        $argv = array('propel-gen');

        foreach (func_get_args() as $cmd)
            $argv[] = $cmd;

        $tmp = self::getTempFolder();
        $tmp .= 'propel/';

        $argv[] = '-Dproject.dir='.$tmp;

        require_once 'phing/Phing.php';

        $outStreamS = fopen("php://memory", "w+");
        $outStream = new \OutputStream($outStreamS);
        $cmd = implode(' ', $argv);
        $outStream->write("\n\nExecute command: ".$cmd."\n\n");


        try {
            /* Setup Phing environment */
            \Phing::startup();

            error_reporting(E_ALL ^ E_NOTICE);

            \Phing::setOutputStream($outStream);
            \Phing::setErrorStream($outStream);

            // Set phing.home property to the value from environment
            // (this may be NULL, but that's not a big problem.)
            \Phing::setProperty('phing.home', getenv('PHING_HOME'));

            // Grab and clean up the CLI arguments
            $args = isset($argv) ? $argv : $_SERVER['argv']; // $_SERVER['argv'] seems to not work (sometimes?) when argv is registered
            array_shift($args); // 1st arg is script name, so drop it
            // Invoke the commandline entry point
            \Phing::fire($args);

            // Invoke any shutdown routines.
            \Phing::shutdown();
        } catch (Exception $x) {
            chdir($chdir);
            set_include_path($oldIncludePath);
            throw $x;
        }
        chdir($chdir);
        set_include_path($oldIncludePath);

        rewind($outStreamS);
        $content = stream_get_contents($outStreamS);

        if (strpos($content, "BUILD FINISHED") !== false && strpos($content, "Aborting.") === false){
            preg_match_all('/\[((propel[a-zA-Z-_]*)|phingcall)\] .*/', $content, $matches);
            $result  = "\nCommand successfully: $cmd\n";
            $result .= '<div style="color: gray;">';
            foreach ($matches[0] as $match){
                $result .= $match."\n";
            }

            return $result.'</div>';
        } else {
            return array($content);
        }
    }

    public static function writeBuildPorperties(){

        $tmp = self::getTempFolder();

        if (!mkdirr($folder = $tmp . 'propel/'))
            throw new Exception('Can not create propel folder in '.$folder);

        $adapter = Kryn::$config['database']['type'];
        if ($adapter == 'postgresql') $adapter = 'pgsql';

        $dsn = $adapter.':host='.Kryn::$config['database']['server'].';dbname='.Kryn::$config['database']['name'].';';

        $properties = '
propel.database = '.$adapter.'
propel.database.url = '.$dsn.'
propel.database.user = '.Kryn::$config['database']['user'].'
propel.database.password = '.Kryn::$config['database']['passwd'].'
propel.tablePrefix = '.Kryn::$config['database']['prefix'].'
propel.database.encoding = utf8
propel.project = kryn';

        return file_put_contents($tmp . 'propel/build.properties', $properties)?true:false;
    }




}
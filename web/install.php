<?php

/*
* This file is part of Kryn.cms.
*
* (c) Kryn.labs, MArc Schmidt <marc@kryn.org>
*
* To get the full copyright and license information, please view the
* LICENSE file, that was distributed with this source code.
*/

if (function_exists('mb_internal_encoding'))
  mb_internal_encoding("UTF-8");

chdir(__DIR__.'/../');
error_reporting(E_CORE_ERROR|E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR|E_USER_ERROR|E_PARSE);

use Core\Kryn;
use Core\SystemFile;

$GLOBALS['krynInstaller'] = true;
if (!defined('PATH')) {
    define('PATH', realpath(__DIR__.'/../') . '/');
    define('PATH_CORE', 'core/');
    define('PATH_MODULE', 'module/');
    define('PATH_WEB', 'web/');
    define('PATH_WEB_CACHE', 'web/cache/');
}

@set_include_path( '.' . PATH_SEPARATOR . PATH . 'lib/pear/' . PATH_SEPARATOR . get_include_path());

require 'core/bootstrap.autoloading.php';

if (file_exists('config.php'))
  Kryn::$config = require('config.php');

if (!is_array(Kryn::$config)) Kryn::$config = array();

include(PATH_CORE . 'global/misc.global.php');
include(PATH_CORE . 'global/database.global.php');
include(PATH_CORE . 'global/template.global.php');
include(PATH_CORE . 'global/internal.global.php');
include(PATH_CORE . 'global/framework.global.php');
include(PATH_CORE . 'global/exceptions.global.php');

$lang = 'en';
$cfg = array();

@ini_set('display_errors', 1);

if( $_REQUEST['step'] == 'checkConfig' )
    checkConfig();

if ($_REQUEST['step'] == '5') {

    spl_autoload_register(function ($pClass) {
      if (substr($pClass, 0, 1) == '\\')
        $pClass = substr($pClass, 1);
      foreach (Kryn::$extensions as $extension) {
        if (file_exists($clazz = 'module/'.$extension.'/model/'.$pClass.'.php')) {
            include $clazz;

            return true;
        }
      }
    });
    $modules = array('admin', 'users');
    step5Init();
}

header("Content-Type: text/html; charset=utf-8");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de" dir="ltr">
  <head>
    <title>Kryn.cms installation</title>
      <link rel="stylesheet" type="text/css" href="core/css/normalize.css"  />
      <link rel="stylesheet" type="text/css" href="admin/css/ka/Button.css"  />
      <link rel="stylesheet" type="text/css" href="admin/css/ka/Input.css"  />

      <style type="text/css">
      body {
          line-height: 150%;
          font-size: 13px;
          margin: 0px;
          font-family: Verdana, Sans;
          background-color: #22628d;
          padding: 0px;
      }

      .logo {
          position: relative;
          left: -240px;
          margin-bottom: 25px;
          margin-top: 10px;
      }

      h1 {
        margin: 0px 0px 10px 0px;
        border-bottom: 1px solid #00273c;
        font-size: 12px;
        font-weight: bold;
        color: #145E84;
      }

      h2 {
        color: #145E84;
      }

      td {
        vertical-align: top;
      }

      .step a, .step a:link {
        text-decoration: none;
        color: gray;
      }

      a, a:link {
          color: gray;
      }

      body {
        text-align: center;
        font-size: 11px;
        font-family: Verdana,Arial,sans-serif;
      }

      table {
        font-size: 11px;
        margin: 5px;
        margin-left: 10px;
        width: 400px;
        color: #555;
      }

      table th {
        color: #444;
        border-bottom: 1px solid silver;
        font-weight: normal;
        text-align: left;
      }

      table.modulelist td {
          border-bottom: 1px solid #eee;
      }

      select {
        width: 152px;
      }

      .wrapper {
        text-align: left;
        margin: auto;
        width: 700px;
        left: 60px;
        -moz-border-radius: 10px;
        -webkit-border-radius: 10px;
        padding: 45px 35px;
        background-color: #f1f1f1;
        position: relative;
        color: #333;
      }

      .step a, .step a:link {
        display: block;
        text-align: left;
        padding: 12px 5px 12px 15px;
      }

      .step a.active {
        color: black;
        font-weight: bold;
      }

      .step {
        border-right: 0px;
        -moz-border-radius-topleft: 10px;
        -moz-border-radius-bottomleft: 10px;
        -webkit-border-top-left-radius: 10px;
        -webkit-border-bottom-left-radius: 10px;
        border-top-left-radius: 3px;
        border-bottom-left-radius: 3px;
        position: absolute;
        top: 27px;
        left: -150px;
        width: 150px;
        background-color: #f1f1f1;
        margin-bottom: 15px;
      }

      h2.main {
          font-size: 12px;
          line-height:13px;
          position: absolute;
          top: 0px;
          left: 35px;
          right: 35px;
          border-bottom: 1px dashed #ddd;
          padding-bottom: 5px;
          color: gray;
      }

      #progressBar {
          -moz-border-radius: 2px;
          -webkit-border-radius: 2px;
          border-radius: 2px;
          background-color: #22628d;
          height: 25px;
          text-align: center;
          color: white;
          line-height: 25px;
          font-weight: bold;
          position: relative;
      }

      #progressError {
          white-space: pre;
          background-color: white;
          padding: 15px;
          border: 1px solid red;
      }

      #progressBarText {
          position: relative;
      }
      #progressBarIn {
          -moz-border-radius: 2px;
          -webkit-border-radius: 2px;
          border-radius: 2px;
          background-color: #22518e;
          height: 25px;
          position: absolute;
          top: 0px;
          left: 0px;
      }

      select {
          width: 141px;
      }

      .breaker { clear: both }

    </style>
    <script type="text/javascript" src="core/mootools-core.js"></script>
    <script type="text/javascript" src="core/mootools-more.js"></script>
    <link rel="SHORTCUT ICON" href="admin/images/favicon.ico" />
  </head>
  <body>
    <img class="logo" src="core/images/logo_white.png" />
    <div class="wrapper">
    <h2 class="main">Installation</h2>
<?php

$step = 1;
if( !empty($_REQUEST['step']) )
    $step = $_REQUEST['step'];
?>

<div class="step">
    <a href="javascript:;" <?php if( $step == 1 ) echo 'class="active"'; ?>>1. Start</a>
    <a href="javascript:;" <?php if( $step == 2 ) echo 'class="active"'; ?>>2. Requirements</a>
    <a href="javascript:;" <?php if( $step == 3 ) echo 'class="active"'; ?>>3. Configuration</a>
    <a href="javascript:;" <?php if( $step == 4 ) echo 'class="active"'; ?>>4. Package</a>
    <a href="javascript:;" <?php if( $step == 5 ) echo 'class="active"'; ?>>5. Installation</a>
    <div class="breaker"></div>
</div>

<?php

switch ($step) {
case '5':
    step5();
    break;
case '4':
    step4();
    break;
case '3':
    step3();
    break;
case '2':
    step2();
    break;
case '1':
    welcome();
}

function checkConfig()
{
    global $cfg;

    $type = $_REQUEST['db_type'];

  if ((file_exists('config.php') && !is_writable('config.php'))
      || !file_exists('config.php') && !is_writable('.')
     ){
      $res['res'] = false;
      $res['error'] = './config.php is not writable.';
      die(json_encode($res));
  }

    $cfg = array(
          "db_server"	  => $_REQUEST['server'],
        "db_user"		  => $_REQUEST['username'],
        "db_password"   => $_REQUEST['password'],
        "db_name"     => $_REQUEST['db'],
        "db_prefix"   => $_REQUEST['prefix'],
        "db_type"     => $_REQUEST['type'],
        "db_pdo"      => $_REQUEST['pdo']
    );

    $res = array('res' => true);

  $dsn = $cfg['db_type'].':dbname='.$cfg['db_name'].';host='.$cfg['db_server'];

  try {
      $connection = new PDO($dsn, $cfg['db_user'], $cfg['db_password']);
  } catch (PDOException $e) {
      $res['res'] = false;
      $res['error'] = $dsn.': '.$e->getMessage();
  }

  if ($res['res'] == true) {

      $timezone = $_REQUEST['timezone'];
      if (!$timezone) $timezone = 'Europe/Berlin';

      $systemTitle = $_REQUEST['systemTitle'];
      if (!$systemTitle) $systemTitle = "Fresh install";

      $cfg = array(
          'id' => $_REQUEST['id'],
          'database' => array(
            'server' => $_REQUEST['server'],
            'user'   => $_REQUEST['username'],
            'password' => $_REQUEST['password'],
            'name'   => $_REQUEST['db'],
            'prefix' => $_REQUEST['prefix'],
            'type'   => $_REQUEST['type'],
            'protectTables' => ($_REQUEST['protectTables'] ? explode(',', preg_replace('[^a-zA-Z_-%0-9]', '', $_REQUEST['protectTables'])) : array()),
            'persistent'   => $_REQUEST['persistent'],
          ),

          'rename'      => $_REQUEST['rename']+0,

          'fileGroupPermission'      => $_REQUEST['fileGroupPermission'],
          'fileGroupName'      => $_REQUEST['fileGroupName'],
          'fileEveryonePermission'   => $_REQUEST['fileEveryonePermission'],
          'fileNoChangeMode'   => $_REQUEST['fileNoChangeMode']+0,
          'fileTemp'   => $_REQUEST['fileTemp'] ? $_REQUEST['fileTemp'] : null,

          'cache' => array(
            'class' => '\Core\Cache\Files'
          ),

          "passwordHashCompat" => 0,
          "passwordHashKey"    => Core\Client\ClientAbstract::getSalt(32),

          "displayErrors"        => $_REQUEST['displayErrors'],
          "displayBeautyErrors"        => $_REQUEST['displayBeautyErrors'],
          "displayDetailedRestErrors"    => $_REQUEST['displayDetailedRestErrors'],
          "logErrors"            => 0,
          "systemTitle"          => $systemTitle,
          "client"                 => array(
            "class"              => "\Core\Client\KrynUsers",
            "config"             => array(
              "emailLogin"       => false,
              "store"            => array(
                "class"          => "database",
                "config"         => array()
              )

            )
          ),
          "timezone"            => $timezone
      );
      $config = '<?php return '. var_export($cfg,true) .'; ?>';

      Kryn::$config = $cfg;

      delDir(Kryn::getTempFolder().'propel');

      $f = \Core\SystemFile::setContent('config.php', $config);

      if (!$f) {
          $res['error'] = 'Can not open file config.php - please change the permissions.';
          $res['res'] = false;
      }
  }

  die(json_encode($res));
}

function welcome()
{
?>

<h2>Thanks for choosing Kryn.cms!</h2>
<br />
Your installation folder is <strong style="color: gray;"><?php echo getcwd(); ?></strong>
<br />
<br />
<b>Kryn.cms license</b><br />
<br />
<div style="height: 350px; background-color: white; padding: 5px; overflow: auto; white-space: pre;">
    <?php $f = fopen("LICENSE", "r"); if($f) while (!feof($f)) print fgets($f, 4096) ?>
</div>
<br /><br />
<b style="color: gray">Kryn.cms comes with amazing additional third party software.</b><br />
      Please respect all of their licenses too:<br />
<br />
<table style="width: 100%" cellpadding="3">
    <tr>
    <th width="160">Name</th>
    <th width="250">Author/Link</th>
    <th>License</th>
</tr>
<tr>
    <td  width="160">Mootools</td>
    <td  width="250"><a href="http://mootools.net/">mootools.net</a></td>
    <td>&raquo; <a href="http://www.opensource.org/licenses/mit-license.php">MIT license</a></td>
</tr>
<tr>
    <td>Smarty</td>
    <td><a href="http://www.smarty.net/">www.smarty.net</a></td>
    <td>&raquo; <a href="http://www.gnu.org/licenses/lgpl.html">LGPL</a></td>
</tr>
<tr>
    <td>Codemirror</td>
    <td><a href="http://codemirror.net/">codemirror.net</a></td>
    <td>&raquo; <a href="../lib/codemirror/LICENSE">MIT-style license</a></td>
</tr>
<tr>
    <td>normalize.css</td>
    <td><a href="http://necolas.github.com/normalize.css/">necolas.github.com/normalize.css</a></td>
    <td>&raquo; <a href="../lib/codemirror/LICENSE">MIT License</a></td>
</tr>

<tr>
    <td>Silk icon set 1.3</td>
    <td><a href="http://www.famfamfam.com/lab/icons/silk/">www.famfamfam.com/lab/icons/silk/</a></td>
    <td>&raquo; <a href="http://creativecommons.org/licenses/by/2.5/">Creative Commons Attribution 2.5 License.</a></td>
</tr>
<tr>
    <td  width="160">[Mootools plugin] Stylesheet</td>
    <td  width="250"><a href="http://mifjs.net">Anton Samoylov</a></td>
    <td>&raquo; <a href="http://mifjs.net/license.txt">MIT-Style License</a></td>
</tr>

<tr>
    <td colspan="3">IconSet:
    </td>
</tr>
<tr>
    <td colspan="3" style="white-space: pre; background-color: white;"><?php print file_get_contents('web/admin/icons/license.txt'); ?>
    </td>
</tr>

</table>
<a href="?step=2" class="ka-Button" >Accept</a>

<?php
}

function step5Failed($pError)
{
    $msg = array('error' => $pError->getMessage(), 'exception' => (string) $pError);

    if ($pError instanceof \PDOException) {
        //$msg['sql'] = ;
    }
    print json_encode($msg);
    exit;
}

function step5Done($pMsg)
{
    $msg = array('data' => $pMsg);
    print json_encode($msg);
    exit;
}

    //prepare
    function step5_1()
    {
        try {

            \Core\TempFile::createFolder('./');
            \Core\WebFile::createFolder('cache/');

            \Core\PropelHelper::cleanup();

            \Core\Kryn::$extensions = array();

            \Core\PropelHelper::updateSchema();
            \Core\PropelHelper::cleanup();

        } catch (\Exception $e) {
            step5Failed($e);
        }

        step5Done(true);
    }

    //Install core modules
    function step5_2()
    {
        global $modules;

        $manager = new Admin\Module\Manager;

        foreach ($modules as $module) {
            try {
                $manager->install('core', true);
                $manager->install('admin', true);
                $manager->install('users', true);
            } catch (Exception $e) {
                step5Failed($e);
            }
        }
        step5Done(true);
    }

    //Update ORM
    function step5_3()
    {
        \Core\TempFile::remove('propel');

        \Core\Kryn::$config['activeModules'] = array();
        \Core\Kryn::$extensions = array('core', 'admin', 'users');

        try {
            \Core\PropelHelper::generateClasses();
            \Core\PropelHelper::updateSchema();

            //\Core\PropelHelper::cleanup();
        } catch (Exception $e) {
            step5Failed($e);
        }
        step5Done(true);
    }

    //execute installDatabase
    function step5_4()
    {
        global $modules;

        $manager = new Admin\Module\Manager;

        try {
            $manager->installDatabase('core');
            $manager->installDatabase('admin');
            $manager->installDatabase('users');
        } catch (Exception $e) {
            step5Failed($e);
        }
        step5Done(true);
    }

    //Install modules
    function step5_5()
    {
        $manager = new \Admin\Module\Manager;

        foreach (Kryn::$extensions as $module) {
            if ($module == 'admin' || $module == 'users' || $module == 'core') continue;
            try {
                $manager->install($module, true);
            } catch (Exception $e) {
                step5Failed($e);
            }
        }
        step5Done(true);
    }

    //Update ORM
    function step5_6()
    {
        \Core\TempFile::remove('propel');

        try {
            \Core\PropelHelper::generateClasses();
            \Core\PropelHelper::updateSchema();
            \Core\PropelHelper::cleanup();
        } catch (Exception $e) {
            step5Failed($e);
        }
        step5Done(true);
    }

    //Execute module database-scripts
    function step5_7()
    {
        global $modules;

        $manager = new \Admin\Module\Manager;

        foreach (Kryn::$extensions as $module) {
            if ($module == 'admin' || $module == 'users' || $module == 'core') continue;
            try {
                $manager->installDatabase($module);
            } catch (Exception $e) {
                step5Failed($e);
            }
        }
        step5Done(true);
    }

    //cleanup
    function step5_8()
    {
        \Core\PropelHelper::cleanup();

        //load all configs
        \Core\Kryn::loadModuleConfigs();

        \Admin\Utils::clearCache();

        if (Kryn::$config['rename']) {
            rename( 'install.php', '../install.doNotRemoveIt.'.rand(123,5123).rand(585,2319293).rand(9384394,313213133) );
        }

        step5Done(true);
    }

    //debug
    function step5_9()
    {
        \Core\TempFile::remove('propel');

        try {

            $diff = \Core\PropelHelper::getSqlDiff();
            header("Content-Type: text/plain");

            echo $diff;
            if (is_array($diff))
                print_r($diff);

            \Core\PropelHelper::cleanup();

            exit;
        } catch (Exception $e) {
            step5Failed($e);
        }

        step5Done(true);
    }

    function step5Init()
    {
        if (!file_exists('config.php')) {
            die('Config.php not found. Please open install.php without arguments again.');
        }

        $subStep = $_GET['substep']+0;
        Kryn::$config = require('config.php');

        define('pfx', Kryn::$config['database']['prefix']);

        if ($subStep == 0 && $_POST['modules']) {

            $dir = opendir( PATH_MODULE );
            if(! $dir ) return;
            while (($file = readdir($dir)) !== false) {
                if (is_dir(PATH_MODULE.$file) && $file != '..' && $file != '.' && $file != 'admin' && $file != 'users' ) {
                    if ($_POST['modules'][$file])
                        $modules[] = $file;
                }
            }

            Kryn::$config['activeModules'] = $modules;

            \Core\SystemFile::setContent('config.php', "<?php\nreturn ".var_export(Kryn::$config, true).";\n?>");
        }
        Kryn::$extensions = array('core', 'admin', 'users');
        Kryn::$extensions = array_merge(Kryn::$extensions, Kryn::$config['activeModules']);

        if (file_exists($file = 'propel-config.php')) {
            $propelConfig = include($file);
            Core\Kryn::$propelClassMap = $propelConfig['classmap'];
        }

        if ($subStep >= 1) {

            \Propel::setConfiguration(\Core\PropelHelper::getConfig());
            \Propel::initialize();
        }

        if ($subStep > 0) {
            $fn = 'step5_'.$subStep;
            $fn();
            exit;
        }

    }

function step5()
{
?>

<br />
<h2>Installation</h2>
<?php

?>
    <div id="progress">
        <div id="progressMessage">Pending ...</div>
        <div id="progressBar">
            <div id="progressBarIn" style="width: 0px"></div>
            <div id="progressBarText">0%</div>
        </div>
        <div id="progressError" style="display: none; overflow-x: auto;"></div>
    </div>
    <div id="installDone" style="display: none;">
        <br />
        <h3 style="color: green;">Installation successful!</h3>
        <br />
        <b>Administration login</b><br/>
        <table width="50%">
            <tr>
                <td>Login:</td>
                <td>admin</td>
            </tr>
            <tr>
                <td>Password:</td>
                <td>admin</td>
            </tr>

        </table>
        !Please change your password as fast as possible!<br />
        <br/>
        <br/>
        &raquo; Go to:
        <a target="_blank" href="../">Frontend</a> or <a target="_blank" href="./admin">Administration</a>
        <br/>
        <br/>
        <div style="color: gray;  border: 1px solid silver; border-top: 1px dashed #888; padding: 15px; margin: 15px 0; border-radius: 5px;">
            Developer information:<br/><br/>
            To get the correct auto-completion in your IDE, you have to include the following folder to your project,
            since there are all propel model classes.<br/>
            <pre><?php echo Kryn::getTempFolder().'propel-classes/' ?></pre>
        </div>
    </div>

    <script type="text/javascript">
        window.addEvent('domready', function(){

            var steps = [
                'Preparing and cleaning database ....',
                'Install core modules ...',
                'Update ORM ...',
                'Execute database-scripts ...',
                'Install modules ...',
                'Update ORM ...',
                'Execute module database-scripts ...',
                'Cleanup ...'
            ];

            var currentStep = 0;
            var handleNextStep;

            document.id('progressBarIn').set('tween', {duration: 250});

            handleNextStep = function(){
                currentStep++;

                document.id('progressMessage').set('text', steps[currentStep-1]);

                new Request.JSON({
                    url: 'install.php?step=5&substep='+currentStep,
                    noCache: 1,
                    onComplete: function(pResult){

                        if (pResult.error) {
                            document.id('progressError').setStyle('display', 'block');
                            document.id('progressError').set('html', pResult.error);
                            if (pResult.sql) {
                                new Element('div', {
                                    text: pResult.sql
                                }).inject(document.id('progressError'));
                            }
                            if (pResult.exception) {
                                new Element('div', {
                                    text: pResult.exception
                                }).inject(document.id('progressError'));
                            }
                            new Element('h2', {
                                style: 'color: red',
                                text: 'Error'
                            }).inject(document.id('progressError'), 'top');
                        } else {

                            width = (currentStep / (steps.length/700));
                            percent = (currentStep / (steps.length/100));
                            document.id('progressBarText').set('text', percent.toFixed(0)+'%');
                            document.id('progressBarIn').tween('width', width);

                            if (steps.length == currentStep) {
                                document.id('progress').setStyle('display', 'none');
                                document.id('installDone').setStyle('display', 'block');
                            } else {
                                handleNextStep.delay(250);
                            }
                        }
                    },
                    onError: function(pResult){

                        document.id('progressError').setStyle('display', 'block');
                        document.id('progressError').set('html', pResult);
                        new Element('h2', {
                            style: 'color: red',
                            text: 'Error'
                        }).inject(document.id('progressError'), 'top');
                    }
                }).get();

            }

            handleNextStep();

        });
    </script>

<?php
}

function step4()
{
?>

<br />
Your installation file contains following modules:<br />
<br />
<br />
<form action="?step=5" method="post" id="form.modules">

<table style="width: 98%" class="modulelist" cellpadding="4">
<?php

    $systemModules = array('core','admin','users');
    buildModInfo( $systemModules );

    $dir = opendir( PATH_MODULE );
    $modules = array();
    if(! $dir ) return;
    while (($file = readdir($dir)) !== false) {
        if (is_dir(PATH_MODULE.$file) && $file != '..' && $file != '.' && $file != '.svn' && (array_search($file, $systemModules) === false) ) {
            $modules[] = $file;
        }
    }
    buildModInfo( $modules );
?>
</table>
</form>
<b style="color: red;">All database tables related to these modules will be dropped in the next step!</b><br /><br/>
<a href="?step=3" class="ka-Button" >Back</a>
<a href="javascript: document.id('form.modules').submit();" class="ka-Button" >Install!</a>
<?php
}

function buildModInfo( $modules )
{
    global $lang;
    foreach ($modules as $module) {
         $config = \Admin\Module\Manager::loadInfo( $module );
         $version = $config['version'];
         $title = $config['title'];
         $desc = $config['desc'];

         $checkbox = '<input name="modules['.$module.']" checked type="checkbox" value="1" />';
         if ($config['system'] == "1") {
             $checkbox = '<input name="modules['.$module.']" checked disabled type="checkbox" value="1" />';
         }
        ?>
        <tr>
            <td valign="top" width="30"><?php print $checkbox ?></td>
            <td valign="top" width="150"><b><?php print $title ?></b></td>
            <td valign="top" width="90"><div style="color: gray; margin-bottom: 11px;">#<?php print $module ?></div></td>
            <td valign="top" >
            <?php print $desc ?>
            </td>
        </tr>
        <?php
    }

}

function step2()
{
    $anyThingOk = true;
    Kryn::initConfig();
?>

<h2>Checking requirements</h2>
<br />
<ol>

    <li><b>PHP Version</b>
    <?php

        $t = explode("-", PHP_VERSION);
        $v = ( $t[0] ) ? $t[0] : PHP_VERSION;
        //5.3.2 because flock()
        if (! version_compare($v, "5.3.2") < 0 ) {
            print '<div style="color: red">PHP version tot old.<br />';
            print "You need PHP version 5.3.0 or greater.<br />";
            print "Installed version: $v (".PHP_VERSION.")<br/></div>";
            $anyThingOk = false;
        } else {
            print '<div style="color: green">OK</div>';
        }
        ?>
    </li>

    <li><b>PHP Extensions</b>
    <?php

        if (!extension_loaded('mbstring')) {
            $anyThingOk = false;
            print '<div style="color: red">mbstring required.</div>';
        } else {
            print '<div style="color: green">mbstring OK</div>';
        }

        if (!extension_loaded('gd')) {
            $anyThingOk = false;
            print '<div style="color: red">libgd required.</div>';
        } else {
            print '<div style="color: green">libgd OK</div>';
        }

        if (!extension_loaded('zip')) {
            $anyThingOk = false;
            print '<div style="color: red">zip required.</div>';
        } else {
            print '<div style="color: green">zip OK</div>';
        }

        ?>
    </li>

    <li><b>Database PDO driver</b>
        <?php

        $drivers = array('mysql' => 'MySQL', 'pgsql' => 'PostgreSQL', 'sqlite' => 'SQLite', 'sqlsrv' => 'MSSQL');

        $oneFound = false;
        foreach ($drivers as $driver => $label) {
            $color = extension_loaded('pdo_'.$driver)?'green':'red';
            if ($color == 'green')
                $oneFound = true;
            print '<div style="color: '.$color.'">'.$label.'</div>';
        }
        if (!$oneFound) {
            print '<br /><div>There is no available pdo driver.</div>';
            $anyThingOk = false;
        }

?>
    </li>

</ol>
    <br />
    <a href="?step=1" class="ka-Button" >Back</a>
    <?php

    if ($anyThingOk) {
        print '<a href="?step=3" class="ka-Button" >Next</a>';
    } else {
        print '<a href="?step=2" class="ka-Button" >Re-Check</a>';
    }

}

function step3()
{
    ?>

<script type="text/javascript">

    window.addEvent('domready', function(){
        var driver = document.id('db_type');
        var server = document.id('db_server');
        var info   = document.id('db_name_sqlite_info');
        var user   = document.id('db_username');
        var passwd = document.id('db_password');
        var port   = document.id('db_port');
        var persis = document.id('db_persistent');

        driver.addEvent('change', function(){
            if (driver.value == 'sqlite') {
                server.required = false;
                user.required = false;
                [server, passwd, user, port, persis].each(function(item){
                    item.getParent('tr').setStyle('display', 'none');
                });
                info.setStyle('display', 'block');
            } else {
                server.required = true;
                user.required = true;
                [server, passwd, user, port, persis].each(function(item){
                    item.getParent('tr').setStyle('display', 'table-row');
                });
                info.setStyle('display', 'none');
            }
        });

        driver.fireEvent('change');

    });

    window.checkConfigEntries = function(){
        var ok = true;
        var driver = document.id('db_type');

        if (driver.value != 'sqlite') {
            if ( document.id('db_server').value == '' ) { document.id('db_server').highlight(); ok = false; }
            if ( document.id('db_username').value == '' ) { document.id('db_username').highlight(); ok = false; }
            if ( document.id('db_name').value == '' ) { document.id('db_name').highlight(); ok = false; }
        }

        if (ok) {
            document.id( 'status' ).set('html', '<span style="color:green;">Check data ...</span>');
            var req = document.id('form').toQueryString().parseQueryString();

            if (window.lcer) window.lcer.cancel();

            window.lcer = new Request.JSON({url: 'install.php?step=checkConfig', onComplete: function(stat){
                if( stat != null && stat.res == true )
                   location = '?step=4';
                else if( stat != null )
                    document.id('status').set('html', '<span style="color:red;">Failed:<br />'+stat.error+'</span>');
                else
                    document.id('status').set('html', '<span style="color:red;">Fatal Error. Please take a look in server logs.</span>');
            },
            onError: function(res){
                document.id('status').set('html', '<span style="color:red;">Fatal Error. Please take a look in server logs.</span> Maybe this helps: <br />'+res);
            }}).post(req);
        }
    }

    window.addSlave = function(){

      var tr = new Element('tr');

      var names = ['host', 'port', 'username', 'password', 'database', 'prefix'];
      for (var i=0; i<6;i++)
        new Element('input', {name: 'slaves['+names[i]+'][]', required: i==1?null:true, 'class': 'ka-Input', style: 'width: 100%'}).inject(new Element('td').inject(tr));

      var remove = new Element('a', {text: 'X', href: 'javascript:;'}).inject(new Element('td').inject(tr));

      remove.addEvent('click', function(){
        tr.destroy();
      })

      tr.inject($('slaves'));

    }
</script>

<form id="form" autocomplete="off" onsubmit="checkConfigEntries(); return false;">

  These settings are only the minimum settings to run Kryn.cms.<br/>
  To set more detailed settings, just login to the administration and open the settings window after the installation.

<h3>System</h3>

<table style="width: 100%" cellpadding="3">
    <tr>
        <td width="450">Title*</td>
        <td>
            <input type="text" class="ka-Input" required name="systemTitle" value="Fresh installation">
        </td>
    </tr>
    <tr>
        <td>Installation id*
            <div style="color: #aaa">No special characters.</div></td>
        <td>
            <input type="text" class="ka-Input" required name="id" value="<?php echo dechex((time() / 1000) / mt_rand(10, 100)); ?>">
        </td>
    </tr>
    <tr>
        <td>Timezone</td>
        <td>
            <select name="timezone">
                <?php
                    $zones = timezone_identifiers_list();
                    foreach ($zones as $zone) {
                        echo "<option ".($zone=='Europe/Berlin'?'selected="selected"':'').">$zone</option>";
                    }
                ?>
            </select>
        </td>
    </tr>

    <tr>
        <td>
            Display errors
            <div style="color: #aaa">Activates regular PHP error reporting.</div>
        </td>
        <td><input type="checkbox" checked="checked" name="displayErrors" value="1" /></td>
    </tr>
    <tr>
        <td>
            Display beauty errors
            <div style="color: #aaa">A fancy error view with highlighted code view of the debug trace.</div>
        </td>
        <td><input type="checkbox" checked="checked" name="displayBeautyErrors" value="1" /></td>
    </tr>

    <tr>
        <td>
            Display detailed RESTful API Error information.
            <div style="color: #aaa">Means file name, line number and backstrace.</div>
        </td>
        <td><input type="checkbox" checked="checked" name="displayDetailedRestErrors" value="1" /></td>
    </tr>
</table>

<h3>Local Filesystem</h3>

<table style="width: 100%" cellpadding="3">

    <tr>
        <td colspan="2"><div style="color: #aaa;">Following settings are used only for modified files and new files created by Kryn.cms.</div>
        </td>
    </tr>
    <tr>
        <td width="450">Default group owner
        </td>
        <td>
            <input type="text" class="ka-Input" name="fileGroupName" value="">
        </td>
    </tr>
    <tr>
        <td>Default group permission</td>
        <td>
            <select name="fileGroupPermission">
                <option value="rw">Read and Write</option>
                <option value="r">Read</option>
                <option value="-">None</option>
            </select>
        </td>
    </tr>
    <tr>
        <td>Default everyone permission</td>
        <td>
            <select name="fileEveryonePermission">
                <option value="-">None</option>
                <option value="r">Read</option>
                <option value="rw">Read and Write</option>
            </select>
        </td>
    </tr>
    <tr>
        <td>Do not change file's mode</td>
        <td>
            <input type="checkbox" value="1" name="fileNoChangeMode" />
        </td>
    </tr>
    <tr>
        <td>Temp directory
            <div style="color: #aaa">A absolute path to a directory for temporary, local caching, propel build and smarty compiling files. If empty, we search in the environment in this priority:<br/>
                TMP, TEMP, TMPDIR or TEMPDIR. If enything is empty, we use php's function sys_get_temp_dir().</div>
        </td>
        <td>
            <input type="text" class="ka-Input" name="fileTemp" value="">
        </td>
    </tr>
</table>

<h3>Database</h3>

<table style="width: 100%" cellpadding="3">
     <tr>
        <td width="450">Database PDO driver *</td>
        <td><select required name="type" id="db_type">
<?php

            $drivers = array('mysql' => 'MySQL', 'pgsql' => 'PostgreSQL', 'sqlite' => 'SQLite', 'sqlsrv' => 'MSSQL');

            foreach ($drivers as $driver => $label) {
                $enabled = extension_loaded('pdo_'.$driver)?'':'disabled="disabled"';
                print "<option $enabled value=\"$driver\">$label</option>";
            }
?>
        </select></td>
    </tr>

    <tr>
        <td>
          Persistent connections
            <div style="color: #aaa">You should probably deactivate this on the most low-cost and free web hoster.</div>
        </td>
        <td><input type="checkbox" checked="checked" id="db_persistent" name="persistent" value="1" /></td>
    </tr>
    <tr>
        <td>
          Host *
        </td>
        <td><input required type="text" class="ka-Input" name="server" id="db_server" value="localhost" /></td>
    </tr>
    <tr>
        <td>
          Port
            <div style="color: #aaa">Empty for default</div>
        </td>
        <td><input type="text" class="ka-Input" style="width:50px" id="db_port" name="port" value="" /></td>
    </tr>
    <tr id="ui_username">
        <td>Username *</td>
        <td><input required type="text" class="ka-Input" name="username" id="db_username" /></td>
    </tr>
    <tr id="ui_password">
        <td>Password</td>
        <td><input type="password" name="password" id="db_password" class="ka-Input" /></td>
    </tr>
    <tr id="ui_db">
        <td>
            Database name *
            <div id="db_name_sqlite_info" style="display: none; color: #aaa">For SQLite enter here the file name</div>
        </td>
        <td><input required type="text" class="ka-Input" name="db" id="db_name" /></td>
    </tr>
    <tr>
        <td>Table Prefix
            <div style="color: #aaa">
                Please use only a lowercase string.
            </div></td>
        <td><input style="width: 80px" type="text" class="ka-Input" name="prefix" id="db_prefix" value="kryn_" /></td>
    </tr>
    <tr>
        <td>Protect tables
            <div style="color: #aaa">
                Per default, the ORM (Propel ORM) we use drops all tables in the defined database, which are not used
                by Kryn.cms or modules. So you may enter here table names (%pfx% available) comma saparated, to
                protect these tables from the deletion.
            </div></td>
        <td><textarea style="height: 75px;" type="text" class="ka-Input" name="protectTables"></textarea></td>
    </tr>
    <tr>
        <td colspan="2">
            <br />
            * Required fields.
        </td>
    </tr>
</table>

<h3>Other</h3>
<table style="width: 100%" cellpadding="3">
    <tr>
        <td width="450">
            Rename install.php to random name
            <div style="color: #aaa">Highly recommended for security reasons.</div>
        </td>
        <td><input type="checkbox" checked="checked" name="rename" value="1" /></td>
    </tr>
</table>
<div id="status" style="padding: 4px;"></div>
<br />
<a href="?step=2" class="ka-Button" >Back</a>
<input type="submit" class="ka-Button" value="Test connection and write config.php" />
</form>

<?php
}

?>
    <script type="text/javascript" src="core/js/bgNoise.js"></script>
    </div>
  </body>
</html>

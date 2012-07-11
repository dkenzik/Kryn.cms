<?php


class test {

    function __construct(){

        return;
        $res = dbObjects('Session')->findAll();

        var_dump($res);
return;

        if (kryn::$admin) return;


        print "\n-------\n";

        $article = kryn::$em->find('Article', 4);

        print_r($article->getId());
        print "\n";
        print_r($article);

        print 'hi';
        exit;
        require 'lib/Doctrine/ORM/Tools/Setup.php';

        $lib = "lib/";
        Doctrine\ORM\Tools\Setup::registerAutoloadDirectory($lib);
        $paths = array("module/test/models/");
        $isDevMode = false;

        $dbParams = array(
            'driver'   => 'pdo_pgsql',
            'user'     => 'postgres',
            'password' => 'marc',
            'dbname'   => 'postgres',
        );

        $config = Doctrine\ORM\Tools\Setup::createXMLMetadataConfiguration($paths, $isDevMode);


        // Table Prefix
        /*
        $evm = new \Doctrine\Common\EventManager;
        require('lib/DoctrineExtensions/TablePrefix.php');
        $tablePrefix = new \DoctrineExtensions\TablePrefix('prefix_');
        $evm->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, $tablePrefix);

        $em = \Doctrine\ORM\EntityManager::create($dbParams, $config, $evm);*/


        $em = \Doctrine\ORM\EntityManager::create($dbParams, $config);
        /*
         $cmf = new Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
         $cmf->setEntityManager($em);

         $metadatas = $cmf->getAllMetadata();

             //create php class
         $entityGenerator = new Doctrine\ORM\Tools\EntityGenerator();

         $entityGenerator->setGenerateStubMethods(true);
         $entityGenerator->setRegenerateEntityIfExists(true);
         $entityGenerator->setUpdateEntityIfExists(true);
         $entityGenerator->generate($metadatas, 'module/test/');

         //update db
         $schemaTool = new Doctrine\ORM\Tools\SchemaTool($em);
         $sql = $schemaTool->updateSchema($metadatas, true);*/



        /*
        $sm = $em->getConnection()->getSchemaManager();

        $fromSchema = $sm->createSchema();
        $toSchema = $schemaTool->getSchemaFromMetadata($metadatas);

        $comparator = new \Doctrine\DBAL\Schema\Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        //$schemaDiff->removedTables = array();
        print_r($schemaDiff->toSql($em->getConnection()->getDatabasePlatform()));*/
        //$schemaTool->updateSchema($metadatas, true);
        //print_r($sql);

        //$articles = $em->find('Test');

        $test = new Article;
        $test->setTitle('HI was geht '.rand());
        $em->persist($test);
        $em->flush();

        print $test->getId();

        //$tests = $em->getRepository('Test')->findAll();
        //print_r($tests);

        //print $tool->getUpdateSchemaSql($classes);

        exit;


        if (kryn::$admin) return;return;

        $items = krynObjects::getTree(
            'node',
            13,
            array(    array('visible', '=', 1), 'OR', array('rsn', '=', 13)       ),//array(array('rsn', '!=', 9)),
            2,
            1
            //array('fields' => '*')
        );
        print '<pre style="font-size: 10px; line-height: 9px;">';
        print_r($items);
        print "</pre>";
        die();

        krynEvent::listen('onRenderSlot', array($this, 'onRenderSlot'));

        return;
        if (kryn::$admin) return;

        $news = new krynObject('news', 1);

        print $news->getTitle().'<br/>';

        if ($news->getTitle() == 'News item number one'){
            $news->setTitle('hoo');
        } else {
            $news->setTitle('News item number one');
        }
        //print $news->getTitle().'<br/>';
        $news->save();

        //exit;
    }

    public function onRenderSlot($pArguments){

        if($pArguments[0]['id'] != 1) return;

        //print_r(krynAcl::getSqlCondition('news'));

    }

}

?>
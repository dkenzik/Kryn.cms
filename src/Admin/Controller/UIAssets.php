<?php

namespace Admin\Controller;

use Core\Kryn;
use Core\Lang;
use Core\Models\LanguageQuery;
use Propel\Runtime\Map\TableMap;

class UIAssets
{
    public function getPossibleLangs()
    {
        $languages = LanguageQuery::create()
            ->filterByVisible(true)
            ->orderByCode()
            ->find()
            ->toArray('Code', null, TableMap::TYPE_STUDLYPHPNAME);

        if (0 === count($languages)) {
            $json = '{"en":{"code":"en","title":"English","langtitle":"English"}}';
        } else {
            $json = json_encode($languages);
        }

        header('Content-Type: text/javascript');
        print "window.ka = window.ka || {}; ka.possibleLangs = " . $json;
        exit;
    }

    public function getLanguagePluralForm($lang)
    {
        $lang = preg_replace('/[^a-z]/', '', $lang);
        $file = Lang::getPluralJsFunctionFile($lang); //just make sure the file has been created
        header('Content-Type: text/javascript');
        echo file_get_contents(PATH_WEB . $file);
        exit;
    }

    public function getLanguage($pLang)
    {
        $lang = esc($pLang, 2);

        if (!Kryn::isValidLanguage($lang)) {
            $lang = 'en';
        }

        Kryn::getAdminClient()->getSession()->setLanguage($lang);
        Kryn::getAdminClient()->syncStore();

        Kryn::loadLanguage($lang);

        if (getArgv('javascript') == 1) {
            header('Content-Type: text/javascript');
            print "if( typeof(ka)=='undefined') window.ka = {}; ka.lang = " . json_encode(Kryn::$lang);
            print "\nLocale.define('en-US', 'Date', " . Kryn::getInstance()->renderView(
                '@AdminBundle/mootools-locale.tpl'
            ) . ");";
            exit;
        } else {
            Kryn::$lang['mootools'] = json_decode(
                Kryn::getInstance()->renderView('@AdminBundle/mootools-locale.tpl'),
                true
            );

            return Kryn::$lang;
        }
    }

    public static function collectFiles($pArray, &$pFiles)
    {
        foreach ($pArray as $jsFile) {
            if (strpos($jsFile, '*') !== -1) {
                $folderFiles = find(PATH_WEB . $jsFile, false);
                foreach ($folderFiles as $file) {
                    if (!array_search($file, $pFiles)) {
                        $pFiles[] = $file;
                    }
                }
            } else {
                if (file_exists($jsFile)) {
                    $pFiles[] = $jsFile;
                }
            }
        }

    }
}

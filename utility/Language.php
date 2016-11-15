<?php


namespace Resty\Utility;
use Resty\Exception\HttpException;

/**
 * Language class
 *
 * Handles the internationalization
 *
 * @package    Resty
 * @subpackage Utility
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class Language {

    /**
     * Default language system wide
     *
     * @var string
     */
    public static $defaultLanguage = 'en-gb';

    /**
     * Active language folder path
     *
     * @var string
     */
    private $languagePath;

    /**
     * Configuration private constructor.
     * @param string $acceptLanguage - Accept language string given by the request
     * @param string $languagesFolder -  Path to the languages folder
     * @throws HttpException
     */
    public function __construct(string $acceptLanguage = null, string $languagesFolder= ROOT . DS . 'languages') {
        if(!file_exists($languagesFolder)) {
            // TODO throw 500 invalid languages directory
            throw new HttpException();
        } else {
            if($acceptLanguage == null) {
                // This folder must exists!
                $this->languagePath = $languagesFolder . DS . Language::$defaultLanguage;
            } else {
                $acceptLanguage = str_replace(' ', '', $acceptLanguage);
                $languages = explode(',', $acceptLanguage);
                $languageStrings = array();
                $weights = array();

                // Find out the requested languages and their weights
                foreach ($languages as $language) {
                    // Remove spaces
                    str_replace(' ', '', $language);

                    // Only if it is not empty
                    if($language != '') {
                        // Check if there is weight defined
                        $weightAndLanguage = explode(';', $language);

                        // Add weight 1 if not specified
                        if(!array_key_exists(1, $weightAndLanguage)) {
                            array_push($weightAndLanguage, 1);
                        } else {
                            str_replace('q=', '', $weightAndLanguage[1]);
                        }

                        array_push($languageStrings, $weightAndLanguage[0]);
                        array_push($weights, $weightAndLanguage[1]);
                    }
                }

                // Check if there are any of there matching te existing languages by folder
                array_multisort($weights, SORT_DESC, $languageStrings, SORT_DESC);
                foreach ($languageStrings as $lang) {

                    if(strpos($lang, '-')) {
                        if(file_exists($languagesFolder . DS . $lang)) {
                            $this->languagePath = $languagesFolder . DS . $lang;
                            return;
                        }
                    } else {
                        // Language like 'en', 'hu' NOT 'en-us'
                        $folderList = glob($languagesFolder . DS . $lang . '-*');
                        if(!empty($folderList)) {
                            $this->languagePath = $folderList[0];
                            return;
                        }
                    }
                }

                // TODO throw 500 couldn't find the language
                throw new HttpException();

            }
        }
    }

    /**
     * Translate a word by defining the key and
     * the resource which contains it
     *
     * @param string $resourceType - Which type of resource contains
     * @param string $key - Array key
     * @return string
     * @throws HttpException
     */
    public function translate(string $resourceType, string $key) : string {
        $translationArray = parse_ini_file($this->languagePath . DS . $resourceType . '.lan');
        if($translationArray && array_key_exists($key, $translationArray)) {
            return $translationArray[$key];
        }

        // TODO throw 500 error couldn't find a translation
        throw new HttpException();
    }

    /**
     * Translate a word and replace the
     * placeholders with the given variables
     *
     * @param string $resourceType - Which type of resource contains
     * @param string $key - Array key
     * @param array $variables - Variables to put in the string
     * @return string
     */
    public function translateWithVars(string $resourceType, string $key, array $variables) : string {
        $translated = self::translate($resourceType, $key);

        $i = 0;
        foreach ($variables as $var) {
            $translated = str_replace('{' . $i . '}', $var, $translated);
        }

        return $translated;
    }

    /**
     * Get the language file path
     *
     * @return string
     */
    public function getLanguagePath() {
        return $this->languagePath;
    }

}
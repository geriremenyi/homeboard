<?php


namespace Resty\Utility;
use Resty\Exception\FileNotFoundException;
use Resty\Exception\InvalidParametersException;

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
    private static $languagePath;

    /**
     * Currently loaded translations
     *
     * @var array
     */
    private static $translations = array();

    /**
     * Configuration private constructor.
     * @param string $acceptLanguage - Accept language string given by the request
     * @param string $languagesFolder -  Path to the languages folder
     * @throws FileNotFoundException
     * @throws InvalidParametersException
     */
    public static function setLanguagePath(string $acceptLanguage = null, string $languagesFolder = ROOT . DS . 'languages') {

        // Empty out static variables
        self::$languagePath = null;
        self::$translations = array();

        if(!file_exists($languagesFolder)) {
            throw new FileNotFoundException('Folder "'. $languagesFolder . '" does not exists"');
        } else {
            if($acceptLanguage == null) {
                // This folder must exists!
                self::$languagePath = $languagesFolder . DS . Language::$defaultLanguage;
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

                // TODO handle * in Accept-Header
                // Check if there are any of there matching te existing languages by folder
                array_multisort($weights, SORT_DESC, $languageStrings, SORT_DESC);
                foreach ($languageStrings as $lang) {

                    if(strpos($lang, '-')) {
                        if(file_exists($languagesFolder . DS . $lang)) {
                            self::$languagePath = $languagesFolder . DS . $lang;
                            return;
                        }
                    } else if($lang != '*') {
                        // Language like 'en', 'hu' NOT 'en-us'
                        $folderList = glob($languagesFolder . DS . $lang . '-*');
                        if(!empty($folderList)) {
                            self::$languagePath = $folderList[0];
                            return;
                        }
                    } else {
                        self::$languagePath = $languagesFolder . DS . self::$defaultLanguage;
                    }
                }

                // If language couldn't find set the default one
                self::$languagePath = $languagesFolder . DS . Language::$defaultLanguage;

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
     * @throws FileNotFoundException
     * @throws InvalidParametersException
     */
    public static function translate(string $resourceType, string $key) : string {
        if(array_key_exists($resourceType, self::$translations)) {
            // Translation is already loaded

            if (array_key_exists($key, self::$translations[$resourceType])) {
                return self::$translations[$resourceType][$key];
            } else {
                throw new InvalidParametersException('"' . $key . '" language can not be found in the "' . $resourceType . '.lan" file');
            }
        } else {
            // Translation is not loaded yet: give it a try
            $translationTemp = @parse_ini_file(self::$languagePath . DS . $resourceType . '.lan');
            if($translationTemp) {
                self::$translations[$resourceType] = $translationTemp;
                if(array_key_exists($key, $translationTemp)) {
                    return $translationTemp[$key];
                } else {
                    throw new InvalidParametersException('"' . $key . '" language can not be found in the "' . $resourceType . '.lan" file');
                }
            } else {
                throw new FileNotFoundException('Unable to parse language lan file from "' . self::$languagePath . DS . $resourceType . '.lan"');
            }
        }
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
    public static function translateWithVars(string $resourceType, string $key, array $variables) : string {
        $translated = self::translate($resourceType, $key);

        // TODO match the array size and placeholder count

        $i = 0;
        foreach ($variables as $var) {
            $translated = str_replace('{' . $i . '}', $var, $translated);
            $i++;
        }

        return $translated;
    }

    /**
     * Get the language file path
     *
     * @return string
     */
    public static function getLanguagePath() {
        return self::$languagePath;
    }

}
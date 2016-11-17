<?php

namespace Resty\Test;

use Resty\Utility\Language;
use Resty\Exception\FileNotFoundException;
use Resty\Exception\InvalidParametersException;

class LanguageTest extends \PHPUnit_Framework_TestCase {

    public function testDefaultLanguage() {
        self::assertEquals('en-gb', Language::$defaultLanguage);
    }

    public function testInvalidLanguageFolder() {
        self::expectException(FileNotFoundException::class);
        Language::setLanguagePath(null, 'fake_directory');
    }

    public function testInvalidLanguageSet() {
        Language::setLanguagePath('this_is_not_a_valid_language');
        $path = Language::getLanguagePath();

        self::assertEquals(ROOT . DS . 'languages' . DS . 'en-gb', $path);
    }

    public function testDefaultLanguageSet() {
        Language::setLanguagePath();
        $path = Language::getLanguagePath();

        self::assertEquals(ROOT . DS . 'languages' . DS . 'en-gb', $path);
    }

    public function testSimpleLanguageSet() {
        Language::setLanguagePath('hu-hu');
        $path = Language::getLanguagePath();

        self::assertEquals(ROOT . DS . 'languages' . DS . 'hu-hu', $path);
    }

    public function testComplexLanguageSet() {
        Language::setLanguagePath('en;q=0.8, hu-hu;q=0.7');
        $path = Language::getLanguagePath();

        self::assertEquals(ROOT . DS . 'languages' . DS . 'en-gb', $path);
    }

    public function testComplexLanguageSetWithStar() {
        Language::setLanguagePath('fake;q=0.8, *');
        $path = Language::getLanguagePath();

        self::assertEquals(ROOT . DS . 'languages' . DS . 'en-gb', $path);
    }

    public function testTranslation() {
        Language::setLanguagePath();
        $translated = Language::translate('resty_test', 'simple');

        self::assertEquals('Hello World!', $translated);
    }

    public function testInvalidTranslationGroup() {
        Language::setLanguagePath();

        self::expectException(FileNotFoundException::class);
        Language::translate('invalid_path_to_lan_file', 'simple');
    }

    public function testInvalidTranslationVariableName() {
        Language::setLanguagePath();

        self::expectException(InvalidParametersException::class);
        Language::translate('resty_test', 'invalid_name');
    }

    public function testInvalidAlreadyLoadedTranslationVariableName() {
        Language::setLanguagePath();

        Language::translate('resty_test', 'simple');

        self::expectException(InvalidParametersException::class);
        Language::translate('resty_test', 'invalid_name');
    }

    public function testTranslationWithParams() {
        Language::setLanguagePath();
        $translated = Language::translateWithVars('resty_test', 'param', array('Resty'));

        self::assertEquals('Hello Resty!', $translated);
    }

}

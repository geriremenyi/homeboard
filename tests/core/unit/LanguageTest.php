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

    public function testTranslation() {
        Language::setLanguagePath();
        $translated = Language::translate('resty_test', 'simple');

        self::assertEquals('Hello World!', $translated);
    }

    public function testTranslationWithParams() {
        Language::setLanguagePath();
        $translated = Language::translateWithVars('resty_test', 'param', array('Resty'));

        self::assertEquals('Hello Resty!', $translated);
    }

}

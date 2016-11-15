<?php

namespace Resty\Test;

use Resty\Utility\Language;
use Resty\Exception\HttpException;

class LanguageTest extends \PHPUnit_Framework_TestCase {

    public function testDefaultLanguage() {
        self::assertEquals('en-gb', Language::$defaultLanguage);
    }

    public function testInvalidLanguageFolder() {
        self::expectException(HttpException::class);
        new Language(null, 'fake_directory');
    }

    public function testDefaultLanguageSet() {
        $language = new Language();
        $path = $language->getLanguagePath();

        self::assertEquals(ROOT . DS . 'languages' . DS . 'en-gb', $path);
    }

    public function testSimpleLanguageSet() {
        $language = new Language('hu-hu');
        $path = $language->getLanguagePath();

        self::assertEquals(ROOT . DS . 'languages' . DS . 'hu-hu', $path);
    }

    public function testComplexLanguageSet() {
        $language = new Language('en;q=0.8, hu-hu;q=0.7');
        $path = $language->getLanguagePath();

        self::assertEquals(ROOT . DS . 'languages' . DS . 'en-gb', $path);
    }

}

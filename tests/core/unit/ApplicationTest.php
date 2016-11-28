<?php

namespace Resty\Test;

use Resty\Exception\HttpException;
use Resty\Utility\Application;
use Resty\Utility\Language;

class ApplicationTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Application
     */
    private $app;

    public function setUp() {
        $autoloader = self::createMock(\ComposerAutoloaderInit19cdafe8116f0e131df3f7474bd7d142::class);
        $this->app = new Application($autoloader);
        Language::setLanguagePath('en');
        parent::setUp();
    }

    public function testEmptyAuth() {
        self::expectException(HttpException::class);
        $this->app->checkAuthorization('');
    }

    public function testClientAuth() {
        self::expectException(HttpException::class);
        $this->app->checkAuthorization('Basic clientauth');
    }

    public function testUserAuth() {
        self::expectException(HttpException::class);
        $this->app->checkAuthorization('Bearer clientauth');
    }

}

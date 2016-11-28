<?php

namespace Resty\Test;

use Resty\Utility\Router;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class RouterTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Router
     */
    private $router;

    public function setUp() {
        $request = self::createMock(ServerRequest::class);
        $response = self::createMock(Response::class);
        $autoloader = self::createMock(\ComposerAutoloaderInit19cdafe8116f0e131df3f7474bd7d142::class);

        $this->router = new Router($request, $response, $autoloader);

        parent::setUp();
    }

    public function testQuery() {
        $query = $this->router->getQuery('q=test&filter=(status=confirmed,last_login>=1477773)&fields=username,email,status&sort=-last_login,status');

        self::assertEquals('test', $query['q']);
        self::assertEquals('status=confirmed,last_login>=1477773', $query['filter']);
        self::assertEquals('username,email,status', $query['fields']);
        self::assertEquals('-last_login,status', $query['sort']);
    }

}

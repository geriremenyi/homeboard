<?php

namespace Resty\Test;

use Resty\Auth\Client;
use Resty\Exception\AuthException;
use Resty\Utility\Configuration;

class ClientTest extends \PHPUnit_Framework_TestCase {

    public function testEmptyString() {
        $stub = self::createMock(Configuration::class);

        self::expectException(AuthException::class);
        new Client($stub, '');
    }

    public function testInvalidString() {
        $stub = self::createMock(Configuration::class);

        self::expectException(AuthException::class);
        new Client($stub, 'áíóúűéjnahfjkh');
    }

    public function testInvalidClient() {
        $stub = self::createMock(Configuration::class);
        $stub->method('getConfiguration')->willThrowException(new AuthException());

        self::expectException(AuthException::class);
        new Client($stub, 'aW52YWxpZGNsaWVudDppbnZhbGlkc2VjcmV0');
    }

    public function testValidClient() {
        $stub = self::createMock(Configuration::class);
        $stub->method('getConfiguration')->willReturn('validsecret');

        // Auth string decoded: validclient:validsecret
        $client = new Client($stub, 'dmFsaWRjbGllbnQ6dmFsaWRzZWNyZXQ=');

        self::assertEquals('validclient', $client->getId());
        self::assertEquals('validsecret', $client->getSecret());
    }

}

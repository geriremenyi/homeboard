<?php

namespace Resty\Test;

use Resty\Auth\Client;
use Resty\Exception\AuthException;
use Resty\Utility\Configuration;

class ClientTest extends \PHPUnit_Framework_TestCase {

    public function testEmptyString() {
        $stub = self::createMock(Configuration::class);
        $client = new Client($stub);

        self::expectException(AuthException::class);
        $client->validate('');
    }

    public function testInvalidString() {
        $stub = self::createMock(Configuration::class);
        $client = new Client($stub);

        self::expectException(AuthException::class);
        $client->validate('áíóúűéjnahfjkh');
    }

    public function testInvalidClient() {
        $stub = self::createMock(Configuration::class);
        $stub->method('getConfiguration')->willThrowException(new AuthException('message', 401));
        $client = new Client($stub);

        self::expectException(AuthException::class);
        $client->validate('aW52YWxpZGNsaWVudDppbnZhbGlkc2VjcmV0');
    }

    public function testValidClient() {
        $stub = self::createMock(Configuration::class);
        $stub->method('getConfiguration')->willReturn('validsecret');
        $client = new Client($stub);

        // Auth string decoded: validclient:validsecret
        $client->validate('dmFsaWRjbGllbnQ6dmFsaWRzZWNyZXQ=');

        self::assertEquals('validclient', $client->getId());
        self::assertEquals('validsecret', $client->getSecret());
    }

}

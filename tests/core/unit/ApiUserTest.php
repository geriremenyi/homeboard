<?php

namespace Resty\Test;

use Resty\Auth\ApiUser;
use Resty\Exception\HttpException;
use Resty\Utility\Configuration;
use Resty\Utility\Language;

class ApiUserTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        Language::setLanguagePath('en');
        parent::setUp();
    }

    public function testTokenGeneration() {
        $config = self::createMock(Configuration::class);
        $config->method('getConfiguration')->will($this->returnValueMap(
            [
                ['api', 'token_expiration', '30 minutes'],
                ['api', 'token_secret', 'secret']
            ]
        ));

        $user = new ApiUser($config);
        $user->generateToken(['user_id' => 1]);

        self::assertEquals(1, $user->getToken()->getPayload()->findClaimByName('user_id')->getValue());
    }

    public function testInvalidTokenCalidation() {
        $config = self::createMock(Configuration::class);
        $config->method('getConfiguration')->will($this->returnValueMap(
            [
                ['api', 'token_expiration', '30 minutes'],
                ['api', 'token_secret', 'secret']
            ]
        ));

        $user = new ApiUser($config);

        self::expectException(HttpException::class);
        $user->validate('invalid_token');
    }

    public function testExpiredTokenCalidation() {
        $config = self::createMock(Configuration::class);
        $config->method('getConfiguration')->will($this->returnValueMap(
            [
                ['api', 'token_expiration', '-30 minutes'],
                ['api', 'token_secret', 'secret']
            ]
        ));

        $user = new ApiUser($config);
        $token = $user->generateToken(['user_id' => 1]);

        self::expectException(HttpException::class);
        $user->validate($token);
    }

    public function testTokenValidation() {
        $config = self::createMock(Configuration::class);
        $config->method('getConfiguration')->will($this->returnValueMap(
            [
                ['api', 'token_expiration', '30 minutes'],
                ['api', 'token_secret', 'secret']
            ]
        ));

        $user = new ApiUser($config);
        $token = $user->generateToken(['user_id' => 1]);
        $user->validate($token);

        self::assertEquals(1, $user->getToken()->getPayload()->findClaimByName('user_id')->getValue());
    }

}

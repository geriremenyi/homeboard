<?php

namespace Resty\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_Error_Warning;
use Resty\Utility\Configuration;
use Resty\Exception\HttpException;

/**
 * @group Utility
 * @group Configuration
 */
class ConfigurationTest extends TestCase  {

    public function testSingleton() {
        $instance1 = Configuration::getInstance();
        $instance2 = Configuration::getInstance();

        self::assertSame($instance1, $instance2);
    }

    public function testIniReadException() {
        $configurations = Configuration::getInstance();

        self::expectException(HttpException::class);

        $configurations->loadConfigurations('invalidinifile');
    }

    public function testConfigurationReadSucceed() {
        $configurations = Configuration::getInstance();
        $configurations->loadConfigurations('test');
        $dbConfig = $configurations->getConfiguration('database');

        self::assertArrayHasKey('driver', $dbConfig);
        self::assertArrayHasKey('host', $dbConfig);
        self::assertArrayHasKey('schema', $dbConfig);
        self::assertArrayHasKey('username', $dbConfig);
        self::assertArrayHasKey('password', $dbConfig);
    }

    public function testConfigurationReadException () {
        $configurations = Configuration::getInstance();
        $configurations->loadConfigurations('test');

        self::expectException(HttpException::class);
        $configurations->getConfiguration('invalidtype');

        self::expectException(HttpException::class);
        $configurations->getConfiguration('database', 'invalidname');
    }

}
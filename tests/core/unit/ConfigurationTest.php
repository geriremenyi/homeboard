<?php

namespace Resty\Test;

use PHPUnit\Framework\TestCase;
use Resty\Exception\FileNotFoundException;
use Resty\Exception\InvalidParametersException;
use Resty\Utility\Configuration;

class ConfigurationTest extends TestCase  {

    public function testSingleton() {
        $instance1 = Configuration::getInstance();
        $instance2 = Configuration::getInstance();

        self::assertSame($instance1, $instance2);
    }

    public function testIniReadException() {
        $configurations = Configuration::getInstance();

        self::expectException(FileNotFoundException::class);

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

        self::expectException(InvalidParametersException::class);
        $configurations->getConfiguration('invalidtype');

        self::expectException(InvalidParametersException::class);
        $configurations->getConfiguration('database', 'invalidname');
    }

}
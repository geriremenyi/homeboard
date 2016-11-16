<?php

namespace Resty\Database;

use Resty\Exception\DatabaseException;
use Resty\Utility\Configuration;

/**
 * Database class
 *
 * Database wrapper for protecting from multiple pdo instances.
 *
 * @package    HomeBoard
 * @subpackage Resty\Database
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class Database {


    /**
     * PDO connection object
     *
     * @var \PDO
     */
    private static $pdo;

    /**
     * Database private constructor
     */
    private function __construct(){}

    /**
     * Database private copy
     */
    private function __clone(){}

    /**
     * Restrict only one instance of the PDO class
     *
     * @return \PDO
     * @throws DatabaseException
     */
    public static function getConnection() : \PDO {

        if(self::$pdo == null) {

            // Load database configurations
            $config = Configuration::getInstance()->getConfiguration('database');

            // Define all the database dependent driver settings here
            $driverOptions = array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\''        // MySQL UTF-8 encoding
            );

            try {
                self::$pdo = new \PDO($config['driver'] . ':dbname=' . $config['schema'] . ';host=' . $config['host'], $config['username'], $config['password'], $driverOptions);
                self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $e) {
                throw new DatabaseException('Could not connect to the database with the configuration values given!');
            }
        }

        return static::$pdo;

    }

}
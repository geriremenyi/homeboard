<?php

namespace Resty\Test;

use Resty\Database\{Database, DatabaseCommand, DatabaseTransaction};
use Resty\Utility\Configuration;

class DatabaseTest extends \PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass() {
        Configuration::getInstance()->loadConfigurations();

        $dropCommand = new DatabaseCommand(Database::getConnection(), 'DROP TABLE IF EXISTS resty_test');
        $dropCommand->execute();

        $createCommand = new DatabaseCommand(Database::getConnection(),
            'CREATE TABLE `resty_test` (
              `key_field` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
              `value_field` varchar(50) COLLATE utf8_unicode_ci NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;');
        $createCommand->execute();
    }

    public function testOnlyOneConnection() {
        $conn1 = Database::getConnection();
        $conn2 = Database::getConnection();

        self::assertEquals($conn1,$conn2);
    }

    public function testEmptyTable() {
        $selectCommand = new DatabaseCommand(Database::getConnection(), 'SELECT * FROM resty_test');
        $selectCommand->execute();
        self::assertEquals(0, $selectCommand->rowCount());
    }

    public function testInsertTestTable() {
        $testKey = 'test_key';
        $testValue = 'test_value';

        $insertCommand = new DatabaseCommand(Database::getConnection(), 'INSERT INTO resty_test VALUES(?,?)');
        $insertCommand->execute(array($testKey, $testValue));

        $selectCommand = new DatabaseCommand(Database::getConnection(), 'SELECT * FROM resty_test WHERE key_field=?');
        $selectCommand->execute(array($testKey));

        self::assertEquals(1, $selectCommand->rowCount());

        $result = $selectCommand->fetchAssoc();

        self::assertEquals($testValue, $result['value_field']);
    }

    public function testUpdateTestTable() {
        $testNewValue = 'test_new_value';
        $testKey = 'test_key';

        $updateCommand = new DatabaseCommand(Database::getConnection(), 'UPDATE resty_test SET value_field=? WHERE key_field = ?');
        $updateCommand->execute(array($testNewValue,$testKey));

        $selectCommand = new DatabaseCommand(Database::getConnection(), 'SELECT * FROM resty_test WHERE key_field=?');
        $selectCommand->execute(array($testKey));

        self::assertEquals(1, $selectCommand->rowCount());

        $result = $selectCommand->fetchAssoc();

        self::assertEquals($testNewValue, $result['value_field']);
    }

    public function testDeleteTestTable() {
        $testKey = 'test_key';

        $deleteCommand = new DatabaseCommand(Database::getConnection(), 'DELETE FROM resty_test WHERE key_field = ?');
        $deleteCommand->execute(array($testKey));

        $selectCommand = new DatabaseCommand(Database::getConnection(), 'SELECT * FROM resty_test WHERE key_field = ?');
        $selectCommand->execute(array($testKey));

        self::assertEquals(0, $selectCommand->rowCount());
    }

    public function testTransactionalInsert() {
        $testNewKey = 'insert_transactional_key';
        $testNewValue = 'insert_transactional_value';

        // First transaction start
        $transaction = new DatabaseTransaction(Database::getConnection());
        $transaction->transactionStart();

        $insertCommand = new DatabaseCommand(Database::getConnection(), 'INSERT INTO resty_test VALUES (?,?)');
        $insertCommand->execute(array($testNewKey, $testNewValue));

        $transaction->transactionError();
        // First transaction end

        $selectCommand = new DatabaseCommand(Database::getConnection(), 'SELECT * FROM resty_test WHERE key_field = ?');
        $selectCommand->execute(array($testNewKey));

        // Table should be empty because the transaction has been dropped
        self::assertEquals(0, $selectCommand->rowCount());



        // Second transaction start
        $transaction = new DatabaseTransaction(Database::getConnection());
        $transaction->transactionStart();

        $insertCommand = new DatabaseCommand(Database::getConnection(), 'INSERT INTO resty_test VALUES (?,?)');
        $insertCommand->execute(array($testNewKey, $testNewValue));

        $transaction->transactionEnd();
        // Second transaction end

        $selectCommand = new DatabaseCommand(Database::getConnection(), 'SELECT * FROM resty_test WHERE key_field = ?');
        $selectCommand->execute(array($testNewKey));

        // Insert should be there because the transaction has been committed
        self::assertEquals(1, $selectCommand->rowCount());
    }

    public static function tearDownAfterClass() {
        $dropCommand = new DatabaseCommand(Database::getConnection(), 'DROP TABLE IF EXISTS resty_test');
        $dropCommand->execute();
    }

}

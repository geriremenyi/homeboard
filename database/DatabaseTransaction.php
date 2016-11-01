<?php

namespace HomeBoard\Framework\Database;

/**
 * DatabaseTransaction.php
 *
 * Database transaction for commits, rollbacks
 * and define transaction start and ends
 *
 * @package    HomeBoard
 * @subpackage Framework\Database
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class DatabaseTransaction {

    /**
     * Database connection
     *
     * @var \PDO
     */
    private $pdo;

    /**
     * DatabaseTransaction constructor
     *
     * @param \PDO $pdo - Database connection PDO object
     */
    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Commit current db transaction
     */
    public function commit() {
        $this->pdo->commit();
    }

    /**
     * Rollback current db transaction
     */
    public function rollback() {
        $this->pdo->rollBack();
    }

    /**
     * Start a database transaction with autocommit OFF
     */
    public function transactionStart() {
        $this->pdo->beginTransaction();
    }

    /**
     * End transaction, commit everything and set autocommit ON
     */
    public function transactionEnd() {
        self::commit();
    }

    /**
     * Set transaction error, rollback and set autocommit ON
     */
    public function transactionError() {
        self::rollback();
    }

}
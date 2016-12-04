<?php

namespace Resty\Database;

use Resty\Exception\DatabaseException;
use Resty\Model\Model;

/**
 * Database command
 *
 * Database command wrapper class for SQL
 * commands using the singleton PDO connection
 *
 * @package    Resty
 * @subpackage Database
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class DatabaseCommand {

    /**
     * Prepared PDO statement
     *
     * @var \PDOStatement
     */
    private $stmt;

    /**
     * DatabaseCommand constructor
     *
     * @param \PDO $pdo - Database connection PDO object
     * @param string $query - Query string
     * @throws DatabaseException
     */
    public function __construct(\PDO $pdo, string $query) {
        try {
            if(!($this->stmt = $pdo->prepare($query))) {
                throw new DatabaseException('Could not prepare statement: ' . $query);
            }
        } catch (\PDOException $e) {
            throw new DatabaseException('Could not prepare statement: ' . $query . PHP_EOL . 'Error message: ' . $e->getMessage());
        }

    }

    /**
     * Execute the prepared query
     *
     * @param array $params - Parameters to bind
     * @throws DatabaseException
     */
    public function execute(array $params = null) {

        try {
            if($params == null) {
                $success = $this->stmt->execute();
            } else {
                $success = $this->stmt->execute($params);
            }

            if(!$success) {
                throw new DatabaseException('Could not execute statement: ' . $this->stmt->queryString);
            }
        } catch (\PDOException $e) {
            if(intval($e->getCode()) !== 0) {
                throw new DatabaseException('Could not execute statement: ' . $this->stmt->queryString . PHP_EOL . 'Error message: ' . $e->getMessage(), $e->getCode());
            } else {
                throw new DatabaseException('Could not execute statement: ' . $this->stmt->queryString . PHP_EOL . 'Error message: ' . $e->getMessage());
            }

        }
    }

    /**
     * Fetch the result list's next row into
     * associative array
     *
     * @return array
     */
    public function fetchAssoc() {
        return $this->stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch the whole result list into
     * an array of associative arrays
     *
     * @return array
     */
    public function fetchAssocAll() : array {
        $resultSet = array();

        while($row = self::fetchAssoc()) {
            array_push($resultSet, $row);
        }

        // Just to make sure it is not gonna block anything
        self::closeCursor();

        return $resultSet;
    }

    /**
     * Fetch the result list's next row into
     * the class given by the name
     *
     * @param string $className - Name of the class to fetch the row into
     * @return Model
     */
    public function fetchClass(string $className) {
        $modelAssocArray = $this->stmt->fetch(\PDO::FETCH_ASSOC);

        if($modelAssocArray == false) {
            return false;
        }

        $model = new $className();
        $model->createFromArray($modelAssocArray);

        return $model;
    }


    /**
     * Fetch the whole result list into
     * an array of classes
     *
     * @param string $className - Name of the class to fetch the row into
     * @return array
     */
    public function fetchClassAll(string $className) : array {
        $resultSet = array();

        while($row = self::fetchClass($className)) {
            array_push($resultSet, $row);
        }

        // Just to make sure it is not gonna block anything
        self::closeCursor();

        return $resultSet;
    }

    /**
     * Count the number of the result list's rows
     *
     * @return int
     */
    public function rowCount() : int {
        return $this->stmt->rowCount();
    }

    /**
     * Count the number of the result list's columns
     *
     * @return int
     */
    public function columnCount() : int {
        return $this->stmt->columnCount();
    }

    /**
     * Close cursor, free result list resources
     */
    public function closeCursor() {
        $this->stmt->closeCursor();
    }

}
<?php
/**
 * DatabaseCommand.php
 *
 * Description
 *
 * @author      Gergely Reményi <geri@eclectiqminds.com>
 * @copyright   Eclectiq Minds 2015 all rights reserved
 * @package     PeakWeb
 * @subpackage
 * @since       1.0.0
 */

namespace HomeBoard\Framework\Database;

/**
 * Database command
 *
 * Database command wrapper class for SQL
 * commands using the singleton PDO connection
 *
 * @package    HomeBoard
 * @subpackage Framework\Database
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
     */
    public function __construct(\PDO $pdo, string $query) {
        if(!($this->stmt = $pdo->prepare($query))) {
            // TODO throw 500 exception
        }
    }

    /**
     * Execute the prepared query
     *
     * @param array $params - Parameters to bind
     */
    public function execute(array $params = null) {

        if($params == null) {
            $success = $this->stmt->execute();
        } else {
            $success = $this->stmt->execute($params);
        }

        if(!$success) {
            // TODO throw 500 exception
        }

    }

    /**
     * Fetch the result list's next row into
     * associative array
     *
     * @return array
     */
    public function fetchAssoc() : array {
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
    public function fetchClass(string $className) : Model{
        return $this->stmt->fetch(\PDO::FETCH_CLASS, $className);
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
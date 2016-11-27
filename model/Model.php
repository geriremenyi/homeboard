<?php

namespace Resty\Model;

use Resty\Database\DatabaseAccessLayer;

/**
 * Model class
 *
 * Parent class for all the model classes in
 * the application logic
 *
 * @package    Resty
 * @subpackage Model
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
abstract class Model {

    /**
     * Database access layer object
     *
     * @var DatabaseAccessLayer
     */
    private $dal;

    /**
     * Model constructor.
     */
    public function __construct() {
        $this->dal = new DatabaseAccessLayer($this);
    }

    /**
     * Get database table name of the model
     *
     * @return string
     */
    public function getTableName() : string {
        $this->convertToUnderscore(get_class($this));
    }

    /**
     * Get available model fields
     *
     * @return array
     */
    public function getAvailableFields() : array {
        $fields = [];
        $vars = get_object_vars($this);

        foreach($vars as $var) {
            array_push($fields, $this->convertToUnderscore($var));
        }

        return $fields;
    }

    /**
     * Get searchable fields in model
     *
     * @return array
     */
    public abstract function getSearchableFields() : array;

    /**
     * Convert a camelCase string to under_scored string
     *
     * @param string $camelCase - Camel cased string
     * @return string
     */
    public final function convertToUnderscore(string $camelCase) : string {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $camelCase, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    /**
     * Dal getter
     *
     * @return DatabaseAccessLayer
     */
    public function getDal() : DatabaseAccessLayer{
        return $this->dal;
    }

}
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
     * ID of the model
     *
     * @var int
     */
    protected $id;

    /**
     * Database access layer object
     *
     * @var DatabaseAccessLayer
     */
    protected $dal;

    /**
     * Excluded fields in the response by the projection
     *
     * @var array
     */
    protected $includedFields = [];

    /**
     * Model constructor.
     */
    public function __construct() {
        $this->dal = new DatabaseAccessLayer($this);
    }

    public function createFromArray(array $attributes) {
        foreach($attributes as $key => $value) {
            $normalizedKey = str_replace(' ', '', lcfirst(ucwords(str_replace('_', ' ', $key))));
            if(property_exists($this, $normalizedKey)) {
                $this->$normalizedKey = $value;
            }
        }
    }

    /**
     * Get database table name of the model
     *
     * @return string
     */
    public function getTableName() : string {
        $reflection = new \ReflectionClass($this);
        return $this->convertToUnderscore(rtrim($reflection->getShortName(), 'Model'));
    }

    /**
     * Get available model fields for filter, project, sort in underscore format
     *
     * @return array
     */
    public function getAvailableFields() : array {
        $fields = [];
        $vars = get_object_vars($this);

        foreach($vars as $key => $var) {
            array_push($fields, $this->convertToUnderscore($key));
        }

        // Unset dal field
        if(($key = array_search('dal', $fields)) !== false) {
            unset($fields[$key]);
        }

        // Unset included field
        if(($key = array_search('included_fields', $fields)) !== false) {
            unset($fields[$key]);
        }

        return $fields;
    }

    /**
     * Get fields mapping for model creation in database
     *
     * @return array
     */
    public function getCreateFieldsMapping() : array {
        $fields = [];

        $vars = get_object_vars($this);
        unset($vars['dal']); // Remove the all model dal variable
        unset($vars['id']); // Remove id which is not necessary on create
        unset($vars['includedFields']); // Remove included fields

        foreach($vars as $key => $var) {
            $fields[$this->convertToUnderscore($key)] = $var;
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
     * Set included fields by array
     *
     * @param array $include - Include these fields from the result
     */
    public function setIncludedFields(array $include) {
        $this->includedFields = $include;
    }

    /**
     * Dal getter
     *
     * @return DatabaseAccessLayer
     */
    public function getDal() : DatabaseAccessLayer{
        return $this->dal;
    }

    /**
     * Id getter
     *
     * @return int
     */
    public function getId() : int {
        return $this->id;
    }

    /**
     * Id setter
     *
     * @param int $id
     */
    public function setId(int $id) {
        $this->id = $id;
    }

    public function __toString() {
        $fields = [];

        $vars = get_object_vars($this);
        unset($vars['dal']); // Remove dal
        unset($vars['includedFields']); // Remove included fields

        foreach($vars as $key => $var) {
            $fieldName = $this->convertToUnderscore($key);

            if(count($this->includedFields) == 0 || in_array($fieldName, $this->includedFields)) {
                $fields[$fieldName] = $var;
            }
        }

        return json_encode($fields);
    }

}
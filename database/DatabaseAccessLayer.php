<?php

namespace Resty\Database;

use Resty\Exception\DatabaseException;
use Resty\Model\Model;
use Resty\Utility\QueryParser;

/**
 * Database access layer class
 *
 * This class provides common access to the database
 * entries and converts these to model class types
 *
 * @package    Resty
 * @subpackage Database
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class DatabaseAccessLayer {

    /**
     * @var \PDO
     */
    private $dbConn;

    /**
     * Model under database selection
     *
     * @var Model
     */
    private $model;

    /**
     * DatabaseAccessLayer constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model) {
        $this->dbConn = Database::getConnection();
        $this->model = $model;
    }

    /**
     * Get a specific model object by id
     *
     * @param int $id - Id of the return model
     * @return Model
     * @throws DatabaseException
     */
    public function getOne(int $id) {
        $sql = 'SELECT * FROM ' . $this->model->getTableName() . ' WHERE id=?;';

        $command= new DatabaseCommand($this->dbConn, $sql);
        $command->execute(array($id));

        return $command->fetchClass(get_class($this->model));
    }

    /**
     * Get a list of model objects
     *
     * @param string $searchKey - Q query string
     * @param string $filters - Filter query string
     * @param string $projections - Fields query string
     * @param string $sorting - Sort query string
     * @return array
     */
    public function getList($searchKey = null, $filters = null, $projections = null, $sorting = null) : array {
        // Parse the incoming parameters
        $parser = new QueryParser();
        $parser->parseSearch($this->model->getSearchableFields(), $searchKey);
        $parser->parseFilter($this->model->getAvailableFields(), $filters);
        $parser->parseProjection($this->model->getAvailableFields(), $projections);
        $parser->parseSorting($this->model->getAvailableFields(), $sorting);

        if(($fields = $parser->getFieldList()) == '') {
            $select = 'SELECT *';
        } else {
            $select = 'SELECT' . $fields;
        }

        if(($condition = $parser->getConditionString()) == '') {
            $where = '';
            $whereParams = null;
        } else {
            $where = ' WHERE'. $condition;
            $whereParams = $parser->getConditionParams();
        }

        if(($order = $parser->getSorting()) == '') {
            $orderBy = '';
        } else {
            $orderBy = ' ORDER BY' . $order;
        }

        $sql = $select . ' FROM ' . $this->model->getTableName() . $where . $orderBy;

        $command = new DatabaseCommand($this->dbConn, $sql);
        $command->execute($whereParams);

        $classes = $command->fetchClassAll(get_class($this->model));

        if(($fields = $parser->getFieldList()) != '') {
            $include = explode(',', str_replace(' ', '', $fields));
            foreach ($classes as $class) {
                $class->setIncludedFields($include);
            }
        }


        return $classes;
    }

    /**
     * Create a new instance in the database
     */
    public function create() {
        $insertSql = 'INSERT INTO ' . $this->model->getTableName() . ' (';

        $fields = $this->model->getCreateFieldsMapping();

        $i = 0;
        foreach($fields as $key => $field) {
            if($i != 0) {
                $insertSql .= ', ' . $key;
            } else {
                $insertSql .= $key;
            }
            $i++;
        }

        $insertSql .= ') VALUES (';

        $i = 0;
        $params = [];
        foreach($fields as $field) {
            if($i != 0) {
                $insertSql .= ', ?';
            } else {
                $insertSql .= '?';
            }
            array_push($params, $field);
            $i++;
        }

        $insertSql .= ');';

        $command = new DatabaseCommand($this->dbConn, $insertSql);
        $command->execute($params);
        $this->model->setId($this->dbConn->lastInsertId());
    }

    /**
     * Update and instance in the database
     *
     * @param int $id - Id of the updateable instance
     * @return int
     */
    public function update(int $id) : int {
        // If something looks stupid but works
        // then it is not stupid
        $updateSql = 'UPDATE ' . $this->model->getTableName() . ' SET ';

        $fields = $this->model->getCreateFieldsMapping();

        $i = 0;
        foreach($fields as $key => $field) {
            if($i != 0) {
                $updateSql .= ', ' . $key . '=?';
            } else {
                $updateSql .= $key . '=?';
            }
            $i++;
        }

        $updateSql .= ' WHERE id=?;';

        $params = [];
        foreach($fields as $key => $field) {
            array_push($params, $field);
        }
        array_push($params, $id);

        $command = new DatabaseCommand($this->dbConn, $updateSql);
        $command->execute($params);

        $this->model->setId($id);

        return $command->rowCount();
    }

    /**
     * Delete an instance in the database
     *
     * @param int $id - Id of the deletable instance
     * @return int
     */
    public function delete(int $id) : int{
        $sql = 'DELETE FROM ' . $this->model->getTableName() . ' WHERE id=?';

        $command = new DatabaseCommand($this->dbConn,$sql);
        $command->execute(array($id));

        return $command->rowCount();
    }
}
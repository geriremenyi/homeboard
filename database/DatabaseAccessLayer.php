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
    public function getOne(int $id) : Model {
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
    public function getList(string $searchKey = null, string $filters = null, string $projections = null, string $sorting = null) : array {
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

        if(($condition = $parser->getConditionParams()) == '') {
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

        return $command->fetchClassAll(get_class($this->model));
    }

    /**
     * Create a new instance in the database
     */
    public function create() {

    }

    /**
     * Update and instance in the database
     *
     * @param int $id - Id of the updateable instance
     */
    public function update(int $id) {

    }

    /**
     * Delete an instance in the database
     *
     * @param int $id - Id of the deletable instance
     */
    public function delete(int $id) {
        $sql = 'DELETE FROM ' . $this->model->getTableName() . ' WHERE id=?';

        $command = new DatabaseCommand($this->dbConn,$sql);
        $command->execute(array($id));
    }
}
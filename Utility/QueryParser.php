<?php

namespace Resty\Utility;

use Resty\Exception\QueryException;

/**
 * Parameter parser
 *
 * This class helps to parse the search, filter,
 * projection and order parameters of the quesry
 *
 * @package    Resty
 * @subpackage Utility
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class QueryParser {

    /**
     * Condition string added to where clause
     *
     * @var string
     */
    private $conditionString;

    /**
     * Parameter values for the condition string
     *
     * @var array
     */
    private $conditionParams;

    /**
     * List of the fields to get
     *
     * @var string
     */
    private $fieldList;

    /**
     * Sorting settings
     *
     * @var string
     */
    private $sorting;

    /**
     * QueryParser constructor.
     */
    public function __construct() {
        $this->conditionString = '';
        $this->conditionParams = [];
        $this->fieldList = '';
        $this->sorting = '';
    }

    /**
     * Parse search key and expand the sql condition with it
     *
     * @param array $searchableFields - Searchable fields in the model
     * @param string|null $searchKey - Incoming search key from the request
     */
    public function parseSearch(array $searchableFields, $searchKey) {
        if ($searchKey != null) {

            // Check if there is something in the condition already
            if($this->conditionString == '') {
                $this->conditionString .= ' (';
            } else {
                $this->conditionString .= ' AND (';
            }

            // First search
            $this->conditionString .= array_shift($searchableFields) . ' LIKE ?';
            array_push($this->conditionParams, '%' . $searchKey . '%');

            // Others
            foreach ($searchableFields as $field) {
                $this->conditionString .= ' OR ' . $field . ' LIKE ?';
                array_push($this->conditionParams, '%' . $searchKey . '%');
            }
            $this->conditionString .= ')';
        }
    }

    /**
     * Parse filters and expand the sql condition with it
     *
     * @param array $availableFields - Available fields in the model
     * @param string|null $filters - Filters string
     * @throws QueryException
     */
    public function parseFilter(array $availableFields, $filters) {
        if($filters != null) {

            // Check if there is something in the condition already
            if($this->conditionString == '') {
                $this->conditionString .= ' (';
            } else {
                $this->conditionString .= ' AND (';
            }

            $filtersArray = explode(',', $filters);
            foreach ($filtersArray as $key => $filter) {

                // Check the equation
                if( strpos($filter, '<=')) {
                    $filterDetails = explode('<=', $filter);
                    $equation = '<=';
                } elseif( strpos($filter, '>=')) {
                    $filterDetails = explode('>=', $filter);
                    $equation = '>=';
                } elseif(strpos($filter, '<>')) {
                    $filterDetails = explode('<>', $filter);
                    $equation = '<>';
                } elseif( strpos($filter, '>')) {
                    $filterDetails = explode('>', $filter);
                    $equation = '>';
                }  elseif( strpos($filter, '<')) {
                    $filterDetails = explode('<', $filter);
                    $equation = '<';
                } elseif(strpos($filter, '=')) {
                    $filterDetails = explode('=', $filter);
                    $equation = '=';
                } else {
                    $error = array();
                    $error['code'] = 400;
                    $error['message'] = Language::translateWithVars('resty_error', 'invalid_query_filter', [$filter]);
                    $error['errors'] = array();

                    throw new QueryException(json_encode($error), 400);
                }

                // Check if it contains more then on equation in one statement
                if(count($filterDetails) > 2) {
                    $error = array();
                    $error['code'] = 400;
                    $error['message'] = Language::translateWithVars('resty_error', 'invalid_query_filter', [$filter]);
                    $error['errors'] = array();

                    throw new QueryException(json_encode($error), 400);
                } elseif(!in_array($filterDetails[0], $availableFields)) {
                    // Check if the filter filed is correct
                    $error = array();
                    $error['code'] = 400;
                    $error['message'] = Language::translateWithVars('resty_error', 'invalid_query_field', [$filter]);
                    $error['errors'] = array();

                    throw new QueryException(json_encode($error), 400);
                }

                // Is it the first filter
                if($key == 0) {
                    $this->conditionString .= $filterDetails[0] . $equation . '?';
                    array_push($this->conditionParams, $filterDetails[1]);
                } else {
                    $this->conditionString .= ' AND ' . $filterDetails[0] . $equation . '?';
                    array_push($this->conditionParams, $filterDetails[1]);
                }

            }
            $this->conditionString .= ')';
        }
    }

    /**
     * Parse projection string and include it in the selected fields
     *
     * @param array $availableFields - Available fields in the model
     * @param string|null $projections - Projections string
     * @throws QueryException
     */
    public function parseProjection(array $availableFields, $projections) {
        if($projections != null) {

            $projectionArray = explode(',', $projections);

            $projection = array_shift($projectionArray);
            if(in_array($projection, $availableFields)) {
                $this->fieldList .= ' ' . $projection;
            } else {
                $error = array();
                $error['code'] = 400;
                $error['message'] = Language::translateWithVars('resty_error', 'invalid_query_field', [$projection]);
                $error['errors'] = array();

                throw new QueryException(json_encode($error), 400);
            }
            foreach ($projectionArray as $projection) {
                if(in_array($projection, $availableFields)) {
                    $this->fieldList .= ', ' . $projection;
                } else {
                    $error = array();
                    $error['code'] = 400;
                    $error['message'] = Language::translateWithVars('resty_error', 'invalid_query_field', [$projection]);
                    $error['errors'] = array();

                    throw new QueryException(json_encode($error), 400);
                }
            }

        }
    }

    /**
     * Parse sorting string and include it in the order by condition
     *
     * @param array $availableFields - Available fields in the model
     * @param string|null $sorting - Sorting string
     * @throws QueryException
     */
    public function parseSorting(array $availableFields, $sorting) {
        if($sorting != null) {

            $sortingArray = explode(',', $sorting);

            foreach ($sortingArray as $key => $sort) {

                // Check if it is ASC or DESC
                $prefix = substr($sort, 0, 1);
                if($prefix == '-') {
                    $direction = 'DESC';
                    $sort = ltrim($sort, '-');
                } else {
                    $direction = 'ASC';
                }


                if(in_array($sort, $availableFields)) {
                    if($key == 0) {
                        $this->sorting .= ' ' . $sort . ' ' . $direction;
                    } else {
                        $this->sorting .= ', ' . $sort . ' ' . $direction;
                    }
                } else {
                    $error = array();
                    $error['code'] = 400;
                    $error['message'] = Language::translateWithVars('resty_error', 'invalid_query_field', [$sort]);
                    $error['errors'] = array();

                    throw new QueryException(json_encode($error), 400);
                }
            }

        }
    }

    /**
     * Get condition string
     *
     * @return string
     */
    public function getConditionString(): string {
        return $this->conditionString;
    }

    /**
     * Get condition parameters
     *
     * @return array
     */
    public function getConditionParams(): array {
        return $this->conditionParams;
    }

    /**
     * Get field list string
     *
     * @return string
     */
    public function getFieldList(): string {
        return $this->fieldList;
    }

    /**
     * Get sorting string
     *
     * @return string
     */
    public function getSorting(): string {
        return $this->sorting;
    }

}
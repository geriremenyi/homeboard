<?php

namespace App\v1\Controller;

use App\v1\Model\FlatsModel;
use Resty\Controller\Controller;
use Resty\Exception\AppException;
use Resty\Utility\Application;
use Resty\Utility\Language;

/**
 * Flats Controller
 *
 * Controller for the flats endpoint
 *
 * @package    App
 * @subpackage v1\Controller
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class FlatsController extends Controller {

    /**
     * Create flat
     *
     * @param $id - Given id, never used
     * @throws AppException
     */
    public function post($id) {
        // No id can be specified in this request
        if($id != null) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'id_given_error');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 400);
        }

        // No chain call
        if ($this->chain != null) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'invalid_chain');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 400);
        }

        // No query params
        if ($this->query != null) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'no_queries_allowed');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 400);
        }

        // Check authorization
        $apiUser = Application::$user;
        if($apiUser == null) {
            $error = [];
            $error['code'] = 403;
            $error['message'] = Language::translate('resty_error', 'unauthorized_access');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 403);
        }

        $body = json_decode($this->request->getBody()->getContents(), true);

        // Predefined variables
        $wrongRequest = false;
        $errorDetails = [];
        $address = '';
        $maxTenants = 1; // At least one otherwise makes no sense

        // Address check
        if (array_key_exists('address', $body)) {
            $address = $body['address'];
            unset($body['address']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['address']));
        }

        // Max tenants check
        if (array_key_exists('max_tenants', $body)) {
            $maxTenants = $body['max_tenants'];
            unset($body['max_tenants']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['max_tenants']));
        }

        $createArray = [
            'address' => $address,
            'max_tenants' => $maxTenants,
            "owner_id" => $apiUser->getToken()->getPayload()->findClaimByName('user_id')->getValue()
        ];

        // Unknown attributes
        if(count($body) != 0) {
            $wrongRequest = true;
            foreach($body as $attribute => $value) {
                array_push($errorDetails, Language::translateWithVars('resty_error', 'no_such_attribute', [$attribute]));
            }
        }

        // In case of wrong request
        if ($wrongRequest) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'invalid_request_body');
            $error['errors'] = $errorDetails;

            throw new AppException(json_encode($error), 400);
        }

        // Create flat
        $flat = new FlatsModel();
        $flat->createFromArray($createArray);
        $flat->getDal()->create();

        $this->response->getBody()->write($flat);
    }

    /**
     * Get one or more flats
     *
     * @param $id - Id of the desired flat
     * @throws AppException
     */
    public function get($id) {
        // No chain call
        if ($this->chain != null) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'invalid_chain');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 400);
        }

        // Check authentication
        $apiUser = Application::$user;
        if($apiUser == null) {
            $error = [];
            $error['code'] = 403;
            $error['message'] = Language::translate('resty_error', 'unauthorized_access');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 403);
        }

        if($id == null) {

            $model = new FlatsModel();
            if($this->query != null) {
                $q = null;
                $filter = null;
                $fields = null;
                $sort = null;

                if(array_key_exists('q', $this->query)) $q = $this->query['q'];
                if(array_key_exists('filter', $this->query)) $filter = $this->query['filter'];
                if(array_key_exists('fields', $this->query)) $fields = $this->query['fields'];
                if(array_key_exists('sort', $this->query)) $sort = $this->query['sort'];

                $results = $model->getDal()->getList($q, $filter, $fields, $sort);
            } else {
                $results = $model->getDal()->getList();
            }

            $responseBody = '[';
            $result = array_shift($results);
            $responseBody .= $result;
            foreach($results as $result) {
                $responseBody .= ',' . $result;
            }
            $responseBody .= ']';

            $this->response->getBody()->write($responseBody);

        } else {
            // No query params
            if ($this->query != null) {
                $error = [];
                $error['code'] = 400;
                $error['message'] = Language::translate('resty_error', 'no_queries_allowed');
                $error['errors'] = [];

                throw new AppException(json_encode($error), 400);
            }

            $flat = new FlatsModel();
            $flat = $flat->getDal()->getOne($id);
            if ($flat) {
                $this->response->getBody()->write($flat);
            } else {
                $error = [];
                $error['code'] = 404;
                $error['message'] = Language::translate('resty_error', 'resource_not_found');
                $error['errors'] = [];

                throw new AppException(json_encode($error), 404);
            }
        }

    }

    /**
     * Update a flat
     *
     * @param $id - Given id, never used
     * @throws AppException
     */
    public function patch($id) {
        // No id can be specified in this request
        if($id == null) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'id_not_given_error');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 400);
        }

        // No chain call
        if ($this->chain != null) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'invalid_chain');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 400);
        }

        // No query params
        if ($this->query != null) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'no_queries_allowed');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 400);
        }

        // Check authentication
        $apiUser = Application::$user;
        $model = new FlatsModel();
        $model = $model->getDal()->getOne($id);
        if($apiUser == null) {
            $error = [];
            $error['code'] = 403;
            $error['message'] = Language::translate('resty_error', 'unauthorized_access');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 403);
        } else {
            if(!$model) {
                $error = [];
                $error['code'] = 404;
                $error['message'] = Language::translate('resty_error', 'resource_not_found');
                $error['errors'] = [];

                throw new AppException(json_encode($error), 404);
            } elseif($apiUser->getToken()->getPayload()->findClaimByName('user_id')->getValue() != $model->getOwnerId() && $apiUser->getToken()->getPayload()->findClaimByName('user_role')->getValue() != 'admin') {
                $error = [];
                $error['code'] = 403;
                $error['message'] = Language::translate('resty_error', 'unauthorized_access');
                $error['errors'] = [];

                throw new AppException(json_encode($error), 403);
            }
        }

        $body = json_decode($this->request->getBody()->getContents(), true);

        // Predefined variables
        $wrongRequest = false;
        $errorDetails = [];
        $address = '';
        $maxTenants = 1; // At least one otherwise makes no sense

        // Address check
        if (array_key_exists('address', $body)) {
            $address = $body['address'];
            unset($body['address']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['address']));
        }

        // Max tenants check
        if (array_key_exists('max_tenants', $body)) {
            $maxTenants = $body['max_tenants'];
            unset($body['max_tenants']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['max_tenants']));
        }

        $createArray = [
            'address' => $address,
            'max_tenants' => $maxTenants,
            "owner_id" => $model->getOwnerId()
        ];

        // Unknown attributes
        if(count($body) != 0) {
            $wrongRequest = true;
            foreach($body as $attribute => $value) {
                array_push($errorDetails, Language::translateWithVars('resty_error', 'no_such_attribute', [$attribute]));
            }
        }

        // In case of wrong request
        if ($wrongRequest) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'invalid_request_body');
            $error['errors'] = $errorDetails;

            throw new AppException(json_encode($error), 400);
        }

        // Create flat
        $model->createFromArray($createArray);
        $model->getDal()->update($id);

        $this->response->getBody()->write($model);
    }

    /**
     * Delete a flat
     *
     * @param $id - Delete this flat by id
     * @throws AppException
     */
    public function delete($id) {
        if($id == null) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'id_not_given_error');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 400);
        }

        // No chain call
        if ($this->chain != null) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'invalid_chain');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 400);
        }

        // No query params
        if ($this->query != null) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'no_queries_allowed');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 400);
        }

        $apiUser = Application::$user;

        if($apiUser != null) {

            if($apiUser->getToken()->getPayload()->findClaimByName('user_role')->getValue() == 'admin') {

                $flat = new FlatsModel();
                if(!$flat->getDal()->delete($id)) {
                    $error = [];
                    $error['code'] = 404;
                    $error['message'] = Language::translate('resty_error', 'resource_not_found');
                    $error['errors'] = [];

                    throw new AppException(json_encode($error), 404);
                }
            } else {
                $flat = new FlatsModel();
                if(!($flat = $flat->getDal()->getOne($id))) {
                    $error = [];
                    $error['code'] = 404;
                    $error['message'] = Language::translate('resty_error', 'resource_not_found');
                    $error['errors'] = [];

                    throw new AppException(json_encode($error), 404);
                }

                if($apiUser->getToken()->getPayload()->findClaimByName('user_id')->getValue() == $flat->getOwnerId()) {
                    $flat->getDal()->delete($id);
                } else {
                    $error = [];
                    $error['code'] = 403;
                    $error['message'] = Language::translate('resty_error', 'unauthorized_access');
                    $error['errors'] = [];

                    throw new AppException(json_encode($error), 403);
                }
            }

        } else {
            $error = [];
            $error['code'] = 403;
            $error['message'] = Language::translate('resty_error', 'unauthorized_access');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 403);
        }
    }

}
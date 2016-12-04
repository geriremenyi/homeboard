<?php

namespace App\v1\Controller;

use App\v1\Model\FlatsModel;
use App\v1\Model\UsersModel;
use Resty\Controller\Controller;
use Resty\Database\Database;
use Resty\Database\DatabaseCommand;
use Resty\Exception\AppException;
use Resty\Exception\DatabaseException;
use Resty\Utility\Application;
use Resty\Utility\Language;

/**
 * User Controller
 *
 * Controller for the users endpoint
 *
 * @package    App
 * @subpackage v1\Controller
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class UsersController extends Controller {

    /**
     * Create user
     *
     * @param $id - Given id, never used
     * @throws AppException
     * @throws DatabaseException
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


        $body = json_decode($this->request->getBody()->getContents(), true);

        // Predefined variables
        $wrongRequest = false;
        $errorDetails = [];
        $username = '';
        $password = '';
        $salt = hash('sha512', uniqid(openssl_random_pseudo_bytes(16), true));
        $role = '';
        $firstName = '';
        $middleName = null;
        $lastName = '';
        $livesFlatId = null; // Should be empty on init. There is no selected flat or not even an own one

        // Username check
        if (array_key_exists('username', $body)) {
            $username = $body['username'];
            unset($body['username']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['username']));
        }

        // Password check
        if (array_key_exists('password', $body)) {
            $password = hash('sha512', $body['password'] . $salt);
            unset($body['password']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['password']));
        }

        // Role check
        if (array_key_exists('role', $body)) {
            if($body['role'] == 'normal') {
                $role = $body['role'];
            } elseif ($body['role'] == 'admin') {
                // Check if the user has access to create an admin user
                $apiUser = Application::$user;
                $access = false;
                if($apiUser != null) {
                    $access = $apiUser->getToken()->getPayload()->findClaimByName('user_role') == 'admin' ? true : false;
                }

                if($access) {
                    $role = $body['role'];
                } else {
                    $error = [];
                    $error['code'] = 403;
                    $error['message'] = Language::translate('resty_error', 'unauthorized_admin_creation');
                    $error['errors'] = [];

                    throw new AppException(json_encode($error), 403);
                }
            } else {
                $wrongRequest = true;
                array_push($errorDetails, Language::translateWithVars('resty_error', 'wrong_attribute_value', [$body['role'], 'role']));
            }
            unset($body['role']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['role']));
        }

        // First name check
        if (array_key_exists('first_name', $body)) {
            $firstName = $body['first_name'];
            unset($body['first_name']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['first_name']));
        }

        // First name check
        if (array_key_exists('last_name', $body)) {
            $lastName = $body['last_name'];
            unset($body['last_name']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['last_name']));
        }

        // Middle name (optional)
        if (array_key_exists('middle_name', $body)) {
            $middleName = $body['middle_name'];
            unset($body['middle_name']);
        }

        // Unknown attributes
        if(count($body) != 0) {
            $wrongRequest = true;
            foreach($body as $attribute => $value) {
                array_push($errorDetails, Language::translateWithVars('resty_error', 'no_such_attribute', [$attribute]));
            }
        }

        // Create user model
        $user = new UsersModel();
        $user->create($username, $password, $salt, $role, $firstName, $middleName, $lastName, $livesFlatId);

        if(!$wrongRequest) {
            try {
                $user->getDal()->create();
            } catch (DatabaseException $e) {
                if($e->getCode() == 23000) {
                    // Username uniqueness violation
                    $wrongRequest = true;
                    array_push($errorDetails, Language::translateWithVars('resty_error', 'already_existing_user', [$username]));
                } else {
                    throw $e;
                }
            }
        }

        if ($wrongRequest) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'invalid_request_body');
            $error['errors'] = $errorDetails;

            throw new AppException(json_encode($error), 400);
        } else {
            $this->response->getBody()->write($user);
        }
    }

    /**
     * Get users/user
     *
     * @param $id - Id of the desired user
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

        $apiUser = Application::$user;

        if($id == null) {
            if($apiUser != null) {
                if($apiUser->getToken()->getPayload()->findClaimByName('user_role')->getValue() == 'admin') {
                    $model = new UsersModel();
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
                    $error = [];
                    $error['code'] = 403;
                    $error['message'] = Language::translate('resty_error', 'unauthorized_access');
                    $error['errors'] = [];

                    throw new AppException(json_encode($error), 403);
                }
            } else {
                $error = [];
                $error['code'] = 403;
                $error['message'] = Language::translate('resty_error', 'unauthorized_access');
                $error['errors'] = [];

                throw new AppException(json_encode($error), 403);
            }
        } else {

            // No query params
            if ($this->query != null) {
                $error = [];
                $error['code'] = 400;
                $error['message'] = Language::translate('resty_error', 'no_queries_allowed');
                $error['errors'] = [];

                throw new AppException(json_encode($error), 400);
            }

            if($apiUser != null) {

                if($apiUser->getToken()->getPayload()->findClaimByName('user_role')->getValue() == 'admin') {

                    $user = new UsersModel();
                    $returnUser = $user->getDal()->getOne($id);
                    if($returnUser) {
                        $this->response->getBody()->write($returnUser);
                    } else {
                        $error = [];
                        $error['code'] = 404;
                        $error['message'] = Language::translate('resty_error', 'resource_not_found');
                        $error['errors'] = [];

                        throw new AppException(json_encode($error), 404);
                    }

                } else {
                    if($apiUser->getToken()->getPayload()->findClaimByName('user_id')->getValue() == $id) {
                        $user = new UsersModel();
                        $this->response->getBody()->write($user->getDal()->getOne($id));
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

    /**
     * Update user
     *
     * @param $id - Update this user by id
     * @throws AppException
     * @throws DatabaseException
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
        if($apiUser == null) {
            $error = [];
            $error['code'] = 403;
            $error['message'] = Language::translate('resty_error', 'unauthorized_access');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 403);
        } else {
            if($apiUser->getToken()->getPayload()->findClaimByName('user_id')->getValue() != $id && $apiUser->getToken()->getPayload()->findClaimByName('user_role')->getValue() != 'admin') {
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
        $username = '';
        $password = '';
        $salt = hash('sha512', uniqid(openssl_random_pseudo_bytes(16), true));
        $role = '';
        $firstName = '';
        $middleName = null;
        $lastName = '';
        $livesFlatId = null;

        // Username check
        if (array_key_exists('username', $body)) {
            $username = $body['username'];
            unset($body['username']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['username']));
        }

        // Password check
        if (array_key_exists('password', $body)) {
            $password = hash('sha512', $body['password'] . $salt);
            unset($body['password']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['password']));
        }

        // Role check
        if (array_key_exists('role', $body)) {
            if($body['role'] == 'normal') {
                $role = $body['role'];
            } elseif ($body['role'] == 'admin') {
                // Check if the user has access to change the user to an admin
                if($apiUser->getToken()->getPayload()->findClaimByName('user_role')->getValue() == 'admin') {
                    $role = $body['role'];
                } else {
                    $error = [];
                    $error['code'] = 403;
                    $error['message'] = Language::translate('resty_error', 'unauthorized_admin_creation');
                    $error['errors'] = [];

                    throw new AppException(json_encode($error), 403);
                }
            } else {
                $wrongRequest = true;
                array_push($errorDetails, Language::translateWithVars('resty_error', 'wrong_attribute_value', [$body['role'], 'role']));
            }
            unset($body['role']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['role']));
        }

        // First name check
        if (array_key_exists('first_name', $body)) {
            $firstName = $body['first_name'];
            unset($body['first_name']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['first_name']));
        }

        // First name check
        if (array_key_exists('last_name', $body)) {
            $lastName = $body['last_name'];
            unset($body['last_name']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['last_name']));
        }

        // Middle name (optional)
        if (array_key_exists('middle_name', $body)) {
            $middleName = $body['middle_name'];
            unset($body['middle_name']);
        }

        // Lives in flat (optional only on move in)
        if (array_key_exists('lives_flat_id', $body)) {
            // Check that the flat exists
            $flat = new FlatsModel();
            if(!($flat = $flat->getDal()->getOne($body['lives_flat_id']))) {
                $wrongRequest = true;
                array_push($errorDetails, Language::translateWithVars('resty_error', 'wrong_attribute_value', [$body['lives_flat_id'], 'lives_flat_id']));
                unset($body['lives_flat_id']);
            } else {
                $maxTenants = $flat->getMaxTenants();

                $command = new DatabaseCommand(Database::getConnection(), 'SELECT count(*) AS c FROM users WHERE lives_flat_id=?');
                $command->execute([$flat->getId()]);
                $c = $command->fetchAssoc()['c'];

                if($c >= $maxTenants) {
                    $wrongRequest = true;
                    array_push($errorDetails, Language::translateWithVars('resty_error', 'too_many_tenants', [$body['lives_flat_id']]));
                } else {
                    $livesFlatId = $body['lives_flat_id'];
                }

                unset($body['lives_flat_id']);
            }
        }

        // Unknown attributes
        if(count($body) != 0) {
            $wrongRequest = true;
            foreach($body as $attribute => $value) {
                array_push($errorDetails, Language::translateWithVars('resty_error', 'no_such_attribute', [$attribute]));
            }
        }

        // Create user model
        $user = new UsersModel();
        $user->create($username, $password, $salt, $role, $firstName, $middleName, $lastName, $livesFlatId);

        try {
            if(!$user->getDal()->update($id)) {
                $error = [];
                $error['code'] = 404;
                $error['message'] = Language::translate('resty_error', 'resource_not_found');
                $error['errors'] = [];

                throw new AppException(json_encode($error), 404);
            }
        } catch (DatabaseException $e) {
            if($e->getCode() == 23000) {
                // Username uniqueness violation
                $wrongRequest = true;
                array_push($errorDetails, Language::translateWithVars('resty_error', 'already_existing_user', [$username]));
            } else {
                throw $e;
            }
        }

        if ($wrongRequest) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'invalid_request_body');
            $error['errors'] = $errorDetails;

            throw new AppException(json_encode($error), 400);
        } else {
            $this->response->getBody()->write($user);
        }
    }

    /**
     * Delete user
     *
     * @param $id - Delete this user by id
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

                $user = new UsersModel();
                if(!$user->getDal()->delete($id)) {
                    $error = [];
                    $error['code'] = 404;
                    $error['message'] = Language::translate('resty_error', 'resource_not_found');
                    $error['errors'] = [];

                    throw new AppException(json_encode($error), 404);
                }
            } else {
                if($apiUser->getToken()->getPayload()->findClaimByName('user_id')->getValue() == $id) {
                    $user = new UsersModel();
                    $user->getDal()->delete($id);
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
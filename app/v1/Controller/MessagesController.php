<?php

namespace App\v1\Controller;
use App\v1\Model\MessagesModel;
use App\v1\Model\UsersModel;
use Resty\Controller\Controller;
use Resty\Database\Database;
use Resty\Database\DatabaseCommand;
use Resty\Exception\AppException;
use Resty\Utility\Application;
use Resty\Utility\Language;

/**
 * Messages Controller
 *
 * Controller for the messages endpoint
 *
 * @package    App
 * @subpackage v1\Controller
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class MessagesController extends Controller {


    /**
     * Create a message
     *
     * @param $id - id of the resource
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
        $fromId = $apiUser->getToken()->getPayload()->findClaimByName('user_id')->getValue();
        $toId = '';
        $message = '';

        // Address check
        if (array_key_exists('to_id', $body)) {
            $toId = $body['to_id'];
            unset($body['to_id']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['to_id']));
        }

        // Max tenants check
        if (array_key_exists('message', $body)) {
            $message = $body['message'];
            unset($body['message']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', ['message']));
        }

        $createArray = [
            'from_id' => $fromId,
            'to_id' => $toId,
            "message" => $message
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

        // Check if the user has access to write a message to the other user
        $user = new UsersModel();
        $user = $user->getDal()->getOne($fromId);
        if($user) {
            if(($flatId = $user->getLivesFlatId()) != null) {
                $sqlFlatMate =
                    'SELECT count(*) AS c
            FROM users
            WHERE 
              id=?
              AND lives_flat_id IS NOT NULL 
              AND lives_flat_id=(SELECT lives_flat_id FROM users WHERE id=?)';
                $commandFlatMate = new DatabaseCommand(Database::getConnection(), $sqlFlatMate);
                $commandFlatMate->execute([$toId, $fromId]);
                $cFlatMate = $commandFlatMate->fetchAssoc()['c'];

                $sqlLandlord =
                    'SELECT count(*) AS c 
                    FROM users
                    WHERE 
                      id=?
                      AND (
                        id=(SELECT owner_id FROM flats WHERE id=?)
                        OR id IN (SELECT lives_flat_id FROM users WHERE lives_flat_id=?)
                       )';
                $commandLandlord = new DatabaseCommand(Database::getConnection(), $sqlLandlord);
                $commandLandlord->execute([$toId, $flatId, $flatId]);
                $cLandlord = $commandLandlord->fetchAssoc()['c'];

                if(!($cFlatMate > 0 || $cLandlord > 0)) {
                    $error = [];
                    $error['code'] = 403;
                    $error['message'] = Language::translate('resty_error', 'unauthorized_access');
                    $error['errors'] = [];

                    throw new AppException(json_encode($error), 403);
                }

                // Create flat
                $flat = new MessagesModel();
                $flat->createFromArray($createArray);
                $flat->getDal()->create();

                $this->response->getBody()->write($flat);
            } else {
                // Empty flat id of the user -> homeless
                $error = [];
                $error['code'] = 400;
                $error['message'] = Language::translate('resty_error', 'no_flat_found');
                $error['errors'] = $errorDetails;

                throw new AppException(json_encode($error), 400);
            }
        } else {
            // User can not be found
            $error = [];
            $error['code'] = 404;
            $error['message'] = Language::translate('resty_error', 'resource_not_found');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 404);
        }
    }

    /**
     * Get messages/message
     *
     * @param $id
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
            $model = new MessagesModel();
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

            $userId = $apiUser->getToken()->getPayload()->findClaimByName('user_id')->getValue();
            $responseBody = '[';
            $result = array_shift($results);
            if($result->getFromId() == $userId || $result->getToId() == $userId ) {
                $responseBody .= $result;
            }
            foreach($results as $result) {
                // TODO push it to database level
                if($result->getFromId() == $userId || $result->getToId() == $userId ) {
                    $responseBody .= ',' . $result;
                }
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

            $message = new MessagesModel();
            $message = $message->getDal()->getOne($id);
            if ($message) {
                $userId = $apiUser->getToken()->getPayload()->findClaimByName('user_id')->getValue();
                if($message->getFromId() != $userId && $message->getToId() != $userId) {
                    $error = [];
                    $error['code'] = 403;
                    $error['message'] = Language::translate('resty_error', 'unauthorized_access');
                    $error['errors'] = [];

                    throw new AppException(json_encode($error), 403);
                } else {
                    $this->response->getBody()->write($message);
                }
            } else {
                $error = [];
                $error['code'] = 404;
                $error['message'] = Language::translate('resty_error', 'resource_not_found');
                $error['errors'] = [];

                throw new AppException(json_encode($error), 404);
            }
        }

    }

}
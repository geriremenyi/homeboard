<?php

namespace App\v1\Controller;

use App\v1\Model\UsersModel;
use Resty\Auth\ApiUser;
use Resty\Controller\Controller;
use Resty\Exception\AppException;
use Resty\Utility\Configuration;
use Resty\Utility\Language;
use Zend\Diactoros\ {
    ServerRequest, Response
};

/**
 * TokenController
 *
 * Controller for the token generation and user
 * login handling
 *
 * @package    App
 * @subpackage v1\Controller
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class TokenController extends Controller {

    public function __construct(ServerRequest $request, Response $response, $query, $chain) {
        parent::__construct($request, $response, $query, $chain);
    }

    /**
     * Create token
     *
     * @param $id - Given id, never used
     * @throws AppException
     */
    public function post($id) {
        // No id can be specified in this request
        if($id != null) {
            $error = [];
            $error['code'] = 404;
            $error['message'] = Language::translate('resty_error', 'id_given_error');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 400);
        }

        // No chain call
        if ($this->chain != null) {
            $error = [];
            $error['code'] = 404;
            $error['message'] = Language::translate('resty_error', 'invalid_chain');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 400);
        }

        // No query params
        if ($this->query != null) {
            $error = [];
            $error['code'] = 404;
            $error['message'] = Language::translate('resty_error', 'no_queries_allowed');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 400);
        }

        // Load body into array
        $body = json_decode($this->request->getBody()->getContents(), true);

        // Predefined variables
        $wrongRequest = false;
        $errorDetails = [];
        $username = '';
        $password = '';

        // Username check
        if (array_key_exists('username', $body)) {
            $username = $body['username'];
            unset($body['username']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', 'username'));
        }

        // Password check
        if (array_key_exists('password', $body)) {
            $password = $body['password'];
            unset($body['password']);
        } else {
            $wrongRequest = true;
            array_push($errorDetails, Language::translateWithVars('resty_error', 'missing_attribute', 'password'));
        }

        // Unknown attributes
        if(count($body) != 0) {
            $wrongRequest = true;
            foreach($body as $attribute => $value) {
                array_push($errorDetails, Language::translateWithVars('resty_error', 'no_such_attribute', $attribute));
            }
        }

        if ($wrongRequest) {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'invalid_request_body');
            $error['errors'] = $errorDetails;

            throw new AppException(json_encode($error), 400);
        }

        $user = new UsersModel();

        if($user->login($username, $password)) {
            $apiUser = new ApiUser(Configuration::getInstance());
            $claims = $user->getClaims();

            $responseBody = [
                'token' => $apiUser->generateToken($claims)
            ];

            $this->response->getBody()->write(json_encode($responseBody));
        } else {
            $error = [];
            $error['code'] = 400;
            $error['message'] = Language::translate('resty_error', 'login_error');
            $error['errors'] = [];

            throw new AppException(json_encode($error), 400);
        }
    }

}
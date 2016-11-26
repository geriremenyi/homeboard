<?php

namespace Resty\Utility;

use Zend\Diactoros\{
    Response, ServerRequest, Uri
};

/**
 * Router
 *
 * Router class for the incoming request
 *
 * @package    Resty
 * @subpackage Utility
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class Router {

    /**
     * Incoming request object
     *
     * @var ServerRequest
     */
    private $request;

    /**
     * Outgoing server response
     *
     * @var Response
     */
    private $response;

    /**
     * Router constructor
     *
     * @param ServerRequest $request - Request object coming from the client
     * @param Response $response - Response object to send back to client
     */
    public function __construct(ServerRequest $request, Response $response) {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @param Uri $uri
     */
    public function route(Uri $uri) {

        // URI pattern: <api_version>/<resource_type>/<resource_id>/<resource_type>/<resource_id>...
        // Also removes the /api/ prepend if given
        $uriArray = explode('/', trim($uri->getPath(), '/api/'));

        // Check API version correctness
        $apiVersion = array_shift($uriArray);
        if(!file_exists(ROOT . DS . 'app' . DS . $apiVersion)) {
            $error = array();
            $error['code'] = 404;
            $error['message'] = Language::translateWithVars('resty_error', 'invalid_api_version', [$apiVersion]);
            $error['errors'] = array();

            $this->response->getBody()->write(json_encode($error));
            $this->response = $this->response->withStatus(404);
        } else {

        }
    }

}
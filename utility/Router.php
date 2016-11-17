<?php

namespace Resty\Utility;

use Resty\Exception\RestyException;
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

    public function route(Uri $uri) {
        $array = array(
            'uri' => $uri->getPath()
        );
        $this->response->getBody()->write(json_encode($array));
    }

}
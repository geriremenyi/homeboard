<?php

namespace Resty\Controller;

use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;

/**
 * Controller class
 *
 * Parent class for all the controller classes in
 * the application logic
 *
 * @package    Resty
 * @subpackage Controller
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
abstract class Controller {

    /**
     * Request from the client
     *
     * @var ServerRequest
     */
    protected $request;

    /**
     * Response to the client
     *
     * @var Response
     */
    protected $response;

    /**
     * Query string from the url
     *
     * @var null|string
     */
    protected $query;

    /**
     * Controller chain
     *
     * @var null|array
     */
    protected $chain;

    /**
     * Controller constructor.
     *
     * @param ServerRequest $request - Request from the client
     * @param Response $response - Response to the client
     * @param null|string $query - Query string from the url
     * @param null|array $chain - Controller chain
     */
    public function __construct(ServerRequest $request, Response $response, $query = null, $chain = null) {
        $this->request = $request;
        $this->response = $response;
        $this->query = $query;
        $this->chain = $chain;
    }
}
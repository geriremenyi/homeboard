<?php

namespace Resty\Controller;

use Resty\Exception\ServerException;
use Zend\Diactoros\ {
    ServerRequest, Response
};

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

    protected $request;

    protected $response;

    protected $query;

    protected $chain;

    public function __construct(ServerRequest $request, Response $response, $query = null, $chain = null) {
        $this->request = $request;
        $this->response = $response;
        $this->query = $query;
        $this->chain = $chain;
    }
}
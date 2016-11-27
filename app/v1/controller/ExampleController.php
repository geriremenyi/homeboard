<?php

namespace App\v1\Controller;

use Resty\Controller\Controller;
use Zend\Diactoros\{
    ServerRequest, Response
};

class ExampleController extends Controller {

    public function __construct(ServerRequest $request, Response $response, array $query = null, array $chain = null) {
        parent::__construct($request, $response, $query, $chain);
    }
}
<?php

namespace Resty\Utility;

use Resty\Exception\HttpException;
use Zend\Diactoros\{
    Response, ServerRequest, Uri
};

/**
 * Router
 *
 * Router class for the incoming request routing
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
     * @param $autoloader - Composer autoloader
     */
    public function __construct(ServerRequest $request, Response $response, $autoloader) {
        $this->request = $request;
        $this->response = $response;
        $this->autoloader = $autoloader;
    }

    /**
     * Start routing
     *
     * @param Uri $uri - Called uri on the API
     * @throws HttpException
     */
    public function route(Uri $uri) {

        // Remove the /api/ prepend if given
        $uriArray = explode('/', trim($uri->getPath(), '/api/'));

        // Check API version correctness
        $apiVersion = array_shift($uriArray);
        if(!file_exists(ROOT . DS . 'app' . DS . $apiVersion)) {
            $error = [];
            $error['code'] = 404;
            $error['message'] = Language::translateWithVars('resty_error', 'invalid_api_version', [$apiVersion]);
            $error['errors'] = [];

            throw new HttpException(json_encode($error), 404);
        }

        $controllerChain = $this->getControllerChain($apiVersion, $uriArray);
        $query = null;

        $query = $this->getQuery($uri->getQuery());

        if(count($controllerChain) == 0) {
            // API root
            $this->response->getBody()->write(json_encode(['Hello' => 'World!']));
        } else {
            // Get last controller to call
            $controllerToCall = array_pop($controllerChain);

            // Check if controllerChain or query is empty
            $controllerChain = (count($controllerChain) == 0 || $this->request->getMethod() != 'GET') ? null : $controllerChain;
            $query = (count($query) == 0) ? null : $query;

            // Create controller
            $controller = new $controllerToCall['controller']($this->request, $this->response, $query, $controllerChain);

            // Call the http method if implemented
            if(method_exists($controller, ($method = strtolower($this->request->getMethod())))) {
                $controller->{$method}($controllerToCall['id']);
            } else {
                // Method not found on the resource
                $error = [];
                $error['code'] = 404;
                $error['message'] = Language::translateWithVars('resty_error', 'method_not_found', [strtolower($this->request->getMethod()), $controllerToCall['resource']]);
                $error['errors'] = [];

                throw new HttpException(json_encode($error), 404);
            }
        }
    }

    /**
     * Find the controller and id chain by the incoming uri
     *
     * @param string $apiVersion - Version of the called API
     * @param array $uriArray - Uri array containing the uri parts
     * @return array
     * @throws HttpException
     */
    public function getControllerChain(string $apiVersion, array $uriArray) : array {
        $chainArray = [];
        $this->autoloader->addPsr4('App\\' . $apiVersion . '\\', ROOT . DS .  'app' . DS . $apiVersion . DS, true);
        $lastController = '';

        // Because of the pre increase
        $i = -1;
        foreach ($uriArray as $key => $uriPart) {
            // Odd means resource type, even means resource id
            if($key % 2 == 0) {
                $convertedUriPart = str_replace(' ', '', ucwords(str_replace('_', ' ', $uriPart)));
                $controllerName = 'App\\v1\\Controller\\' . $convertedUriPart . 'Controller';
                if(class_exists($controllerName, true)) {
                    $i++;
                    $chainArray[$i] = ['resource' => $uriPart, 'controller' => $controllerName, 'id' => null];
                } else {
                    $error = [];
                    $error['code'] = 404;
                    $error['message'] = Language::translateWithVars('resty_error', 'unknown_resource_found', [$uriPart]);
                    $error['errors'] = [];
                    throw new HttpException(json_encode($error), 404);
                }
            } else {
                $chainArray[$i]['id'] = $uriPart;
            }
        }

        return $chainArray;
    }

    /**
     * Create queries array by query string
     *
     * @param string $queryString - Query string in the URL (e.g: ?key=value&key2=value2)
     * @return array
     */
    public function getQuery(string $queryString) : array {
        $queryArray = [];
        $queries = explode('&', $queryString);

        // TODO handle invalid inputs

        foreach($queries as $query) {
            if(strpos($query, 'filter=') != false) {
                $value = trim($query, 'filter=()');
                $queryArray['filter'] = $value;
            } else {
                $keyValue = explode('=', $query);
                if(count($keyValue) == 2) {
                    $queryArray[$keyValue[0]] = $keyValue[1];
                }
            }
        }

        return $queryArray;
    }

}
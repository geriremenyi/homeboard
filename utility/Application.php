<?php

namespace Resty\Utility;

use Resty\Exception\RestyException;
use Zend\Diactoros\{
    ServerRequestFactory, Response, Uri
};

/**
 * Application
 *
 * Application class
 *
 * @package    Resty
 * @subpackage Utility
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class Application {

    /**
     * Request object from client
     *
     * @var
     */
    private $request;

    /**
     * Response object to send back
     *
     * @var
     */
    private $response;

    public function __construct() {
        // Init HTTP message objects
        $this->request = ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

        $this->response = new Response();
        $this->response = $this->response->withHeader('Content-Type', 'application/json');
    }

    /**
     *
     */
    public function start() {
        try {
            // Set configurations
            Configuration::getInstance()->loadConfigurations();
            Language::setLanguagePath($this->request->getHeaderLine('Accept-Language'));

            // Start routing
            $router = new Router($this->request, $this->response);
            $router->route($this->request->getUri());
        } catch (RestyException $e) {
            //TODO log the exception

            $error = array();
            $error['code'] = 500;
            $error['message'] = Language::translate('resty_error', 'internal_server_error');

            // If development environment then add development error description
            if(ENVIRONMENT == 'dev') {
                $error['developer_message'] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
            }
            // Empty errors list
            $error['errors'] = array();

            $this->response->getBody()->write(json_encode($error));
            $this->response = $this->response->withStatus(500);
        } finally {
            // Compress the output with gzip if needed
            ob_start('ob_gzhandler');
            if(!strpos($this->request->getHeaderLine('Accept-Encoding'), 'gzip')) {
                $this->response = $this->response->withHeader('Content-Encoding', 'gzip');
            } else if (!strpos($this->request->getHeaderLine('Accept-Encoding'), 'deflate')) {
                $this->response = $this->response->withHeader('Content-Encoding', 'deflate');
            }

            echo $this->response->getBody();
            foreach ($this->response->getHeaders() as $headerKey => $headerValue) {
                foreach ($headerValue as $headerValueItem) {
                    header($headerKey . ': ' . $headerValueItem);
                }
            }
            http_response_code($this->response->getStatusCode());
            ob_end_flush();
        }
    }

}
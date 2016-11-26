<?php

namespace Resty\Utility;

use Resty\Auth\ {
    Client, ApiUser
};
use Resty\Exception\ {
    AuthException, RestyException
};
use Zend\Diactoros\{
    ServerRequest, ServerRequestFactory, Response
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
     * @var ServerRequest
     */
    private $request;

    /**
     * Response object to send back
     *
     * @var
     */
    private $response;

    /**
     * Request is coming from this client
     *
     * @var Client
     */
    public static $client;

    /**
     * The user sent the request
     *
     * @var ApiUser
     */
    public static $user;

    public function __construct() {
        // Init HTTP message objects
        $this->request = ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
        $this->response = new Response();
        $this->response = $this->response->withHeader('Content-Type', 'json');
    }

    /**
     *
     */
    public function start() {
        try {
            // Set configurations
            Configuration::getInstance()->loadConfigurations();
            Language::setLanguagePath($this->request->getHeaderLine('Accept-Language'));

            // Authenticate request
            if($this->checkAuthorization($this->request->getHeaderLine('Authorization'))) {
                // Start routing
                $router = new Router($this->request, $this->response);
                $router->route($this->request->getUri());
            }
        } catch (RestyException $e) {

            $error = array();
            $error['code'] = 500;
            $error['message'] = Language::translate('resty_error', 'internal_server_error');

            // Helper for framework development
            if(ENVIRONMENT == 'dev') {
                $error['dev'] = array(
                    'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
                );
            }

            $error['errors'] = array();

            $this->response->getBody()->write(json_encode($error));
            $this->response = $this->response->withStatus(500);
        } finally {
            // Compress the output in gzip if needed
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

    /**
     * Check if the authorization token is okay
     *
     * @param string $authHeader
     * @return bool
     */
    public function checkAuthorization(string $authHeader) : bool {

        // No authorization header given -> unauthorized
        if($authHeader == '') {
            $error = array();
            $error['code'] = 401;
            $error['message'] = Language::translate('resty_error', 'empty_auth_header');
            $error['errors'] = array();

            $this->response->getBody()->write(json_encode($error));
            $this->response = $this->response->withStatus(401);
            return false;
        }

        // Client authorization
        if(strpos($authHeader, 'Basic') == 0) {
            try {
                Application::$client = new Client(Configuration::getInstance(), str_replace('Basic ', '', $authHeader));
                return true;
            } catch (AuthException $e) {
                $error = array();
                $error['code'] = 401;
                $error['message'] = Language::translate('resty_error', 'invalid_client');
                $error['errors'] = array();

                $this->response->getBody()->write(json_encode($error));
                $this->response = $this->response->withStatus(401);
                return false;
            }
        }

        return false;
    }
}
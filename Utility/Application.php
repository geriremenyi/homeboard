<?php

namespace Resty\Utility;

use Resty\Auth\ApiUser;
use Resty\Auth\Client;
use Resty\Exception\{
    AuthException, HttpException, RestyException, ServerException
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
     * @var Response
     */
    private $response;

    /**
     * Composer autoloader
     *
     * @var
     */
    private $autoloader;

    /**
     * Request is coming from this client
     *
     * @var Client
     */
    public static $client;

    /**
     * Current user in the APi
     *
     * @var ApiUser
     */
    public static $user;

    /**
     * Application constructor.
     *
     * @param $autoloader - Composer autoloader
     */
    public function __construct($autoloader) {
        // Init HTTP message objects
        $this->request = ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
        $this->response = new Response();
        $this->response = $this->response->withHeader('Content-Type', 'json');
        $this->autoloader = $autoloader;
    }

    /**
     * Starting point of the application
     */
    public function start() {
        try {
            // Set configurations
            Configuration::getInstance()->loadConfigurations();
            Language::setLanguagePath($this->request->getHeaderLine('Accept-Language'));

            // Authenticate request
            $this->checkAuthorization($this->request->getHeaderLine('Authorization'));

            // Start routing
            $router = new Router($this->request, $this->response, $this->autoloader);
            $router->route($this->request->getUri());

        } catch (HttpException $e) {
            // Http exception which is something to show to the user
            $this->response->getBody()->write($e->getMessage());
            $this->response = $this->response->withStatus($e->getCode());
        } catch (ServerException $e) {
            // Server exception which is something to not to show to the user

            $error = array();
            $error['code'] = 500;
            $error['message'] = Language::translate('resty_error', 'internal_server_error');

            // Send back the error in dev mode: helper for framework development
            if(ENVIRONMENT == 'dev') {
                $error['dev'] = array(
                    'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
                );
            }

            $error['errors'] = array();

            $this->response->getBody()->write(json_encode($error));
            $this->response = $this->response->withStatus(500);
        } catch (\Throwable $e) {
            // Any unexpected exceptions

            $error = array();
            $error['code'] = 500;
            $error['message'] = Language::translate('resty_error', 'internal_server_error');

            // Send back the error in dev mode: helper for framework development
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
     * @param string $authHeader - Authorization header of the request
     * @return bool
     * @throws HttpException
     */
    public function checkAuthorization(string $authHeader) {

        // No authorization header given -> unauthorized
        if($authHeader === '') {
            $error = array();
            $error['code'] = 401;
            $error['message'] = Language::translate('resty_error', 'empty_auth_header');
            $error['errors'] = array();

            throw new HttpException(json_encode($error), 401);
        }

        // Client authorization
        if(strpos($authHeader, 'Basic') === 0) {
            // Validate client
            $newClient = new Client(Configuration::getInstance());
            $newClient->validate(str_replace('Basic ', '', $authHeader));
            Application::$client = $newClient;

            // Empty ApiUser
        }
        // User authentication
        elseif(strpos($authHeader, 'Bearer') === 0) {
            $user = new ApiUser(Configuration::getInstance());
            $user->validate(str_replace('Bearer ', '', $authHeader));
            Application::$user = $user;

            // Client id and secret by the JWT token
            $client = new Client(Configuration::getInstance());
        } else {
            $error = array();
            $error['code'] = 401;
            $error['message'] = Language::translate('resty_error', 'unsupported_auth_header');
            $error['errors'] = array();

            throw new AuthException(json_encode($error), 401);
        }
    }
}
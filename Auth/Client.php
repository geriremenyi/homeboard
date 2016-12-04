<?php

namespace Resty\Auth;

use Resty\Utility\ {
    Configuration, Language
};
use Resty\Exception\{
    InvalidParametersException, AuthException
};

/**
 * Client class
 *
 * This class checks if a client exists and the
 * given client credentials are correct
 *
 * @package    Resty
 * @subpackage Auth
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class Client {

    /**
     * Configuration object
     *
     * @var Configuration
     */
    private $config;

    /**
     * Client ID
     *
     * @var string
     */
    private $id;

    /**
     * Client secret
     *
     * @var string
     */
    private $secret;

    /**
     * Client constructor.
     *
     * @param Configuration $config - Configuration object
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
    }

    /**
     * Validate client
     *
     * @param string $clientCredentials - CLient credentials in a string
     * @throws AuthException
     */
    public function validate(string $clientCredentials) {
        $decodedCredentials = explode(':', base64_decode($clientCredentials));

        if(count($decodedCredentials) != 2) {
            throw new AuthException('The ' . $clientCredentials . ' is not a valid client authorization string!', 401);
        }

        // Check if client is available
        try {
            $this->config->getConfiguration('clients', $decodedCredentials[0]);
            $this->id = $decodedCredentials[0];
            $this->secret = $decodedCredentials[1];
        } catch (InvalidParametersException $e) {
            $error = array();
            $error['code'] = 401;
            $error['message'] = Language::translate('resty_error', 'invalid_token');

            throw new AuthException(json_encode($error), 401);
        }
    }

    /**
     * Id getter
     *
     * @return string
     */
    public function getId() : string {
        return $this->id;
    }

    /**
     * Secret getter
     *
     * @return string
     */
    public function getSecret() : string {
        return $this->secret;
    }

}
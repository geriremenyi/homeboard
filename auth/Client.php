<?php

namespace Resty\Auth;

use Resty\Utility\Configuration;
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

    private $id;

    private $secret;

    public function __construct(Configuration $configuration, string $clientCredentials) {
        $decodedCredentials = explode(':', base64_decode($clientCredentials));

        if(count($decodedCredentials) != 2) {
            throw new AuthException('The ' . $clientCredentials . ' is not a valid client authorization string!', 401);
        }

        // Check if client is available
        try {
            $configuration->getConfiguration('clients', $decodedCredentials[0]);
            $this->id = $decodedCredentials[0];
            $this->secret = $decodedCredentials[1];
        } catch (InvalidParametersException $e) {
            throw new AuthException('Invalid client credentials. Client ID: ' . $decodedCredentials[0] . ' Client Secret: ' . $decodedCredentials[1], 401);
        }
    }

    public function getId() : string {
        return $this->id;
    }

    public function getSecret() : string {
        return $this->secret;
    }

}
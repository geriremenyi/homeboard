<?php

namespace Resty\Auth;

use Resty\Utility\Configuration;
use Resty\Exception\{
    InvalidParametersException, AuthException
};

class Client {

    private $id;

    private $secret;

    public function __construct(Configuration $configuration, string $clientCredentials) {
        $decodedCredentials = explode(':', base64_decode($clientCredentials));

        if(count($decodedCredentials) != 2) {
            throw new AuthException('The ' . $clientCredentials . ' is not a valid client authorization string!');
        }

        // Check if client is available
        try {
            $configuration->getConfiguration('clients', $decodedCredentials[0]);
            $this->id = $decodedCredentials[0];
            $this->secret = $decodedCredentials[1];
        } catch (InvalidParametersException $e) {
            throw new AuthException('Invalid client credentials. Client ID: ' . $decodedCredentials[0] . ' Client Secret: ' . $decodedCredentials[1]);
        }
    }

    public function getId() : string {
        return $this->id;
    }

    public function getSecret() : string {
        return $this->secret;
    }

}
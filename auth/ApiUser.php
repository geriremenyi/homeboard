<?php

namespace Resty\Auth;

use Emarref\Jwt\Algorithm\Hs256;
use Emarref\Jwt\Claim\Expiration;
use Emarref\Jwt\Claim\IssuedAt;
use Emarref\Jwt\Claim\PrivateClaim;
use Emarref\Jwt\Encryption\Factory;
use Emarref\Jwt\Exception\VerificationException;
use Emarref\Jwt\Jwt;
use Emarref\Jwt\Token;
use Emarref\Jwt\Verification\Context;
use Resty\Exception\AuthException;
use Resty\Utility\ {
    Configuration, Language
};

class ApiUser {

    /**
     * Configuration object
     *
     * @var Configuration
     */
    private $config;

    /**
     * Deserialized JWT token
     *
     * @var Token
     */
    private $token;

    /**
     * ApiUser constructor.
     *
     * @param Configuration $config - Configuration object
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
        $this->token = null;
    }

    /**
     * Generate token by claims
     *
     * @param array $claims - Claims array of key value pairs passed by the user
     * @return string
     */
    public function generateToken(array $claims) : string{
        $token = new Token();

        // Issued at and expiration
        $token->addClaim(new Expiration(new \DateTime($this->config->getConfiguration('api', 'token_expiration'))));
        $token->addClaim(new IssuedAt(new \DateTime('now')));

        // Custom claims
        foreach($claims as $key => $claim) {
            $token->addClaim(new PrivateClaim($key, $claim));
        }

        $jwt = new Jwt();
        $algorithm = new Hs256($this->config->getConfiguration('api', 'token_secret'));
        $encryption = Factory::create($algorithm);

        $this->token = $token;

        return $jwt->serialize($token, $encryption);
    }

    /**
     * Check if the token is valid
     *
     * @param string $tokenString - Serialized token
     * @throws AuthException
     */
    public function validate(string $tokenString) {
        try {
            // Deserialize
            $jwt = new Jwt();
            $this->token = $jwt->deserialize($tokenString);
            // Setup validation context
            $algorithm = new Hs256($this->config->getConfiguration('api', 'token_secret'));
            $encryption = Factory::create($algorithm);
            $context = new Context($encryption);

            // Verify
            $jwt->verify($this->token, $context);

        } catch (VerificationException $e) {
            $error = array();
            $error['code'] = 401;
            $error['message'] = Language::translate('resty_error', 'invalid_token');
            $error['errors'] = array();

            throw new AuthException(json_encode($error), 401);
        }
    }

    /**
     * Token getter
     *
     * @return Token
     */
    public function getToken(): Token {
        return $this->token;
    }

}
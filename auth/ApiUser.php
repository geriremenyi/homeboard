<?php

namespace Resty\Auth;

use Emarref\Jwt\Algorithm\Hs256;
use Emarref\Jwt\Claim\ {
    Expiration, IssuedAt, PrivateClaim
};
use Emarref\Jwt\Encryption\Factory;
use Emarref\Jwt\Exception\ExpiredException;
use Emarref\Jwt\ {
    Jwt, Token
};
use Emarref\Jwt\Verification\Context;
use Resty\Exception\AuthException;
use Resty\Utility\ {
    Configuration, Language
};

/**
 * Api User
 *
 * This class is responsible for the
 * token generation and validation
 *
 * @package    App
 * @subpackage v1\Model
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
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
    }

    /**
     * Generate token by claims
     *
     * @param array $claims - Claims array of key value pairs passed by the user
     * @return string
     */
    public function generateToken(array $claims) : string {
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

        } catch (ExpiredException $e) {
            $error = array();
            $error['code'] = 403;
            $error['message'] = Language::translate('resty_error', 'token_expired');
            $error['errors'] = array();

            throw new AuthException(json_encode($error), 403);
        } catch (\Throwable $e) {
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
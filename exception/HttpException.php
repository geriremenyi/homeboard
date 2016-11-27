<?php

namespace Resty\Exception;

/**
 * HttpException
 *
 * Base exception class for all the http exceptions generated
 * in the application logic or resty framework
 *
 * @package    Resty
 * @subpackage Exception
 * @author     Gergely Reményi <gergo@remenyicsalad.hu>
 */
class HttpException extends RestyException {

    /**
     * Make message and code mandatory.
     *
     * @param string $message - Exception message
     * @param int $code - Exception code -> HTTP code
     * @param \Exception|null $previous - Previous exception reference
     */
    public function __construct(string $message, int $code, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}
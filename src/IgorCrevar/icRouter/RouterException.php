<?php

namespace IgorCrevar\icRouter;

/**
 * icRouter exception
 * @author igor
 *
 */
class RouterException extends \Exception
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
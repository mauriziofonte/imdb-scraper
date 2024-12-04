<?php
namespace Mfonte\ImdbScraper\Exceptions;

use Exception;

/**
 * Class BadMethodCall
 * Thrown when a method is called in a way that is not allowed.
 */
class BadMethodCall extends Exception
{
    /**
     * BadMethodCall constructor.
     */
    public function __construct(string $exception)
    {
        parent::__construct("Bad method call: '{$exception}'");
    }
}

<?php
namespace Mfonte\ImdbScraper\Exceptions;

use Exception;

/**
 * Class NoSearchResults
 * Thrown when no search results are found.
 */
class NoSearchResults extends Exception
{
    /**
     * NoSearchResults constructor.
     */
    public function __construct(string $keyword)
    {
        parent::__construct("No search results found for keyword: '{$keyword}'");
    }
}
<?php
namespace Mfonte\ImdbScraper\Exceptions;

use Exception;
use Mfonte\ImdbScraper\Entities\Dataset;

/**
 * Class MultipleSearchResults
 * Thrown when multiple search results are found.
 */
class MultipleSearchResults extends Exception
{
    /**
     * MultipleSearchResults constructor.
     */
    public function __construct(string $keyword, Dataset $results)
    {
        $resultTitles = $results->map(function ($result) {
            return "{$result->title} ({$result->year})";
        })->toArray();
        $resultTitlesString = implode(", ", $resultTitles);

        parent::__construct("Multiple search results found for keyword: '{$keyword}'. Results: {$resultTitlesString}");
    }
}
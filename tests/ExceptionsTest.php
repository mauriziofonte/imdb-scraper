<?php

use PHPUnit\Framework\TestCase;
use Mfonte\ImdbScraper\Imdb;
use Mfonte\ImdbScraper\Entities\Dataset;
use Mfonte\ImdbScraper\Exceptions\NoSearchResults;
use Mfonte\ImdbScraper\Exceptions\MultipleSearchResults;
use Mfonte\ImdbScraper\Exceptions\BadMethodCall;

class ExceptionsTest extends TestCase
{
    public function testEmptySearchResults()
    {
        $imdb = Imdb::new(['locale' => 'it']);
        $results = $imdb->search('this is not a valid search query');

        $this->assertInstanceOf(Dataset::class, $results);
        $this->assertEquals(0, $results->count());
    }

    public function testThrowsNoSearchResults()
    {
        $this->expectException(NoSearchResults::class);

        $imdb = Imdb::new(['locale' => 'it']);
        $imdb->movieByYear('this is not a valid search query', 2021);
    }

    public function testThrowsMultipleSearchResults()
    {
        $this->expectException(MultipleSearchResults::class);

        // https://movies.stackexchange.com/a/86245
        $imdb = Imdb::new(['locale' => 'en', 'guzzleLogFile' => '.phpunit.guzzle.log']);
        $test = $imdb->movieByYear('night club', 1989);
    }

    public function testThrowsBadMethodCall()
    {
        $this->expectException(BadMethodCall::class);

        $imdb = Imdb::new(['locale' => 'it']);
        $imdb->id(2347623784);
    }
}

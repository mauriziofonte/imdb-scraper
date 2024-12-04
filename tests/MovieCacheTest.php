<?php

use PHPUnit\Framework\TestCase;
use Mfonte\ImdbScraper\Imdb;
use Mfonte\ImdbScraper\Entities\Title;

class MovieCacheTest extends TestCase
{
    public function testMovieByYear()
    {
        $imdb = Imdb::new([
            'locale' => 'en',
            'cache' => true
        ]);
        $movie = $imdb->movieByYear('the martian', 2015);

        // assert that film is a Title instance
        $this->assertInstanceOf(Title::class, $movie);

        // assert that the id is tt3659388
        $this->assertEquals('tt3659388', $movie->id);

        // re-load the movie from cache
        $init = microtime(true);
        $movie = $imdb->movieByYear('the martian', 2015);
        $end = microtime(true);

        // assert that the id is tt3659388
        $this->assertEquals('tt3659388', $movie->id);

        // assert that the cache was used (no more than 0.2 seconds)
        $this->assertLessThan(0.2, $end - $init);
    }
}

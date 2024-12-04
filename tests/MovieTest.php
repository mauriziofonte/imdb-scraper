<?php

use PHPUnit\Framework\TestCase;
use Mfonte\ImdbScraper\Imdb;
use Mfonte\ImdbScraper\Entities\Dataset;
use Mfonte\ImdbScraper\Entities\Title;

class MovieTest extends TestCase
{
    public function testMovieByYear()
    {
        $imdb = Imdb::new(['locale' => 'it']);
        $movie = $imdb->movieByYear('la ricerca della felicità', 2006);

        // assert that film is a Title instance
        $this->assertInstanceOf(Title::class, $movie);

        // assert that the id is tt0454921
        $this->assertEquals('tt0454921', $movie->id);

        // assert the link is https://www.imdb.com/it/title/tt0454921/
        $this->assertEquals('https://www.imdb.com/it/title/tt0454921/', $movie->link);

        // assert isTvSeries is false
        $this->assertFalse($movie->isTvSeries);

        // assert the title is "la ricerca della felicità"
        $this->assertEqualsIgnoringCase('la ricerca della felicità', $movie->title);

        // assert the year is 2006
        $this->assertEquals(2006, $movie->year);

        // assert that the plot contains the substring "venditore ambulante"
        $this->assertStringContainsStringIgnoringCase('venditore ambulante', $movie->plot);

        // assert a non-empty actors Dataset
        $this->assertInstanceOf(Dataset::class, $movie->actors);
        $this->assertGreaterThan(0, $movie->actors->count());

        // assert a non-empty similars Dataset
        $this->assertInstanceOf(Dataset::class, $movie->similars);
        $this->assertGreaterThan(0, $movie->similars->count());
    }

    public function testMovieByBestMatch()
    {
        $imdb = Imdb::new(['locale' => 'it']);
        $movie = $imdb->movie('gomorra');

        // assert that tvShow is a Title instance
        $this->assertInstanceOf(Title::class, $movie);

        // assert that the id is tt0386676
        $this->assertEquals('tt0929425', $movie->id);

        // assert the link is https://www.imdb.com/it/title/tt0929425/
        $this->assertEquals('https://www.imdb.com/it/title/tt0929425/', $movie->link);

        // assert isTvSeries is false
        $this->assertFalse($movie->isTvSeries);

        // assert the title is "Gomorra"
        $this->assertEqualsIgnoringCase('Gomorra', $movie->title);

        // assert the year is 2008
        $this->assertEquals(2008, $movie->year);

        // assert that the plot contains the substring "famiglie criminali"
        $this->assertStringContainsStringIgnoringCase('famiglie criminali', $movie->plot);

        // assert a non-empty actors Dataset
        $this->assertInstanceOf(Dataset::class, $movie->actors);
        $this->assertGreaterThan(0, $movie->actors->count());

        // assert a non-empty similars Dataset
        $this->assertInstanceOf(Dataset::class, $movie->similars);
        $this->assertGreaterThan(0, $movie->similars->count());
    }

    public function testEnglishMovieByYear()
    {
        $imdb = Imdb::new(['locale' => 'en']);
        $movie = $imdb->movieByYear('from dusk dawn', 1996);

        // assert that film is a Title instance
        $this->assertInstanceOf(Title::class, $movie);

        // assert that the id is tt0116367
        $this->assertEquals('tt0116367', $movie->id);

        // assert the link is https://www.imdb.com/title/tt0116367/
        $this->assertEquals('https://www.imdb.com/title/tt0116367/', $movie->link);

        // assert isTvSeries is false
        $this->assertFalse($movie->isTvSeries);

        // assert the title is "From Dusk Till Dawn"
        $this->assertEqualsIgnoringCase('From Dusk Till Dawn', $movie->title);

        // assert the year is 1996
        $this->assertEquals(1996, $movie->year);

        // assert that the plot contains the substring "temporary refuge"
        $this->assertStringContainsStringIgnoringCase('temporary refuge', $movie->plot);

        // assert a non-empty actors Dataset
        $this->assertInstanceOf(Dataset::class, $movie->actors);
        $this->assertGreaterThan(0, $movie->actors->count());

        // assert a non-empty similars Dataset
        $this->assertInstanceOf(Dataset::class, $movie->similars);
        $this->assertGreaterThan(0, $movie->similars->count());
    }

    public function testEnglishMovieByBestMatch()
    {
        $imdb = Imdb::new(['locale' => 'en']);
        $movie = $imdb->movie('godfather');
        
        // assert that tvShow is a Title instance
        $this->assertInstanceOf(Title::class, $movie);

        // assert that the id is tt0068646
        $this->assertEquals('tt0068646', $movie->id);

        // assert the link is https://www.imdb.com/title/tt0068646/
        $this->assertEquals('https://www.imdb.com/title/tt0068646/', $movie->link);

        // assert isTvSeries is false
        $this->assertFalse($movie->isTvSeries);

        // assert the title is "The Godfather"
        $this->assertEqualsIgnoringCase('The Godfather', $movie->title);

        // assert the year is 1972
        $this->assertEquals(1972, $movie->year);

        // assert that the plot contains the substring "aging patriarch"
        $this->assertStringContainsStringIgnoringCase('aging patriarch', $movie->plot);

        // assert a non-empty actors Dataset
        $this->assertInstanceOf(Dataset::class, $movie->actors);
        $this->assertGreaterThan(0, $movie->actors->count());

        // assert a non-empty similars Dataset
        $this->assertInstanceOf(Dataset::class, $movie->similars);
        $this->assertGreaterThan(0, $movie->similars->count());
    }
}

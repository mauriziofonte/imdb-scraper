<?php

use PHPUnit\Framework\TestCase;
use Mfonte\ImdbScraper\Imdb;
use Mfonte\ImdbScraper\Entities\Dataset;
use Mfonte\ImdbScraper\Entities\Title;
use Mfonte\ImdbScraper\Entities\Season;
use Mfonte\ImdbScraper\Entities\Episode;

class TvSeriesTest extends TestCase
{
    public function testTvSeriesByYear()
    {
        $imdb = Imdb::new(['locale' => 'it']);
        $tvShow = $imdb->tvSeries('la casa di carta');

        // assert that film is a Title instance
        $this->assertInstanceOf(Title::class, $tvShow);

        // assert that the id is tt6468322
        $this->assertEquals('tt6468322', $tvShow->id);

        // assert the link is https://www.imdb.com/it/title/tt6468322/
        $this->assertEquals('https://www.imdb.com/it/title/tt6468322/', $tvShow->link);

        // assert isTvSeries is true
        $this->assertTrue($tvShow->isTvSeries);

        // assert the title is "La casa di carta"
        $this->assertEqualsIgnoringCase('La casa di carta', $tvShow->title);

        // assert the year is 2017
        $this->assertEquals(2017, $tvShow->year);

        // assert that the plot contains the substring "fabbrica di Moneda e Timbre"
        $this->assertStringContainsStringIgnoringCase('fabbrica di moneda e timbre', $tvShow->plot);

        // assert a non-empty actors Dataset
        $this->assertInstanceOf(Dataset::class, $tvShow->actors);
        $this->assertGreaterThan(0, $tvShow->actors->count());

        // assert a non-empty similars Dataset
        $this->assertInstanceOf(Dataset::class, $tvShow->similars);
        $this->assertGreaterThan(0, $tvShow->similars->count());

        // assert that seasonRefs is exactly [1, 2, 3, 4, 5]
        $this->assertEquals([1, 2, 3, 4, 5], $tvShow->seasonRefs);
    }

    public function testTvSeriesWithEpisodes() {
        $imdb = Imdb::new(['locale' => 'en', 'seasons' => true]);
        $tvShow = $imdb->tvSeries('money heist');

        // assert that film is a Title instance
        $this->assertInstanceOf(Title::class, $tvShow);

        // assert that the id is tt6468322
        $this->assertEquals('tt6468322', $tvShow->id);

        // assert the link is https://www.imdb.com/title/tt6468322/
        $this->assertEquals('https://www.imdb.com/title/tt6468322/', $tvShow->link);

        // assert isTvSeries is true
        $this->assertTrue($tvShow->isTvSeries);

        // assert the title is "Money Heist"
        $this->assertEqualsIgnoringCase('Money Heist', $tvShow->title);

        // assert the year is 2017
        $this->assertEquals(2017, $tvShow->year);

        // assert that the plot contains the substring "billion euros"
        $this->assertStringContainsStringIgnoringCase('billion euros', $tvShow->plot);

        // assert a non-empty actors Dataset
        $this->assertInstanceOf(Dataset::class, $tvShow->actors);
        $this->assertGreaterThan(0, $tvShow->actors->count());

        // assert a non-empty similars Dataset
        $this->assertInstanceOf(Dataset::class, $tvShow->similars);
        $this->assertGreaterThan(0, $tvShow->similars->count());

        // assert a non-empty seasons Dataset, with a count of 5
        $this->assertInstanceOf(Dataset::class, $tvShow->seasons);
        $this->assertEquals(5, $tvShow->seasons->count());

        // assert that the encapsulated seasons are Season instances, with non-empty episodes Datasets
        $this->assertInstanceOf(Season::class, $tvShow->seasons->first());
        $this->assertInstanceOf(Dataset::class, $tvShow->seasons->first()->episodes);
        $this->assertGreaterThan(0, $tvShow->seasons->first()->episodes->count());
        $this->assertInstanceOf(Episode::class, $tvShow->seasons->first()->episodes->first());

        // assert that the 1st season has 9 episodes, the second 6, the third 8, the fourth 8, and the fifth 10
        $this->assertEquals(9, $tvShow->seasons->get("S01")->episodes->count());
        $this->assertEquals(6, $tvShow->seasons->get("S02")->episodes->count());
        $this->assertEquals(8, $tvShow->seasons->get("S03")->episodes->count());
        $this->assertEquals(8, $tvShow->seasons->get("S04")->episodes->count());
        $this->assertEquals(10, $tvShow->seasons->get("S05")->episodes->count());
    }
}

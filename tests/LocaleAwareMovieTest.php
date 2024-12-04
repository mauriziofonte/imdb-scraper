<?php

use PHPUnit\Framework\TestCase;
use Mfonte\ImdbScraper\Imdb;
use Mfonte\ImdbScraper\Entities\Title;

class LocaleAwareMovieTest extends TestCase
{
    public function testEnglishMovie()
    {
        $imdb = Imdb::new(['locale' => 'en']);
        $movie = $imdb->id('tt0111161');

        // assert that film is a Title instance
        $this->assertInstanceOf(Title::class, $movie);

        // assert that the id is tt0111161
        $this->assertEquals('tt0111161', $movie->id);

        // assert the title is "The Shawshank Redemption"
        $this->assertEqualsIgnoringCase('The Shawshank Redemption', $movie->title);
    }

    public function testFrenchMovie() {
        $imdb = Imdb::new(['locale' => 'fr']);
        $movie = $imdb->id('tt0111161');

        // assert that film is a Title instance
        $this->assertInstanceOf(Title::class, $movie);

        // assert that the id is tt0111161
        $this->assertEquals('tt0111161', $movie->id);

        // assert the title is "Les Évadés"
        $this->assertEqualsIgnoringCase('Les Évadés', $movie->title);
    }

    public function testGermanMovie() {
        $imdb = Imdb::new(['locale' => 'de']);
        $movie = $imdb->id('tt0111161');

        // assert that film is a Title instance
        $this->assertInstanceOf(Title::class, $movie);

        // assert that the id is tt0111161
        $this->assertEquals('tt0111161', $movie->id);

        // assert the title is "Die Verurteilten"
        $this->assertEqualsIgnoringCase('Die Verurteilten', $movie->title);
    }

    public function testHindiMovie() {
        $imdb = Imdb::new(['locale' => 'hi']);
        $movie = $imdb->id('tt0111161');

        // assert that film is a Title instance
        $this->assertInstanceOf(Title::class, $movie);

        // assert that the id is tt0111161
        $this->assertEquals('tt0111161', $movie->id);

        // assert the title is "द शौशैंक रिडेम्प्शन"
        $this->assertEqualsIgnoringCase('द शौशैंक रिडेम्प्शन', $movie->title);
    }

    public function testItalianMovie() {
        $imdb = Imdb::new(['locale' => 'it']);
        $movie = $imdb->id('tt0111161');

        // assert that film is a Title instance
        $this->assertInstanceOf(Title::class, $movie);

        // assert that the id is tt0111161
        $this->assertEquals('tt0111161', $movie->id);

        // assert the title is "Le ali della libertà"
        $this->assertEqualsIgnoringCase('Le ali della libertà', $movie->title);
    }

    public function testSpanishMovie() {
        $imdb = Imdb::new(['locale' => 'es']);
        $movie = $imdb->id('tt0111161');

        // assert that film is a Title instance
        $this->assertInstanceOf(Title::class, $movie);

        // assert that the id is tt0111161
        $this->assertEquals('tt0111161', $movie->id);

        // assert the title is "Cadena perpetua"
        $this->assertEqualsIgnoringCase('Cadena perpetua', $movie->title);
    }
}

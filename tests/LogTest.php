<?php

use PHPUnit\Framework\TestCase;
use Mfonte\ImdbScraper\Imdb;

class LogTest extends TestCase
{
    public function testCreatesLogFile()
    {
        $tmpFile = realpath(rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.phpunit.test.log';

        $imdb = Imdb::new([
            'locale' => 'en',
            'guzzleLogFile' => $tmpFile
        ]);
        $results = $imdb->search('fight club');

        // assert that the log file exists
        $this->assertFileExists($tmpFile);

        // assert that the log file is not empty
        $this->assertGreaterThan(0, filesize($tmpFile));

        unlink($tmpFile);
    }
}

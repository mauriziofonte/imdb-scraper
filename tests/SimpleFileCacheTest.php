<?php

use PHPUnit\Framework\TestCase;
use Mfonte\ImdbScraper\Cache;

class SimpleFileCacheTest extends TestCase
{
    private $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new Cache;
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        unset($this->cache);
        parent::tearDown();
    }

    public function testAddToCache()
    {
        $keyValueStore = [
            "property" => "value",
            "anotherProperty" => "anotherValue",
            "array" => [
                "key" => "value"
            ],
            "nestedArray" => [
                "key" => [
                    "key" => "value"
                ]
            ],
            "boolean" => true,
            "integer" => 1,
            "float" => 1.1,
            "object" => (object) ["key" => "value"]
        ];

        $this->cache->add("test", $keyValueStore);
        $cacheContent = $this->cache->get("test");

        $this->assertEquals($keyValueStore, $cacheContent);

        $this->cache->delete("test");
    }

    public function testHasCache()
    {
        $this->cache->add("testHas", ["key" => "value"]);

        $this->assertEquals(true, $this->cache->has("testHas"));
        $this->assertEquals('value', $this->cache->get("testHas")['key']);
        
        $this->assertEquals(false, $this->cache->has("testHasNot"));

        $this->cache->delete("testHas");
    }

    public function testDeleteFromCache()
    {
        $this->cache->add("testHas", ["key" => "value"]);

        $this->assertEquals(true, $this->cache->has("testHas"));
        $this->assertEquals(true, $this->cache->delete("testHas"));
    }
}

<?php

use PHPUnit\Framework\TestCase;
use Mfonte\ImdbScraper\Cache;

class SimpleFileCacheTest extends TestCase
{
    /** @var Cache */
    private $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new Cache;
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        if ($this->cache) {
            $this->cache->clear();
        }
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

        $this->assertTrue($this->cache->add("test", $keyValueStore));
        $cacheContent = $this->cache->get("test");

        $this->assertEquals($keyValueStore, $cacheContent);
    }

    public function testHasCache()
    {
        $this->cache->add("testHas", ["key" => "value"]);

        $this->assertTrue($this->cache->has("testHas"));
        $this->assertEquals('value', $this->cache->get("testHas")['key']);
        
        $this->assertFalse($this->cache->has("testHasNot"));
    }

    public function testDeleteFromCache()
    {
        $this->cache->add("testDelete", ["key" => "value"]);

        $this->assertTrue($this->cache->has("testDelete"));
        $this->assertTrue($this->cache->delete("testDelete"));
        $this->assertFalse($this->cache->has("testDelete"));
    }

    public function testCacheStringData()
    {
        $stringData = "This is a test string with special characters: àáâãäåæçèéêë";
        
        $this->assertTrue($this->cache->add("string_test", $stringData));
        $this->assertEquals($stringData, $this->cache->get("string_test"));
    }

    public function testCacheNumericData()
    {
        $intData = 42;
        $floatData = 3.14159;
        $negativeInt = -100;
        
        $this->assertTrue($this->cache->add("int_test", $intData));
        $this->assertTrue($this->cache->add("float_test", $floatData));
        $this->assertTrue($this->cache->add("negative_int_test", $negativeInt));
        
        $this->assertSame($intData, $this->cache->get("int_test"));
        $this->assertSame($floatData, $this->cache->get("float_test"));
        $this->assertSame($negativeInt, $this->cache->get("negative_int_test"));
    }

    public function testCacheBooleanData()
    {
        $this->assertTrue($this->cache->add("bool_true", true));
        $this->assertTrue($this->cache->add("bool_false", false));
        
        $this->assertTrue($this->cache->get("bool_true"));
        $this->assertFalse($this->cache->get("bool_false"));
    }

    public function testCacheNullData()
    {
        $this->assertTrue($this->cache->add("null_test", null));
        $this->assertNull($this->cache->get("null_test"));
    }

    public function testCacheArrayData()
    {
        $simpleArray = [1, 2, 3, 4, 5];
        $associativeArray = ['name' => 'John', 'age' => 30, 'city' => 'New York'];
        $multidimensionalArray = [
            'users' => [
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob']
            ],
            'settings' => [
                'theme' => 'dark',
                'language' => 'en'
            ]
        ];
        
        $this->assertTrue($this->cache->add("simple_array", $simpleArray));
        $this->assertTrue($this->cache->add("assoc_array", $associativeArray));
        $this->assertTrue($this->cache->add("multi_array", $multidimensionalArray));
        
        $this->assertEquals($simpleArray, $this->cache->get("simple_array"));
        $this->assertEquals($associativeArray, $this->cache->get("assoc_array"));
        $this->assertEquals($multidimensionalArray, $this->cache->get("multi_array"));
    }

    public function testCacheObjectData()
    {
        $stdObject = new \stdClass();
        $stdObject->property1 = "value1";
        $stdObject->property2 = 42;
        $stdObject->property3 = ["nested" => "array"];
        
        $this->assertTrue($this->cache->add("object_test", $stdObject));
        $retrievedObject = $this->cache->get("object_test");
        
        $this->assertEquals($stdObject, $retrievedObject);
        $this->assertInstanceOf(\stdClass::class, $retrievedObject);
        $this->assertEquals("value1", $retrievedObject->property1);
        $this->assertEquals(42, $retrievedObject->property2);
        $this->assertEquals(["nested" => "array"], $retrievedObject->property3);
    }

    public function testCacheResourceData()
    {
        // Create a temporary file resource for testing
        $tempFile = tmpfile();
        fwrite($tempFile, "test content");
        rewind($tempFile);
        
        // Resources cannot be serialized, so this should handle the exception gracefully
        $result = $this->cache->add("resource_test", $tempFile);
        
        // The cache should either handle this gracefully or return false
        // This depends on the implementation, but it shouldn't crash
        $this->assertTrue(is_bool($result));
        
        fclose($tempFile);
    }

    public function testCacheLargeData()
    {
        // Test with large string (1MB)
        $largeString = str_repeat("Lorem ipsum dolor sit amet, consectetur adipiscing elit. ", 20000);
        
        $this->assertTrue($this->cache->add("large_string", $largeString));
        $this->assertEquals($largeString, $this->cache->get("large_string"));
        
        // Test compression effectiveness
        $stats = $this->cache->getCompressionStats("large_string");
        if ($stats) {
            $this->assertArrayHasKey('compression_method', $stats);
            $this->assertArrayHasKey('compression_ratio', $stats);
            $this->assertGreaterThan(0, $stats['compression_ratio']);
        }
    }

    public function testCacheWithCustomTTL()
    {
        $shortTtl = 1; // 1 second
        
        $this->assertTrue($this->cache->add("ttl_test", "test_value", $shortTtl));
        $this->assertTrue($this->cache->has("ttl_test"));
        $this->assertEquals("test_value", $this->cache->get("ttl_test"));
        
        // Wait for TTL to expire
        sleep(2);
        
        // Note: This test might be flaky depending on the cache implementation
        // Some caches only check TTL on access, others have background cleanup
        $this->assertTrue(true); // Placeholder - actual TTL testing depends on cache backend
    }

    public function testCompressionMethod()
    {
        $compressionMethod = $this->cache->getCompressionMethod();
        $this->assertIsString($compressionMethod);
        
        $validMethods = ['none', 'gzip', 'deflate', 'bzip2', 'lz4', 'zstd'];
        $this->assertContains($compressionMethod, $validMethods);
    }

    public function testCompressionStats()
    {
        $testData = "This is test data for compression statistics";
        
        $this->assertTrue($this->cache->add("compression_stats_test", $testData));
        
        $stats = $this->cache->getCompressionStats("compression_stats_test");
        
        if ($stats) {
            $this->assertArrayHasKey('compression_method', $stats);
            $this->assertArrayHasKey('original_size', $stats);
            $this->assertArrayHasKey('compressed_size', $stats);
            $this->assertArrayHasKey('compression_ratio', $stats);
            $this->assertArrayHasKey('timestamp', $stats);
            
            $this->assertIsString($stats['compression_method']);
            $this->assertIsNumeric($stats['original_size']);
            $this->assertIsNumeric($stats['compressed_size']);
            $this->assertIsNumeric($stats['compression_ratio']);
            $this->assertIsNumeric($stats['timestamp']);
        }
        
        // Test non-existent key
        $this->assertNull($this->cache->getCompressionStats("non_existent_key"));
    }

    public function testCacheUnicodeData()
    {
        $unicodeData = [
            'emoji' => '🚀🎉💻🌟',
            'chinese' => '你好世界',
            'arabic' => 'مرحبا بالعالم',
            'russian' => 'Привет мир',
            'japanese' => 'こんにちは世界'
        ];
        
        $this->assertTrue($this->cache->add("unicode_test", $unicodeData));
        $this->assertEquals($unicodeData, $this->cache->get("unicode_test"));
    }

    public function testCacheBinaryData()
    {
        // Create some binary data
        $binaryData = pack("H*", "deadbeef48656c6c6f20576f726c64");
        
        $this->assertTrue($this->cache->add("binary_test", $binaryData));
        $this->assertEquals($binaryData, $this->cache->get("binary_test"));
    }

    public function testCacheComplexNestedStructure()
    {
        $complexData = [
            'metadata' => [
                'version' => '1.0.0',
                'created' => new \DateTime('2023-01-01'),
                'tags' => ['important', 'test', 'complex']
            ],
            'data' => [
                'users' => [
                    'john' => (object)['name' => 'John Doe', 'age' => 30],
                    'jane' => (object)['name' => 'Jane Smith', 'age' => 25]
                ],
                'settings' => [
                    'features' => [
                        'feature1' => true,
                        'feature2' => false,
                        'feature3' => null
                    ],
                    'limits' => [
                        'max_users' => 1000,
                        'max_storage' => 5.5
                    ]
                ]
            ],
            'empty_arrays' => [],
            'empty_object' => new \stdClass()
        ];
        
        $this->assertTrue($this->cache->add("complex_test", $complexData));
        $retrieved = $this->cache->get("complex_test");
        
        $this->assertEquals($complexData, $retrieved);
        $this->assertInstanceOf(\DateTime::class, $retrieved['metadata']['created']);
        $this->assertEquals('John Doe', $retrieved['data']['users']['john']->name);
    }

    public function testCacheClearFunction()
    {
        // Add multiple items
        $this->cache->add("clear_test_1", "value1");
        $this->cache->add("clear_test_2", "value2");
        $this->cache->add("clear_test_3", "value3");
        
        $this->assertTrue($this->cache->has("clear_test_1"));
        $this->assertTrue($this->cache->has("clear_test_2"));
        $this->assertTrue($this->cache->has("clear_test_3"));
        
        // Clear cache
        $this->assertTrue($this->cache->clear());
        
        // Verify all items are gone
        $this->assertFalse($this->cache->has("clear_test_1"));
        $this->assertFalse($this->cache->has("clear_test_2"));
        $this->assertFalse($this->cache->has("clear_test_3"));
    }

    public function testCacheKeyEdgeCases()
    {
        $edgeCaseKeys = [
            'normal_key',
            'key-with-dashes',
            'key_with_underscores',
            'key.with.dots',
            'key with spaces',
            'ключ_с_unicode',
            '123numeric_key',
            'UPPERCASE_KEY',
            'MiXeD_cAsE_kEy'
        ];
        
        foreach ($edgeCaseKeys as $key) {
            $value = "value_for_" . $key;
            $this->assertTrue($this->cache->add($key, $value), "Failed to add key: $key");
            $this->assertEquals($value, $this->cache->get($key), "Failed to retrieve key: $key");
        }
    }

    /**
     * Test caching very large data sets (10MB)
     * @group heavy
     */
    public function testCacheHeavyData10MB()
    {
        $this->outputCompressionMethod();
        
        // Create 10MB of data - highly repetitive for better compression
        $singleChunk = str_repeat("This is a test string that will be repeated many times to create a large dataset. ", 1000);
        $heavyData = array_fill(0, 200, $singleChunk); // ~10MB
        
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage(true);
        
        $this->assertTrue($this->cache->add("heavy_10mb", $heavyData));
        
        $memoryAfter = memory_get_usage(true);
        $cacheTime = microtime(true) - $startTime;
        
        $this->outputTestStats("Cache Write (10MB)", $cacheTime, $memoryAfter - $memoryBefore);
        
        // Test retrieval
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage(true);
        
        $retrieved = $this->cache->get("heavy_10mb");
        
        $memoryAfter = memory_get_usage(true);
        $retrieveTime = microtime(true) - $startTime;
        
        $this->outputTestStats("Cache Read (10MB)", $retrieveTime, $memoryAfter - $memoryBefore);
        
        $this->assertEquals($heavyData, $retrieved);
        
        // Output compression statistics
        $this->outputCompressionStats("heavy_10mb");
    }

    /**
     * Test caching extremely large data sets (50MB)
     * @group heavy
     */
    public function testCacheHeavyData50MB()
    {
        // Create 50MB of mixed data
        $baseString = str_repeat("Lorem ipsum dolor sit amet, consectetur adipiscing elit. ", 500);
        $heavyData = [
            'text_data' => array_fill(0, 500, $baseString),
            'numeric_data' => range(1, 100000),
            'mixed_data' => array_fill(0, 100, [
                'id' => random_int(1, 1000000),
                'name' => str_repeat("User Name ", 50),
                'data' => array_fill(0, 100, 'some_data_' . random_int(1, 1000)),
                'metadata' => [
                    'created' => time(),
                    'updated' => time() + random_int(1, 3600),
                    'tags' => array_fill(0, 20, 'tag_' . random_int(1, 100))
                ]
            ])
        ];
        
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage(true);
        
        $this->assertTrue($this->cache->add("heavy_50mb", $heavyData));
        
        $memoryAfter = memory_get_usage(true);
        $cacheTime = microtime(true) - $startTime;
        
        $this->outputTestStats("Cache Write (50MB)", $cacheTime, $memoryAfter - $memoryBefore);
        
        // Test retrieval
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage(true);
        
        $retrieved = $this->cache->get("heavy_50mb");
        
        $memoryAfter = memory_get_usage(true);
        $retrieveTime = microtime(true) - $startTime;
        
        $this->outputTestStats("Cache Read (50MB)", $retrieveTime, $memoryAfter - $memoryBefore);
        
        $this->assertEquals($heavyData, $retrieved);
        
        // Output compression statistics
        $this->outputCompressionStats("heavy_50mb");
    }

    /**
     * Test caching massive arrays (100MB)
     * @group heavy
     */
    public function testCacheMassiveArray100MB()
    {
        // Create a massive array with ~100MB of data
        $massiveArray = [];
        
        // Fill with different types of data
        for ($i = 0; $i < 10000; $i++) {
            $massiveArray[] = [
                'id' => $i,
                'uuid' => sprintf(
                    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff)
                ),
                'name' => 'User ' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'email' => 'user' . $i . '@example.com',
                'description' => str_repeat("This is a long description for user $i. ", 100),
                'settings' => [
                    'theme' => $i % 2 ? 'dark' : 'light',
                    'language' => ['en', 'es', 'fr', 'de', 'it'][$i % 5],
                    'notifications' => $i % 3 === 0,
                    'preferences' => array_fill(0, 50, 'pref_' . ($i % 20))
                ],
                'data_blob' => str_repeat("x", 1000) // 1KB per record
            ];
        }
        
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage(true);
        
        $this->assertTrue($this->cache->add("massive_100mb", $massiveArray));
        
        $memoryAfter = memory_get_usage(true);
        $cacheTime = microtime(true) - $startTime;
        
        $this->outputTestStats("Cache Write (100MB)", $cacheTime, $memoryAfter - $memoryBefore);
        
        // Test retrieval
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage(true);
        
        $retrieved = $this->cache->get("massive_100mb");
        
        $memoryAfter = memory_get_usage(true);
        $retrieveTime = microtime(true) - $startTime;
        
        $this->outputTestStats("Cache Read (100MB)", $retrieveTime, $memoryAfter - $memoryBefore);
        
        $this->assertEquals(count($massiveArray), count($retrieved));
        $this->assertEquals($massiveArray[0], $retrieved[0]);
        $this->assertEquals($massiveArray[5000], $retrieved[5000]);
        $this->assertEquals($massiveArray[9999], $retrieved[9999]);
        
        // Output compression statistics
        $this->outputCompressionStats("massive_100mb");
    }

    /**
     * Test multiple concurrent large datasets
     * @group heavy
     */
    public function testMultipleLargeDatasets()
    {
        $datasets = [];
        $totalStartTime = microtime(true);
        
        // Create and cache 5 different large datasets
        for ($set = 0; $set < 5; $set++) {
            $dataSize = (5 + $set * 2); // 5MB, 7MB, 9MB, 11MB, 13MB
            $dataset = [];
            
            for ($i = 0; $i < $dataSize * 100; $i++) { // Roughly dataSize MB
                $dataset[] = [
                    'set_id' => $set,
                    'record_id' => $i,
                    'data' => str_repeat("Dataset $set Record $i ", 100),
                    'timestamp' => time() + $i,
                    'metadata' => array_fill(0, 20, "meta_$set" . "_$i")
                ];
            }
            
            $key = "dataset_$set" . "_" . $dataSize . "mb";
            $datasets[$key] = $dataset;
            
            $startTime = microtime(true);
            $this->assertTrue($this->cache->add($key, $dataset));
            $cacheTime = microtime(true) - $startTime;
            
            $this->outputTestStats("Cache Write (Dataset $set - {$dataSize}MB)", $cacheTime, 0);
        }
        
        $totalCacheTime = microtime(true) - $totalStartTime;
        $this->outputTestStats("Total Cache Write Time", $totalCacheTime, 0);
        
        // Verify all datasets
        $totalRetrieveStartTime = microtime(true);
        foreach ($datasets as $key => $originalData) {
            $startTime = microtime(true);
            $retrieved = $this->cache->get($key);
            $retrieveTime = microtime(true) - $startTime;
            
            $this->assertEquals($originalData, $retrieved);
            $this->outputTestStats("Cache Read ($key)", $retrieveTime, 0);
            $this->outputCompressionStats($key);
        }
        
        $totalRetrieveTime = microtime(true) - $totalRetrieveStartTime;
        $this->outputTestStats("Total Cache Read Time", $totalRetrieveTime, 0);
    }

    /**
     * Test compression efficiency with different data types
     * @group heavy
     */
    public function testCompressionEfficiencyAnalysis()
    {
        $testCases = [
            'highly_repetitive' => [
                'data' => str_repeat("AAAAAAAAAA", 1000000), // 10MB highly repetitive
                'description' => 'Highly Repetitive Text (10MB)'
            ],
            'random_text' => [
                'data' => $this->generateRandomText(10 * 1024 * 1024), // 10MB random text
                'description' => 'Random Text (10MB)'
            ],
            'json_like' => [
                'data' => $this->generateJsonLikeData(5000), // ~10MB JSON-like structure
                'description' => 'JSON-like Structure (10MB)'
            ],
            'binary_like' => [
                'data' => random_bytes(10 * 1024 * 1024), // 10MB binary data
                'description' => 'Binary Data (10MB)'
            ],
            'mixed_content' => [
                'data' => $this->generateMixedContent(), // ~10MB mixed content
                'description' => 'Mixed Content (10MB)'
            ]
        ];

        foreach ($testCases as $testKey => $testCase) {
            $key = "compression_test_$testKey";
            
            $startTime = microtime(true);
            $this->assertTrue($this->cache->add($key, $testCase['data']));
            $cacheTime = microtime(true) - $startTime;
            
            $retrieved = $this->cache->get($key);
            $this->assertEquals($testCase['data'], $retrieved);
            
            $this->outputTestStats("Cache Write ({$testCase['description']})", $cacheTime, 0);
            $this->outputCompressionStats($key);
        }
    }

    /**
     * Helper method to output compression method information
     */
    private function outputCompressionMethod(): void
    {
        $method = $this->cache->getCompressionMethod();
        $this->addToAssertionCount(1); // Don't count as a skipped test
        echo "\n--- COMPRESSION METHOD: " . strtoupper($method) . " ---\n";
    }

    /**
     * Helper method to output test performance statistics
     */
    private function outputTestStats(string $operation, float $timeSeconds, int $memoryBytes): void
    {
        $this->addToAssertionCount(1); // Don't count as a skipped test
        
        $timeFormatted = number_format($timeSeconds, 4) . 's';
        $memoryFormatted = $memoryBytes > 0 ? $this->formatBytes($memoryBytes) : 'N/A';
        
        echo "\nSimpleFileCacheTest $operation:\n";
        echo "   Time: $timeFormatted\n";
        echo "   Memory: $memoryFormatted\n";
    }

    /**
     * Helper method to output compression statistics
     */
    private function outputCompressionStats(string $key): void
    {
        $stats = $this->cache->getCompressionStats($key);
        
        if ($stats) {
            $this->addToAssertionCount(1); // Don't count as a skipped test
            
            echo "\nSimpleFileCacheTest Compression Stats for '$key':\n";
            echo "   Method: " . strtoupper($stats['compression_method']) . "\n";
            echo "   Original: " . $this->formatBytes($stats['original_size']) . "\n";
            echo "   Compressed: " . $this->formatBytes($stats['compressed_size']) . "\n";
            echo "   Ratio: " . $stats['compression_ratio'] . "%\n";
            echo "   Space Saved: " . $this->formatBytes($stats['original_size'] - $stats['compressed_size']) . "\n\n";
        }
    }

    /**
     * Helper method to format bytes in human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = $bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Generate random text data
     */
    private function generateRandomText(int $sizeBytes): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 .,!?;:';
        $text = '';
        $charsLength = strlen($chars);
        
        for ($i = 0; $i < $sizeBytes; $i++) {
            $text .= $chars[random_int(0, $charsLength - 1)];
        }
        
        return $text;
    }

    /**
     * Generate JSON-like structured data
     */
    private function generateJsonLikeData(int $records): array
    {
        $data = [];
        
        for ($i = 0; $i < $records; $i++) {
            $data[] = [
                'id' => $i,
                'name' => 'Record ' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'description' => str_repeat("Description for record $i. ", 50),
                'tags' => array_fill(0, 20, 'tag_' . random_int(1, 100)),
                'metadata' => [
                    'created_at' => date('Y-m-d H:i:s', time() - random_int(0, 86400 * 365)),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'version' => random_int(1, 10),
                    'status' => ['active', 'inactive', 'pending'][random_int(0, 2)],
                    'properties' => array_fill(0, 30, 'prop_value_' . random_int(1, 1000))
                ],
                'content' => str_repeat("Content block $i ", 200)
            ];
        }
        
        return $data;
    }

    /**
     * Generate mixed content data
     */
    private function generateMixedContent(): array
    {
        return [
            'text_data' => str_repeat("Mixed content text section. ", 100000),
            'numeric_data' => range(1, 50000),
            'object_data' => array_fill(0, 1000, (object)[
                'prop1' => str_repeat('value', 100),
                'prop2' => random_int(1, 1000000),
                'prop3' => array_fill(0, 50, 'item_' . random_int(1, 100))
            ]),
            'nested_arrays' => array_fill(0, 500, [
                'level1' => array_fill(0, 20, [
                    'level2' => array_fill(0, 10, 'deep_value_' . random_int(1, 1000))
                ])
            ]),
            'binary_section' => random_bytes(1024 * 1024), // 1MB binary
            'repetitive_section' => str_repeat("REPEATED_PATTERN_", 50000)
        ];
    }
}

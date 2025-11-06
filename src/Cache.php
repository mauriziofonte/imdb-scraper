<?php
namespace Mfonte\ImdbScraper;

use voku\cache\Cache as CacheDriver;
use voku\cache\AdapterFileSimple;
use voku\cache\SerializerDefault;
use FilesystemIterator;
use SplFileInfo;

/**
* Class Cache
*
* @package mfonte/imdb-scraper
* @author Maurizio Fonte
*/
class Cache
{
    const TTL = 2678400; // 31 days
    const COMPRESSION_NONE = 'none';
    const COMPRESSION_GZIP = 'gzip';
    const COMPRESSION_DEFLATE = 'deflate';
    const COMPRESSION_BZIP2 = 'bzip2';
    const COMPRESSION_LZ4 = 'lz4';
    const COMPRESSION_ZSTD = 'zstd';

    private $cacheDir;
    private static $compressionMethod = null;
    private static $isPruned = false;

    /**
     * voku\cache\Cache instance
     *
     * @var \voku\cache\Cache
     */
    private $cache;
    
    public function __construct()
    {
        $this->cacheDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cache';

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        if (!is_writable($this->cacheDir)) {
            throw new \Exception("Mfonte\ImdbScraper\Cache: Cache directory \"{$this->cacheDir}\" is not writable.");
        }

        // Detect the best compression method available
        if (self::$compressionMethod === null) {
            self::$compressionMethod = $this->detectBestCompressionMethod();
        }

        // Perform pruning only once per instance
        if (self::$isPruned === false) {
            self::$isPruned = $this->pruneCacheFiles();
        }
        
        $adapter = new AdapterFileSimple($this->cacheDir);
        $serializer = new SerializerDefault();
        $this->cache = new CacheDriver($adapter, $serializer);
    }

    /**
     * Get the TTL for the cache item
     *
     * @param int|null $ttl
     * @return int
     */
    private function getTtl(?int $ttl): int
    {
        return ($ttl > 0) ? $ttl : self::TTL;
    }

    /**
     * Add (or modify) an item in the cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     *
     * @return bool
     */
    public function add(string $key, $value, ?int $ttl = null): bool
    {
        $wrappedValue = $this->wrapWithMetadata($value);
        return $this->cache->setItem($key, $wrappedValue, $this->getTtl($ttl));
    }

    /**
     * Deletes an item from the cache
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->cache->removeItem($key);
    }

    /**
     * Get an item from the cache
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function get(string $key)
    {
        if (!$this->has($key)) {
            return null;
        }

        $wrappedValue = $this->cache->getItem($key);
        
        // Handle backward compatibility and unwrap data
        if (is_array($wrappedValue) && isset($wrappedValue['cdata'])) {
            return $this->unwrapFromMetadata($wrappedValue);
        }
        
        // Fallback for old cache entries
        return $wrappedValue;
    }

    /**
     * Check if an item exists in the cache
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->cache->existsItem($key);
    }

    /**
     * Get compression statistics for a cached item
     *
     * @param string $key
     * @return array|null
     */
    public function getCompressionStats(string $key): ?array
    {
        if (!$this->has($key)) {
            return null;
        }

        $wrappedValue = $this->cache->getItem($key);
        
        if (is_array($wrappedValue) && isset($wrappedValue['compression_method'])) {
            return [
                'compression_method' => $wrappedValue['compression_method'],
                'original_size' => $wrappedValue['original_size'] ?? 0,
                'compressed_size' => $wrappedValue['compressed_size'] ?? 0,
                'compression_ratio' => $wrappedValue['original_size'] > 0
                    ? round((1 - ($wrappedValue['compressed_size'] / $wrappedValue['original_size'])) * 100, 2)
                    : 0,
                'timestamp' => $wrappedValue['timestamp'] ?? null
            ];
        }
        
        return null;
    }

    /**
     * Get the current compression method being used
     *
     * @return string
     */
    public function getCompressionMethod(): string
    {
        return self::$compressionMethod;
    }

    /**
     * Clear all cached items
     *
     * @return bool
     */
    public function clear(): bool
    {
        try {
            $iterator = new FilesystemIterator($this->cacheDir, FilesystemIterator::SKIP_DOTS);
            
            foreach ($iterator as $fileInfo) {
                /** @var SplFileInfo $fileInfo */
                if ($fileInfo->isFile() && $fileInfo->getExtension() === 'cache') {
                    unlink($fileInfo->getPathname());
                }
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Cache clear failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Detect the best compression method available on the system
     *
     * @return string
     */
    private function detectBestCompressionMethod(): string
    {
        // Check for compression extensions in order of efficiency and speed
        if (extension_loaded('zstd')) {
            return self::COMPRESSION_ZSTD;
        }
        
        if (extension_loaded('lz4')) {
            return self::COMPRESSION_LZ4;
        }
        
        if (extension_loaded('bz2')) {
            return self::COMPRESSION_BZIP2;
        }
        
        if (extension_loaded('zlib')) {
            return self::COMPRESSION_GZIP;
        }
        
        return self::COMPRESSION_NONE;
    }

    /**
     * Prune cache files older than TTL using modern file collection methods
     *
     * @return bool
     */
    private function pruneCacheFiles(): bool
    {
        try {
            $iterator = new FilesystemIterator($this->cacheDir, FilesystemIterator::SKIP_DOTS);
            $currentTime = time();
            
            foreach ($iterator as $fileInfo) {
                /** @var SplFileInfo $fileInfo */
                if ($fileInfo->isFile() && $fileInfo->getExtension() === 'cache') {
                    $fileAge = $currentTime - $fileInfo->getMTime();
                    
                    if ($fileAge > self::TTL) {
                        try {
                            unlink($fileInfo->getPathname());
                        } catch (\Exception $e) {
                            // Log the error but continue with other files
                            error_log("Cache pruning failed for file {$fileInfo->getPathname()}: " . $e->getMessage());
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't throw - cache should still work
            error_log("Cache pruning failed: " . $e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Compress data using the best available method
     *
     * @param mixed $data
     * @return string
     */
    private function compressData($data): string
    {
        $serializedData = serialize($data);

        switch (self::$compressionMethod) {
            case self::COMPRESSION_ZSTD:
                if (function_exists('zstd_compress')) {
                    return zstd_compress($serializedData, 3);
                }
                break;
                
            case self::COMPRESSION_LZ4:
                if (function_exists('lz4_compress')) {
                    return lz4_compress($serializedData);
                }
                break;
                
            case self::COMPRESSION_BZIP2:
                if (function_exists('bzcompress')) {
                    return bzcompress($serializedData, 6);
                }
                break;
                
            case self::COMPRESSION_GZIP:
                if (function_exists('gzcompress')) {
                    return gzcompress($serializedData, 6);
                }
                break;
                
            case self::COMPRESSION_DEFLATE:
                if (function_exists('gzdeflate')) {
                    return gzdeflate($serializedData, 6);
                }
                break;
        }
        
        return $serializedData;
    }

    /**
     * Decompress data using the specified method
     *
     * @param string $compressedData
     * @param string $method
     * @return mixed
     */
    private function decompressData(string $compressedData, string $method)
    {
        $decompressedData = $compressedData;
        
        switch ($method) {
            case self::COMPRESSION_ZSTD:
                if (function_exists('zstd_uncompress')) {
                    $decompressedData = zstd_uncompress($compressedData);
                }
                break;
                
            case self::COMPRESSION_LZ4:
                if (function_exists('lz4_uncompress')) {
                    $decompressedData = lz4_uncompress($compressedData);
                }
                break;
                
            case self::COMPRESSION_BZIP2:
                if (function_exists('bzdecompress')) {
                    $decompressedData = bzdecompress($compressedData);
                }
                break;
                
            case self::COMPRESSION_GZIP:
                if (function_exists('gzuncompress')) {
                    $decompressedData = gzuncompress($compressedData);
                }
                break;
                
            case self::COMPRESSION_DEFLATE:
                if (function_exists('gzinflate')) {
                    $decompressedData = gzinflate($compressedData);
                }
                break;
        }
        
        return unserialize($decompressedData);
    }

    /**
     * Wrap data with compression metadata
     *
     * @param mixed $value
     * @return array
     */
    private function wrapWithMetadata($value): array
    {
        $compressedData = $this->compressData($value);
        
        return [
            'cdata' => $compressedData,
            'compression_method' => self::$compressionMethod,
            'original_size' => strlen(serialize($value)),
            'compressed_size' => strlen($compressedData),
            'timestamp' => time()
        ];
    }

    /**
     * Unwrap data with compression metadata
     *
     * @param array $wrappedData
     * @return mixed
     */
    private function unwrapFromMetadata(array $wrappedData)
    {
        if (!isset($wrappedData['cdata']) || !isset($wrappedData['compression_method'])) {
            // Fallback for old cache entries without metadata
            return $wrappedData;
        }
        
        return $this->decompressData($wrappedData['cdata'], $wrappedData['compression_method']);
    }
}

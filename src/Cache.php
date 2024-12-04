<?php
namespace Mfonte\ImdbScraper;

use voku\cache\Cache as CacheDriver;
use voku\cache\AdapterFileSimple;
use voku\cache\SerializerDefault;

/**
* Class Cache
*
* @package mfonte/imdb-scraper
* @author Maurizio Fonte
*/
class Cache
{
    const TTL = 2678400; // 31 days

    private $cacheDir;

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

        // auto-prune the cache files older than TTL
        $cacheFiles = glob($this->cacheDir . DIRECTORY_SEPARATOR . '*.cache');
        foreach ($cacheFiles as $cacheFile) {
            if (time() - filemtime($cacheFile) > self::TTL) {
                unlink($cacheFile);
            }
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
    public function add(string $key, $value, ?int $ttl = null)
    {
        return $this->cache->setItem($key, $value, $this->getTtl($ttl));
    }

    /**
     * Deletes an item from the cache
     *
     * @return bool
     */
    public function delete(string $key)
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

        return $this->cache->getItem($key);
    }

    /**
     * Check if an item exists in the cache
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key)
    {
        return $this->cache->existsItem($key);
    }
}

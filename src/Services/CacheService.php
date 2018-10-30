<?php
/**
 * Date: 15/10/2018
 * Time: 22:42
 */

namespace App\Services;


use Doctrine\Common\Cache\MongoDBCache;
use Monolog\Handler\Mongo;
use Psr\Cache\CacheItemPoolInterface;

class CacheService implements Service
{

    /**
     * @var CacheItemPoolInterface
     */
    private $pool;
    /**
     * @var MongoDBCache
     */
    private $cache;

    public function __construct(MongoDBCache $cache)
    {

        $this->cache = $cache;
    }

    /**
     * @param \Closure $closure
     * @param string $key
     * @param int $time
     * @return false|mixed
     */
    public function getItem(string $key)
    {
        return $this->cache->fetch($key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasItem(string $key)
    {
        return $this->cache->contains($key);
    }

    /**
     * @param string $key
     * @param $data
     * @param int $time
     */
    public function save(string $key, $data, int $time = 60)
    {
        $this->cache->save($key, $data, $time);
    }

    public function clearAll()
    {
        $this->cache->deleteAll();
    }


}
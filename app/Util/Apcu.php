<?php

namespace App\Util;

class Apcu
{
    private $cachePrefix;
    
    /** @var Logger */
    private $logger;
    
    public function __construct(Config $config, Logger $logger)
    {
        $this->cachePrefix = $config->get('cachePrefix');
        $this->logger = $logger;
    }
    
    private function getKey($className)
    {
        return $this->cachePrefix . 'sharedObject_' . $className;
    }
    
    public function addObject($className, $object, $ttl = null)
    {
        $key = $this->getKey($className);
        $this->logger->log("Adding $key to cache (ttl = $ttl seconds).");
        // does not overwrite
        return apcu_add($key, $object, $ttl);
    }
    
    public function fetchObject($className)
    {
        $key = $this->getKey($className);
        $this->logger->log("Fetching $key from cache.");
        return apcu_fetch($key);
    }
    
    public function objectExists($className)
    {
        $key = $this->getKey($className);
        return apcu_exists($key);
    }
    
    public function storeObject($className, $object, $ttl = null)
    {
        $key = $this->getKey($className);
        $this->logger->log("Overwriting $key in cache (ttl = $ttl seconds).");
        return apcu_store($key, $object, $ttl);
    }
    
    public function deleteObject($className)
    {
        $key = $this->getKey($className);
        $this->logger->log("Deleting $key from cache.");
        return apcu_delete($key);
    }
}
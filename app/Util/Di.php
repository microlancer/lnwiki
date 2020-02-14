<?php
namespace App\Util;

/**
 * @codeCoverageIgnore
 */
class Di
{
    private static $objects = [];
    private static $handlers = [];
    private static $instance;

    private $cacheSharedObjects;
    
    /** @var Apcu */
    private $apcu;

    public function __construct()
    {
        $this->cacheSharedObjects = null;
        $this->apcu = null;
    }

    /**
     *
     * @return Di
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Di();
        }
        return self::$instance;
    }
    
    public function initApcu()
    {
        $this->apcu = $this->get(Apcu::class);
    }
    
    public function set($className, $object)
    {
        if (is_null($this->cacheSharedObjects) &&
            array_key_exists(Config::class, self::$objects)) {
            $this->cacheSharedObjects = $this->get(Config::class)
                ->get('cacheSharedObjects', false);
        }
        
        if (isset($this->apcu) && $this->cacheSharedObjects &&
            in_array(SharedObject::class, class_implements($className))) {
            
            if (isset($object->cacheTtlSeconds)) {
                $ttl = $object->cacheTtlSeconds;
            } else {
                $ttl = null;
            }
            
            $this->apcu->addObject($className, $object, $ttl);
        }

        self::$objects[$className] = $object;
    }

    public function create($className)
    {
        if (!isset(self::$handlers[$className]) && !class_exists($className)) {
            throw new \Exception("Cannot instantiate $className, no handler found");
        } elseif (!isset(self::$handlers[$className]) && class_exists($className)) {
            $reflection = new \ReflectionClass($className);
            $constructor = $reflection->getConstructor();

            $arguments = [];

            if ($constructor) {
                $constructorParams = $constructor->getParameters();

                foreach ($constructorParams as $s => $param) {
                    $class = $param->getClass();

                    if (is_null($class)) {
                        throw new \Exception("Cannot auto-resolve parameter $param of $className; define an explicit handler instead.");
                    }

                    $paramType = $class->getName();
                    if (class_exists($paramType)) {
                        $arguments[] = $this->get($paramType);
                    } else {
                        throw new \Exception("Cannot auto-resolve parameter $param of $className; define an explicit handler instead.");
                    }
                }
            } else {
                $arguments = [];
            }

            $object = $reflection->newInstanceArgs($arguments);
        } else {
            $callable = self::$handlers[$className];
            $object = $callable($this);
        }

        return $object;
    }

    public function get($className)
    {
        if ($this->cacheSharedObjects && isset($this->apcu) && 
            $this->apcu->objectExists($className)) {
            return $this->apcu->fetchObject($className);
        }

        if (!isset(self::$objects[$className])) {
            $this->set($className, $this->create($className));
        }

        return self::$objects[$className];
    }
    
    public function register($className, callable $callback)
    {
        self::$handlers[$className] = $callback;
    }
}

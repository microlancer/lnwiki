<?php

namespace App\Util;

class Config implements SharedObject
{
    public static $config;

    /**
     * Get configuration array from file.
     *
     * @return array
     */
    public function getConfig()
    {
        if (!isset(self::$config)) {
            self::$config = include dirname(__FILE__) . '/../../config.php';
        }
        return self::$config;
    }

    /**
     * Get a specific config value, by name.
     *
     * @param string $var
     * @return mixed
     * @throws \Exception
     */
    public function get($var, $default = null)
    {
        $hasDefault = !is_null($default);
        $hasConfig = isset($this->getConfig()[$var]);

        if ($hasDefault && !$hasConfig) {
            return $default;
        } elseif (!$hasDefault && !$hasConfig) {
            throw new \Exception("Config variable `$var` must be defined in config.php");
        }

        return $this->getConfig()[$var];
    }
}

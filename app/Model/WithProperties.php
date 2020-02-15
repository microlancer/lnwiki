<?php

namespace App\Model;

/**
 * @codeCoverageIgnore
 */
trait WithProperties
{
    private $properties;
    private $propertyNames;
    private $modified;

    public function __set($property, $value)
    {
        if (!in_array($property, $this->propertyNames)) {
            throw new \Exception(get_class($this) . " does not have the property `$property`.");
        }

        if ($value !== $this->properties[$property]) {
            $this->modified[$property] = true;
            $this->properties[$property] = $value;
        }
    }

    public function __isset($property)
    {
        if (!in_array($property, $this->propertyNames)) {
            throw new \Exception(get_class($this) . " does not have the property `$property`.");
        }
        return isset($this->properties[$property]);
    }

    public function __toString()
    {
        return json_encode($this->properties);
    }

// Removing this function because it causes serialize() to fail, e.g. when Message
// object is serialized it has WithProperties trait but serialize() expects sleep
// to return an array of instance-variable names.
//    public function __sleep()
//    {
//        return $this->__toString();
//    }

    // return reference to support indirect modification
    public function &__get($property)
    {
        if (!in_array($property, $this->propertyNames)) {
            throw new \Exception(get_class($this) . " does not have the property `$property`.");
        }
        return $this->properties[$property];
    }

    public function getPropertyNames()
    {
        return $this->propertyNames;
    }

    public function init(array $properties)
    {
        foreach ($properties as $property => $value) {
            if (!in_array($property, $this->propertyNames)) {
                throw new \Exception(get_class($this) . " does not have the property `$property`.");
            }

            $this->properties[$property] = $value;
        }
    }

    public function fromJsonString($json)
    {
        $properties = json_decode($json);
        foreach ($properties as $property => $value) {
            $this->$property = $value;
            // Treat as unmodified when deserializing entire object.
            $this->modified[$property] = false;
        }
    }

    public function getModifiedProperties()
    {
        $modified = [];

        foreach ($this->modified as $propertyName => $isModified) {
            if ($isModified) {
                $modified[] = $propertyName;
            }
        }

        return $modified;
    }
    
    public function isModifiedProperty($propertyName)
    {
        return $this->modified[$propertyName];
    }
    
    public function clearModificationFlags()
    {
        foreach ($this->modified as $propertyName => $isModified) {
            $this->modified[$propertyName] = false;
        }
    }

    public function toArray()
    {
        return $this->properties;
    }

    protected function defineProperties(array $propertyNames)
    {
        $this->properties = [];
        $this->modified = [];
        $this->propertyNames = $propertyNames;

        foreach ($propertyNames as $propertyName) {
            $this->properties[$propertyName] = null;
            $this->modified[$propertyName] = false;
        }
    }
}

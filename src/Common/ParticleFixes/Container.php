<?php

namespace Spot\Api\Common\ParticleFixes;

class Container extends \Particle\Validator\Value\Container
{
    /**
     * Traverses the key using dot notation. Based on the second parameter, it will return the value or if it was set.
     *
     * @param string $key
     * @param bool $returnValue
     * @return mixed
     */
    protected function traverse($key, $returnValue = true)
    {
        $value = $this->values;
        foreach (explode('.', $key) as $part) {
            if (!array_key_exists($part, $value)) {
                return $returnValue ? null : false;
            }
            $value = $value[$part];
        }
        return $returnValue ? $value : true;
    }

    /**
     * Uses dot-notation to set a value.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    protected function setTraverse($key, $value)
    {
        $parts = explode('.', $key);
        $ref = &$this->values;

        foreach ($parts as $i => $part) {
            if ($i < count($parts) - 1 && (!isset($ref[$part]) || !is_array($ref[$part]))) {
                $ref[$part] = [];
            }
            $ref = &$ref[$part];
        }

        $ref = $value;
        return $this;
    }
}

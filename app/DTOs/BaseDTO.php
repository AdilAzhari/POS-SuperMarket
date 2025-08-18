<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

abstract class BaseDTO implements Arrayable, JsonSerializable
{
    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        $data = [];
        foreach ($properties as $property) {
            $value = $property->getValue($this);
            if ($value instanceof BaseDTO) {
                $data[$property->getName()] = $value->toArray();
            } elseif (is_array($value)) {
                $data[$property->getName()] = array_map(function ($item) {
                    return $item instanceof BaseDTO ? $item->toArray() : $item;
                }, $value);
            } else {
                $data[$property->getName()] = $value;
            }
        }
        
        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromArray(array $data): static
    {
        $reflection = new \ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            return new static();
        }
        
        $parameters = $constructor->getParameters();
        $args = [];
        
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $args[] = $data[$name] ?? ($parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null);
        }
        
        return new static(...$args);
    }
}
<?php

namespace App\Utilities\General;

use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

abstract class ShallowSerializable implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        $data = [];
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $data[$property->getName()] = $property->getValue($this);
        }

        return $data;
    }
}

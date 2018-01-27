<?php

namespace markhuot\CraftQL\Builders;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;

class Union extends Field {

    protected $types = [];
    protected $resolveType;
    protected static $rawTypes = [];

    function resolveType(callable $resolveType): self {
        $this->resolveType = $resolveType;
        return $this;
    }

    function getResolveType() {
        return $this->resolveType;
    }

    function addType(Schema $type) {
        return $this->types[$type->getName()] = $type;
    }

    function getTypes(): array {
        return $this->types;
    }

    function getRawTypes(): array {
        $types = [];

        foreach ($this->types as $typeName => $typeSchema) {
            $types[] = new ObjectType([
                'name' => $typeName,
                'fields' => $typeSchema->getFieldConfig(),
            ]);
        }

        return $types;
    }

    function getRawType() {
        if (!empty(static::$rawTypes[$this->getName()])) {
            return static::$rawTypes[$this->getName()];
        }

        return static::$rawTypes[$this->getName()] = new UnionType([
            'name' => ucfirst($this->getName()).'Union',
            'description' => 'A union of possible blocks types',
            'types' => $this->getRawTypes(),
            'resolveType' => $this->getResolveType(),
        ]);
    }

    function getConfig() {
        $type = $this->getRawType();

        if ($this->isList) {
            $type = Type::listOf($type);
        }

        if ($this->isNonNull) {
            $type = Type::nonNull($type);
        }

        return [
            'type' => $type,
            'description' => $this->getDescription(),
            'resolve' => $this->getResolve(),
        ];
    }

}
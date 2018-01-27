<?php

namespace markhuot\CraftQL\Builders;

use GraphQL\Type\Definition\Type;

trait HasNonNullAttribute {

    /**
     * If the schema is required
     *
     * @var boolean
     */
    protected $isNonNull = false;

    /**
     * Set if required
     *
     * @param boolean $nonnull
     * @return self
     */
    function nonNull(/* php 7.1: bool? */ $nonNull=true): self {
        $this->isNonNull = $nonNull;
        return $this;
    }

    /**
     * Get if required
     *
     * @return boolean
     */
    function isNonNull(): bool {
        return $this->isNonNull;
    }

}
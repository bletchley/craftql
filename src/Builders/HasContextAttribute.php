<?php

namespace markhuot\CraftQL\Builders;

use GraphQL\Type\Definition\Type;

trait HasContextAttribute {

    /**
     * The context
     *
     * @var boolean
     */
    protected $context = false;

    /**
     * Store the context
     *
     * @param mixed $context
     * @return self
     */
    function context($context=true): self {
        $this->context = $context;
        return $this;
    }

    /**
     * Get the context
     *
     * @return mixed
     */
    function getContext() {
        return $this->context;
    }

}
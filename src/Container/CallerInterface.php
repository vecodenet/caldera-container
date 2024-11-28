<?php

namespace Caldera\Container;

interface CallerInterface {

    /**
     * Call a callable and return its result
     * @param  mixed $callable
     * @param  array $arguments
     * @return mixed
     */
    public function call(mixed $callable, array $arguments = []): mixed;
}
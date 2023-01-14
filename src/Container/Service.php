<?php

declare(strict_types = 1);

/**
 * Caldera Container
 * Container implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Container;

class Service {

	/**
	 * Locked flag
	 * @var boolean
	 */
	protected $locked = false;

	/**
	 * Shared flag
	 * @var boolean
	 */
	protected $shared = false;

	/**
	 * Service instance
	 * @var mixed
	 */
	protected $instance;

	/**
	 * Constructor arguments
	 * @var array
	 */
	protected $arguments = [];

	/**
	 * Decorator methods
	 * @var array
	 */
	protected $decorators = [];

	/**
	 * Constructor
	 * @param bool   $shared   Shared flag
	 * @param mixed  $instance Service instance
	 */
	public function __construct(bool $shared, $instance = null) {
		$this->shared = $shared;
		$this->instance = $instance;
	}

	/**
	 * Set locked flag
	 * @param  bool $locked Locked flag
	 * @return $this
	 */
	public function setLocked(bool $locked) {
		$this->locked = $locked;
		return $this;
	}

	/**
	 * Set shared flag
	 * @param  bool $shared Shared flag
	 * @return $this
	 */
	public function setShared(bool $shared) {
		$this->shared = $shared;
		return $this;
	}

	/**
	 * Set service instance
	 * @param  mixed $instance Service instance
	 * @return $this
	 */
	public function setInstance($instance) {
		$this->instance = $instance;
		return $this;
	}

	/**
	 * Add a constructor argument
	 * @param  string $name  Argument name
	 * @param  string $value Argument value
	 * @return $this
	 */
	public function withArgument(string $name, $value = '') {
		$this->arguments[$name] = $value;
		return $this;
	}

	/**
	 * Add a decorator method
	 * @param  string $name      Method name
	 * @param  array  $arguments Method arguments
	 * @return $this
	 */
	public function withDecorator(string $name, array $arguments = []) {
		$this->decorators[$name] = $arguments;
		return $this;
	}

	/**
	 * Check locked flag
	 * @return boolean
	 */
	public function isLocked() {
		return $this->locked == true;
	}

	/**
	 * Check shared flag
	 * @return boolean
	 */
	public function isShared() {
		return $this->shared == true;
	}

	/**
	 * Get service instance
	 * @return mixed
	 */
	public function getInstance() {
		return $this->instance;
	}

	/**
	 * Get constructor argument
	 * @param  string $name    Argument name
	 * @param  string $default Default argument value
	 * @return mixed
	 */
	public function getArgument(string $name, $default = '') {
		return $this->arguments[$name] ?? $default;
	}

	/**
	 * Get decorator method
	 * @param  string $name Decorator name
	 * @return mixed
	 */
	public function getDecorator(string $name) {
		return $this->decorators[$name] ?? null;
	}

	/**
	 * Get constructor arguments
	 * @return array
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Get decorator methods
	 * @return array
	 */
	public function getDecorators() {
		return $this->decorators;
	}
}

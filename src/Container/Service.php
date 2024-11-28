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
	 * @var bool
	 */
	protected bool $locked = false;

	/**
	 * Shared flag
	 * @var bool
	 */
	protected bool $shared = false;

	/**
	 * Service instance
	 * @var mixed|null
	 */
	protected mixed $instance;

	/**
	 * Constructor arguments
	 * @var array
	 */
	protected array $arguments = [];

	/**
	 * Decorator methods
	 * @var array
	 */
	protected array $decorators = [];

	/**
	 * Constructor
	 * @param bool       $shared   Shared flag
	 * @param mixed|null $instance Service instance
	 */
	public function __construct(bool $shared, mixed $instance = null) {
		$this->shared = $shared;
		$this->instance = $instance;
	}

	/**
	 * Set locked flag
	 * @param  bool $locked Locked flag
	 * @return self
	 */
	public function setLocked(bool $locked): self {
		$this->locked = $locked;
		return $this;
	}

	/**
	 * Set shared flag
	 * @param  bool $shared Shared flag
	 * @return self
	 */
	public function setShared(bool $shared): self {
		$this->shared = $shared;
		return $this;
	}

	/**
	 * Set service instance
	 * @param  mixed $instance Service instance
	 * @return self
	 */
	public function setInstance($instance): self {
		$this->instance = $instance;
		return $this;
	}

	/**
	 * Add a constructor argument
	 * @param  string $name  Argument name
	 * @param  string $value Argument value
	 * @return self
	 */
	public function withArgument(string $name, $value = ''): self {
		$this->arguments[$name] = $value;
		return $this;
	}

	/**
	 * Add a decorator method
	 * @param  string $name      Method name
	 * @param  array  $arguments Method arguments
	 * @return self
	 */
	public function withDecorator(string $name, array $arguments = []): self {
		$this->decorators[$name] = $arguments;
		return $this;
	}

	/**
	 * Check locked flag
	 * @return bool
	 */
	public function isLocked(): bool {
		return $this->locked == true;
	}

	/**
	 * Check shared flag
	 * @return bool
	 */
	public function isShared(): bool {
		return $this->shared == true;
	}

	/**
	 * Get service instance
	 * @return mixed
	 */
	public function getInstance(): mixed {
		return $this->instance;
	}

	/**
	 * Get constructor argument
	 * @param  string $name    Argument name
	 * @param  mixed $default Default argument value
	 * @return mixed
	 */
	public function getArgument(string $name, mixed $default = ''): mixed {
		return $this->arguments[$name] ?? $default;
	}

	/**
	 * Get decorator method
	 * @param  string $name Decorator name
	 * @return mixed
	 */
	public function getDecorator(string $name): mixed {
		return $this->decorators[$name] ?? null;
	}

	/**
	 * Get constructor arguments
	 * @return array
	 */
	public function getArguments(): array {
		return $this->arguments;
	}

	/**
	 * Get decorator methods
	 * @return array
	 */
	public function getDecorators(): array {
		return $this->decorators;
	}
}

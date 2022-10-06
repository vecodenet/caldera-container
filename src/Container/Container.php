<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Container implementation, part of Vecode Caldera
 * @version 1.0
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Container;

use ArrayAccess;
use Closure;
use Exception;
use ReflectionClass;

use Psr\Container\ContainerInterface;

use Caldera\Container\ContainerException;
use Caldera\Container\NotFoundException;
use Caldera\Container\Service;

class Container implements ContainerInterface, ArrayAccess {

	/**
	 * Services array
	 * @var array
	 */
	protected $services = [];

	/**
	 * Add a service
	 * @param  string  $name     Service name
	 * @param  bool    $shared   Shared flag
	 * @param  mixed   $instance Service instance
	 * @return Service
	 */
	public function add(string $name, bool $shared = false, $instance = null): ?Service {
		if (! $this->has($name) ) {
			$service = new Service($shared, $instance);
			$this->services[$name] = $service;
			return $service;
		} else {
			return $this->services[$name];
		}
	}

	/**
	 * Remove an entry
	 * @param string $name Service name
	 * @return $this
	 */
	public function remove(string $name) {
		if ( $this->has($name) ) {
			unset( $this->services[$name] );
		}
		return $this;
	}

	/**
	 * Finds an entry of the container by its identifier and returns it.
	 * @param string $name Identifier of the entry to look for.
	 * @return mixed
	 */
	public function get(string $name) {
		if ( $this->has($name) ) {
			$service = $this->services[$name];
			$instance = $service->getInstance();
			$arguments = $service->getArguments();
			$decorators = $service->getDecorators();
			# Check if there's a lock or not
			if ( $service->isLocked() ) {
				throw new ContainerException($this, "Circular reference for '{$name}' service");
			}
			# Set the lock to avoid circular refs
			$service->setLocked(true);
			if ( is_object($instance) ) {
				if ( $instance instanceof Closure ) {
					# Factory, call Closure
					$instance = $instance->call($this, $this);
				}
			} else if ( class_exists($name) ) {
				# Class name, get a new instance
				$reflector = new ReflectionClass($name);
				$instance = $this->make($reflector, $arguments);
				$this->decorate($instance, $decorators);
			} else if ( interface_exists($name) && $this->has($instance) ) {
				# And interface with a registered implementation
				$instance = $this->get($instance);
			} else if ( interface_exists($name) && class_exists($instance) ) {
				# An Interface, get class name and try to get a new instance
				$reflector = new ReflectionClass($instance);
				$instance = $this->make($reflector, $arguments);
				$this->decorate($instance, $decorators);
			}
			# Unlock service
			$service->setLocked(false);
			if ( is_object( $instance ) ) {
				# Check if the shared flag is set
				if ($service->isShared() && ($service->getInstance() === null || $service->getInstance() instanceof Closure)) {
					# Save the instance
					$service->setInstance($instance);
				}
				return $instance;
			} else {
				throw new ContainerException($this, "Service '{$name}' can not be instantiated");
			}
		} else if ( class_exists($name) ) {
			# Fully-qualified class name, get a new instance
			$reflector = new ReflectionClass($name);
			$instance = $this->make($reflector);
			if ( is_object( $instance ) ) {
				return $instance;
			} else {
				throw new ContainerException($this, "Class '{$name}' can not be instantiated");
			}
		} else {
			throw new NotFoundException($this, "Service '{$name}' not found");
		}
	}

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 * @param string $name Identifier of the entry to look for.
	 * @return bool
	 */
	public function has(string $name): bool {
		return isset( $this->services[$name] );
	}

	/**
	 * Check if an element exists at the given offset
	 * @param  mixed  $offset Offset key
	 * @return bool
	 */
	public function offsetExists(mixed $offset): bool {
		return $this->has($offset);
	}

	/**
	 * Get an element at offset
	 * @param  mixed  $offset Offset key
	 * @return mixed
	 */
	public function offsetGet(mixed $offset): mixed {
		return $this->get($offset);
	}

	/**
	 * Set element at given offset
	 * @param  mixed  $offset Offset key
	 * @param  mixed  $value  Offset value
	 * @return void
	 */
	public function offsetSet(mixed $offset, mixed $value): void {
		$this->add($offset, false, $value);
	}

	/**
	 * Delete an element at given offset
	 * @param  mixed  $offset Offset key
	 * @return void
	 */
	public function offsetUnset(mixed $offset): void {
		$this->remove($offset);
	}

	/**
	 * Make a new instance using a reflector
	 * @param  ReflectionClass $reflector Reflector
	 * @param  array           $arguments Array of constructor arguments
	 * @return mixed
	 */
	protected function make(ReflectionClass $reflector, array $arguments = []) {
		$constructor = $reflector->getConstructor();
		if ($constructor) {
			# Resolve constructor parameters
			$parameters = $constructor->getParameters();
			$resolved = $this->resolve($parameters, $arguments);
			# Now call the constructor
			return $reflector->newInstanceArgs($resolved);
		} else {
			# Get a new instance
			return $reflector->newInstance();
		}
	}

	/**
	 * Resolve the dependencies
	 * @param  array  $parameters Array of parameters
	 * @param  array  $arguments  Array of constructor arguments
	 * @return array
	 */
	protected function resolve(array $parameters, array $arguments = []): array {
		$resolved = [];
		if ($parameters) {
			foreach ($parameters as $parameter) {
				# Get parameter type
				$type = $parameter->getType();
				$argument = $arguments[ $parameter->getName() ] ?? null;
				if (! $type->isBuiltin() ) {
					if ( $argument && get_class($argument) === $type->getName() ) {
						# Use the given argument
						$resolved[] = $argument;
					} else {
						# Resolve the dependency out of the container
						try {
							$dependency = new ReflectionClass( $type->getName() );
							$resolved[] = $this->get($dependency->name);
						} catch (Exception $e) {
							throw new ContainerException($this, "Can not resolve parameter '{$parameter->name}'");
						}
					}
				} else {
					# Built-in type
					if ( $argument ) {
						# Use the given argument
						$resolved[] = $argument;
					} else if ( $parameter->isDefaultValueAvailable() ) {
						# Use the default value
						$resolved[] = $parameter->getDefaultValue();
					} else {
						throw new ContainerException($this, "Can not resolve parameter '{$parameter->name}'");
					}
				}
			}
		}
		return $resolved;
	}

	/**
	 * Run initialization method calls
	 * @param  object $instance   Service instance
	 * @param  array  $decorators Array of decorator methods
	 */
	protected function decorate(object $instance, array $decorators): void {
		if ($decorators) {
			foreach ($decorators as $method => $arguments) {
				$callable = [$instance, $method];
				# Check if the method is callable
				if ( is_callable($callable) ) {
					# Just call the method
					call_user_func_array($callable, $arguments);
				} else {
					# Nope, throw an exception
					throw new ContainerException($this, "Method '{$method}' does not exist");
				}
			}
		}
	}
}

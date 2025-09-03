<?php

declare(strict_types = 1);

/**
 * Caldera Container
 * Container implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Container;

use ArrayAccess;
use Closure;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface, CallerInterface, ArrayAccess {

	/**
	 * Services array
	 * @var array<string, Service>
	 */
	protected array $services = [];

	/**
	 * Providers array
	 * @var array<ProviderInterface>
	 */
	protected array $providers = [];

	/**
	 * Registered providers array
	 * @var array<ProviderInterface>
	 */
	protected array $registered = [];

	/**
	 * Booted providers array
	 * @var array<ProviderInterface>
	 */
	protected array $booted = [];

	/**
	 * Add a service
	 * @param  string  $name     Service name
	 * @param  bool    $shared   Shared flag
	 * @param  mixed   $instance Service instance
	 * @return Service
	 */
	public function add(string $name, bool $shared = false, $instance = null): ?Service {
		if ( !$this->has($name) || !isset( $this->services[$name] ) ) {
			$service = new Service($shared, $instance);
			$this->services[$name] = $service;
			return $service;
		} else {
			return $this->services[$name];
		}
	}

	/**
	 * Add a new service provider
	 * @param  ProviderInterface $provider Service provider
	 * @return self
	 */
	public function provider(ProviderInterface $provider): self {
		if ($provider instanceof ContainerAwareInterface) {
			$provider->setContainer($this);
		}
		$provider->bootstrap();
		$this->providers[] = $provider;
		return $this;
	}

	/**
	 * Remove an entry
	 * @param string $name Service name
	 * @return self
	 */
	public function remove(string $name): self {
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
	public function get(string $name): mixed {
		if ( $this->has($name) ) {
			if ( $this->provides($name) ) {
				$this->register($name);
				if ( !$this->has($name) || !isset( $this->services[$name] ) ) {
					throw new ContainerException($this, "Service '{$name}' not provided by any registered provider");
				}
			}
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
				# An Interface with a registered implementation
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
				throw new ContainerException($this, "Service '{$name}' can not be instantiated"); // @codeCoverageIgnore
			}
		} else if ( class_exists($name) ) {
			# Fully-qualified class name, get a new instance
			$reflector = new ReflectionClass($name);
			$instance = $this->make($reflector);
			if ( is_object( $instance ) ) {
				return $instance;
			} else {
				throw new ContainerException($this, "Class '{$name}' can not be instantiated"); // @codeCoverageIgnore
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
		if ( isset( $this->services[$name] ) ) {
			return true;
		}
		if ( $this->provides($name) ) {
			return true;
		}
		return false;
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
	protected function make(ReflectionClass $reflector, array $arguments = []): mixed {
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
				if ( $type && !$type->isBuiltin() ) {
					if ( $argument && get_class($argument) === $type->getName() ) {
						# Use the given argument
						$resolved[] = $argument;
					} else {
						# Resolve the dependency out of the container
						try {
							$dependency = new ReflectionClass( $type->getName() );
							$resolved[] = $this->get($dependency->name);
						} catch (Exception $e) {
							throw new ContainerException($this, "Can not resolve parameter '{$parameter->name}'"); // @codeCoverageIgnore
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
						throw new ContainerException($this, "Can not resolve parameter '{$parameter->name}'"); // @codeCoverageIgnore
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

	/**
	 * Check if one of the service providers provide the given service
	 * @param  string $service Service name
	 * @return bool
	 */
	protected function provides(string $service): bool {
		if ($this->providers) {
			foreach ($this->providers as $provider) {
				if ( $provider->provides($service) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Register a service thorugh a service provider
	 * @param  string $service Service name
	 * @return void
	 */
	protected function register(string $service): void {
		if ($this->providers) {
			foreach ($this->providers as $provider) {
				$class = get_class($provider);
				if ( in_array($class, $this->registered) ) continue;
				if ( $provider->provides($service) ) {
					if (! in_array($class, $this->booted) ) {
						$this->booted[] = $class;
						$provider->boot();
					}
					$this->registered[] = $class;
					$provider->register();
					return;
				}
			}
		}
	}

    /**
     * Call a callable resolving its parameters
     * @param mixed $callable
     * @param array $arguments
     * @return mixed
     * @throws ReflectionException
     */
    public function call(mixed $callable, array $arguments = []): mixed {
        $reflector = $this->callableReflector($callable);
        # Resolve callable parameters
        $parameters = $reflector->getParameters();
        $resolved = $this->resolve($parameters, $arguments);
        # Sort the resolved arguments array
        ksort($resolved);
        # Call the callable
        return call_user_func_array($callable, $resolved);
    }

    /**
     * Get a reflector for a callable
     * @param  mixed $callable The callable to get a reflector for
     * @throws ReflectionException
     */
    protected function callableReflector(mixed $callable): ReflectionFunctionAbstract {
        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }
        if (is_array($callable)) {
            [$class, $method] = $callable;
            if (! method_exists($class, $method)) {
                throw new ContainerException($this, "Method '{$method}' does not exist");
            }
            return new ReflectionMethod($class, $method);
        }
        if (is_object($callable) && method_exists($callable, '__invoke')) {
            return new ReflectionMethod($callable, '__invoke');
        }
        if (is_string($callable) && function_exists($callable)) {
            return new ReflectionFunction($callable);
        }
        throw new ContainerException($this, "The specified value is not a callable");
    }
}

<?php

declare(strict_types = 1);

/**
 * Caldera Container
 * Container implementation, part of Vecode Caldera
 * @version 1.0
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Container;

use Exception;

use PHPUnit\Framework\TestCase;

use Caldera\Container\Container;
use Caldera\Container\ContainerException;
use Caldera\Container\NotFoundException;
use Caldera\Container\Service;

class ContainerTest extends TestCase {

	public function testGetUnknownService() {
		# Create container
		$container = new Container();
		try {
			$container->get('FooBarBaz');
			$this->fail('This must throw a DatabaseException');
		} catch (Exception $e) {
			$this->assertInstanceOf(NotFoundException::class, $e);
		}
	}

	public function testAddServiceAndThenRetrieveInstance() {
		# Create container
		$container = new Container();
		# Add simple service
		$container->add(Foo::class);
		# Check if service is inside container
		if ( $container->has(Foo::class) ) {
			# Try to retrieve service instance
			$instance = $container->get(Foo::class);
			$this->assertInstanceOf(Foo::class, $instance);
		} else {
			$this->fail('Does not have service registered');
		}
		# If you register the service again it just return the Service object
		$service = $container->add(Foo::class);
		$this->assertInstanceOf(Service::class, $service);
		$this->assertNull( $service->getInstance() );
	}

	public function testAddServiceAndThenRemoveIt() {
		# Create container
		$container = new Container();
		# Add simple service
		$container->add(Foo::class);
		# Make sure it has been added
		$this->assertTrue( $container->has(Foo::class) );
		# Remove it
		$container->remove(Foo::class);
		# Make sure it has been removed
		$this->assertFalse( $container->has(Foo::class) );
	}

	public function testAddServiceUsingFactoryFunction() {
		# Create container
		$container = new Container();
		# Add service using a factory function
		$container->add(Foo::class, false, function() {
			return new Foo();
		});
		# Try to retrieve service instance
		$instance = $container[Foo::class] ?? null;
		$this->assertInstanceOf(Foo::class, $instance);
	}

	public function testAddServiceUsingConcreteObject() {
		# Create container
		$container = new Container();
		# Add container service with the concrete object
		$container->add(Container::class, false, $container);
		# Retrieve instance from container and make sure its the same object
		$instance = $container[Container::class] ?? null;
		$this->assertInstanceOf(Container::class, $instance);
		$this->assertSame($container, $instance);
	}

	public function testAddServiceAndThenRetrieveInstanceWithArrayAccess() {
		# Create container
		$container = new Container();
		# Add a service with no implementation, using array access
		$container[Foo::class] = null;
		# Retrieve instance from container, using array access
		$instance = $container[Foo::class] ?? null;
		$this->assertInstanceOf(Foo::class, $instance);
		# Now remove it and make sure it has been removed
		unset( $container[Foo::class] );
		$this->assertFalse( isset( $container[Foo::class] ) );
	}

	public function testAddServiceUsingFactoryAndThenRetrieveInstanceWithArrayAccess() {
		# Create container
		$container = new Container();
		# Add service with factory function, using array access
		$container[Foo::class] = function() {
			return new Foo();
		};
		# Retrieve instance from container, using array access
		$instance = $container[Foo::class] ?? null;
		$this->assertInstanceOf(Foo::class, $instance);
	}

	public function testAddServiceUsingConcreteObjectAndThenRetrieveInstanceWithArrayAccess() {
		# Create container
		$container = new Container();
		# Add service with a concrete object, using array access
		$container[Container::class] = $container;
		# Retrieve instance from container, using array access
		$instance = $container[Container::class] ?? null;
		$this->assertInstanceOf(Container::class, $instance);
		$this->assertSame($container, $instance);
	}

	public function testAddSharedServiceAndThenRetrieveInstance() {
		# Create container
		$container = new Container();
		# Add shared service
		$service = $container->add(Foo::class, true);
		# Retrieve the service twice, it MUST be the same object
		$instance = $container->get(Foo::class);
		$other = $container->get(Foo::class);
		$this->assertSame($other, $instance);
		$this->assertSame($instance, $service->getInstance());
	}

	public function testAddServiceSetSharedFlagAndThenRetrieveInstance() {
		# Create container
		$container = new Container();
		# Add shared service
		$service = $container->add(Foo::class);
		$service->setShared(true);
		# Retrieve the service twice, it MUST be the same object
		$instance = $container->get(Foo::class);
		$other = $container->get(Foo::class);
		$this->assertSame($other, $instance);
		$this->assertSame($instance, $service->getInstance());
	}

	public function testAddSharedServiceUsingFactoryAndThenRetrieveInstance() {
		# Create container
		$container = new Container();
		# Add shared service using a factory function
		$container->add(Foo::class, true, function() {
			return new Container();
		});
		# Retrieve the service twice, it MUST be the same object
		$instance = $container->get(Foo::class);
		$other = $container->get(Foo::class);
		$this->assertSame($other, $instance);
	}

	public function testAddSharedServiceUsingConcreteObjectAndThenRetrieveInstance() {
		# Create container
		$container = new Container();
		# Add shared service using a concrete object
		$container->add(Container::class, true, $container);
		# Retrieve the service twice, it MUST be the same object AND also the concrete object
		$instance = $container->get(Container::class);
		$other = $container->get(Container::class);
		$this->assertSame($other, $instance);
		$this->assertSame($container, $instance);
	}

	public function testAddServiceWithCircularReference() {
		# Create container
		$container = new Container();
		# Add a service with a circular reference in its constructor
		$container->add(Bar::class);
		try {
			# Try to retrieve the instance, it MUST throw an exception
			$container->get(Bar::class);
			$this->fail('This must throw a ContainerException');
		} catch (ContainerException $e) {
			# Check the container reference
			$this->assertEquals($container, $e->getContainer());
		} catch (Exception $e) {
			$this->fail('This must be a ContainerException instance');
		}
	}

	public function testAddServiceWithAutoWiringDependencies() {
		# Create container
		$container = new Container();
		# Add a service that has a dependency, but don't add the dependency itself
		$container->add(Tap::class);
		# Retrieve the instance
		$instance = $container->get(Tap::class);
		$this->assertInstanceOf(Tap::class, $instance);
	}

	public function testAddServiceWithAutoWiringDependenciesAndConstructorArgument() {
		# Create container
		$container = new Container();
		# Add a service that has a dependency AND a constructor argument
		$num = 5;
		$container->add(Tap::class)->withArgument('num', $num);
		# Retrieve the instance
		$instance = $container->get(Tap::class);
		$this->assertInstanceOf(Tap::class, $instance);
		$this->assertEquals($num, $instance->getNum());
	}

	public function testAddServiceWithConstructorArguments() {
		# Create container
		$container = new Container();
		# Add a service that has two constructor arguments
		$num = 5;
		$foo = new Foo();
		$service = $container->add(Tap::class)
			->withArgument('foo', $foo)
			->withArgument('num', $num);
		# Check bound arguments
		$this->assertEquals($foo, $service->getArgument('foo'));
		$this->assertEquals($num, $service->getArgument('num'));
		# Retrieve the instance
		$instance = $container->get(Tap::class);
		$this->assertInstanceOf(Tap::class, $instance);
		$this->assertEquals($num, $instance->getNum());
	}

	public function testAddServiceWithDecoratorMethods() {
		# Create container
		$container = new Container();
		# Add a service that has a call to a decorator function
		$num = 5;
		$args = ['num' => $num];
		$service = $container->add(Tap::class)->withDecorator('setNum', $args);
		# Check bound decorators
		$this->assertEquals($args, $service->getDecorator('setNum'));
		# Retrieve the instance
		$instance = $container->get(Tap::class);
		$this->assertInstanceOf(Tap::class, $instance);
		$this->assertEquals($num, $instance->getNum());
	}

	public function testAddServiceWithDecoratorMethodsThatDoesNotExist() {
		# Create container
		$container = new Container();
		# Add a service that has a call to a decorator function
		$num = 5;
		$container->add(Tap::class)->withDecorator('putNum', ['num' => $num]);
		# Retrieve the instance
		try {
			$instance = $container->get(Tap::class);
			$this->fail('This must throw a ContainerException');
		} catch (Exception $e) {
			$this->assertInstanceOf(ContainerException::class, $e);
		}
	}

	public function testAddServiceWithInterfaceAndImplementation() {
		# Create container
		$container = new Container();
		# Add a service using an Interface and an Implementation class
		$container->add(Cuz::class, false, Baz::class);
		# Retrieve the instance and check that the type equals to the Implementation's type
		$instance = $container->get(Cuz::class);
		$this->assertInstanceOf(Baz::class, $instance);
	}
}

class Foo {

	# Empty class
}

class Bar {

	public function __construct(Bar $bar) {
		# Invalid for auto-wiring as this requires an instance of its same type
	}
}

interface Cuz {

	public function quz(): void;
}

class Baz implements Cuz {

	public function quz(): void {
		# Do nothing
	}
}

class Tap {

	protected $num = 0;

	public function __construct(Foo $foo, int $num = 0) {
		$this->num = $num;
	}

	public function setNum(int $num) {
		$this->num = $num;
	}

	public function getNum(): int {
		return $this->num;
	}
}
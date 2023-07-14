<?php

declare(strict_types = 1);

/**
 * Caldera Container
 * Container implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Container;

use Psr\Container\ContainerInterface;

interface ProviderInterface {

	/**
	 * Bootstrap service provider
	 */
	public function bootstrap(): void;

	/**
	 * Check if a service is provided by this provider
	 * @param  string $service Service name
	 * @return bool
	 */
	public function provides(string $service): bool;

	/**
	 * Register services
	 */
	public function register(): void;

	/**
	 * Boot service provider
	 */
	public function boot(): void;
}

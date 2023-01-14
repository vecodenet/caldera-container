<?php

declare(strict_types = 1);

/**
 * Caldera Container
 * Container implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Container;

use Caldera\Container\ContainerAware;
use Caldera\Container\ProviderInterface;

abstract class AbstractProvider implements ProviderInterface, ContainerAwareInterface {

	use ContainerAware;

	/**
	 * Map of provided classes
	 * @var array
	 */
	protected $provides = [];

	/**
	 * Check if a service is provided by this provider
	 * @param  string $service Service name
	 * @return bool
	 */
	public function provides(string $service): bool {
		return in_array($service, $this->provides);
	}
}

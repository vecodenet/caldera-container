<?php

declare(strict_types = 1);

/**
 * Caldera Container
 * Container implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Container;

use RuntimeException;

use Caldera\Container\Container;

interface ContainerAwareInterface {

	/**
	 * Set container instance
	 * @param Container $container Container instance
	 * @return $this
	 */
	public function setContainer(Container $container);

	/**
	 * Get container instance
	 * @return Container
	 */
	public function getContainer(): Container;
}

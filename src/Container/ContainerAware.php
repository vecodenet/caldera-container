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

trait ContainerAware {

	/**
	 * Container instance
	 * @var ?Container
	 */
	protected ?Container $container = null;

    /**
	 * Set container instance
	 * @param Container $container Container instance
	 * @return $this
	 */
	public function setContainer(Container $container) {
		$this->container = $container;
		return $this;
	}

	/**
	 * Get container instance
	 * @return Container
	 */
	public function getContainer(): Container {
		if ( $this->container instanceof Container ) {
			return $this->container;
		}
		throw new RuntimeException('Container instance not set');
	}
}

<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Container implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Container;

use LogicException;
use Throwable;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;

class ContainerException extends LogicException implements ContainerExceptionInterface {

	/**
	 * Container instance
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Constructor
	 * @param ContainerInterface $container Container instance
	 */
	public function __construct(ContainerInterface $container, string $message = '', int $code = 0, ?Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
		$this->container = $container;
	}

	/**
	 * Get container instance
	 * @return ContainerInterface
	 */
	public function getContainer(): ContainerInterface {
		return $this->container;
	}
}

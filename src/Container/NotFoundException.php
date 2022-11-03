<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Container implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Container;

use Psr\Container\NotFoundExceptionInterface;

use Caldera\Container\ContainerException;

class NotFoundException extends ContainerException implements NotFoundExceptionInterface { }

<?php
declare(strict_types = 1);

namespace Innmind\Compose\Exception;

use Psr\Container\NotFoundExceptionInterface;

class NotFound extends LogicException implements NotFoundExceptionInterface
{
}

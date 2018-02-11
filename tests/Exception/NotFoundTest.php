<?php
declare(strict_types = 1);

namespace Tests\Innmind\Compose\Exception;

use Innmind\Compose\Exception\{
    NotFound,
    LogicException
};
use PSr\Container\NotFoundExceptionInterface;
use PHPUnit\Framework\TestCase;

class NotFoundTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(LogicException::class, new NotFound);
        $this->assertInstanceOf(NotFoundExceptionInterface::class, new NotFound);
    }
}

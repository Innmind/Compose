<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Url\PathInterface;

interface Loader
{
    public function __invoke(PathInterface $definition): Definitions;
}

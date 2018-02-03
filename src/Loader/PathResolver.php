<?php
declare(strict_types = 1);

namespace Innmind\Compose\Loader;

use Innmind\Url\PathInterface;

interface PathResolver
{
    public function __invoke(PathInterface $from, PathInterface $to): PathInterface;
}

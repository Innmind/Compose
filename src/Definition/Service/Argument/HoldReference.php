<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\Definition\Name;

interface HoldReference
{
    public function reference(): Name;
}

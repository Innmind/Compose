<?php
declare(strict_types = 1);

namespace Innmind\Compose\Definition\Service\Argument;

use Innmind\Compose\Definition\Name;
use Innmind\Immutable\SetInterface;

interface HoldReferences
{
    /**
     * @return SetInterface<Name>
     */
    public function references(): SetInterface;
}

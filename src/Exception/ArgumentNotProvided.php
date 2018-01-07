<?php
declare(strict_types = 1);

namespace Innmind\Compose\Exception;

use Innmind\Compose\Definition\Argument;

class ArgumentNotProvided extends RuntimeException
{
    private $argument;

    public function __construct(Argument $argument)
    {
        $this->argument = $argument;
        parent::__construct();
    }

    public function argument(): Argument
    {
        return $this->argument;
    }
}

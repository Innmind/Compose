<?php
declare(strict_types = 1);

namespace Innmind\Compose\Loader\PathResolver;

use Innmind\Compose\{
    Loader\PathResolver,
    Exception\ValueNotSupported
};
use Innmind\Url\PathInterface;

final class Delegate implements PathResolver
{
    private $resolvers;

    public function __construct(PathResolver ...$resolvers)
    {
        $this->resolvers = $resolvers;
    }

    public function __invoke(PathInterface $from, PathInterface $to): PathInterface
    {
        foreach ($this->resolvers as $resolve) {
            try {
                return $resolve($from, $to);
            } catch (ValueNotSupported $e) {
                //pass
            }
        }

        throw new ValueNotSupported((string) $to);
    }
}

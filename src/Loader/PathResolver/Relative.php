<?php
declare(strict_types = 1);

namespace Innmind\Compose\Loader\PathResolver;

use Innmind\Compose\Loader\PathResolver;
use Innmind\Url\{
    PathInterface,
    Path
};
use Innmind\UrlResolver\{
    Path as ResolverPath,
    RelativePath
};
use Innmind\Immutable\Str;

final class Relative implements PathResolver
{
    public function __invoke(PathInterface $from, PathInterface $to): PathInterface
    {
        $target = Str::of((string) $to);

        if ((string) $target->substring(0, 1) === '/') {
            return $to;
        }

        $origin = Str::of((string) $from);
        $toRemove = 0;

        if ((string) $origin->substring(0, 1) !== '/') {
            $origin = $origin->prepend('/');
            ++$toRemove;
        }

        $target = (new ResolverPath((string) $origin))->pointingTo(
            new RelativePath((string) $to)
        );

        return new Path(
            (string) Str::of((string) $target)->substring($toRemove)
        );
    }
}

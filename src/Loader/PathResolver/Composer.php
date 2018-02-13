<?php
declare(strict_types = 1);

namespace Innmind\Compose\Loader\PathResolver;

use Innmind\Compose\{
    Loader\PathResolver,
    Exception\ValueNotSupported
};
use Innmind\Url\{
    PathInterface,
    Path
};
use Innmind\Immutable\{
    Str,
    Sequence
};
use Composer\Autoload\ClassLoader;

final class Composer implements PathResolver
{
    private $vendorPath;

    public function __construct()
    {
        $composer = Sequence::of(...get_declared_classes())->filter(static function(string $class): bool {
            return (string) Str::of($class)->substring(0, 22) === 'ComposerAutoloaderInit';
        });

        //use the last as there may be 2 classes, the first being the global autoloader
        $refl = new \ReflectionClass((string) $composer->last());
        $vendorPath = dirname(dirname($refl->getFileName()));

        $this->vendorPath = Str::of($vendorPath)->append('/');
    }

    public function __invoke(PathInterface $from, PathInterface $to): PathInterface
    {
        $target = Str::of((string) $to);

        if (!$target->matches('|^@[a-zA-Z0-9-_]+/[a-zA-Z0-9-_]+/.+|')) {
            throw new ValueNotSupported((string) $to);
        }

        return new Path(
            (string) $this->vendorPath->append((string) $target->substring(1))
        );
    }
}

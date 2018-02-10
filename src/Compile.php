<?php
declare(strict_types = 1);

namespace Innmind\Compose;

use Innmind\Compose\{
    Services,
    Loader
};
use Innmind\Url\PathInterface;
use Innmind\Immutable\{
    MapInterface,
    Set,
    Str
};
use Symfony\Component\Config\{
    ConfigCache,
    Resource\FileResource
};
use Psr\Container\ContainerInterface;

final class Compile
{
    private $cache;

    public function __construct(PathInterface $cache)
    {
        $this->cache = Str::of((string) $cache)->rightTrim('/');
    }

    /**
     * @param MapInterface<string, mixed> $arguments
     */
    public function __invoke(
        Loader $load,
        PathInterface $path,
        MapInterface $arguments
    ): ContainerInterface {
        $cachePath = sprintf(
            '%s/%s.php',
            $this->cache,
            md5((string) $path)
        );
        $cache = new ConfigCache($cachePath, false);

        if ($cache->isFresh()) {
            return require $cachePath;
        }

        $services = $load($path);
        $code = $this->generateCode($services);
        $cache->write($code, [new FileResource((string) $path)]);

        return require $cachePath;
    }

    private function generateCode(Services $services): string
    {
        $compiled = $services->compile();

        return <<<PHP
<?php
declare(strict_types = 1);

use Innmind\Compose\Exception\NotFound;
use Innmind\Immutable\MapInterface;
use Psr\Container\ContainerInterface;

return new class(\$arguments) implements ContainerInterface {
    private \$container;

    public function __construct(MapInterface \$arguments)
    {
        //wrapping is used to avoid access to public method of the real
        //compiled container, methods to build services are public so we
        //can access tunnelled arguments between dependencies
        \$this->container = $compiled
    }

    public function get(\$id): object
    {
        return \$this->container->get(\$id);
    }

    public function has(\$id): bool
    {
        return \$this->container->has(\$id);
    }
};

PHP;
    }
}

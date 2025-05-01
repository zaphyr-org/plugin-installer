<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstaller;

use Zaphyr\Framework\ApplicationPathResolver;
use Zaphyr\PluginInstaller\Exceptions\PluginInstallerException;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class PathResolver extends ApplicationPathResolver
{
    /**
     * @param string ...$paths
     *
     * @return string|null
     */
    public function concat(string ...$paths): ?string
    {
        if (empty($paths)) {
            return null;
        }

        $firstPath = array_shift($paths);

        return array_reduce(
            $paths,
            static fn(string $initialPath, string $nextPath): string => rtrim($initialPath, '/') . '/' . ltrim(
                $nextPath,
                '/'
            ),
            $firstPath
        );
    }

    /**
     * @param string $path
     *
     * @throws PluginInstallerException if the path contains an invalid key
     * @return string
     */
    public function resolve(string $path): string
    {
        foreach (array_keys($this->paths) as $searchString) {
            $methodName = 'get' . ucfirst($searchString) . 'Path';
            $path = str_replace("%$searchString%", $this->{$methodName}(), $path);
        }

        if (preg_match('/%([^%]+)%/', $path, $matches)) {
            throw new PluginInstallerException("'$matches[1]' is not a valid path resolver key.");
        }

        return $path;
    }
}

<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstaller;

use Zaphyr\PluginInstaller\Exceptions\PluginInstallerException;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class PathResolver
{
    /**
     * @var array<string, string>
     */
    protected array $paths = [
        'app' => 'app/',
        'bin' => 'bin/',
        'config' => 'config/',
        'public' => 'public/',
        'resources' => 'resources/',
        'storage' => 'storage/',
    ];

    /**
     * @param string[] $paths
     */
    public function __construct(array $paths)
    {
        $this->paths = array_merge($this->paths, $paths);
    }

    /**
     * @param string ...$paths
     *
     * @return string
     */
    public function concat(string ...$paths): string
    {
        if (empty($paths)) {
            return '';
        }

        $firstPath = array_shift($paths);

        return array_reduce(
            $paths,
            static fn(string $initialPath, string $nextPath): string => rtrim($initialPath, '/') . '/' . trim(
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
        if (preg_match('/%([^%]+)%/', $path, $match)) {
            [$placeholder, $key] = $match;

            if (!isset($this->paths[$key])) {
                throw new PluginInstallerException("'$key' is not a valid path resolver key.");
            }

            if ($key !== 'root') {
                $tempPath = str_replace($placeholder, trim($this->paths[$key], '/'), $path);

                return $this->concat($this->paths['root'], $tempPath);
            }

            return str_replace($placeholder, $this->paths[$key], $path);
        }

        return $path;
    }
}

<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstaller\Types;

use Composer\Package\PackageInterface;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class Plugin
{
    /**
     * @param PackageInterface $package
     */
    public function __construct(private readonly PackageInterface $package)
    {
    }

    /**
     * @return PackageInterface
     */
    public function getPackage(): PackageInterface
    {
        return $this->package;
    }

    /**
     * @param string $type
     *
     * @return mixed[]
     */
    public function getExtra(string $type): array
    {
        return $this->package->getExtra()[$type] ?? [];
    }

    /**
     * @return array<string, string[]>
     */
    public function getClasses(): array
    {
        return $this->getExtra('plugin-classes');
    }

    /**
     * @return array<string, string>
     */
    public function getCopyPaths(): array
    {
        return $this->getExtra('copy');
    }
}

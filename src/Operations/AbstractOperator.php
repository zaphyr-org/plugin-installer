<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstaller\Operations;

use Zaphyr\Framework\Contracts\ApplicationPathResolverInterface;
use Zaphyr\PluginInstaller\Types\Plugin;
use Zaphyr\PluginInstaller\Types\PluginUpdate;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
abstract class AbstractOperator
{
    /**
     * @param ApplicationPathResolverInterface $applicationPathResolver
     */
    public function __construct(protected readonly ApplicationPathResolverInterface $applicationPathResolver)
    {
    }

    /**
     * @param Plugin $plugin
     *
     * @return void
     */
    abstract public function install(Plugin $plugin): void;

    /**
     * @param PluginUpdate $pluginUpdate
     *
     * @return void
     */
    abstract public function update(PluginUpdate $pluginUpdate): void;

    /**
     * @param Plugin $plugin
     *
     * @return void
     */
    abstract public function uninstall(Plugin $plugin): void;
}

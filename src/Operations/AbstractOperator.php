<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstaller\Operations;

use Composer\Composer;
use Composer\IO\IOInterface;
use Zaphyr\PluginInstaller\PathResolver;
use Zaphyr\PluginInstaller\Types\Plugin;
use Zaphyr\PluginInstaller\Types\PluginUpdate;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
abstract class AbstractOperator
{
    /**
     * @param Composer     $composer
     * @param IOInterface  $io
     * @param PathResolver $pathResolver
     */
    public function __construct(
        protected Composer $composer,
        protected IOInterface $io,
        protected readonly PathResolver $pathResolver
    ) {
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

    /**
     * @param string $message
     *
     * @return void
     */
    protected function writeWarning(string $message): void
    {
        $this->io->writeError("<warning>$message</warning>");
    }
}

<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstaller;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class PluginUpdate
{
    /**
     * @param Plugin $currentPlugin
     * @param Plugin $newPlugin
     */
    public function __construct(protected readonly Plugin $currentPlugin, protected readonly Plugin $newPlugin)
    {
    }

    /**
     * @return Plugin
     */
    public function getCurrentPlugin(): Plugin
    {
        return $this->currentPlugin;
    }

    /**
     * @return Plugin
     */
    public function getNewPlugin(): Plugin
    {
        return $this->newPlugin;
    }
}

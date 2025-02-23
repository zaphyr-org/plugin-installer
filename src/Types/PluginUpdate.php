<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstaller\Types;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class PluginUpdate
{
    /**
     * @param Plugin $currentPlugin
     * @param Plugin $newPlugin
     */
    public function __construct(private readonly Plugin $currentPlugin, private readonly Plugin $newPlugin)
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

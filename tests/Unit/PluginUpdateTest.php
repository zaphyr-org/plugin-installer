<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstallerTests\Unit;

use Composer\Package\PackageInterface;
use PHPUnit\Framework\TestCase;
use Zaphyr\PluginInstaller\Plugin;
use Zaphyr\PluginInstaller\PluginUpdate;

class PluginUpdateTest extends TestCase
{
    protected Plugin $currentPlugin;

    protected Plugin $newPlugin;

    protected PluginUpdate $pluginUpdate;

    protected function setUp(): void
    {
        $packageMock = $this->createMock(PackageInterface::class);
        $this->currentPlugin = new Plugin($packageMock);
        $this->newPlugin = new Plugin($packageMock);
        $this->pluginUpdate = new PluginUpdate($this->currentPlugin, $this->newPlugin);
    }

    protected function tearDown(): void
    {
        unset($this->currentPlugin, $this->newPlugin, $this->pluginUpdate);
    }

    /* -------------------------------------------------
     * GET CURRENT PLUGIN
     * -------------------------------------------------
     */

    public function testGetCurrentPlugin(): void
    {
        self::assertSame($this->currentPlugin, $this->pluginUpdate->getCurrentPlugin());
    }

    /* -------------------------------------------------
     * GET NEW PLUGIN
     * -------------------------------------------------
     */

    public function testGetNewPlugin(): void
    {
        self::assertSame($this->newPlugin, $this->pluginUpdate->getNewPlugin());
    }
}

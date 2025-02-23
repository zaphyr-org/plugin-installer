<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstallerTests\Unit\Types;

use Composer\Package\PackageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zaphyr\PluginInstaller\Types\Plugin;

class PluginTest extends TestCase
{
    protected PackageInterface&MockObject $packageMock;

    protected Plugin $plugin;

    protected function setUp(): void
    {
        $this->packageMock = $this->createMock(PackageInterface::class);
        $this->plugin = new Plugin($this->packageMock);
    }

    protected function tearDown(): void
    {
        unset($this->plugin, $this->packageMock);
    }

    /* -------------------------------------------------
     * HAS EXTRA
     * -------------------------------------------------
     */

    public function testHasExtra(): void
    {
        $this->packageMock->method('getExtra')
            ->willReturn(['plugin-classes' => []]);

        self::assertTrue($this->plugin->hasExtra('plugin-classes'));
        self::assertFalse($this->plugin->hasExtra('foo'));
    }

    /* -------------------------------------------------
     * GET EXTRA
     * -------------------------------------------------
     */

    public function testGetExtra(): void
    {
        $this->packageMock->method('getExtra')
            ->willReturn(['plugin-classes' => ['foo' => 'bar']]);

        self::assertSame(['foo' => 'bar'], $this->plugin->getExtra('plugin-classes'));
        self::assertSame([], $this->plugin->getExtra('foo'));
    }

    /* -------------------------------------------------
     * GET CLASSES
     * -------------------------------------------------
     */

    public function testGetClasses(): void
    {
        $this->packageMock->method('getExtra')
            ->willReturn(['plugin-classes' => ['foo' => 'bar']]);

        self::assertSame(['foo' => 'bar'], $this->plugin->getClasses());
        self::assertSame([], $this->plugin->getExtra('foo'));
    }
}

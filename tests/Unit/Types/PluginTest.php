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
     * GET PACKAGE
     * -------------------------------------------------
     */

    public function testGetPackage(): void
    {
        self::assertSame($this->packageMock, $this->plugin->getPackage());
    }

    /* -------------------------------------------------
     * GET NAME
     * -------------------------------------------------
     */

    public function testGetName(): void
    {
        $this->packageMock->method('getName')
            ->willReturn('foo/bar');

        self::assertSame('foo/bar', $this->plugin->getName());
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
    }

    /* -------------------------------------------------
     * GET COPY PATHS
     * -------------------------------------------------
     */

    public function testGetCopyPaths(): void
    {
        $this->packageMock->method('getExtra')
            ->willReturn(['copy' => ['foo' => 'bar']]);

        self::assertSame(['foo' => 'bar'], $this->plugin->getCopyPaths());
    }

    /* -------------------------------------------------
     * GET ENV VARS
     * -------------------------------------------------
     */

    public function testGetEnvVars(): void
    {
        $this->packageMock->method('getExtra')
            ->willReturn(['env' => ['foo' => 'bar']]);

        self::assertSame(['foo' => 'bar'], $this->plugin->getEnvVars());
    }
}

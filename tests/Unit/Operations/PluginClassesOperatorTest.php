<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstallerTests\Unit\Operations;

use Composer\Composer;
use Composer\IO\IOInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Zaphyr\PluginInstaller\Operations\PluginClassesOperator;
use Zaphyr\PluginInstaller\PathResolver;
use Zaphyr\PluginInstaller\Plugin;
use Zaphyr\PluginInstaller\PluginUpdate;

class PluginClassesOperatorTest extends TestCase
{
    protected PathResolver&MockObject $pathResolverMock;

    protected Plugin&MockObject $pluginMock;

    protected PluginUpdate&MockObject $pluginUpdateMock;

    protected PluginClassesOperator $pluginClassesOperator;

    protected string $pluginClassesDir = 'plugins';

    protected string $pluginClassesFile = 'plugins/classes.yml';

    protected function setUp(): void
    {
        $this->pathResolverMock = $this->createMock(PathResolver::class);
        $this->pluginMock = $this->createMock(Plugin::class);
        $this->pluginUpdateMock = $this->createMock(PluginUpdate::class);

        $composerMock = $this->createMock(Composer::class);
        $ioMock = $this->createMock(IOInterface::class);

        $this->pluginClassesOperator = new PluginClassesOperator($composerMock, $ioMock, $this->pathResolverMock);
    }

    protected function tearDown(): void
    {
        unset(
            $this->pathResolverMock,
            $this->pluginMock,
            $this->pluginUpdateMock,
            $this->pluginClassesOperator
        );

        if (file_exists($this->pluginClassesFile)) {
            unlink($this->pluginClassesFile);
        }

        if (file_exists($this->pluginClassesDir)) {
            rmdir($this->pluginClassesDir);
        }
    }

    /* -------------------------------------------------
     * INSTALL
     * -------------------------------------------------
     */

    public function testInstall(): void
    {
        $this->pathResolverMock
            ->method('getConfigPath')
            ->willReturnCallback(fn($key) => match (true) {
                $key === $this->pluginClassesDir => $this->pluginClassesDir,
                $key === $this->pluginClassesFile => $this->pluginClassesFile
            });

        $this->pluginMock->expects(self::once())
            ->method('getClasses')
            ->willReturn([
                'Acme\\AllClass\\' => ['all'],
                'Acme\\DevClass\\' => ['development'],
                'Acme\\ProdClass\\' => ['production'],
                'Acme\\TestClass\\' => ['testing']
            ]);

        $this->pluginClassesOperator->install($this->pluginMock);

        $pluginConfigData = Yaml::parseFile($this->pluginClassesFile);

        self::assertEquals('Acme\\AllClass', $pluginConfigData['all'][0]);
        self::assertEquals('Acme\\DevClass', $pluginConfigData['development'][0]);
        self::assertEquals('Acme\\ProdClass', $pluginConfigData['production'][0]);
        self::assertEquals('Acme\\TestClass', $pluginConfigData['testing'][0]);
    }

    /* -------------------------------------------------
     * UPDATE
     * -------------------------------------------------
     */

    public function testUpdate(): void
    {
        $this->pathResolverMock
            ->method('getConfigPath')
            ->willReturnCallback(fn($key) => match (true) {
                $key === $this->pluginClassesDir => $this->pluginClassesDir,
                $key === $this->pluginClassesFile => $this->pluginClassesFile
            });

        $currentPluginMock = $this->pluginMock;
        $currentPluginMock->expects(self::once())
            ->method('getClasses')
            ->willReturn([
                'Acme\\ExistingClass' => ['all'],
                'Acme\\DeletedClass' => ['all']
            ]);

        $newPluginMock = $this->createMock(Plugin::class);
        $newPluginMock->expects(self::once())
            ->method('getClasses')
            ->willReturn([
                'Acme\\ExistingClass' => ['all'],
                'Acme\\NewClass' => ['all']
            ]);

        $this->pluginUpdateMock->expects(self::once())
            ->method('getCurrentPlugin')
            ->willReturn($this->pluginMock);

        $this->pluginUpdateMock->expects(self::once())
            ->method('getNewPlugin')
            ->willReturn($newPluginMock);

        $this->pluginClassesOperator->update($this->pluginUpdateMock);

        $pluginConfigData = Yaml::parseFile($this->pluginClassesFile);

        self::assertNotContains('Acme\\DeletedClass', $pluginConfigData['all']);
        self::assertEquals('Acme\\ExistingClass', $pluginConfigData['all'][0]);
        self::assertEquals('Acme\\NewClass', $pluginConfigData['all'][1]);
    }

    /* -------------------------------------------------
     * UNINSTALL
     * -------------------------------------------------
     */

    public function testUninstall(): void
    {
        $this->pathResolverMock
            ->method('getConfigPath')
            ->willReturnCallback(fn($key) => match (true) {
                $key === $this->pluginClassesDir => $this->pluginClassesDir,
                $key === $this->pluginClassesFile => $this->pluginClassesFile
            });

        $this->pluginMock->expects(self::once())
            ->method('getClasses')
            ->willReturn([
                'Acme\\ExistingClass' => ['all'],
                'Acme\\DeletedClass' => ['all']
            ]);

        $this->pluginClassesOperator->uninstall($this->pluginMock);

        self::assertFileDoesNotExist($this->pluginClassesFile);
    }
}

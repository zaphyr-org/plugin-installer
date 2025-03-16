<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstallerTests\Operations;

use Composer\Composer;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zaphyr\PluginInstaller\Exceptions\PluginInstallerException;
use Zaphyr\PluginInstaller\Operations\CopyOperator;
use Zaphyr\PluginInstaller\PathResolver;
use Zaphyr\PluginInstaller\Types\Plugin;
use Zaphyr\PluginInstaller\Types\PluginUpdate;
use Zaphyr\Utils\File;

class CopyOperatorTest extends TestCase
{
    protected string $sourceDir;

    protected string $targetDir;

    protected InstallationManager&MockObject $installationManagerMock;

    protected Composer&MockObject $composerMock;

    protected IOInterface&MockObject $ioMock;

    protected Plugin&MockObject $pluginMock;

    protected PluginUpdate&MockObject $pluginUpdateMock;

    protected PackageInterface&MockObject $packageMock;

    protected PathResolver $pathResolver;

    protected CopyOperator $copyOperator;

    protected function setUp(): void
    {
        $this->sourceDir = dirname(__DIR__, 2) . '/TestAssets';
        $this->targetDir = __DIR__ . '/config';
        @mkdir($this->targetDir);

        $this->installationManagerMock = $this->createMock(InstallationManager::class);
        $this->composerMock = $this->createMock(Composer::class);
        $this->ioMock = $this->createMock(IOInterface::class);
        $this->pluginMock = $this->createMock(Plugin::class);
        $this->pluginUpdateMock = $this->createMock(PluginUpdate::class);
        $this->packageMock = $this->createMock(PackageInterface::class);

        $this->pathResolver = new PathResolver(['root' => __DIR__]);

        $this->copyOperator = new CopyOperator(
            $this->composerMock,
            $this->ioMock,
            $this->pathResolver
        );
    }

    protected function setupMockMethods(): void
    {
        $this->installationManagerMock->expects(self::once())
            ->method('getInstallPath')
            ->with($this->packageMock)
            ->willReturn($this->sourceDir);

        $this->composerMock->expects(self::once())
            ->method('getInstallationManager')
            ->willReturn($this->installationManagerMock);

        $this->pluginMock->expects($this->once())
            ->method('getPackage')
            ->willReturn($this->packageMock);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->targetDir);

        unset(
            $this->sourceDir,
            $this->targetDir,
            $this->installationManagerMock,
            $this->composerMock,
            $this->ioMock,
            $this->pluginMock,
            $this->packageMock,
            $this->copyOperators
        );
    }

    /* -------------------------------------------------
     * INSTALL
     * -------------------------------------------------
     */

    public function testInstall(): void
    {
        $this->setupMockMethods();

        $this->pluginMock->expects(self::once())
            ->method('getCopyPaths')
            ->willReturn([
                'config/' => '%config%',
                'config/baz.yaml' => '%config%/plugins/baz.yaml',
            ]);

        $this->copyOperator->install($this->pluginMock);

        self::assertFileExists($this->targetDir . '/plugins/foo.yaml');
        self::assertFileExists($this->targetDir . '/plugins/subdir/bar.yaml');
        self::assertFileExists($this->targetDir . '/plugins/baz.yaml');
    }

    public function testInstallOverwriteExistingFiles(): void
    {
        $this->setupMockMethods();

        file_put_contents($this->targetDir . '/overwrite.yaml', 'existing: content');

        $this->pluginMock->expects(self::once())
            ->method('getCopyPaths')
            ->willReturn([
                'config/baz.yaml' => '%config%/overwrite.yaml',
            ]);

        $this->ioMock->expects(self::once())
            ->method('askConfirmation')
            ->willReturn(true);

        $this->copyOperator->install($this->pluginMock);

        self::assertEquals("baz: baz\n", file_get_contents($this->targetDir . '/overwrite.yaml'));
    }

    public function testInstallSkipExistingFiles(): void
    {
        $this->setupMockMethods();

        file_put_contents($this->targetDir . '/skip.yaml', 'existing: content');

        $this->pluginMock->expects(self::once())
            ->method('getCopyPaths')
            ->willReturn([
                'config/baz.yaml' => '%config%/skip.yaml',
            ]);

        $this->ioMock->expects(self::once())
            ->method('askConfirmation')
            ->willReturn(false);

        $this->copyOperator->install($this->pluginMock);

        self::assertEquals("existing: content", file_get_contents($this->targetDir . '/skip.yaml'));
    }

    public function testInstallThrowsExceptionOnNonExistingPluginDir(): void
    {
        $this->expectException(PluginInstallerException::class);

        $package = $this->createMock(PackageInterface::class);
        $pluginMock = $this->createMock(Plugin::class);
        $pluginMock->expects($this->once())
            ->method('getPackage')
            ->willReturn($package);

        $installationManagerMock = $this->createMock(InstallationManager::class);
        $installationManagerMock
            ->method('getInstallPath')
            ->with($this->packageMock)
            ->willReturn(null);

        $composerMock = $this->createMock(Composer::class);
        $composerMock->expects(self::once())
            ->method('getInstallationManager')
            ->willReturn($installationManagerMock);

        $pathResolver = new PathResolver(['root' => __DIR__]);
        (new CopyOperator($composerMock, $this->ioMock, $pathResolver))->install($pluginMock);
    }

    public function testInstallThrowsExceptionOnMissingSourceFile(): void
    {
        $this->setupMockMethods();

        $this->expectException(PluginInstallerException::class);

        $this->pluginMock->expects(self::once())
            ->method('getCopyPaths')
            ->willReturn([
                'config/missing.yaml' => '%config%/plugins/missing.yaml',
            ]);

        $this->copyOperator->install($this->pluginMock);
    }

    /* -------------------------------------------------
     * UPDATE
     * -------------------------------------------------
     */

    public function testUpdate(): void
    {
        $this->setupMockMethods();

        $this->pluginMock->expects(self::once())
            ->method('getCopyPaths')
            ->willReturn([
                'config/' => '%config%',
                'config/baz.yaml' => '%config%/plugins/baz.yaml',
            ]);

        $this->pluginUpdateMock->expects(self::once())
            ->method('getNewPlugin')
            ->willReturn($this->pluginMock);

        $this->copyOperator->update($this->pluginUpdateMock);

        self::assertFileExists($this->targetDir . '/plugins/foo.yaml');
        self::assertFileExists($this->targetDir . '/plugins/subdir/bar.yaml');
        self::assertFileExists($this->targetDir . '/plugins/baz.yaml');
    }

    public function testUpdateOverwriteExistingFiles(): void
    {
        $this->setupMockMethods();

        file_put_contents($this->targetDir . '/overwrite.yaml', 'existing: content');

        $this->pluginMock->expects(self::once())
            ->method('getCopyPaths')
            ->willReturn([
                'config/baz.yaml' => '%config%/overwrite.yaml',
            ]);

        $this->pluginUpdateMock->expects(self::once())
            ->method('getNewPlugin')
            ->willReturn($this->pluginMock);

        $this->ioMock->expects(self::once())
            ->method('askConfirmation')
            ->willReturn(true);

        $this->copyOperator->update($this->pluginUpdateMock);

        self::assertEquals("baz: baz\n", file_get_contents($this->targetDir . '/overwrite.yaml'));
    }


    public function testUpdateSkipExistingFiles(): void
    {
        $this->setupMockMethods();

        file_put_contents($this->targetDir . '/skip.yaml', 'existing: content');

        $this->pluginMock->expects(self::once())
            ->method('getCopyPaths')
            ->willReturn([
                'config/baz.yaml' => '%config%/skip.yaml',
            ]);

        $this->pluginUpdateMock->expects(self::once())
            ->method('getNewPlugin')
            ->willReturn($this->pluginMock);

        $this->ioMock->expects(self::once())
            ->method('askConfirmation')
            ->willReturn(false);

        $this->copyOperator->update($this->pluginUpdateMock);

        self::assertEquals("existing: content", file_get_contents($this->targetDir . '/skip.yaml'));
    }

    public function testUpdateCheckIfSourceFileContentsChanged(): void
    {
        $this->setupMockMethods();

        file_put_contents($this->targetDir . '/baz.yaml', "baz: baz\n");

        $this->pluginMock->expects(self::once())
            ->method('getCopyPaths')
            ->willReturn([
                'config/baz.yaml' => '%config%/baz.yaml',
            ]);

        $this->pluginUpdateMock->expects(self::once())
            ->method('getNewPlugin')
            ->willReturn($this->pluginMock);

        $this->copyOperator->update($this->pluginUpdateMock);

        self::assertEquals("baz: baz\n", file_get_contents($this->targetDir . '/baz.yaml'));
    }

    /* -------------------------------------------------
     * UNINSTALL
     * -------------------------------------------------
     */

    public function testUninstall(): void
    {
        $this->setupMockMethods();

        mkdir($this->targetDir . '/plugins/subdir', recursive: true);
        file_put_contents($this->targetDir . '/baz.yaml', 'baz: baz');

        $this->pluginMock->expects(self::once())
            ->method('getCopyPaths')
            ->willReturn([
                'config/' => '%config%',
                'config/baz.yaml' => '%config%/baz.yaml',
            ]);

        $this->copyOperator->uninstall($this->pluginMock);

        self::assertFileDoesNotExist($this->targetDir . '/baz.yaml');
    }
}

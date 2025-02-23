<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstallerTests\Unit;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zaphyr\Framework\Contracts\ApplicationPathResolverInterface;
use Zaphyr\PluginInstaller\PluginInstaller;

class PluginInstallerTest extends TestCase
{
    protected ApplicationPathResolverInterface&MockObject $applicationPathResolverMock;

    protected Composer&MockObject $composerMock;

    protected IOInterface&MockObject $ioMock;

    protected PackageEvent&MockObject $packageEventMock;

    protected Package&MockObject $packageMock;

    protected InstallOperation&MockObject $installOperationMock;

    protected UpdateOperation&MockObject $updateOperationMock;

    protected PluginInstaller $pluginInstaller;

    protected function setUp(): void
    {
        $this->applicationPathResolverMock = $this->createMock(ApplicationPathResolverInterface::class);
        $this->composerMock = $this->createMock(Composer::class);
        $this->ioMock = $this->createMock(IOInterface::class);
        $this->packageEventMock = $this->createMock(PackageEvent::class);
        $this->packageMock = $this->createMock(Package::class);
        $this->installOperationMock = $this->createMock(InstallOperation::class);
        $this->updateOperationMock = $this->createMock(UpdateOperation::class);

        $this->pluginInstaller = new PluginInstaller();
        $this->pluginInstaller->activate($this->composerMock, $this->ioMock);
    }

    protected function tearDown(): void
    {
        unset(
            $this->applicationPathResolverMock,
            $this->composerMock,
            $this->ioMock,
            $this->packageEventMock,
            $this->packageMock,
            $this->installOperationMock,
            $this->updateOperationMock,
            $this->pluginInstaller
        );
    }

    /* -------------------------------------------------
     * INSTALL PLUGIN
     * -------------------------------------------------
     */

    public function testInstallPlugin(): void
    {
        $this->packageEventMock->expects(self::once())
            ->method('getOperation')
            ->willReturn($this->installOperationMock);

        $this->installOperationMock->expects(self::once())
            ->method('getPackage')
            ->willReturn($this->packageMock);

        $this->packageMock->expects(self::once())
            ->method('getType')
            ->willReturn('zaphyr-plugin');

        $this->pluginInstaller->installPlugin($this->packageEventMock);
    }

    public function testInstallPluginWithNonZaphyrPluginPackage(): void
    {
        $this->packageEventMock->expects(self::once())
            ->method('getOperation')
            ->willReturn($this->installOperationMock);

        $this->installOperationMock->expects(self::once())
            ->method('getPackage')
            ->willReturn($this->packageMock);

        $this->packageMock->expects(self::once())
            ->method('getType')
            ->willReturn('non-zaphyr-plugin');

        $this->pluginInstaller->installPlugin($this->packageEventMock);
    }

    /* -------------------------------------------------
     * UPDATE PLUGIN
     * -------------------------------------------------
     */

    public function testUpdatePlugin(): void
    {
        $this->packageEventMock->expects(self::once())
            ->method('getOperation')
            ->willReturn($this->updateOperationMock);

        $this->updateOperationMock->expects(self::once())
            ->method('getTargetPackage')
            ->willReturn($this->packageMock);

        $this->packageMock->expects(self::once())
            ->method('getType')
            ->willReturn('zaphyr-plugin');

        $this->updateOperationMock->expects(self::once())
            ->method('getInitialPackage')
            ->willReturn($this->packageMock);

        $this->pluginInstaller->updatePlugin($this->packageEventMock);
    }

    public function testUpdatePluginWithNonZaphyrPluginPackage(): void
    {
        $this->packageEventMock->expects(self::once())
            ->method('getOperation')
            ->willReturn($this->updateOperationMock);

        $this->updateOperationMock->expects(self::once())
            ->method('getTargetPackage')
            ->willReturn($this->packageMock);

        $this->packageMock->expects(self::once())
            ->method('getType')
            ->willReturn('non-zaphyr-plugin');

        $this->updateOperationMock->expects(self::never())->method('getInitialPackage');

        $this->pluginInstaller->updatePlugin($this->packageEventMock);
    }

    /* -------------------------------------------------
     * UNINSTALL PLUGIN
     * -------------------------------------------------
     */

    public function testUninstallPlugin(): void
    {
        $this->packageEventMock->expects(self::once())
            ->method('getOperation')
            ->willReturn($this->installOperationMock);

        $this->installOperationMock->expects(self::once())
            ->method('getPackage')
            ->willReturn($this->packageMock);

        $this->packageMock->expects(self::once())
            ->method('getType')
            ->willReturn('zaphyr-plugin');

        $this->pluginInstaller->uninstallPlugin($this->packageEventMock);
    }

    /* -------------------------------------------------
     * SUBSCRIBED EVENTS
     * -------------------------------------------------
     */

    public function testSubscribedEvents(): void
    {
        $subscribedEvents = PluginInstaller::getSubscribedEvents();

        self::assertEquals('installPlugin', $subscribedEvents['post-package-install']);
        self::assertEquals('updatePlugin', $subscribedEvents['post-package-update']);
        self::assertEquals('uninstallPlugin', $subscribedEvents['post-package-uninstall']);
    }
}

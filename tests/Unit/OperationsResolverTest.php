<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstallerTests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zaphyr\Framework\Contracts\ApplicationPathResolverInterface;
use Zaphyr\PluginInstaller\Operations\PluginClassesOperator;
use Zaphyr\PluginInstaller\OperationsResolver;
use Zaphyr\PluginInstaller\Types\Plugin;
use Zaphyr\PluginInstaller\Types\PluginUpdate;

class OperationsResolverTest extends TestCase
{
    protected ApplicationPathResolverInterface&MockObject $applicationPathResolverMock;

    protected Plugin&MockObject $pluginMock;

    protected PluginUpdate&MockObject $pluginUpdateMock;

    protected OperationsResolver $operationsResolver;

    protected function setUp(): void
    {
        $this->applicationPathResolverMock = $this->createMock(ApplicationPathResolverInterface::class);
        $this->pluginMock = $this->createMock(Plugin::class);
        $this->pluginUpdateMock = $this->createMock(PluginUpdate::class);

        $this->operationsResolver = new OperationsResolver($this->applicationPathResolverMock, [
            'test-operator' => PluginClassesOperator::class,
        ]);
    }

    protected function tearDown(): void
    {
        unset($this->applicationPathResolverMock, $this->pluginMock, $this->pluginUpdateMock, $this->operationsResolver);
    }

    /* -------------------------------------------------
     * INSTALL
     * -------------------------------------------------
     */

    public function testInstall(): void
    {
        $this->pluginMock->expects(self::once())
            ->method('hasExtra')
            ->with('test-operator')
            ->willReturn(true);

        $this->operationsResolver->install($this->pluginMock);
    }

    /* -------------------------------------------------
     * UPDATE
     * -------------------------------------------------
     */

    public function testUpdate(): void
    {
        $this->pluginUpdateMock->method('getCurrentPlugin')
            ->willReturn($this->pluginMock);

        $this->pluginUpdateMock->method('getNewPlugin')
            ->willReturn($this->pluginMock);

        $this->pluginMock->expects(self::exactly(2))
            ->method('hasExtra')
            ->with('test-operator')
            ->willReturn(true);

        $this->operationsResolver->update($this->pluginUpdateMock);
    }

    /* -------------------------------------------------
     * UNINSTALL
     * -------------------------------------------------
     */

    public function testUninstall(): void
    {
        $this->pluginMock->expects(self::once())
            ->method('hasExtra')
            ->with('test-operator')
            ->willReturn(true);

        $this->operationsResolver->uninstall($this->pluginMock);
    }
}

<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstallerTests\Unit\Operations;

use Composer\Composer;
use Composer\IO\IOInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zaphyr\PluginInstaller\Operations\EnvOperator;
use Zaphyr\PluginInstaller\PathResolver;
use Zaphyr\PluginInstaller\Plugin;
use Zaphyr\PluginInstaller\PluginUpdate;

class EnvOperatorTest extends TestCase
{
    protected string $envFile;

    protected Plugin&MockObject $pluginMock;

    protected PluginUpdate&MockObject $pluginUpdateMock;

    protected EnvOperator $operator;

    protected function setUp(): void
    {
        $this->envFile = __DIR__ . '/.env';
        file_put_contents($this->envFile, '');

        $this->pluginMock = $this->createMock(Plugin::class);
        $this->pluginMock->method('getName')->willReturn('zaphyr/foo-plugin');

        $this->pluginUpdateMock = $this->createMock(PluginUpdate::class);

        $composerMock = $this->createMock(Composer::class);
        $ioMock = $this->createMock(IOInterface::class);

        $pathResolver = new PathResolver(['root' => __DIR__]);

        $this->envOperator = new EnvOperator($composerMock, $ioMock, $pathResolver);
    }

    protected function tearDown(): void
    {
        @unlink($this->envFile);

        unset(
            $this->pluginMock,
            $this->pluginUpdateMock,
            $this->envOperator
        );
    }

    /* -------------------------------------------------
     * INSTALL
     * -------------------------------------------------
     */

    public function testInstall(): void
    {
        $envContents = <<<EOF

### start-plugin-config:zaphyr/foo-plugin ###
FOO=bar
BAZ="path with spaces & special \"chars\""
### end-plugin-config:zaphyr/foo-plugin ###

EOF;

        $this->pluginMock->method('getEnvVars')->willReturn([
            'FOO' => 'bar',
            'BAZ' => 'path with spaces & special "chars"',
        ]);

        $this->envOperator->install($this->pluginMock);

        self::assertStringEqualsFile($this->envFile, $envContents);
    }

    public function testInstallWithEmptyVars(): void
    {
        $this->pluginMock->method('getEnvVars')->willReturn([]);

        $this->envOperator->install($this->pluginMock);

        self::assertStringEqualsFile($this->envFile, '');
    }

    public function testInstallWithMultipleEnvFiles(): void
    {
        $envContents = <<<EOF

### start-plugin-config:zaphyr/foo-plugin ###
FOO=bar
### end-plugin-config:zaphyr/foo-plugin ###

EOF;

        file_put_contents($envDist = __DIR__ . '/.env.dist', '');

        $this->pluginMock->method('getEnvVars')->willReturn(['FOO' => 'bar']);

        $this->envOperator->install($this->pluginMock);

        self::assertStringEqualsFile($this->envFile, $envContents);
        self::assertStringEqualsFile($envDist, $envContents);

        @unlink($envDist);
    }

    public function testInstallSkipsExistingEnvVars(): void
    {
        $envContents = <<<EOF

### start-plugin-config:zaphyr/foo-plugin ###
FOO=bar
### end-plugin-config:zaphyr/foo-plugin ###

EOF;

        file_put_contents($this->envFile, $envContents);

        $this->pluginMock->method('getEnvVars')->willReturn(['BAZ' => 'qux']);

        $this->envOperator->install($this->pluginMock);

        self::assertStringEqualsFile($this->envFile, $envContents);
    }

    /* -------------------------------------------------
     * UPDATE
     * -------------------------------------------------
     */

    public function testUpdate(): void
    {
        $envOrigContents = <<<EOF

### start-plugin-config:zaphyr/foo-plugin ###
FOO=bar
### end-plugin-config:zaphyr/foo-plugin ###

EOF;

        $envNewContents = <<<EOF

### start-plugin-config:zaphyr/foo-plugin ###
FOO=bar
BAZ="path with spaces & special \"chars\""
### end-plugin-config:zaphyr/foo-plugin ###

EOF;

        file_put_contents($this->envFile, $envOrigContents);

        $this->pluginUpdateMock->expects(self::once())
            ->method('getNewPlugin')
            ->willReturn($this->pluginMock);

        $this->pluginMock->method('getEnvVars')->willReturn([
            'FOO' => 'bar',
            'BAZ' => 'path with spaces & special "chars"',
        ]);

        self::assertStringEqualsFile($this->envFile, $envOrigContents);

        $this->envOperator->update($this->pluginUpdateMock);

        self::assertStringEqualsFile($this->envFile, $envNewContents);
    }

    public function testUpdateWithEmptyVars(): void
    {
        $this->pluginUpdateMock->expects(self::once())
            ->method('getNewPlugin')
            ->willReturn($this->pluginMock);

        $this->pluginMock->method('getEnvVars')->willReturn([]);

        $this->envOperator->update($this->pluginUpdateMock);

        self::assertStringEqualsFile($this->envFile, '');
    }

    public function testUpdateKeepsExistingEnvVars(): void
    {
        file_put_contents($this->envFile, 'FOO=bar');

        $this->pluginUpdateMock->expects(self::once())
            ->method('getNewPlugin')
            ->willReturn($this->pluginMock);

        $this->pluginMock
            ->method('getEnvVars')
            ->willReturn(['BAZ' => 'qux']);

        $this->envOperator->update($this->pluginUpdateMock);

        $contents = file_get_contents($this->envFile);

        self::assertStringContainsString('FOO=bar', $contents);
        self::assertStringContainsString(
            "### start-plugin-config:zaphyr/foo-plugin ###\nBAZ=qux\n### end-plugin-config:zaphyr/foo-plugin ###",
            $contents
        );
    }

    public function testUpdateKeepsChangedEnvVar(): void
    {
        $envOrigContents = <<<EOF

### start-plugin-config:zaphyr/foo-plugin ###
FOO=bar
### end-plugin-config:zaphyr/foo-plugin ###

EOF;

        file_put_contents($this->envFile, $envOrigContents);

        $this->pluginUpdateMock->expects(self::once())
            ->method('getNewPlugin')
            ->willReturn($this->pluginMock);

        $this->pluginMock->method('getEnvVars')->willReturn([
            'FOO' => 'changed',
        ]);

        $this->envOperator->update($this->pluginUpdateMock);

        self::assertStringEqualsFile($this->envFile, $envOrigContents);
    }

    public function testUpdateRemovesExistingEnvVars(): void
    {
        $envOrigContents = <<<EOF

### start-plugin-config:zaphyr/foo-plugin ###
FOO=bar
### end-plugin-config:zaphyr/foo-plugin ###

EOF;

        file_put_contents($this->envFile, $envOrigContents);

        $currentPluginMock = $this->createMock(Plugin::class);
        $currentPluginMock->method('getName')->willReturn('zaphyr/foo-plugin');
        $currentPluginMock->method('getEnvVars')->willReturn(['FOO' => 'bar']);

        $newPluginMock = $this->createMock(Plugin::class);
        $newPluginMock->method('getName')->willReturn('zaphyr/foo-plugin');
        $newPluginMock->method('getEnvVars')->willReturn([]);

        $this->pluginUpdateMock->expects(self::once())
            ->method('getNewPlugin')
            ->willReturn($newPluginMock);

        $this->pluginUpdateMock->expects(self::once())
            ->method('getCurrentPlugin')
            ->willReturn($currentPluginMock);


        self::assertStringEqualsFile($this->envFile, $envOrigContents);

        $this->envOperator->update($this->pluginUpdateMock);

        self::assertStringEqualsFile($this->envFile, "\n");
    }

    /* -------------------------------------------------
     * UNINSTALL
     * -------------------------------------------------
     */

    public function testUninstall(): void
    {
        $envContents = <<<EOF

### start-plugin-config:zaphyr/foo-plugin ###
FOO=bar
### end-plugin-config:zaphyr/foo-plugin ###

EOF;

        file_put_contents($this->envFile, $envContents);

        $this->pluginMock->method('getEnvVars')->willReturn(['FOO' => 'bar']);

        self::assertStringEqualsFile($this->envFile, $envContents);

        $this->envOperator->uninstall($this->pluginMock);

        self::assertStringEqualsFile($this->envFile, "\n");
    }

    public function testUninstallWithEmptyVars(): void
    {
        $this->pluginMock->method('getEnvVars')->willReturn([]);

        $this->envOperator->uninstall($this->pluginMock);

        self::assertStringEqualsFile($this->envFile, '');
    }

    public function testUninstallKeepsExistingEnvVars(): void
    {
        $envContents = <<<EOF

### start-plugin-config:zaphyr/foo-plugin ###
FOO=bar
### end-plugin-config:zaphyr/foo-plugin ###

### start-plugin-config:zaphyr/baz-plugin ###
BAZ=qux
### end-plugin-config:zaphyr/baz-plugin ###

EOF;

        file_put_contents($this->envFile, $envContents);

        $this->pluginMock->method('getEnvVars')->willReturn(['FOO' => 'bar']);

        $this->envOperator->uninstall($this->pluginMock);

        $contents = file_get_contents($this->envFile);

        self::assertStringContainsString('BAZ=qux', $contents);
        self::assertStringNotContainsString(
            "### start-plugin-config:zaphyr/foo-plugin ###\nFOO=bar\n### end-plugin-config:zaphyr/foo-plugin ###",
            $contents
        );
    }
}

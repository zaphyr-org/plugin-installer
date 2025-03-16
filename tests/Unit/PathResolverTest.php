<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstallerTests\Unit;

use PHPUnit\Framework\TestCase;
use Zaphyr\PluginInstaller\Exceptions\PluginInstallerException;
use Zaphyr\PluginInstaller\PathResolver;

class PathResolverTest extends TestCase
{
    protected PathResolver $pathResolver;

    protected function setUp(): void
    {
        $this->pathResolver = new PathResolver([]);
    }

    protected function tearDown(): void
    {
        unset($this->pathResolver);
    }

    /* -------------------------------------------------
     * CONCAT
     * -------------------------------------------------
     */

    public function testConcat(): void
    {
        self::assertSame('foo/bar/baz', $this->pathResolver->concat('foo', 'bar', 'baz'));
    }

    public function testConcatEmpty(): void
    {
        self::assertNull($this->pathResolver->concat());
    }

    /* -------------------------------------------------
     * RESOLVE
     * -------------------------------------------------
     */

    public function testResolve(): void
    {
        $root = dirname(__DIR__, 2);

        self::assertSame("$root/foo/bar/baz", $this->pathResolver->resolve('%root%/foo/bar/baz'));
        self::assertSame("$root/app/foo/bar/baz", $this->pathResolver->resolve('%app%/foo/bar/baz'));
        self::assertSame("$root/bin/foo/bar/baz", $this->pathResolver->resolve('%bin%/foo/bar/baz'));
        self::assertSame("$root/config/foo/bar/baz", $this->pathResolver->resolve('%config%/foo/bar/baz'));
        self::assertSame("$root/public/foo/bar/baz", $this->pathResolver->resolve('%public%/foo/bar/baz'));
        self::assertSame("$root/resources/foo/bar/baz", $this->pathResolver->resolve('%resources%/foo/bar/baz'));
        self::assertSame("$root/storage/foo/bar/baz", $this->pathResolver->resolve('%storage%/foo/bar/baz'));
    }

    public function testResolveInvalidKeyThrowsException(): void
    {
        $this->expectException(PluginInstallerException::class);

        $this->pathResolver->resolve('%src%/foo/bar/baz');
    }
}

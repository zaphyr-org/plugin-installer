<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstaller\Operations;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Zaphyr\PluginInstaller\Exceptions\PluginInstallerException;
use Zaphyr\PluginInstaller\Types\Plugin;
use Zaphyr\PluginInstaller\Types\PluginUpdate;
use Zaphyr\Utils\File;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class CopyOperator extends AbstractOperator
{
    /**
     * {@inheritdoc}
     *
     * @throws PluginInstallerException if unable to determine plugin directory or source file does not exist
     */
    public function install(Plugin $plugin): void
    {
        $this->copyFiles($this->getFiles($plugin));
    }

    /**
     * {@inheritdoc}
     *
     * @throws PluginInstallerException if unable to determine plugin directory or source file does not exist
     */
    public function update(PluginUpdate $pluginUpdate): void
    {
        $this->updateFiles($this->getFiles($pluginUpdate->getNewPlugin()));
    }

    /**
     * {@inheritdoc}
     *
     * @throws PluginInstallerException if unable to determine plugin directory
     */
    public function uninstall(Plugin $plugin): void
    {
        $this->removeFiles($this->getFiles($plugin));
    }

    /**
     * @param Plugin $plugin
     *
     * @throws PluginInstallerException if unable to determine plugin directory
     * @return array<string, string>
     */
    private function getFiles(Plugin $plugin): array
    {
        $pluginDir = $this->getPluginDirectory($plugin);
        $files = [];

        foreach ($plugin->getCopyPaths() as $source => $target) {
            $sourcePath = (string)$this->pathResolver->concat($pluginDir, $source);
            $targetPath = $this->pathResolver->resolve($target);

            $files = is_dir($targetPath)
                ? array_merge($files, $this->getFilesForDir($sourcePath, $targetPath))
                : array_merge($files, [$sourcePath => $targetPath]);
        }

        return $files;
    }

    /**
     * @param Plugin $plugin
     *
     * @throws PluginInstallerException if unable to determine plugin directory
     * @return string
     */
    private function getPluginDirectory(Plugin $plugin): string
    {
        $pluginDir = $this->composer->getInstallationManager()->getInstallPath($plugin->getPackage());

        if (!is_string($pluginDir)) {
            throw new PluginInstallerException('Unable to determine plugin directory');
        }

        return $pluginDir;
    }

    /**
     * @param string $source
     * @param string $target
     *
     * @return array<string, string>
     */
    private function getFilesForDir(string $source, string $target): array
    {
        $iterator = $this->createIterator($source, RecursiveIteratorIterator::SELF_FIRST);
        $files = [];

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            $files[(string)$item] = (string)$this->pathResolver->concat($target, $iterator->getSubPathName());
        }

        return $files;
    }

    /**
     * @param string $source
     * @param int    $mode
     *
     * @return RecursiveIteratorIterator<RecursiveDirectoryIterator>
     */
    private function createIterator(string $source, int $mode): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            $mode
        );
    }

    /**
     * @param array<string, string> $files
     *
     * @throws PluginInstallerException if source file does not exist
     * @return void
     */
    private function copyFiles(array $files): void
    {
        foreach ($files as $source => $target) {
            if (is_dir($source)) {
                continue;
            }

            $this->validateSource($source);

            if ($this->shouldSkipCopy($target)) {
                continue;
            }

            $this->ensureTargetDirectory($target);
            $this->performCopy($source, $target);
        }
    }

    /**
     * @param string $source
     *
     * @throws PluginInstallerException if source file does not exist
     * @return void
     */
    private function validateSource(string $source): void
    {
        if (!file_exists($source)) {
            throw new PluginInstallerException("File '$source' does not exist");
        }
    }

    /**
     * @param string $target
     *
     * @return bool
     */
    private function shouldSkipCopy(string $target): bool
    {
        return file_exists($target) && !$this->confirmOverwrite($target, 'already exists');
    }

    /**
     * @param string $target
     * @param string $reason
     *
     * @return bool
     */
    private function confirmOverwrite(string $target, string $reason): bool
    {
        return $this->io->askConfirmation("File <fg=yellow>$target</> $reason, overwrite? [Y/n] ", false);
    }

    /**
     * @param string $target
     *
     * @return void
     */
    private function ensureTargetDirectory(string $target): void
    {
        $targetDir = dirname($target);

        if (!file_exists($targetDir) && !File::createDirectory($targetDir, recursive: true)) {
            $this->writeWarning("Could not create directory $targetDir");
        }
    }

    /**
     * @param string $source
     * @param string $target
     *
     * @return void
     */
    private function performCopy(string $source, string $target): void
    {
        if (!File::copy($source, $target)) {
            $this->writeWarning("Could not copy file $source to $target");
        }
    }

    /**
     * @param array<string, string> $files
     *
     * @throws PluginInstallerException if source file does not exist
     * @return void
     */
    private function updateFiles(array $files): void
    {
        foreach ($files as $source => $target) {
            if (is_dir($source)) {
                continue;
            }

            $this->validateSource($source);

            if (!$this->shouldUpdateFile($source, $target)) {
                continue;
            }

            $this->ensureTargetDirectory($target);
            $this->performCopy($source, $target);
        }
    }

    /**
     * @param string $source
     * @param string $target
     *
     * @return bool
     */
    private function shouldUpdateFile(string $source, string $target): bool
    {
        if (!file_exists($target)) {
            return true;
        }

        if (md5_file($target) === md5_file($source)) {
            return false;
        }

        return $this->confirmOverwrite($target, 'has been modified');
    }

    /**
     * @param array<string, string> $files
     *
     * @return void
     */
    private function removeFiles(array $files): void
    {
        foreach ($files as $source => $target) {
            if (is_dir($source)) {
                $this->removeFilesFromDir($source, $target);
                continue;
            }

            if (file_exists($target)) {
                File::delete($target);
            }
        }
    }

    /**
     * @param string $source
     * @param string $target
     *
     * @return void
     */
    private function removeFilesFromDir(string $source, string $target): void
    {
        $iterator = $this->createIterator($source, RecursiveIteratorIterator::CHILD_FIRST);

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            $targetPath = (string)$this->pathResolver->concat($target, $iterator->getSubPathName());
            $item->isDir() ? File::deleteDirectory($target) : File::delete($targetPath);
        }
    }
}

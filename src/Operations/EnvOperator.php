<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstaller\Operations;

use Zaphyr\PluginInstaller\Exceptions\PluginInstallerException;
use Zaphyr\PluginInstaller\Types\Plugin;
use Zaphyr\PluginInstaller\Types\PluginUpdate;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class EnvOperator extends AbstractOperator
{
    /**
     * @var string[]
     */
    protected const ENV_FILES = ['.env', '.env.dist'];

    /**
     * @var string
     */
    protected const ENV_BLOCK_START = '### start-plugin-config:%s ###';

    /**
     * @var string
     */
    protected const ENV_BLOCK_END = '### end-plugin-config:%s ###';

    /**
     * {@inheritdoc}
     *
     * @throws PluginInstallerException if the path contains an invalid key or the env file cannot be read
     */
    public function install(Plugin $plugin): void
    {
        if (empty($plugin->getEnvVars())) {
            return;
        }

        $this->process($plugin);
    }

    /**
     * {@inheritdoc}
     *
     * @throws PluginInstallerException if the path contains an invalid key or the env file cannot be read
     */
    public function update(PluginUpdate $pluginUpdate): void
    {
        $newPlugin = $pluginUpdate->getNewPlugin();

        if (empty($newPlugin->getEnvVars())) {
            $this->uninstall($pluginUpdate->getCurrentPlugin());

            return;
        }

        $this->process($newPlugin, true);
    }

    /**
     * {@inheritdoc}
     *
     * @throws PluginInstallerException if the env file cannot be read
     */
    public function uninstall(Plugin $plugin): void
    {
        foreach ($this->getEnvFilePaths() as $envFile) {
            if (!file_exists($envFile)) {
                continue;
            }

            $contents = $this->readEnvFileContents($envFile);
            $pluginName = $plugin->getName();
            $pattern = sprintf(
                "{\n*" . self::ENV_BLOCK_START . '.*' . self::ENV_BLOCK_END . "\n+}s",
                $pluginName,
                $pluginName
            );

            $data = preg_replace($pattern, "\n", $contents, -1, $count);

            if ($count > 0) {
                file_put_contents($envFile, $data);
            }
        }
    }

    /**
     * @param Plugin $plugin
     * @param bool   $update
     *
     * @throws PluginInstallerException if the path contains an invalid key or the env file cannot be read
     * @return void
     */
    protected function process(Plugin $plugin, bool $update = false): void
    {
        foreach ($this->getEnvFilePaths() as $envFile) {
            if (!file_exists($envFile)) {
                continue;
            }

            $contents = $this->readEnvFileContents($envFile);
            $startTag = sprintf(self::ENV_BLOCK_START, $plugin->getName());

            if (!$update && str_contains($contents, $startTag)) {
                continue;
            }

            $data = $this->formatData($plugin, $contents);

            if (!$this->updateBlock($envFile, $data)) {
                file_put_contents($envFile, "\n$data\n", FILE_APPEND);
            }
        }
    }

    /**
     * @param Plugin $plugin
     * @param string $contents
     *
     * @throws PluginInstallerException if the path contains an invalid key
     * @return string
     */
    protected function formatData(Plugin $plugin, string $contents): string
    {
        $lines = [];
        $pluginName = $plugin->getName();

        foreach ($plugin->getEnvVars() as $key => $value) {
            $value = $this->pathResolver->resolve($value);

            if (strpbrk($value, " \t\n&!\"") !== false) {
                $value = '"' . str_replace(['\\', '"', "\t", "\n"], ['\\\\', '\\"', '\\t', '\\n'], $value) . '"';
            }

            $value = $this->extractExistingValue($pluginName, $contents, $key) ?? $value;
            $lines[] = "$key=$value";
        }

        $data = implode("\n", $lines);

        return sprintf(self::ENV_BLOCK_START . "\n%s\n" . self::ENV_BLOCK_END, $pluginName, $data, $pluginName);
    }

    /**
     * @param string $pluginName
     * @param string $contents
     * @param string $key
     *
     * @return string|null
     */
    protected function extractExistingValue(string $pluginName, string $contents, string $key): ?string
    {
        $startTag = preg_quote(sprintf(self::ENV_BLOCK_START, $pluginName), '/');
        $endTag = preg_quote(sprintf(self::ENV_BLOCK_END, $pluginName), '/');
        $blockPattern = '/' . $startTag . '.*?' . $endTag . '/s';

        $value = null;

        if (preg_match($blockPattern, $contents, $matches)) {
            $valuePattern = '/' . preg_quote($key, '/') . '=(.*?)(\n|$)/';

            if (preg_match($valuePattern, $matches[0], $matches)) {
                $value = $matches[1];
            }
        }

        return $value;
    }

    /**
     * @param string $file
     * @param string $data
     *
     * @throws PluginInstallerException if the env file cannot be read
     * @return bool
     */
    protected function updateBlock(string $file, string $data): bool
    {
        $contents = $this->readEnvFileContents($file);
        $updatedData = $this->updateBlockContents($contents, $data);

        if ($updatedData === null) {
            return false;
        }

        file_put_contents($file, $updatedData);

        return true;
    }

    /**
     * @param string $contents
     * @param string $data
     *
     * @return string|null
     */
    protected function updateBlockContents(string $contents, string $data): ?string
    {
        $pieces = explode("\n", trim($data));
        $startTag = trim(reset($pieces));
        $endTag = trim(end($pieces));

        if (!str_contains($contents, $startTag) || !str_contains($contents, $endTag)) {
            return null;
        }

        $pattern = '/' . preg_quote($startTag, '/') . '.*?' . preg_quote($endTag, '/') . '/s';

        return preg_replace($pattern, $data, $contents);
    }

    /**
     * @return string[]
     */
    protected function getEnvFilePaths(): array
    {
        return array_map(fn(string $file) => $this->pathResolver->getRootPath($file), self::ENV_FILES);
    }

    /**
     * @param string $envFile
     *
     * @throws PluginInstallerException if the env file cannot be read
     * @return string
     */
    protected function readEnvFileContents(string $envFile): string
    {
        $contents = file_get_contents($envFile);

        if (!is_string($contents)) {
            throw new PluginInstallerException('Unable to read file ' . $envFile);
        }

        return $contents;
    }
}

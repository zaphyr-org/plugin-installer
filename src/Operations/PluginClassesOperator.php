<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstaller\Operations;

use Symfony\Component\Yaml\Yaml;
use Zaphyr\PluginInstaller\Types\Plugin;
use Zaphyr\PluginInstaller\Types\PluginUpdate;
use Zaphyr\Utils\File;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class PluginClassesOperator extends AbstractOperator
{
    /**
     * {@inheritdoc}
     */
    public function install(Plugin $plugin): void
    {
        $classes = $this->getExistingClasses();
        $classes = $this->addClasses($classes, $plugin->getClasses());

        $this->processConfigFile($classes);
    }

    /**
     * {@inheritdoc}
     */
    public function update(PluginUpdate $pluginUpdate): void
    {
        $currentClasses = $pluginUpdate->getCurrentPlugin()->getClasses();
        $newClasses = $pluginUpdate->getNewPlugin()->getClasses();

        $classes = $this->getExistingClasses();
        $classes = $this->removeClasses($classes, $currentClasses);
        $classes = $this->addClasses($classes, $newClasses);

        $this->processConfigFile($classes);
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(Plugin $plugin): void
    {
        $classes = $this->getExistingClasses();
        $classes = $this->removeClasses($classes, $plugin->getClasses());

        $this->processConfigFile($classes);
    }

    /**
     * @return array<string, string[]>
     */
    private function getExistingClasses(): array
    {
        $pluginClassesFile = $this->getPluginClassesFile();

        return file_exists($pluginClassesFile) ? Yaml::parseFile($pluginClassesFile) : [];
    }

    /**
     * @param array<string, string[]> $existingClasses
     * @param array<string, string[]> $pluginClasses
     *
     * @return array<string, string[]>
     */
    private function addClasses(array $existingClasses, array $pluginClasses): array
    {
        foreach ($pluginClasses as $class => $environments) {
            foreach ($environments as $environment) {
                $values = $existingClasses[$environment] ?? [];
                $newClass = $this->prepareClass($class);

                if (!in_array($newClass, $values, true)) {
                    $values[] = $newClass;
                }

                $existingClasses[$environment] = array_values($values);
            }
        }

        return $existingClasses;
    }

    /**
     * @param array<string, string[]> $existingClasses
     * @param array<string, string[]> $pluginClasses
     *
     * @return array<string, string[]>
     */
    private function removeClasses(array $existingClasses, array $pluginClasses): array
    {
        foreach ($pluginClasses as $class => $environments) {
            foreach ($environments as $environment) {
                $values = $existingClasses[$environment] ?? [];
                $existingClasses[$environment] = array_values(
                    array_diff($values, [$this->prepareClass($class)])
                );

                if (empty($existingClasses[$environment])) {
                    unset($existingClasses[$environment]);
                }
            }
        }

        return $existingClasses;
    }

    /**
     * @param string $class
     *
     * @return string
     */
    private function prepareClass(string $class): string
    {
        return trim($class, '\\');
    }

    /**
     * @param array<string, string[]> $classes
     *
     * @return void
     */
    private function processConfigFile(array $classes): void
    {
        $pluginConfigDir = $this->getPluginClassesDir();
        $pluginClassesFile = $this->getPluginClassesFile();

        if (empty($classes)) {
            File::delete($pluginClassesFile);

            return;
        }

        if (!is_dir($pluginConfigDir) && !File::createDirectory($pluginConfigDir)) {
            $this->writeWarning("Could not create directory $pluginConfigDir");
        }

        if (!file_put_contents($pluginClassesFile, Yaml::dump($classes))) {
            $this->writeWarning("Could not write to file $pluginClassesFile");
        }
    }

    /**
     * @return string
     */
    private function getPluginClassesDir(): string
    {
        return $this->pathResolver->getConfigPath('plugins');
    }

    /**
     * @return string
     */
    private function getPluginClassesFile(): string
    {
        return $this->pathResolver->getConfigPath('plugins/classes.yml');
    }
}

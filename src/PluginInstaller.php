<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstaller;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class PluginInstaller implements PluginInterface, EventSubscriberInterface
{
    /**
     * @const string
     */
    protected const ZAPHYR_PLUGIN_TYPE = 'zaphyr-plugin';

    /**
     * @var OperationsResolver
     */
    protected OperationsResolver $operationsResolver;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $pathResolver = $this->getPathResolver($composer);

        $this->operationsResolver = new OperationsResolver($composer, $io, $pathResolver);
    }

    /**
     * @param Composer $composer
     *
     * @return PathResolver
     */
    protected function getPathResolver(Composer $composer): PathResolver
    {
        $paths = $composer->getPackage()->getExtra()['zaphyr']['paths'] ?? [];
        $paths['root'] ??= realpath(dirname(Factory::getComposerFile()));

        return new PathResolver($paths);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * @param PackageEvent $packageEvent
     *
     * @return void
     */
    public function installPlugin(PackageEvent $packageEvent): void
    {
        $this->execute($packageEvent, 'install');
    }

    /**
     * @param PackageEvent $packageEvent
     *
     * @return void
     */
    public function updatePlugin(PackageEvent $packageEvent): void
    {
        /** @var UpdateOperation $operation */
        $operation = $packageEvent->getOperation();
        $targetPackage = $operation->getTargetPackage();

        if ($targetPackage->getType() !== self::ZAPHYR_PLUGIN_TYPE) {
            return;
        }

        $initialPackage = $operation->getInitialPackage();

        $currentPlugin = new Plugin($initialPackage);
        $newPlugin = new Plugin($targetPackage);

        $this->operationsResolver->update(new PluginUpdate($currentPlugin, $newPlugin));
    }

    /**
     * @param PackageEvent $packageEvent
     *
     * @return void
     */
    public function uninstallPlugin(PackageEvent $packageEvent): void
    {
        $this->execute($packageEvent, 'uninstall');
    }

    /**
     * @param PackageEvent $event
     * @param string       $type
     *
     * @return void
     */
    protected function execute(PackageEvent $event, string $type): void
    {
        /** @var InstallOperation $operation */
        $operation = $event->getOperation();
        $package = $operation->getPackage();

        if ($package->getType() !== self::ZAPHYR_PLUGIN_TYPE) {
            return;
        }

        $this->operationsResolver->$type(new Plugin($package));
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'installPlugin',
            PackageEvents::POST_PACKAGE_UPDATE => 'updatePlugin',
            PackageEvents::PRE_PACKAGE_UNINSTALL => 'uninstallPlugin',
        ];
    }
}

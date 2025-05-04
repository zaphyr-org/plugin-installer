<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Zaphyr\PluginInstaller\Operations\AbstractOperator;
use Zaphyr\PluginInstaller\Operations\CopyOperator;
use Zaphyr\PluginInstaller\Operations\EnvOperator;
use Zaphyr\PluginInstaller\Operations\PluginClassesOperator;
use Zaphyr\PluginInstaller\Types\Plugin;
use Zaphyr\PluginInstaller\Types\PluginUpdate;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class OperationsResolver
{
    /**
     * @var array<string, class-string<AbstractOperator>>
     */
    public const DEFAULT_OPERATORS = [
        'plugin-classes' => PluginClassesOperator::class,
        'copy' => CopyOperator::class,
        'env' => EnvOperator::class,
    ];

    /**
     * @var array<string, AbstractOperator>
     */
    private array $cachedOperators = [];

    /**
     * @param Composer                                      $composer
     * @param IOInterface                                   $io
     * @param PathResolver                                  $pathResolver
     * @param array<string, class-string<AbstractOperator>> $operators
     */
    public function __construct(
        private readonly Composer $composer,
        private readonly IOInterface $io,
        private readonly PathResolver $pathResolver,
        private readonly array $operators = self::DEFAULT_OPERATORS
    ) {
    }

    /**
     * @param Plugin $plugin
     *
     * @return void
     */
    public function install(Plugin $plugin): void
    {
        $this->executeOperation($plugin, 'install');
    }

    /**
     * @param PluginUpdate $pluginUpdate
     *
     * @return void
     */
    public function update(PluginUpdate $pluginUpdate): void
    {
        $this->executeOperation($pluginUpdate, 'update');
    }

    /**
     * @param Plugin $plugin
     *
     * @return void
     */
    public function uninstall(Plugin $plugin): void
    {
        $this->executeOperation($plugin, 'uninstall');
    }

    /**
     * @param Plugin|PluginUpdate $target
     * @param string              $operation
     *
     * @return void
     */
    private function executeOperation(Plugin|PluginUpdate $target, string $operation): void
    {
        foreach (array_keys($this->operators) as $operator) {
            $this->getOperator($operator)->$operation($target);
        }
    }

    /**
     * @param string $operation
     *
     * @return AbstractOperator
     */
    private function getOperator(string $operation): AbstractOperator
    {
        if (!isset($this->cachedOperators[$operation])) {
            $operatorClass = $this->operators[$operation];
            $this->cachedOperators[$operation] = new $operatorClass($this->composer, $this->io, $this->pathResolver);
        }

        return $this->cachedOperators[$operation];
    }
}

<?php

declare(strict_types=1);

namespace Zaphyr\PluginInstaller;

use Zaphyr\Framework\Contracts\ApplicationPathResolverInterface;
use Zaphyr\PluginInstaller\Operations\AbstractOperator;
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
    ];

    /**
     * @var array<string, AbstractOperator>
     */
    private array $cachedOperators = [];

    /**
     * @param ApplicationPathResolverInterface              $applicationPathResolver
     * @param array<string, class-string<AbstractOperator>> $operators
     */
    public function __construct(
        private readonly ApplicationPathResolverInterface $applicationPathResolver,
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
            if ($this->shouldExecuteOperation($target, $operator)) {
                $this->getOperator($operator)->$operation($target);
            }
        }
    }

    /**
     * @param Plugin|PluginUpdate $target
     * @param string              $operator
     *
     * @return bool
     */
    private function shouldExecuteOperation(Plugin|PluginUpdate $target, string $operator): bool
    {
        if ($target instanceof Plugin) {
            return $target->hasExtra($operator);
        }

        return $target->getCurrentPlugin()->hasExtra($operator) && $target->getNewPlugin()->hasExtra($operator);
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
            $this->cachedOperators[$operation] = new $operatorClass($this->applicationPathResolver);
        }

        return $this->cachedOperators[$operation];
    }
}

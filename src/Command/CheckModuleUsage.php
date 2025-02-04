<?php

declare(strict_types=1);

namespace Elgentos\ModuleCheck\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckModuleUsage extends Command
{
    protected const string ARGUMENT_MODULE = 'Elgentos_ModuleCheck';
    protected const string OPTION_MODULE_NAME = 'module-name';

    public function __construct(
        private readonly ModuleListInterface   $moduleList,
        private readonly ScopeConfigInterface  $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly ResourceConnection    $resource
    ) {
        parent::__construct('module:check-usage');
    }

    protected function configure(): void
    {
        $options = [
            new InputOption(
                self::OPTION_MODULE_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Module name'
            ),
        ];
        $this->setName('elgentos:check-module-usage')
             ->setDescription("Check if a module is actively used");
        $this->setDefinition($options);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = $input->getOption(self::OPTION_MODULE_NAME);
        if (!$moduleName) {
            $output->writeln('<error>Module name is required</error>');
            return Command::FAILURE;
        }

        $output->writeln("Checking module: <info>$moduleName</info>");

        // Check if module is enabled
        if (!$this->moduleList->has($moduleName)) {
            $output->writeln("<error>Module $moduleName is not enabled.</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Module is enabled.</info>");

        // Get module-related config settings dynamically
        $grepCommand = "bin/magento config:show | grep '$moduleName' 2>/dev/null";
        $configPaths = shell_exec($grepCommand);

        foreach ($this->storeManager->getStores() as $store) {
            foreach ($configPaths as $path) {
                $configValue = $this->scopeConfig->getValue($path, 'stores', $store->getCode());
                if ($configValue) {
                    $output->writeln("<info>Config enabled in store: {$store->getCode()}</info>");
                }
            }
        }
        // What about on website level?

        // Check database usage
        $connection = $this->resource->getConnection();
        $tables = $connection->fetchCol("SHOW TABLES LIKE '%" . strtolower($moduleName) . "%'");
        if (!empty($tables)) {
            $output->writeln("<info>Found module-related database tables.</info>");
        }

        // Check for theme references
        $themeDir = BP . '/app/design/front end/';
        $grepCommand = "grep -r '$moduleName' $themeDir 2>/dev/null";
        $themeReferences = shell_exec($grepCommand);
        if (!empty($themeReferences)) {
            $output->writeln("<info>Module is referenced in theme files.</info>");
        }

        $output->writeln("Check complete.");
        return Command::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Vendor\ModuleCheck\Console\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckModuleUsage extends Command
{
    protected const ARGUMENT_MODULE = 'Elgentos_ModuleCheck';
    protected const OPTION_MODULE_NAME = 'module-name';

    public function __construct(
        private ModuleListInterface $moduleList,
        private ScopeConfigInterface $scopeConfig,
        private InputOption $inputOption,
        private File $fileDriver,
        private StoreManagerInterface $storeManager,
        private ResourceConnection $resource
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $options = [
            new InputArgument(
                self::ARGUMENT_MODULE,
                InputArgument::REQUIRED,
                'Module name'
            ),
        ];
        $this->setName('elgentos:check-module-usage')
             ->setDescription("Check if a module is actively used in themes, stores, and database. \n bin/magento module:check-usage module-name=Vendor_Module");
        $this->setDefinition($options);
        
        // $this->addOption(
        //     self::OPTION_MODULE_NAME,
        //     null,
        //     InputOption::VALUE_REQUIRED,
        //     'Module name'
        // );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = $input->getArgument(self::ARGUMENT_MODULE);
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

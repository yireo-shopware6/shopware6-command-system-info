<?php declare(strict_types=1);

namespace Yireo\CommandSystemInfo\Console\Command;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception as DalException;
use Shopware\Core\Kernel;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;

class SystemInfoCommand extends Command
{
    private Kernel $kernel;
    private Connection $connection;

    public function __construct(
        Kernel $kernel,
        Connection $connection,
        string $name = null
    ) {
        parent::__construct($name);
        $this->kernel = $kernel;
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this->setName('system:info')
            ->setDescription('Show details on this system');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Shopware version: ' . $this->getShopwareVersion());
        $output->writeln('Symfony version: ' . $this->getSymfonyVersion());
        $output->writeln('Root folder: ' . $this->getProjectFolder());
        $output->writeln('Application mode: ' . $this->getApplicationMode());
        $output->writeln('Customers: ' . $this->getNumberOfCustomers());
        $output->writeln('Products: '.$this->getNumberOfProducts());
        $output->writeln('Categories: '.$this->getNumberOfCategories());
        return 1;
    }

    private function getInstalledPackages(): array
    {
        $installedFile = $this->kernel->getProjectDir() . '/vendor/composer/installed.json';
        if (!is_file($installedFile)) {
            return [];
        }

        $installedData = json_decode(file_get_contents($installedFile), true);
        if (!isset($installedData['packages'])) {
            return [];
        }

        return $installedData['packages'];
    }

    private function getShopwareVersion(): string
    {
        $installedPackages = $this->getInstalledPackages();
        foreach ($installedPackages as $installedPackage) {
            if ($installedPackage['name'] === 'shopware/core') {
                return $installedPackage['version'];
            }
        }

        return 'n/a';
    }

    private function getSymfonyVersion(): string
    {
        return SymfonyKernel::VERSION;
    }

    private function getProjectFolder(): string
    {
        return $this->kernel->getProjectDir();
    }

    private function getApplicationMode(): string
    {
        return $this->kernel->getEnvironment();
    }

    /**
     * @throws DalException
     */
    private function getNumberOfProducts(): int
    {
        $query = $this->connection->executeQuery('SELECT COUNT(id) FROM product');
        return (int)$query->fetchOne();
    }

    /**
     * @throws DalException
     */
    private function getNumberOfCategories(): int
    {
        $query = $this->connection->executeQuery('SELECT COUNT(id) FROM category');
        return (int)$query->fetchOne();
    }

    /**
     * @throws Exception
     * @throws DalException
     */
    private function getNumberOfCustomers(): int
    {
        $query = $this->connection->executeQuery('SELECT COUNT(id) FROM customer');
        return (int)$query->fetchOne();
    }
}

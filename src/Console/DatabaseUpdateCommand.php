<?php

namespace FFans\IpLocation\Console;

use Flarum\Console\AbstractCommand;
use FFans\IpLocation\Database\BundledDatabaseInstaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

class DatabaseUpdateCommand extends AbstractCommand
{
    public function __construct(protected BundledDatabaseInstaller $installer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('ffans-ip-location:database-update')
            ->setDescription('Install and verify the bundled ip2region IPv4 and IPv6 XDB files.')
            ->addOption(
                'family',
                null,
                InputOption::VALUE_REQUIRED,
                'Address family to install: all, 4, or 6',
                'all'
            );
    }

    protected function fire(): int
    {
        $family = strtolower((string) $this->input->getOption('family'));

        if (! in_array($family, ['all', '4', '6'], true)) {
            $this->error('The --family option must be all, 4, or 6.');

            return Command::INVALID;
        }

        $versions = $family === 'all' ? [4, 6] : [(int) $family];

        foreach ($versions as $version) {
            try {
                $this->installer->install($version);
                $this->info("IPv$version bundled database is ready.");
            } catch (Throwable $e) {
                $this->error("IPv$version installation failed: ".$e->getMessage());

                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}

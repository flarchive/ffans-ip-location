<?php

namespace FFans\IpLocation\Console;

use Flarum\Console\AbstractCommand;
use FFans\IpLocation\Contract\LocationResolverInterface;
use Symfony\Component\Console\Command\Command;

class DatabaseStatusCommand extends AbstractCommand
{
    public function __construct(protected LocationResolverInterface $resolver)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('ffans-ip-location:database-status')
            ->setDescription('Check the installed IPv4 and IPv6 XDB database files.');
    }

    protected function fire(): int
    {
        $status = $this->resolver->databaseStatus();
        $ready = true;

        $this->info('Provider: '.$status['provider']);

        foreach ([4 => 'ipv4', 6 => 'ipv6'] as $version => $key) {
            $file = $status[$key];
            $state = $file['ready'] ? 'ready' : 'not ready';
            $this->output->writeln("IPv$version: $state");
            $this->output->writeln('  Path: '.$file['path']);

            if ($file['error']) {
                $this->output->writeln('  Error: '.$file['error']);
            }

            $ready = $ready && $file['ready'];
        }

        return $ready ? Command::SUCCESS : Command::FAILURE;
    }
}

<?php

namespace FFans\IpLocation\Console;

use Flarum\Console\AbstractCommand;
use FFans\IpLocation\BackfillPostQuery;
use FFans\IpLocation\LocationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;

class BackfillLocationsCommand extends AbstractCommand
{
    public function __construct(
        protected LocationManager $manager,
        protected BackfillPostQuery $postQuery,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('ffans-ip-location:backfill')
            ->setDescription('Resolve IP location labels for existing comment posts without exposing their IP addresses.')
            ->addOption('chunk', null, InputOption::VALUE_REQUIRED, 'Posts processed per database chunk', '200')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Re-resolve posts that already have a location record');
    }

    protected function fire(): int
    {
        $chunkSize = max(1, min(2000, (int) $this->input->getOption('chunk')));
        $force = (bool) $this->input->getOption('force');
        $query = $this->postQuery->query($force);

        $total = $query->count();

        if ($total === 0) {
            $this->info('No posts require IP location processing.');

            return Command::SUCCESS;
        }

        $this->info("Processing $total post(s). Raw IP addresses will not be printed.");
        $progress = new ProgressBar($this->output, $total);
        $progress->start();
        $counts = [];

        $query->chunkById($chunkSize, function ($posts) use ($force, $progress, &$counts) {
            foreach ($posts as $post) {
                $location = $this->manager->resolveForPost($post, $force);
                $counts[$location->status] = ($counts[$location->status] ?? 0) + 1;
                $progress->advance();
            }
        });

        $progress->finish();
        $this->output->writeln('');

        foreach ($counts as $status => $count) {
            $this->output->writeln(ucfirst($status).": $count");
        }

        return Command::SUCCESS;
    }
}

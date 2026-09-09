<?php

namespace FFans\IpLocation\Listener;

use Flarum\Post\Event\Posted;
use Flarum\Settings\SettingsRepositoryInterface;
use FFans\IpLocation\LocationManager;
use Psr\Log\LoggerInterface;
use Throwable;

class ResolvePostedLocation
{
    public function __construct(
        protected LocationManager $manager,
        protected SettingsRepositoryInterface $settings,
        protected LoggerInterface $log,
    ) {
    }

    public function handle(Posted $event): void
    {
        if ($this->settings->get('ffans-ip-location.enabled', '1') !== '1') {
            return;
        }

        try {
            $this->manager->resolveForPost($event->post);
        } catch (Throwable $e) {
            // Location enrichment must never prevent a post from being published.
            // Do not include the source IP in this log context.
            $this->log->warning('Unable to persist an IP location result.', [
                'postId' => $event->post->id,
                'exception' => $e,
            ]);
        }
    }
}

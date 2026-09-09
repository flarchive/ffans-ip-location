<?php

namespace FFans\IpLocation\Tests\integration;

use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RecalculationRequiresQueueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('ffans-ip-location');
    }

    #[Test]
    public function the_sync_driver_is_reported_and_cannot_start_a_background_task(): void
    {
        $statusResponse = $this->send($this->request('GET', '/api/ffans-ip-location/recalculate', [
            'authenticatedAs' => 1,
        ]));
        $status = json_decode((string) $statusResponse->getBody(), true);

        $this->assertSame(200, $statusResponse->getStatusCode(), json_encode($status));
        $this->assertSame('sync', $status['queueDriver']);
        $this->assertFalse($status['queueReady']);

        $startResponse = $this->send($this->request('POST', '/api/ffans-ip-location/recalculate', [
            'authenticatedAs' => 1,
        ]));

        $this->assertSame(422, $startResponse->getStatusCode());
    }
}


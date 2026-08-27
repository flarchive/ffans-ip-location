<?php

namespace FFans\IpLocation\Tests\integration;

use Flarum\Post\CommentPost;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use FFans\IpLocation\BackfillPostQuery;
use FFans\IpLocation\Job\RecalculateLocationsJob;
use FFans\IpLocation\LocationManager;
use FFans\IpLocation\PostLocation;
use FFans\IpLocation\Recalculation;
use Illuminate\Contracts\Queue\Queue;
use PHPUnit\Framework\Attributes\Test;

class IpLocationApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->config('queue.driver', 'database');
        $this->extension('ffans-ip-location');
        $this->prepareDatabase([
            User::class => [['id' => 2]],
        ]);
    }

    #[Test]
    public function only_administrators_can_initialize_the_bundled_databases(): void
    {
        $forbiddenResponse = $this->send($this->request('POST', '/api/ffans-ip-location/database', [
            'authenticatedAs' => 2,
        ]));

        $this->assertSame(403, $forbiddenResponse->getStatusCode());

        $response = $this->send($this->request('POST', '/api/ffans-ip-location/database', [
            'authenticatedAs' => 1,
        ]));
        $document = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode(), json_encode($document));
        $this->assertSame([
            'ready' => true,
            'ipv4Ready' => true,
            'ipv6Ready' => true,
        ], $document);
    }

    #[Test]
    public function profile_location_can_be_disabled_independently(): void
    {
        $this->setting('ffans-ip-location.display_on_profile', '0');

        $profileResponse = $this->send($this->request('GET', '/api/users/1'));
        $profileDocument = json_decode((string) $profileResponse->getBody(), true);

        $this->assertSame(200, $profileResponse->getStatusCode(), json_encode($profileDocument));
        $this->assertArrayNotHasKey('ipLocation', $profileDocument['data']['attributes']);

        $forumResponse = $this->send($this->request('GET', '/api'));
        $forumDocument = json_decode((string) $forumResponse->getBody(), true);

        $this->assertSame(200, $forumResponse->getStatusCode(), json_encode($forumDocument));
        $this->assertFalse($forumDocument['data']['attributes']['ffansIpLocationDisplayOnProfile']);
    }

    #[Test]
    public function posting_from_the_test_loopback_address_records_a_private_result(): void
    {
        $this->setting('ffans-ip-location.show_unknown', '1');

        $response = $this->send($this->request('POST', '/api/discussions', [
            'authenticatedAs' => 1,
            'json' => [
                'data' => [
                    'type' => 'discussions',
                    'attributes' => [
                        'title' => 'Location test',
                        'content' => 'Test body',
                    ],
                ],
            ],
        ]));

        $document = json_decode((string) $response->getBody(), true);

        $this->assertSame(201, $response->getStatusCode(), json_encode($document));
        $this->assertSame('private', PostLocation::sole()->status);

        $firstPost = collect($document['included'] ?? [])->firstWhere('type', 'posts');
        $this->assertSame(['status' => 'private'], $firstPost['attributes']['ipLocation']);

        $guestResponse = $this->send($this->request('GET', '/api/discussions/'.$document['data']['id']));
        $guestDocument = json_decode((string) $guestResponse->getBody(), true);
        $guestFirstPost = collect($guestDocument['included'] ?? [])->firstWhere('type', 'posts');

        $this->assertSame(200, $guestResponse->getStatusCode(), json_encode($guestDocument));
        $this->assertSame(['status' => 'unknown'], $guestFirstPost['attributes']['ipLocation']);

        $adminProfileResponse = $this->send($this->request('GET', '/api/users/1', [
            'authenticatedAs' => 1,
        ]));
        $adminProfileDocument = json_decode((string) $adminProfileResponse->getBody(), true);

        $this->assertSame(200, $adminProfileResponse->getStatusCode(), json_encode($adminProfileDocument));
        $this->assertSame(['status' => 'private'], $adminProfileDocument['data']['attributes']['ipLocation']);

        $guestProfileResponse = $this->send($this->request('GET', '/api/users/1'));
        $guestProfileDocument = json_decode((string) $guestProfileResponse->getBody(), true);

        $this->assertSame(200, $guestProfileResponse->getStatusCode(), json_encode($guestProfileDocument));
        $this->assertSame(['status' => 'unknown'], $guestProfileDocument['data']['attributes']['ipLocation']);

        $userIndexResponse = $this->send($this->request('GET', '/api/users', [
            'authenticatedAs' => 1,
        ]));
        $userIndexDocument = json_decode((string) $userIndexResponse->getBody(), true);

        $this->assertSame(200, $userIndexResponse->getStatusCode(), json_encode($userIndexDocument));

        foreach ($userIndexDocument['data'] as $userData) {
            $this->assertArrayNotHasKey('ipLocation', $userData['attributes']);
        }

        $location = PostLocation::sole();
        $location->status = 'failed';
        $location->save();

        $retried = $this->app()->getContainer()->make(LocationManager::class)->resolveForPost(CommentPost::findOrFail($location->post_id));

        $this->assertSame('private', $retried->status, 'Failed records should be retried without requiring --force.');

        $recalculateResponse = $this->send($this->request('POST', '/api/ffans-ip-location/recalculate', [
            'authenticatedAs' => 1,
            'json' => ['force' => true],
        ]));
        $recalculateDocument = json_decode((string) $recalculateResponse->getBody(), true);

        $this->assertSame(202, $recalculateResponse->getStatusCode(), json_encode($recalculateDocument));
        $this->assertSame('pending', $recalculateDocument['status']);
        $this->assertSame('database', $recalculateDocument['queueDriver']);
        $this->assertTrue($recalculateDocument['force']);
        $this->assertSame(1, $this->database()->table('queue_jobs')->count());

        $task = Recalculation::sole();
        (new RecalculateLocationsJob($task->id))->handle(
            $this->app()->getContainer()->make(LocationManager::class),
            $this->app()->getContainer()->make(BackfillPostQuery::class),
            $this->app()->getContainer()->make(Queue::class),
        );

        $statusResponse = $this->send($this->request('GET', '/api/ffans-ip-location/recalculate', [
            'authenticatedAs' => 1,
        ]));
        $statusDocument = json_decode((string) $statusResponse->getBody(), true);

        $this->assertSame(200, $statusResponse->getStatusCode(), json_encode($statusDocument));
        $this->assertSame('completed', $statusDocument['status']);
        $this->assertGreaterThanOrEqual(1, $statusDocument['processed']);

        $normalResponse = $this->send($this->request('POST', '/api/ffans-ip-location/recalculate', [
            'authenticatedAs' => 1,
        ]));
        $normalDocument = json_decode((string) $normalResponse->getBody(), true);

        $this->assertSame(202, $normalResponse->getStatusCode(), json_encode($normalDocument));
        $this->assertFalse($normalDocument['force']);
        $this->assertSame(0, $normalDocument['total']);

        $normalTask = Recalculation::query()->latest('id')->firstOrFail();
        (new RecalculateLocationsJob($normalTask->id))->handle(
            $this->app()->getContainer()->make(LocationManager::class),
            $this->app()->getContainer()->make(BackfillPostQuery::class),
            $this->app()->getContainer()->make(Queue::class),
        );

        $this->assertSame('completed', $normalTask->fresh()->status);
        $this->assertSame(0, $normalTask->fresh()->processed);

        $forbiddenResponse = $this->send($this->request('POST', '/api/ffans-ip-location/recalculate', [
            'authenticatedAs' => 2,
        ]));

        $this->assertSame(403, $forbiddenResponse->getStatusCode());
    }
}

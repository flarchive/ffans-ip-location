<?php

namespace FFans\IpLocation\Tests\unit;

use Flarum\Api\Context;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use FFans\IpLocation\Api\LocationDataSerializer;
use FFans\IpLocation\PostLocation;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LocationDataSerializerTest extends TestCase
{
    #[Test]
    public function it_exposes_country_and_subdivision_codes_for_chinese_subdivisions(): void
    {
        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('0');

        $actor = $this->createStub(User::class);
        $actor->method('can')->willReturn(false);

        $context = new class($actor) extends Context {
            public function __construct(private User $actor)
            {
            }

            public function getActor(): User
            {
                return $this->actor;
            }
        };

        $location = new PostLocation();
        $location->status = 'resolved';
        $location->country_code = 'CN';
        $location->subdivision_code = 'HK';
        $location->country_name = '中国';
        $location->subdivision_name = '香港特别行政区';

        $post = new Post();
        $post->setRelation('ipLocation', $location);

        $data = (new LocationDataSerializer($settings))->serialize($post, $context);

        $this->assertSame([
            'status' => 'resolved',
            'countryCode' => 'CN',
            'subdivisionCode' => 'HK',
        ], $data);
    }

    #[Test]
    public function it_exposes_only_country_code_when_no_subdivision_exists(): void
    {
        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('0');

        $actor = $this->createStub(User::class);
        $actor->method('can')->willReturn(false);

        $context = new class($actor) extends Context {
            public function __construct(private User $actor)
            {
            }

            public function getActor(): User
            {
                return $this->actor;
            }
        };

        $location = new PostLocation();
        $location->status = 'resolved';
        $location->country_code = 'US';
        $location->country_name = 'United States';

        $post = new Post();
        $post->setRelation('ipLocation', $location);

        $data = (new LocationDataSerializer($settings))->serialize($post, $context);

        $this->assertSame([
            'status' => 'resolved',
            'countryCode' => 'US',
        ], $data);
    }
}

<?php

namespace FFans\IpLocation\Tests\unit;

use FFans\IpLocation\Location\IpNormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class IpNormalizerTest extends TestCase
{
    private IpNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new IpNormalizer();
    }

    #[Test]
    public function it_normalizes_ipv4_mapped_ipv6(): void
    {
        $this->assertSame('203.0.113.10', $this->normalizer->normalize('::ffff:203.0.113.10'));
    }

    #[Test]
    public function it_rejects_invalid_addresses(): void
    {
        $this->assertNull($this->normalizer->normalize('not-an-ip'));
        $this->assertNull($this->normalizer->normalize(''));
        $this->assertNull($this->normalizer->normalize(null));
    }

    #[Test]
    public function it_distinguishes_public_and_special_use_addresses(): void
    {
        $this->assertTrue($this->normalizer->isPublic('1.1.1.1'));
        $this->assertFalse($this->normalizer->isPublic('127.0.0.1'));
        $this->assertFalse($this->normalizer->isPublic('192.168.1.1'));
        $this->assertFalse($this->normalizer->isPublic('2001:db8::1'));
    }
}

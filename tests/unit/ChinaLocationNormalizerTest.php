<?php

namespace FFans\IpLocation\Tests\unit;

use FFans\IpLocation\Location\ChinaLocationNormalizer;
use FFans\IpLocation\Location\RawLocation;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ChinaLocationNormalizerTest extends TestCase
{
    private ChinaLocationNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ChinaLocationNormalizer();
    }

    #[Test]
    public function it_normalizes_mainland_provinces_from_four_field_data(): void
    {
        $result = $this->normalizer->normalize(
            new RawLocation('中国', '广东省', '深圳市', '移动')
        );

        $this->assertSame('resolved', $result->status);
        $this->assertSame('CN', $result->countryCode);
        $this->assertSame('GD', $result->subdivisionCode);
        $this->assertSame('广东省', $result->subdivisionName);
    }

    #[Test]
    public function it_normalizes_hong_kong_as_a_region_not_a_mainland_province(): void
    {
        $result = $this->normalizer->normalize(
            new RawLocation('中国', '香港', '', '')
        );

        $this->assertSame('HK', $result->countryCode);
        $this->assertNull($result->subdivisionCode);
    }

    #[Test]
    public function it_accepts_five_field_data_with_an_iso_country_code(): void
    {
        $result = $this->normalizer->normalize(
            new RawLocation('United States', 'California', 'San Jose', '', 'us')
        );

        $this->assertSame('US', $result->countryCode);
        $this->assertSame('United States', $result->countryName);
        $this->assertNull($result->subdivisionName, 'Foreign state/province data must not be persisted or exposed.');
    }

    #[Test]
    public function it_marks_empty_database_results_unknown(): void
    {
        $result = $this->normalizer->normalize(new RawLocation(''));

        $this->assertSame('unknown', $result->status);
    }
}

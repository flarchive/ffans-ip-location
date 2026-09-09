<?php

namespace FFans\IpLocation\Tests\unit;

use ip2region\xdb\IPv4;
use ip2region\xdb\Searcher;
use ip2region\xdb\Util;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BundledDatabaseDataTest extends TestCase
{
    private string $databasePath;

    protected function setUp(): void
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'ffans-ip-location-xdb-');

        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create a temporary XDB file.');
        }

        $this->databasePath = $temporaryPath;
        $source = gzopen(dirname(__DIR__, 2).'/resources/database/ip2region_v4.xdb.gz', 'rb');
        $destination = fopen($this->databasePath, 'wb');

        if ($source === false || $destination === false) {
            throw new RuntimeException('Unable to open the bundled IPv4 XDB test files.');
        }

        while (! gzeof($source)) {
            $chunk = gzread($source, 1024 * 1024);

            if ($chunk === false || fwrite($destination, $chunk) !== strlen($chunk)) {
                throw new RuntimeException('Unable to extract the bundled IPv4 XDB for testing.');
            }
        }

        gzclose($source);
        fclose($destination);
    }

    protected function tearDown(): void
    {
        if (isset($this->databasePath) && is_file($this->databasePath)) {
            unlink($this->databasePath);
        }
    }

    #[Test]
    public function it_contains_the_documented_cogent_geofeed_corrections(): void
    {
        $this->assertNull(Util::verifyFromFile($this->databasePath));

        $searcher = Searcher::newWithFileOnly(IPv4::default(), $this->databasePath);
        $corrections = file(
            dirname(__DIR__, 2).'/resources/database/ip2region_v4_corrections.txt',
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES,
        );

        if ($corrections === false) {
            throw new RuntimeException('Unable to read the bundled IPv4 correction source.');
        }

        $this->assertNotEmpty($corrections);

        try {
            foreach ($corrections as $correction) {
                $fields = explode('|', $correction);
                $this->assertCount(7, $fields, "Invalid correction row: $correction");

                [$start, $end] = $fields;
                $expected = implode('|', array_slice($fields, 2));

                $this->assertSame($expected, $searcher->search($start), "Correction start mismatch: $start");
                $this->assertSame($expected, $searcher->search($end), "Correction end mismatch: $end");
            }

            $losAngeles = 'United States|California|Los Angeles|Cogent Communications|US';

            for ($lastOctet = 170; $lastOctet <= 179; $lastOctet++) {
                $ipAddress = "154.64.235.$lastOctet";
                $this->assertSame($losAngeles, $searcher->search($ipAddress));
            }

            $this->assertNotSame($losAngeles, $searcher->search('154.64.223.255'));
            $this->assertNotSame($losAngeles, $searcher->search('154.65.0.0'));
        } finally {
            $searcher->close();
        }
    }
}

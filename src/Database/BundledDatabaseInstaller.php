<?php

namespace FFans\IpLocation\Database;

use FFans\IpLocation\Resolver\XdbResolver;
use ip2region\xdb\Util;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class BundledDatabaseInstaller
{
    private const BUNDLED_DATABASES = [
        4 => [
            'file' => 'ip2region_v4.xdb.gz',
            'sha256' => 'c6edaf379fe524d7283a9c11c7eac27d5641a0976baa48c22c319ccd59aa3f36',
        ],
        6 => [
            'file' => 'ip2region_v6.xdb.gz',
            'sha256' => '939f6b46bd2b8bec3cf7c5ceb8ba782266ae9b1f35b5ba7916700dec0b7506ed',
        ],
    ];

    public function __construct(
        protected XdbResolver $resolver,
        protected LoggerInterface $log,
    ) {
    }

    public function installAll(): void
    {
        foreach ([4, 6] as $version) {
            $this->install($version);
        }
    }

    public function install(int $version): void
    {
        $target = $this->resolver->databasePath($version);
        $directory = dirname($target);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create database directory: $directory");
        }

        $backup = $target.'.backup';
        $lockPath = $target.'.lock';
        $lock = @fopen($lockPath, 'c');

        if ($lock === false) {
            throw new RuntimeException("Unable to open the database update lock: $lockPath");
        }

        if (! flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);

            throw new RuntimeException("Another IPv$version database installation is already running.");
        }

        $temporary = null;

        try {
            $temporary = $this->extractBundledDatabase($target, $version);

            if (is_file($backup) && ! unlink($backup)) {
                throw new RuntimeException("Unable to replace previous backup: $backup");
            }

            $targetWasBackedUp = false;
            if (is_file($target)) {
                if (! rename($target, $backup)) {
                    throw new RuntimeException("Unable to back up the current database: $target");
                }

                $targetWasBackedUp = true;
            }

            if (! rename($temporary, $target)) {
                $activationError = "Unable to activate the bundled database: $target";

                if ($targetWasBackedUp && ! rename($backup, $target)) {
                    throw new RuntimeException("$activationError; restoring the previous database also failed. The backup remains at $backup");
                }

                throw new RuntimeException($activationError);
            }

            $temporary = null;
        } finally {
            if ($temporary !== null && is_file($temporary) && ! unlink($temporary)) {
                $this->log->warning('Unable to remove a temporary IP location database file.', [
                    'path' => $temporary,
                ]);
            }

            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function extractBundledDatabase(string $target, int $version): string
    {
        $database = self::BUNDLED_DATABASES[$version] ?? null;

        if ($database === null) {
            throw new RuntimeException("No bundled IPv$version database is configured.");
        }

        $source = dirname(__DIR__, 2).'/resources/database/'.$database['file'];

        if (! is_file($source) || ! is_readable($source)) {
            throw new RuntimeException("Bundled IPv$version database is not readable: $source");
        }

        $temporary = $target.'.'.bin2hex(random_bytes(8)).'.install';
        $input = gzopen($source, 'rb');

        if ($input === false) {
            throw new RuntimeException("Unable to open bundled IPv$version database: $source");
        }

        $output = fopen($temporary, 'wb');

        if ($output === false) {
            gzclose($input);

            throw new RuntimeException("Unable to create temporary database file: $temporary");
        }

        try {
            while (! gzeof($input)) {
                $chunk = gzread($input, 1024 * 1024);

                if ($chunk === false) {
                    throw new RuntimeException("Unable to decompress bundled IPv$version database.");
                }

                $this->writeAll($output, $chunk, $temporary);
            }
        } catch (Throwable $e) {
            fclose($output);
            gzclose($input);

            if (is_file($temporary)) {
                unlink($temporary);
            }

            throw $e;
        }

        fclose($output);
        gzclose($input);

        try {
            $hash = hash_file('sha256', $temporary);

            if ($hash === false || ! hash_equals($database['sha256'], $hash)) {
                throw new RuntimeException("Bundled IPv$version database checksum verification failed.");
            }

            $this->verifyDatabase($temporary, $version);
        } catch (Throwable $e) {
            if (is_file($temporary)) {
                unlink($temporary);
            }

            throw $e;
        }

        return $temporary;
    }

    /** @param resource $output */
    private function writeAll($output, string $data, string $path): void
    {
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length) {
            $written = fwrite($output, substr($data, $offset));

            if ($written === false || $written === 0) {
                throw new RuntimeException("Unable to write temporary database file: $path");
            }

            $offset += $written;
        }
    }

    private function verifyDatabase(string $path, int $version): void
    {
        $error = Util::verifyFromFile($path);

        if ($error !== null) {
            throw new RuntimeException("XDB verification failed: $error");
        }

        $header = Util::loadHeaderFromFile($path);
        $detected = $header ? Util::versionFromHeader($header) : null;

        if (! $detected || $detected->id !== $version) {
            throw new RuntimeException('The bundled XDB address family does not match the requested file.');
        }
    }
}

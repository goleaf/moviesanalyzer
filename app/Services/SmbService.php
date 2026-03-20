<?php

namespace App\Services;

use App\Services\Smb\PackageAuth;
use App\Services\Smb\PackageSystem;
use Icewind\SMB\IShare;
use Icewind\SMB\Options;
use Icewind\SMB\ServerFactory;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class SmbService
{
    /**
     * @var array<int, string>
     */
    private array $videoExtensions;

    private string $host;

    private string $share;

    private string $sharePath;

    private string $username;

    private string $password;

    private string $backend;

    private string $workgroup;

    private string $binary;

    private string $configFile;

    private int $timeoutSeconds;

    private ?string $resolvedBinary = null;

    private ?IShare $packageShare = null;

    public function __construct()
    {
        $this->host = (string) config('cineclean.smb.host');
        $this->share = (string) config('cineclean.smb.share');
        $this->sharePath = trim((string) config('cineclean.smb.path', 'Movies'), '/');
        $this->username = (string) config('cineclean.smb.username');
        $this->password = (string) config('cineclean.smb.password');
        $this->backend = mb_strtolower((string) config('cineclean.smb.backend', 'auto'), 'UTF-8');
        $this->workgroup = trim((string) config('cineclean.smb.workgroup', ''));
        $this->binary = (string) config('cineclean.smb.binary', 'smbclient');
        $this->configFile = (string) config('cineclean.smb.config_file', '');
        $this->timeoutSeconds = $this->resolveTimeoutSeconds((int) config('cineclean.smb.timeout_seconds', 180));
        $this->videoExtensions = array_map(
            static fn (string $extension): string => mb_strtolower($extension, 'UTF-8'),
            (array) config('cineclean.smb.video_extensions', []),
        );
    }

    /**
     * @return array<int, array{filename: string, smb_path: string, file_size_bytes: int, extension: string}>
     */
    public function listVideoFiles(): array
    {
        if ($this->shouldUsePackageBackend()) {
            try {
                return $this->listVideoFilesWithPackage();
            } catch (Throwable $exception) {
                $this->handlePackageFailure('listing files', $exception);
            }
        }

        return $this->listVideoFilesWithCli();
    }

    public function deleteFile(string $smbPath): bool
    {
        if (! (bool) config('cineclean.files.allow_delete', false)) {
            throw new RuntimeException('File deletion is disabled by configuration.');
        }

        if ($this->shouldUsePackageBackend()) {
            try {
                return $this->deleteFileWithPackage($smbPath);
            } catch (Throwable $exception) {
                $this->handlePackageFailure('deleting files', $exception);
            }
        }

        return $this->deleteFileWithCli($smbPath);
    }

    /**
     * @return array<int, array{filename: string, smb_path: string, file_size_bytes: int, extension: string}>
     */
    private function listVideoFilesWithPackage(): array
    {
        $directories = [$this->sharePath];
        $visited = [];
        $files = [];

        while ($directories !== []) {
            $currentDirectory = array_shift($directories);

            if (! is_string($currentDirectory)) {
                continue;
            }

            $currentDirectory = $this->normalizePath($currentDirectory);

            if (isset($visited[$currentDirectory])) {
                continue;
            }

            $visited[$currentDirectory] = true;

            foreach ($this->packageShare()->dir($currentDirectory) as $entry) {
                $entryPath = $this->normalizePath($entry->getPath());

                if ($entry->isDirectory()) {
                    $directories[] = $entryPath;

                    continue;
                }

                $filename = $entry->getName();
                $extension = mb_strtolower(pathinfo($filename, PATHINFO_EXTENSION), 'UTF-8');

                if ($extension === '' || ! in_array($extension, $this->videoExtensions, true)) {
                    continue;
                }

                $files[$entryPath] = [
                    'filename' => $filename,
                    'smb_path' => $entryPath,
                    'file_size_bytes' => max(0, $entry->getSize()),
                    'extension' => $extension,
                ];
            }
        }

        return array_values($files);
    }

    private function deleteFileWithPackage(string $smbPath): bool
    {
        $targetPath = $this->toAbsoluteSharePath($smbPath);

        if ($targetPath === $this->sharePath) {
            throw new RuntimeException('Refusing to delete SMB root path.');
        }

        $this->packageShare()->del($targetPath);

        return true;
    }

    /**
     * @return array<int, array{filename: string, smb_path: string, file_size_bytes: int, extension: string}>
     */
    private function listVideoFilesWithCli(): array
    {
        $command = sprintf('cd "%s"; recurse ON; ls', $this->escapeSmbValue($this->sharePath));
        $output = $this->runSmbCommand($command);

        return $this->parseListingOutput($output);
    }

    private function deleteFileWithCli(string $smbPath): bool
    {
        $relativePath = $this->toRelativeSharePath($smbPath);

        if ($relativePath === '') {
            throw new RuntimeException('Refusing to delete SMB root path.');
        }

        $command = sprintf(
            'cd "%s"; rm "%s"',
            $this->escapeSmbValue($this->sharePath),
            $this->escapeSmbValue($relativePath),
        );

        $this->runSmbCommand($command);

        return true;
    }

    private function packageShare(): IShare
    {
        if ($this->packageShare !== null) {
            return $this->packageShare;
        }

        if (! class_exists(ServerFactory::class)) {
            throw new RuntimeException('SMB package backend unavailable. Install icewind/smb.');
        }

        $binary = $this->resolveBinary();

        if ($binary === null) {
            throw new RuntimeException(
                sprintf(
                    'SMB package backend failed: smbclient binary not found. Current: %s. Checked: %s',
                    $this->binary,
                    implode(', ', $this->binaryCandidates()),
                ),
            );
        }

        $options = new Options;
        $options->setTimeout($this->timeoutSeconds);

        $factory = new ServerFactory($options, new PackageSystem($binary));
        $auth = new PackageAuth(
            username: $this->username,
            workgroup: $this->workgroup !== '' ? $this->workgroup : null,
            password: $this->password,
            configFile: $this->resolveSmbConfigFile(),
        );

        $server = $factory->createServer($this->host, $auth);
        $this->packageShare = $server->getShare($this->share);

        return $this->packageShare;
    }

    private function shouldUsePackageBackend(): bool
    {
        if ($this->backend === 'cli') {
            return false;
        }

        if ($this->backend === 'package') {
            return true;
        }

        return class_exists(ServerFactory::class);
    }

    private function handlePackageFailure(string $operation, Throwable $exception): void
    {
        $exceptionDetail = $exception->getMessage() !== '' ? $exception->getMessage() : $exception::class;

        if ($this->backend === 'package') {
            throw new RuntimeException(
                sprintf('SMB package backend failed while %s: %s', $operation, $exceptionDetail),
                previous: $exception,
            );
        }

        report($exception);
    }

    private function runSmbCommand(string $command): string
    {
        $binary = $this->resolveBinary();

        if ($binary === null) {
            throw new RuntimeException(
                sprintf(
                    'SMB command failed: smbclient binary not found. Install smbclient and set SMBCLIENT_BIN if needed (current: %s). Checked: %s',
                    $this->binary,
                    implode(', ', $this->binaryCandidates()),
                ),
            );
        }

        $processArguments = [
            $binary,
            '-s',
            $this->resolveSmbConfigFile(),
            sprintf('//%s/%s', $this->host, $this->share),
            '-U',
            sprintf('%s%%%s', $this->username, $this->password),
            '-c',
            $command,
        ];

        if ($this->workgroup !== '') {
            array_splice($processArguments, 4, 0, ['-W', $this->workgroup]);
        }

        $process = new Process($processArguments);

        $process->setTimeout($this->timeoutSeconds);
        $process->setIdleTimeout($this->timeoutSeconds);
        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            throw new RuntimeException(
                sprintf(
                    'SMB command timed out after %d seconds. Adjust SMB_TIMEOUT_SECONDS or improve SMB response time.',
                    $this->timeoutSeconds,
                ),
                previous: $exception,
            );
        }

        if (! $process->isSuccessful()) {
            $errorMessage = trim($process->getErrorOutput() ?: $process->getOutput());

            if (str_contains($errorMessage, 'Can\'t load') && str_contains($errorMessage, 'smb.conf')) {
                $errorMessage .= sprintf(
                    ' (Resolved config file: %s. Set SMBCLIENT_CONFIG_FILE to a valid smb.conf path if needed.)',
                    $this->resolveSmbConfigFile(),
                );
            }

            throw new RuntimeException(sprintf('SMB command failed: %s', $errorMessage));
        }

        return $process->getOutput();
    }

    private function resolveTimeoutSeconds(int $configuredTimeout): int
    {
        $normalizedConfiguredTimeout = $configuredTimeout > 0 ? $configuredTimeout : 180;
        $phpMaxExecutionTime = $this->phpMaxExecutionTime();

        if ($phpMaxExecutionTime === null) {
            return $normalizedConfiguredTimeout;
        }

        $safeTimeout = max(1, $phpMaxExecutionTime - 5);

        return min($normalizedConfiguredTimeout, $safeTimeout);
    }

    private function phpMaxExecutionTime(): ?int
    {
        $rawValue = ini_get('max_execution_time');

        if (! is_string($rawValue) || trim($rawValue) === '' || ! is_numeric($rawValue)) {
            return null;
        }

        $seconds = (int) $rawValue;

        return $seconds > 0 ? $seconds : null;
    }

    private function resolveSmbConfigFile(): string
    {
        if ($this->configFile !== '') {
            return $this->configFile;
        }

        $defaultCandidates = [
            '/opt/homebrew/etc/smb.conf',
            '/opt/homebrew/etc/samba/smb.conf',
            '/usr/local/etc/smb.conf',
            '/etc/samba/smb.conf',
        ];

        foreach ($defaultCandidates as $candidate) {
            if (is_readable($candidate)) {
                return $candidate;
            }
        }

        return '/dev/null';
    }

    private function normalizePath(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }

    private function toAbsoluteSharePath(string $path): string
    {
        $normalized = $this->normalizePath($path);

        if ($normalized === '') {
            return $this->sharePath;
        }

        if ($normalized === $this->sharePath || str_starts_with($normalized, $this->sharePath.'/')) {
            return $normalized;
        }

        return trim($this->sharePath.'/'.$normalized, '/');
    }

    private function toRelativeSharePath(string $path): string
    {
        $absolute = $this->toAbsoluteSharePath($path);

        if ($absolute === $this->sharePath) {
            return '';
        }

        return ltrim(substr($absolute, strlen($this->sharePath)), '/');
    }

    private function resolveBinary(): ?string
    {
        if ($this->resolvedBinary !== null) {
            return $this->resolvedBinary;
        }

        foreach ($this->binaryCandidates() as $candidate) {
            if (is_executable($candidate)) {
                $this->resolvedBinary = $candidate;

                return $this->resolvedBinary;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function binaryCandidates(): array
    {
        if (str_contains($this->binary, '/')) {
            return [$this->binary];
        }

        $paths = explode(PATH_SEPARATOR, (string) getenv('PATH'));
        $candidates = array_map(
            fn (string $path): string => rtrim($path, '/').'/'.$this->binary,
            array_filter($paths),
        );

        if ($this->binary === 'smbclient') {
            $candidates[] = '/opt/homebrew/bin/smbclient';
            $candidates[] = '/usr/local/bin/smbclient';
            $candidates[] = '/usr/bin/smbclient';
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @return array<int, array{filename: string, smb_path: string, file_size_bytes: int, extension: string}>
     */
    private function parseListingOutput(string $output): array
    {
        $lines = preg_split('/\R/u', $output) ?: [];
        $currentDirectory = $this->sharePath;
        $files = [];

        foreach ($lines as $line) {
            $line = rtrim($line);

            if ($line === '' || str_contains($line, 'blocks available')) {
                continue;
            }

            if (preg_match('/^\s*(\\[^:]+):\s*$/u', $line, $matches)) {
                $directory = trim($matches[1], '\\');
                $directory = str_replace('\\', '/', $directory);

                if (! str_starts_with($directory, $this->sharePath)) {
                    $directory = trim($this->sharePath.'/'.$directory, '/');
                }

                $currentDirectory = trim($directory, '/');

                continue;
            }

            if (! preg_match('/^\s*(?<name>.+?)\s+(?<attrs>[A-Z]+)\s+(?<size>\d+)\s+/u', $line, $matches)) {
                continue;
            }

            if (str_contains($matches['attrs'], 'D')) {
                continue;
            }

            $filename = trim($matches['name']);
            $extension = mb_strtolower(pathinfo($filename, PATHINFO_EXTENSION), 'UTF-8');

            if ($extension === '' || ! in_array($extension, $this->videoExtensions, true)) {
                continue;
            }

            $path = trim($currentDirectory.'/'.$filename, '/');
            $files[$path] = [
                'filename' => $filename,
                'smb_path' => $path,
                'file_size_bytes' => (int) $matches['size'],
                'extension' => $extension,
            ];
        }

        return array_values($files);
    }

    private function escapeSmbValue(string $value): string
    {
        return str_replace('"', '\\"', $value);
    }
}

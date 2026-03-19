<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

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

    private string $binary;

    public function __construct()
    {
        $this->host = (string) config('cineclean.smb.host');
        $this->share = (string) config('cineclean.smb.share');
        $this->sharePath = trim((string) config('cineclean.smb.path', 'Movies'), '/');
        $this->username = (string) config('cineclean.smb.username');
        $this->password = (string) config('cineclean.smb.password');
        $this->binary = (string) config('cineclean.smb.binary', 'smbclient');
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
        $command = sprintf('cd "%s"; recurse ON; ls', $this->escapeSmbValue($this->sharePath));
        $output = $this->runSmbCommand($command);

        return $this->parseListingOutput($output);
    }

    public function deleteFile(string $smbPath): bool
    {
        if (! (bool) config('cineclean.files.allow_delete', true)) {
            throw new RuntimeException('File deletion is disabled by configuration.');
        }

        $relativePath = preg_replace(
            sprintf('/^%s\//', preg_quote($this->sharePath, '/')),
            '',
            ltrim(str_replace('\\', '/', $smbPath), '/'),
        );

        $command = sprintf(
            'cd "%s"; rm "%s"',
            $this->escapeSmbValue($this->sharePath),
            $this->escapeSmbValue($relativePath ?? $smbPath),
        );

        $this->runSmbCommand($command);

        return true;
    }

    private function runSmbCommand(string $command): string
    {
        if (! $this->binaryExists()) {
            throw new RuntimeException(
                sprintf(
                    'SMB command failed: smbclient binary not found. Install smbclient and set SMBCLIENT_BIN if needed (current: %s).',
                    $this->binary,
                ),
            );
        }

        $process = new Process([
            $this->binary,
            sprintf('//%s/%s', $this->host, $this->share),
            '-U',
            sprintf('%s%%%s', $this->username, $this->password),
            '-c',
            $command,
        ]);

        $process->setTimeout(180);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                'SMB command failed: %s',
                trim($process->getErrorOutput() ?: $process->getOutput()),
            ));
        }

        return $process->getOutput();
    }

    private function binaryExists(): bool
    {
        if (str_contains($this->binary, '/')) {
            return is_executable($this->binary);
        }

        $paths = explode(PATH_SEPARATOR, (string) getenv('PATH'));

        foreach ($paths as $path) {
            $candidate = rtrim($path, '/').'/'.$this->binary;

            if (is_executable($candidate)) {
                return true;
            }
        }

        return false;
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

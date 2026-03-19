<?php

namespace App\Services\Smb;

use Icewind\SMB\IAuth;

class PackageAuth implements IAuth
{
    public function __construct(
        private string $username,
        private ?string $workgroup,
        private string $password,
        private string $configFile,
    ) {}

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getWorkgroup(): ?string
    {
        return $this->workgroup;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getExtraCommandLineArguments(): string
    {
        $arguments = [];

        if ($this->workgroup !== null && $this->workgroup !== '') {
            $arguments[] = '-W '.escapeshellarg($this->workgroup);
        }

        if ($this->configFile !== '') {
            $arguments[] = '--option='.escapeshellarg('config file='.$this->configFile);
        }

        return implode(' ', $arguments);
    }

    /**
     * @param  resource  $smbClientState
     */
    public function setExtraSmbClientOptions($smbClientState): void {}
}

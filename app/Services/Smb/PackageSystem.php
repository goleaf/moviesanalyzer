<?php

namespace App\Services\Smb;

use Icewind\SMB\System;

class PackageSystem extends System
{
    public function __construct(private string $smbClientPath) {}

    public function getSmbclientPath(): ?string
    {
        if (is_executable($this->smbClientPath)) {
            return $this->smbClientPath;
        }

        return parent::getSmbclientPath();
    }
}

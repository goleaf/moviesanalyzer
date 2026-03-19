<?php

use App\Services\Smb\PackageAuth;
use App\Services\Smb\PackageSystem;

it('builds package auth arguments with workgroup and config file', function (): void {
    $auth = new PackageAuth('andrej', 'WORKGROUP', 'secret', '/dev/null');

    expect($auth->getUsername())->toBe('andrej')
        ->and($auth->getWorkgroup())->toBe('WORKGROUP')
        ->and($auth->getPassword())->toBe('secret')
        ->and($auth->getExtraCommandLineArguments())
        ->toContain("-W 'WORKGROUP'")
        ->toContain("--option='config file=/dev/null'");
});

it('omits optional package auth arguments when not configured', function (): void {
    $auth = new PackageAuth('andrej', null, 'secret', '');

    expect($auth->getExtraCommandLineArguments())->toBe('');
});

it('uses configured smbclient path in package system when executable', function (): void {
    $system = new PackageSystem('/bin/sh');

    expect($system->getSmbclientPath())->toBe('/bin/sh');
});

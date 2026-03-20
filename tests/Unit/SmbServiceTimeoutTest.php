<?php

use App\Services\SmbService;
use Tests\TestCase;

uses(TestCase::class);

it('caps smb timeout to stay below php max execution time', function (): void {
    $originalMaxExecutionTime = ini_get('max_execution_time');

    try {
        ini_set('max_execution_time', '30');
        config()->set('cineclean.smb.timeout_seconds', 180);

        $service = new SmbService;
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('timeoutSeconds');
        $resolvedTimeout = (int) $property->getValue($service);

        expect($resolvedTimeout)->toBe(25);
    } finally {
        ini_set('max_execution_time', (string) $originalMaxExecutionTime);
    }
});

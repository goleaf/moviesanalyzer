<?php

use App\Actions\DeleteMovieFilesAction;
use App\Models\MovieFile;
use App\Services\SmbService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes multiple files using deletion action and reports aggregate result', function (): void {
    config()->set('cineclean.files.allow_delete', true);

    $firstFile = MovieFile::query()->create([
        'smb_path' => 'Movies/Blade.Runner.1982.1080p.mkv',
        'filename' => 'Blade.Runner.1982.1080p.mkv',
        'file_size_bytes' => 3_000_000_000,
        'extension' => 'mkv',
        'tmdb_id' => 78,
        'tmdb_title' => 'Blade Runner',
        'tmdb_year' => 1982,
        'movie_year' => 1982,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $secondFile = MovieFile::query()->create([
        'smb_path' => 'Movies/Blade.Runner.1982.720p.avi',
        'filename' => 'Blade.Runner.1982.720p.avi',
        'file_size_bytes' => 2_000_000_000,
        'extension' => 'avi',
        'tmdb_id' => 78,
        'tmdb_title' => 'Blade Runner',
        'tmdb_year' => 1982,
        'movie_year' => 1982,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $smbService = Mockery::mock(SmbService::class);
    $smbService->shouldReceive('deleteFile')
        ->once()
        ->with('Movies/Blade.Runner.1982.1080p.mkv')
        ->andReturnTrue();
    $smbService->shouldReceive('deleteFile')
        ->once()
        ->with('Movies/Blade.Runner.1982.720p.avi')
        ->andReturnTrue();

    $this->app->instance(SmbService::class, $smbService);

    $result = app(DeleteMovieFilesAction::class)->handle([$firstFile->id, $secondFile->id]);

    expect($result['deleted_count'])->toBe(2)
        ->and($result['failed_count'])->toBe(0)
        ->and(count($result['results']))->toBe(2);

    expect(MovieFile::query()->whereKey($firstFile->id)->exists())->toBeFalse();
    expect(MovieFile::query()->whereKey($secondFile->id)->exists())->toBeFalse();
});

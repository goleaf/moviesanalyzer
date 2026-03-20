<?php

use App\Actions\ScanMovieLibraryAction;
use App\Data\ParsedFilename;
use App\Data\TmdbMatch;
use App\Enums\MatchStatus;
use App\Models\MovieFile;
use App\Services\FilenameParser;
use App\Services\SmbService;
use App\Services\TmdbService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('skips files scanned today when rescan all is disabled', function (): void {
    $movieFile = MovieFile::factory()->unmatched()->create([
        'smb_path' => 'Movies/The.Matrix.1999.1080p.BluRay.x264.mkv',
        'filename' => 'The.Matrix.1999.1080p.BluRay.x264.mkv',
        'parsed_clean_title' => 'Old Matrix',
        'scanned_at' => now(),
    ]);

    $originalScannedAt = $movieFile->scanned_at?->toDateTimeString();

    $smbService = Mockery::mock(SmbService::class);
    $smbService->shouldReceive('listVideoFiles')
        ->once()
        ->andReturn([
            [
                'smb_path' => 'Movies/The.Matrix.1999.1080p.BluRay.x264.mkv',
                'filename' => 'The.Matrix.1999.1080p.BluRay.x264.mkv',
                'file_size_bytes' => 1_500_000_000,
                'extension' => 'mkv',
            ],
        ]);

    $filenameParser = Mockery::mock(FilenameParser::class);
    $filenameParser->shouldNotReceive('parse');

    $tmdbService = Mockery::mock(TmdbService::class);
    $tmdbService->shouldNotReceive('match');

    $scanLog = (new ScanMovieLibraryAction($smbService, $filenameParser, $tmdbService))
        ->handle(rescanAll: false);

    $movieFile->refresh();

    expect($movieFile->parsed_clean_title)->toBe('Old Matrix')
        ->and($movieFile->scanned_at?->toDateTimeString())->toBe($originalScannedAt)
        ->and($scanLog->total_files)->toBe(1)
        ->and($scanLog->matched)->toBe(0)
        ->and($scanLog->unmatched)->toBe(0);
});

it('rescans files scanned today when rescan all is enabled', function (): void {
    $movieFile = MovieFile::factory()->unmatched()->create([
        'smb_path' => 'Movies/The.Matrix.1999.1080p.BluRay.x264.mkv',
        'filename' => 'The.Matrix.1999.1080p.BluRay.x264.mkv',
        'parsed_clean_title' => 'Old Matrix',
        'scanned_at' => now(),
    ]);

    $smbService = Mockery::mock(SmbService::class);
    $smbService->shouldReceive('listVideoFiles')
        ->once()
        ->andReturn([
            [
                'smb_path' => 'Movies/The.Matrix.1999.1080p.BluRay.x264.mkv',
                'filename' => 'The.Matrix.1999.1080p.BluRay.x264.mkv',
                'file_size_bytes' => 1_500_000_000,
                'extension' => 'mkv',
            ],
        ]);

    $filenameParser = Mockery::mock(FilenameParser::class);
    $filenameParser->shouldReceive('parse')
        ->once()
        ->with('The.Matrix.1999.1080p.BluRay.x264.mkv')
        ->andReturn(new ParsedFilename(
            originalFilename: 'The.Matrix.1999.1080p.BluRay.x264.mkv',
            baseName: 'The.Matrix.1999.1080p.BluRay.x264',
            cleanTitle: 'The Matrix',
            releaseYear: 1999,
            searchQueries: ['The Matrix'],
        ));

    $tmdbService = Mockery::mock(TmdbService::class);
    $tmdbService->shouldReceive('match')
        ->once()
        ->andReturn(TmdbMatch::fromMovie([
            'tmdb_id' => 603,
            'title' => 'Матрица',
            'original_title' => 'The Matrix',
            'release_year' => 1999,
            'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
            'overview' => 'A hacker discovers reality is a simulation.',
            'vote_average' => 8.2,
            'tmdb_url' => 'https://www.themoviedb.org/movie/603',
        ], MatchStatus::Matched, 0.99));

    $scanLog = (new ScanMovieLibraryAction($smbService, $filenameParser, $tmdbService))
        ->handle(rescanAll: true);

    $movieFile->refresh();

    expect($movieFile->parsed_clean_title)->toBe('The Matrix')
        ->and($movieFile->tmdb_id)->toBe(603)
        ->and($movieFile->tmdb_title)->toBe('Матрица')
        ->and($movieFile->movie_year)->toBe(1999)
        ->and($movieFile->match_status)->toBe(MatchStatus::Matched)
        ->and($scanLog->total_files)->toBe(1)
        ->and($scanLog->matched)->toBe(1)
        ->and($scanLog->unmatched)->toBe(0);
});

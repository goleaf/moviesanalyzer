<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanLog extends Model
{
    /** @use HasFactory<\Database\Factories\ScanLogFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'started_at',
        'finished_at',
        'total_files',
        'matched',
        'unmatched',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'total_files' => 'integer',
            'matched' => 'integer',
            'unmatched' => 'integer',
        ];
    }
}

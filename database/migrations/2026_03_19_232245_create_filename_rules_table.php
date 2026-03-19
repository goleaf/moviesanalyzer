<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filename_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_mode', 20);
            $table->string('pattern');
            $table->string('replacement')->default('');
            $table->boolean('is_regex')->default(false);
            $table->boolean('is_case_sensitive')->default(false);
            $table->boolean('whole_word')->default(true);
            $table->unsignedInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order', 'id']);
            $table->index('rule_mode');
        });

        $now = now();

        DB::table('filename_rules')->insert([
            [
                'rule_mode' => 'replace',
                'pattern' => '[._]+',
                'replacement' => ' ',
                'is_regex' => true,
                'is_case_sensitive' => false,
                'whole_word' => false,
                'sort_order' => 10,
                'is_active' => true,
                'notes' => 'Replace dots and underscores with spaces',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'rule_mode' => 'replace',
                'pattern' => '-+',
                'replacement' => ' ',
                'is_regex' => true,
                'is_case_sensitive' => false,
                'whole_word' => false,
                'sort_order' => 20,
                'is_active' => true,
                'notes' => 'Replace hyphens with spaces',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ...collect([
                '4k',
                '2160p',
                '1080p',
                '720p',
                '480p',
                'hdrip',
                'bluray',
                'bdrip',
                'webrip',
                'webdl',
                'dvdrip',
                'camrip',
                'hdtv',
                'pdtv',
                'brrip',
                'x264',
                'x265',
                'hevc',
                'avc',
                'xvid',
                'divx',
                'h264',
                'h265',
                'ac3',
                'dts',
                'aac',
                'mp3',
                'truehd',
                'atmos',
                'dd5',
                'dd51',
                'remastered',
                'yts',
                'mx',
                'rarbg',
            ])
                ->values()
                ->map(fn (string $token, int $index): array => [
                    'rule_mode' => 'remove_token',
                    'pattern' => $token,
                    'replacement' => '',
                    'is_regex' => false,
                    'is_case_sensitive' => false,
                    'whole_word' => true,
                    'sort_order' => 100 + $index,
                    'is_active' => true,
                    'notes' => 'Default removable token',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('filename_rules');
    }
};

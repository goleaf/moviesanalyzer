<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('filename_rules')) {
            return;
        }

        $now = now();
        $seedNotePrefix = 'Seeded parser default';

        $rules = [
            [
                'rule_mode' => 'replace',
                'pattern' => '\\[[^\\]]*\\]|\\([^\\)]*\\)',
                'replacement' => ' ',
                'is_regex' => true,
                'is_case_sensitive' => false,
                'whole_word' => false,
                'sort_order' => 5,
                'is_active' => true,
                'notes' => $seedNotePrefix.': remove bracket content',
            ],
            [
                'rule_mode' => 'replace',
                'pattern' => '\\bH[\\s._-]?26([45])\\b',
                'replacement' => 'H26$1',
                'is_regex' => true,
                'is_case_sensitive' => false,
                'whole_word' => false,
                'sort_order' => 6,
                'is_active' => true,
                'notes' => $seedNotePrefix.': normalize h264/h265 token',
            ],
            [
                'rule_mode' => 'replace',
                'pattern' => '\\bDDP?5[\\s._-]?1\\b',
                'replacement' => 'DDP51',
                'is_regex' => true,
                'is_case_sensitive' => false,
                'whole_word' => false,
                'sort_order' => 7,
                'is_active' => true,
                'notes' => $seedNotePrefix.': normalize ddp audio token',
            ],
            [
                'rule_mode' => 'replace',
                'pattern' => '[._]+',
                'replacement' => ' ',
                'is_regex' => true,
                'is_case_sensitive' => false,
                'whole_word' => false,
                'sort_order' => 10,
                'is_active' => true,
                'notes' => $seedNotePrefix.': normalize dots and underscores',
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
                'notes' => $seedNotePrefix.': normalize hyphens',
            ],
            [
                'rule_mode' => 'remove_token',
                'pattern' => '^\\d{3,4}p$',
                'replacement' => '',
                'is_regex' => true,
                'is_case_sensitive' => false,
                'whole_word' => false,
                'sort_order' => 80,
                'is_active' => true,
                'notes' => $seedNotePrefix.': resolution token',
            ],
            [
                'rule_mode' => 'remove_token',
                'pattern' => '^(web|dl)$',
                'replacement' => '',
                'is_regex' => true,
                'is_case_sensitive' => false,
                'whole_word' => false,
                'sort_order' => 81,
                'is_active' => true,
                'notes' => $seedNotePrefix.': split web-dl tokens',
            ],
            [
                'rule_mode' => 'remove_token',
                'pattern' => '^dd5(?:1)?$',
                'replacement' => '',
                'is_regex' => true,
                'is_case_sensitive' => false,
                'whole_word' => false,
                'sort_order' => 82,
                'is_active' => true,
                'notes' => $seedNotePrefix.': dd audio token',
            ],
            [
                'rule_mode' => 'remove_token',
                'pattern' => '^ddp\\d{1,3}$',
                'replacement' => '',
                'is_regex' => true,
                'is_case_sensitive' => false,
                'whole_word' => false,
                'sort_order' => 83,
                'is_active' => true,
                'notes' => $seedNotePrefix.': ddp audio token',
            ],
            [
                'rule_mode' => 'remove_token',
                'pattern' => '^(s\\d{1,2}e\\d{1,2}|cd\\d)$',
                'replacement' => '',
                'is_regex' => true,
                'is_case_sensitive' => false,
                'whole_word' => false,
                'sort_order' => 84,
                'is_active' => true,
                'notes' => $seedNotePrefix.': episode or disc token',
            ],
            [
                'rule_mode' => 'truncate_after_token',
                'pattern' => 'от',
                'replacement' => '',
                'is_regex' => false,
                'is_case_sensitive' => false,
                'whole_word' => true,
                'sort_order' => 85,
                'is_active' => true,
                'notes' => $seedNotePrefix.': truncate source suffix',
            ],
        ];

        $removableTokens = [
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
            'amzn',
            'sdr',
            'selezen',
            'd',
            'flex',
            'yts',
            'mx',
            'rarbg',
        ];

        foreach ($removableTokens as $index => $token) {
            $rules[] = [
                'rule_mode' => 'remove_token',
                'pattern' => $token,
                'replacement' => '',
                'is_regex' => false,
                'is_case_sensitive' => false,
                'whole_word' => true,
                'sort_order' => 100 + $index,
                'is_active' => true,
                'notes' => $seedNotePrefix.': removable token',
            ];
        }

        foreach ($rules as $rule) {
            $existingRule = DB::table('filename_rules')
                ->select(['id'])
                ->where('rule_mode', $rule['rule_mode'])
                ->where('pattern', $rule['pattern'])
                ->where('is_regex', $rule['is_regex'])
                ->first();

            if ($existingRule !== null) {
                continue;
            }

            DB::table('filename_rules')->insert(array_merge($rule, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('filename_rules')) {
            return;
        }

        DB::table('filename_rules')
            ->where('notes', 'like', 'Seeded parser default:%')
            ->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movie_files', function (Blueprint $table) {
            $table->string('parsed_base_name')->nullable()->after('extension');
            $table->string('parsed_clean_title')->nullable()->after('parsed_base_name');
            $table->json('parsed_search_queries')->nullable()->after('parsed_clean_title');
            $table->unsignedSmallInteger('parsed_release_year')->nullable()->after('parsed_search_queries');

            $table->index('parsed_clean_title');
        });
    }

    public function down(): void
    {
        Schema::table('movie_files', function (Blueprint $table) {
            $table->dropIndex(['parsed_clean_title']);
            $table->dropColumn([
                'parsed_base_name',
                'parsed_clean_title',
                'parsed_search_queries',
                'parsed_release_year',
            ]);
        });
    }
};

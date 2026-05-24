<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique('events_slug_unique');
            $table->dropColumn('slug');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->dropUnique('partners_slug_unique');
            $table->dropColumn('slug');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('slug')->unique();
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->string('slug')->unique();
        });
    }
};

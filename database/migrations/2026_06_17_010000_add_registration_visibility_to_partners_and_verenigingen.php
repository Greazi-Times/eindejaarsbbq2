<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->boolean('show_on_registration_form')
                ->default(false)
                ->after('description');
        });

        Schema::table('verenigingen', function (Blueprint $table): void {
            $table->boolean('show_on_registration_form')
                ->default(false)
                ->after('education');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->dropColumn('show_on_registration_form');
        });

        Schema::table('verenigingen', function (Blueprint $table): void {
            $table->dropColumn('show_on_registration_form');
        });
    }
};

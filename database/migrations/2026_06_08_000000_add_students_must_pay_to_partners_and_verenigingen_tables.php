<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->boolean('students_must_pay')->default(false);
        });

        Schema::table('verenigingen', function (Blueprint $table) {
            $table->boolean('students_must_pay')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('students_must_pay');
        });

        Schema::table('verenigingen', function (Blueprint $table) {
            $table->dropColumn('students_must_pay');
        });
    }
};

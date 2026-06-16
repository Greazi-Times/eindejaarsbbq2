<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->decimal('default_payment_amount', 8, 2)
                ->nullable()
                ->after('description');
        });

        Schema::table('verenigingen', function (Blueprint $table) {
            $table->string('education')
                ->nullable()
                ->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('default_payment_amount');
        });

        Schema::table('verenigingen', function (Blueprint $table) {
            $table->dropColumn('education');
        });
    }
};

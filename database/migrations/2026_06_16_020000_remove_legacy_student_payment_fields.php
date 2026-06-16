<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('student_payment_amount');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('students_must_pay');
        });

        Schema::table('verenigingen', function (Blueprint $table) {
            $table->dropColumn('students_must_pay');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->decimal('student_payment_amount', 8, 2)->nullable()->after('description');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->boolean('students_must_pay')->default(false);
        });

        Schema::table('verenigingen', function (Blueprint $table) {
            $table->boolean('students_must_pay')->default(false);
        });
    }
};

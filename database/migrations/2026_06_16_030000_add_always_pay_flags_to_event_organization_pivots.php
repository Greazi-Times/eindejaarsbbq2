<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_partner', function (Blueprint $table) {
            $table->boolean('students_always_pay')->default(false)->after('student_payment_amount');
            $table->boolean('docents_always_pay')->default(false)->after('docent_payment_amount');
        });

        Schema::table('event_vereniging', function (Blueprint $table) {
            $table->boolean('students_always_pay')->default(false)->after('student_payment_amount');
            $table->boolean('docents_always_pay')->default(false)->after('docent_payment_amount');
        });

        foreach (['event_partner', 'event_vereniging'] as $table) {
            DB::table($table)
                ->whereNotNull('student_payment_amount')
                ->where('student_payment_amount', '>', 0)
                ->update(['students_always_pay' => true]);

            DB::table($table)
                ->whereNotNull('docent_payment_amount')
                ->where('docent_payment_amount', '>', 0)
                ->update(['docents_always_pay' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('event_partner', function (Blueprint $table) {
            $table->dropColumn([
                'students_always_pay',
                'docents_always_pay',
            ]);
        });

        Schema::table('event_vereniging', function (Blueprint $table) {
            $table->dropColumn([
                'students_always_pay',
                'docents_always_pay',
            ]);
        });
    }
};

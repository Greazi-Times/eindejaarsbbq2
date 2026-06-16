<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_partner', function (Blueprint $table) {
            $table->decimal('student_payment_amount', 8, 2)->nullable()->after('over_limit_payment_amount');
            $table->decimal('docent_payment_amount', 8, 2)->nullable()->after('student_payment_amount');
        });

        Schema::table('event_vereniging', function (Blueprint $table) {
            $table->decimal('student_payment_amount', 8, 2)->nullable()->after('over_limit_payment_amount');
            $table->decimal('docent_payment_amount', 8, 2)->nullable()->after('student_payment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('event_partner', function (Blueprint $table) {
            $table->dropColumn([
                'student_payment_amount',
                'docent_payment_amount',
            ]);
        });

        Schema::table('event_vereniging', function (Blueprint $table) {
            $table->dropColumn([
                'student_payment_amount',
                'docent_payment_amount',
            ]);
        });
    }
};

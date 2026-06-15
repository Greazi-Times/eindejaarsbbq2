<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_partner', function (Blueprint $table) {
            $table->unsignedInteger('free_guest_limit')->nullable()->after('partner_id');
            $table->decimal('over_limit_payment_amount', 8, 2)->nullable()->after('free_guest_limit');
        });

        Schema::table('event_vereniging', function (Blueprint $table) {
            $table->unsignedInteger('free_guest_limit')->nullable()->after('vereniging_id');
            $table->decimal('over_limit_payment_amount', 8, 2)->nullable()->after('free_guest_limit');
        });
    }

    public function down(): void
    {
        Schema::table('event_partner', function (Blueprint $table) {
            $table->dropColumn([
                'free_guest_limit',
                'over_limit_payment_amount',
            ]);
        });

        Schema::table('event_vereniging', function (Blueprint $table) {
            $table->dropColumn([
                'free_guest_limit',
                'over_limit_payment_amount',
            ]);
        });
    }
};

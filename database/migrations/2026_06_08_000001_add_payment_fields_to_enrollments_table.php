<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->boolean('requires_payment')->default(false)->after('dietary_preferences');
            $table->string('payment_status')->nullable()->after('requires_payment');
            $table->decimal('payment_amount', 8, 2)->nullable()->after('payment_status');
            $table->string('payment_currency', 3)->nullable()->after('payment_amount');
            $table->string('mollie_payment_link_id')->nullable()->after('payment_currency');
            $table->text('mollie_payment_link_url')->nullable()->after('mollie_payment_link_id');
            $table->string('mollie_payment_id')->nullable()->after('mollie_payment_link_url');
            $table->timestamp('paid_at')->nullable()->after('mollie_payment_id');

            $table->index('mollie_payment_link_id');
            $table->index('mollie_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['mollie_payment_link_id']);
            $table->dropIndex(['mollie_payment_id']);

            $table->dropColumn([
                'requires_payment',
                'payment_status',
                'payment_amount',
                'payment_currency',
                'mollie_payment_link_id',
                'mollie_payment_link_url',
                'mollie_payment_id',
                'paid_at',
            ]);
        });
    }
};

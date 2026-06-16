<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->boolean('is_organization_member')
                ->nullable()
                ->after('partner_organization_name');
        });

        Schema::table('event_partner', function (Blueprint $table) {
            $table->boolean('members_must_pay')
                ->default(false)
                ->after('docents_always_pay');
        });

        Schema::table('event_vereniging', function (Blueprint $table) {
            $table->boolean('members_must_pay')
                ->default(false)
                ->after('docents_always_pay');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('is_organization_member');
        });

        Schema::table('event_partner', function (Blueprint $table) {
            $table->dropColumn('members_must_pay');
        });

        Schema::table('event_vereniging', function (Blueprint $table) {
            $table->dropColumn('members_must_pay');
        });
    }
};

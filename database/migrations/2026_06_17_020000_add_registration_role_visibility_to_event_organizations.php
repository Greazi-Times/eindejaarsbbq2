<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_partner', function (Blueprint $table): void {
            $table->boolean('show_for_students_docents')
                ->default(false)
                ->after('members_must_pay');
            $table->boolean('show_for_partner_companies')
                ->default(true)
                ->after('show_for_students_docents');
        });

        Schema::table('event_vereniging', function (Blueprint $table): void {
            $table->boolean('show_for_students_docents')
                ->default(false)
                ->after('members_must_pay');
            $table->boolean('show_for_partner_companies')
                ->default(true)
                ->after('show_for_students_docents');
        });
    }

    public function down(): void
    {
        Schema::table('event_partner', function (Blueprint $table): void {
            $table->dropColumn([
                'show_for_students_docents',
                'show_for_partner_companies',
            ]);
        });

        Schema::table('event_vereniging', function (Blueprint $table): void {
            $table->dropColumn([
                'show_for_students_docents',
                'show_for_partner_companies',
            ]);
        });
    }
};

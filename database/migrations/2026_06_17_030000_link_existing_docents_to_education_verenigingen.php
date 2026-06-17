<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('enrollments as enrollments')
            ->join('event_vereniging', 'event_vereniging.event_id', '=', 'enrollments.event_id')
            ->join('verenigingen', 'verenigingen.id', '=', 'event_vereniging.vereniging_id')
            ->where('enrollments.type', 'docent')
            ->whereNotNull('enrollments.education')
            ->whereNull('enrollments.student_association')
            ->whereNull('enrollments.partner_organization_name')
            ->whereColumn('verenigingen.education', 'enrollments.education')
            ->select([
                'enrollments.id',
                'verenigingen.name',
            ])
            ->orderBy('enrollments.id')
            ->eachById(function (object $enrollment): void {
                DB::table('enrollments')
                    ->where('id', $enrollment->id)
                    ->update([
                        'student_association' => $enrollment->name,
                        'partner_organization_type' => 'vereniging',
                        'partner_organization_name' => $enrollment->name,
                        'is_organization_member' => null,
                        'updated_at' => now(),
                    ]);
            }, column: 'enrollments.id', alias: 'id');
    }

    public function down(): void
    {
        //
    }
};

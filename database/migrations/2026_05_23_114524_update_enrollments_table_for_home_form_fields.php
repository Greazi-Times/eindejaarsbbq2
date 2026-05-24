<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'phone',
                'amount_of_people',
            ]);

            $table->string('full_name')->after('event_id');
            $table->string('type')->after('email');

            $table->string('student_association')->nullable()->after('type');
            $table->string('custom_student_association')->nullable()->after('student_association');

            $table->string('education')->nullable()->after('custom_student_association');
            $table->string('custom_education')->nullable()->after('education');

            $table->string('company_name')->nullable()->after('custom_education');

            $table->unsignedInteger('guest_amount')->default(1)->after('company_name');

            $table->json('dietary_preferences')->nullable()->after('guest_amount');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'full_name',
                'type',
                'student_association',
                'custom_student_association',
                'education',
                'custom_education',
                'company_name',
                'guest_amount',
                'dietary_preferences',
            ]);

            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->unsignedInteger('amount_of_people')->default(1);
        });
    }
};

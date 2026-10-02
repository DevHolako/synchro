<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Official surname and given name, the order of exam room splits (spec 04). `users.name`
        // stays the display name a student may edit.
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('last_name', 100)->nullable()->after('user_id');
            $table->string('first_name', 100)->nullable()->after('last_name');
        });

        // Existing students: the last word of the display name is taken as the surname.
        DB::table('student_profiles')
            ->join('users', 'users.id', '=', 'student_profiles.user_id')
            ->select(['student_profiles.id', 'users.name'])
            ->orderBy('student_profiles.id')
            ->each(function (object $row): void {
                $name = trim((string) $row->name);
                $space = mb_strrpos($name, ' ');

                DB::table('student_profiles')->where('id', $row->id)->update([
                    'last_name' => $space === false ? $name : mb_substr($name, $space + 1),
                    'first_name' => $space === false ? '' : mb_substr($name, 0, $space),
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['last_name', 'first_name']);
        });
    }
};

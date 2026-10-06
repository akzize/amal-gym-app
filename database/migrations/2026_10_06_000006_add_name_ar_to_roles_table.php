<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ARABIC_NAMES = [
        'super_admin' => 'المدير العام',
        'admin' => 'مدير',
        'subadmin' => 'مدير مساعد',
        'staff' => 'موظف',
        'trainer' => 'مدرب',
    ];

    public function up(): void
    {
        $table = config('permission.table_names.roles');

        Schema::table($table, function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
        });

        // Arabic names for the roles that already exist
        foreach (self::ARABIC_NAMES as $name => $nameAr) {
            DB::table($table)->where('name', $name)->update(['name_ar' => $nameAr]);
        }
    }

    public function down(): void
    {
        Schema::table(config('permission.table_names.roles'), function (Blueprint $table) {
            $table->dropColumn('name_ar');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     * Tracks when a ticket last changed status, so time-in-status rules
     * (stale notifications, escalations) can be evaluated.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('tickets', 'status_changed_at')) {
            return;
        }
        Schema::table('tickets', function (Blueprint $table) {
            $table->dateTime('status_changed_at')->nullable()->after('status')->index();
        });

        // No status history exists, so seed with updated_at as the closest approximation.
        DB::table('tickets')->update(['status_changed_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('tickets', 'status_changed_at')) {
            return;
        }
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('status_changed_at');
        });
    }
};

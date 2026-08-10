<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     * Lets a rule also notify whoever opened the ticket, on top of the
     * fixed address list.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('ticket_status_notifications', 'notify_creator')) {
            return;
        }
        Schema::table('ticket_status_notifications', function (Blueprint $table) {
            $table->boolean('notify_creator')->default(false)->after('recipients');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('ticket_status_notifications', 'notify_creator')) {
            return;
        }
        Schema::table('ticket_status_notifications', function (Blueprint $table) {
            $table->dropColumn('notify_creator');
        });
    }
};

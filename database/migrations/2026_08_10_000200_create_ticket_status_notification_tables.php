<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     * Rules for "ticket stuck in status X for N days -> email these people",
     * plus a log so the same ticket is not notified twice for the same
     * status change.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('ticket_status_notifications')) {
            Schema::create('ticket_status_notifications', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('status_id')->unsigned()->index();
                $table->integer('days')->unsigned()->default(2);
                $table->text('recipients');           // comma separated email addresses
                $table->boolean('enabled')->default(true);
                $table->boolean('repeat_daily')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ticket_status_notification_log')) {
            Schema::create('ticket_status_notification_log', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('rule_id')->unsigned()->index();
                $table->integer('ticket_id')->unsigned()->index();
                $table->dateTime('status_changed_at')->nullable();
                $table->dateTime('notified_at');
                $table->unique(['rule_id', 'ticket_id', 'status_changed_at'], 'tsn_log_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_status_notification_log');
        Schema::dropIfExists('ticket_status_notifications');
    }
};

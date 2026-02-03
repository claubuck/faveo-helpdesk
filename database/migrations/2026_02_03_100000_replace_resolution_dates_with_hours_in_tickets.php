<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['estimated_resolution_date', 'actual_resolution_date']);
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->decimal('estimated_resolution_hours', 8, 2)->nullable()->after('duedate');
            $table->decimal('actual_resolution_hours', 8, 2)->nullable()->after('estimated_resolution_hours');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['estimated_resolution_hours', 'actual_resolution_hours']);
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->dateTime('estimated_resolution_date')->nullable()->after('duedate');
            $table->dateTime('actual_resolution_date')->nullable()->after('estimated_resolution_date');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     * Adds "In progress" / "En curso" ticket status (id 8).
     *
     * @return void
     */
    public function up()
    {
        $exists = DB::table('ticket_status')->where('id', 8)->exists();
        if ($exists) {
            return;
        }
        DB::table('ticket_status')->insert([
            'id' => 8,
            'name' => 'In progress',
            'state' => 'open',
            'mode' => 3,
            'message' => 'El ticket está en curso por',
            'flags' => 0,
            'sort' => 8,
            'email_user' => null,
            'icon_class' => null,
            'properties' => 'Ticket is being worked on by an agent.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('ticket_status')->where('id', 8)->delete();
    }
};

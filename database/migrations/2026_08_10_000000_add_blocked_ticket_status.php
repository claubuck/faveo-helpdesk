<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     * Adds "Blocked" / "Bloqueado" ticket status (id 9).
     *
     * @return void
     */
    public function up()
    {
        $exists = DB::table('ticket_status')->where('id', 9)->exists();
        if ($exists) {
            return;
        }
        DB::table('ticket_status')->insert([
            'id' => 9,
            'name' => 'Blocked',
            'state' => 'open',
            'mode' => 3,
            'message' => 'El ticket fue bloqueado por',
            'flags' => 0,
            'sort' => 9,
            'email_user' => null,
            'icon_class' => null,
            'properties' => 'Ticket is blocked waiting on an external dependency.',
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
        DB::table('ticket_status')->where('id', 9)->delete();
    }
};

<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');

            // check driver database
            $connection = config('database.default');
            $driver = DB::connection($connection)->getDriverName();

            if ($driver === 'pgsql') {
                $table->jsonb('data')->nullable(); // PostgreSQL
            } else {
                $table->text('data'); // MySQL, SQLite, SQL Server
            }
            
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

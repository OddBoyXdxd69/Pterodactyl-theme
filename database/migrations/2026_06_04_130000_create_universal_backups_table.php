<?php

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
        Schema::create('universal_backups', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('node_id')->nullable();
            $table->unsignedInteger('server_id')->nullable();
            $table->string('backup_type');
            $table->string('status')->default('pending');
            $table->string('file_id')->nullable();
            $table->string('filename');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->foreign('node_id')->references('id')->on('nodes')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('universal_backups');
    }
};

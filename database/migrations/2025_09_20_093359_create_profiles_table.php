<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('username')->index();
            $table->string('name')->nullable();
            $table->text('bio')->nullable();
            $table->json('metadata')->nullable(); // store raw parsed JSON or array
            $table->json('sources')->nullable();  // e.g. list of scraped URLs / snapshots
            $table->unsignedBigInteger('likes')->nullable()->default(0);
            $table->timestamps();
            $table->unique('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};

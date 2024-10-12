<?php

use App\Models\Episode;
use App\Models\Show;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shows', function (Blueprint $table) {
            $table->id();

            $table->string('code');
            $table->string('title');

            $table->string('color');

            $table->timestamps();
        });

        Schema::create('episodes', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Show::class)->constrained()->restrictOnDelete();

            $table->string('title');

            $table->integer('season');
            $table->integer('episode_number');

            $table->date('air_date');
            $table->text('wiki_url');

            $table->timestamps();
        });

        Schema::create('views', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(Episode::class)->constrained()->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
        Schema::dropIfExists('shows');
        Schema::dropIfExists('views');
    }
};

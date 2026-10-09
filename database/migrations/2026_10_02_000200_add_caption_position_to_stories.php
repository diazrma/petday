<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Posição da legenda na foto, em % (centro da etiqueta)
        Schema::table('stories', function (Blueprint $table) {
            $table->unsignedTinyInteger('caption_x')->nullable()->after('caption');
            $table->unsignedTinyInteger('caption_y')->nullable()->after('caption_x');
        });
    }

    public function down(): void
    {
        Schema::table('stories', fn (Blueprint $table) => $table->dropColumn(['caption_x', 'caption_y']));
    }
};

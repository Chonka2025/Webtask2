<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Таблица boards — доски (Trello-подобные).
 *
 * owner_user_id — владелец доски (ссылка на users.user_id). Связь логическая:
 * обычный INT без явного FOREIGN KEY, как в ТЗ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('boards', function (Blueprint $table) {
            $table->increments('board_id');
            $table->string('title', 255);
            $table->unsignedInteger('owner_user_id');
            $table->boolean('is_public')->default(false);
            $table->timestamp('created_datetime');
            $table->timestamp('updated_datetime')->nullable();
        });
    }

    public function down(): void
    {
        Capsule::schema()->drop('boards');
    }
};

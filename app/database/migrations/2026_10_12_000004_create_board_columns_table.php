<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Таблица board_columns — колонки внутри доски ("To do", "In progress", ...).
 *
 * ordinal_position — порядковый номер колонки на доске (SMALLINT).
 * board_id — индексируется (idx_board_id), т.к. колонки почти всегда
 * запрашиваются в рамках одной доски.
 *
 * У этой таблицы временных меток нет — как в ТЗ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('board_columns', function (Blueprint $table) {
            $table->increments('column_id');
            $table->unsignedInteger('board_id');
            $table->smallInteger('ordinal_position');
            $table->string('title', 255);
            $table->string('color', 6)->nullable();

            $table->index('board_id', 'idx_board_id');
        });
    }

    public function down(): void
    {
        Capsule::schema()->drop('board_columns');
    }
};

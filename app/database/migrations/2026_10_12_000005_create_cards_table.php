<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Таблица cards — карточки на доске.
 *
 * board_id   — доска, к которой относится карточка.
 * column_id  — колонка, в которой лежит карточка.
 * sort_index — позиция внутри колонки (SMALLINT).
 * is_archived — заархивирована ли карточка.
 *
 * Частичный индекс idx_active_board_id покрывает только неархивные карточки
 * конкретной доски — по ним идёт основной запрос доски, архив в индекс
 * не попадает (экономия места и ускорение выборки активных карточек).
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('cards', function (Blueprint $table) {
            $table->increments('card_id');
            $table->unsignedInteger('board_id');
            $table->unsignedInteger('column_id');
            $table->smallInteger('sort_index');
            $table->string('title', 255);
            $table->text('content')->nullable();
            $table->string('color', 6)->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('created_datetime');
            $table->timestamp('updated_datetime')->nullable();
        });

        // Частичный индекс: WHERE is_archived = false.
        // Делаем через raw SQL, т.к. Schema builder (Blueprint::index()->where())
        // в этой версии illuminate не прогоняет предикат и создаёт обычный btree.
        Capsule::connection()->statement(
            'CREATE INDEX idx_active_board_id ON cards (board_id) WHERE is_archived = false'
        );
    }

    public function down(): void
    {
        Capsule::schema()->drop('cards');
    }
};

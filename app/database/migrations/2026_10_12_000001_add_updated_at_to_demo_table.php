<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Таблицу demo изначально создал initdb-скрипт (docker/postgres/initdb/01-init.sql),
 * и там есть только created_at. Eloquent по умолчанию управляет двумя колонками
 * времени — created_at и updated_at, поэтому дополняем схему недостающей колонкой.
 *
 * Фасад Schema в standalone-приложении недоступен, поэтому используем Capsule::schema().
 * Capsule подключён как глобальный (Bootstrap::db() вызывает setAsGlobal()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->table('demo', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Capsule::schema()->table('demo', function (Blueprint $table) {
            $table->dropColumn('updated_at');
        });
    }
};

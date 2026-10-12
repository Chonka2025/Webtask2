<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Таблица users — пользователи приложения.
 *
 * Первичный ключ user_id — SERIAL (auto-increment). Временные метки
 * нестандартные (created_datetime / updated_datetime), как в ТЗ, поэтому
 * описаны явно, а не через $table->timestamps().
 *
 * Связи с другими таблицами — через обычные INT-колонки и индексы (без
 * явных FOREIGN KEY): целостность обеспечивается на уровне приложения.
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('email', 255);
            $table->string('password_hash', 255);
            $table->string('name', 255);
            $table->timestamp('created_datetime');
            $table->timestamp('updated_datetime')->nullable();

            // Уникальный индекс по email — логин не может повторяться
            $table->unique('email', 'idx_email');
        });
    }

    public function down(): void
    {
        Capsule::schema()->drop('users');
    }
};

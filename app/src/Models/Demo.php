<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель таблицы demo (её создал initdb-скрипт, а updated_at добавила миграция).
 *
 * Имя класса выводится автоматически: Demo -> таблица "demos". Так как у нас
 * исходная таблица называется "demo" (единственное число), имя указываем явно.
 */
class Demo extends Model
{
    protected $table = 'demo';

    protected $fillable = ['note'];

    // PostgreSQL возвращает id как bigint; по умолчанию Eloquent не кастит тип,
    // а здесь это просто для корректного строгого типирования
    protected $casts = [
        'id'         => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

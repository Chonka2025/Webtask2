<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Колонка доски (таблица board_columns).
 *
 * У этой таблицы по ТЗ нет временных меток — поэтому отключаем их
 * ($timestamps = false), иначе Eloquent попытается писать created_at/updated_at.
 */
class BoardColumn extends Model
{
    protected $table = 'board_columns';

    protected $primaryKey = 'column_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = ['board_id', 'ordinal_position', 'title', 'color'];

    /** Доска, которой принадлежит колонка. */
    public function board()
    {
        return $this->belongsTo(Board::class, 'board_id', 'board_id');
    }

    /** Карточки в этой колонке. */
    public function cards()
    {
        return $this->hasMany(Card::class, 'column_id', 'column_id');
    }
}

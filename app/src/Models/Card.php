<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Карточка (таблица cards). Лежит в колонке конкретной доски.
 */
class Card extends Model
{
    protected $table = 'cards';

    protected $primaryKey = 'card_id';

    public $incrementing = true;

    protected $keyType = 'int';

    const CREATED_AT = 'created_datetime';
    const UPDATED_AT = 'updated_datetime';

    protected $fillable = [
        'board_id', 'column_id', 'sort_index',
        'title', 'content', 'color', 'is_archived',
    ];

    /** Доска, которой принадлежит карточка. */
    public function board()
    {
        return $this->belongsTo(Board::class, 'board_id', 'board_id');
    }

    /** Колонка, в которой лежит карточка. */
    public function column()
    {
        return $this->belongsTo(BoardColumn::class, 'column_id', 'column_id');
    }
}

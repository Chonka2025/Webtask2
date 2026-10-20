<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Доска (таблица boards). owner_user_id — владелец (users.user_id).
 */
class Board extends Model
{
    protected $table = 'boards';

    protected $primaryKey = 'board_id';

    public $incrementing = true;

    protected $keyType = 'int';

    const CREATED_AT = 'created_datetime';
    const UPDATED_AT = 'updated_datetime';

    protected $fillable = ['title', 'owner_user_id', 'is_public'];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /** Владелец доски (boards.owner_user_id → users.user_id). */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id', 'user_id');
    }

    /** Колонки этой доски (board_columns.board_id → boards.board_id). */
    public function columns()
    {
        return $this->hasMany(BoardColumn::class, 'board_id', 'board_id');
    }
}

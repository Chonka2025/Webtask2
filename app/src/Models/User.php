<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Пользователь (таблица users).
 *
 * Первичный ключ — user_id (не стандартный id), временные метки —
 * created_datetime / updated_datetime (не created_at / updated_at):
 * это имена из ТЗ, поэтому переопределяем константы Eloquent.
 */
class User extends Model
{
    protected $table = 'users';

    protected $primaryKey = 'user_id';

    public $incrementing = true;

    protected $keyType = 'int';

    const CREATED_AT = 'created_datetime';
    const UPDATED_AT = 'updated_datetime';

    protected $fillable = ['email', 'password_hash', 'name'];

    /** Доски, которыми владеет пользователь. */
    public function boards()
    {
        return $this->hasMany(Board::class, 'owner_user_id', 'user_id');
    }
}

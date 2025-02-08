<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // 加入這行

class User extends Authenticatable
{
    use HasApiTokens, Notifiable; // 使用 HasApiTokens trait

    /**
     * 可大量賦值的屬性
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // 其他設定或方法...
}

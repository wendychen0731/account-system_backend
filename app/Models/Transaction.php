<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'date', 'amount', 'type', 'description'
    ];

    // 可選：設定與 User 的關聯
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

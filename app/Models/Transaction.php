<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'userID', 'foodID', 'quantity',
        'total', 'status', 'paymentUrl'
    ];

    // relasi
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'userID');
    }

    public function food()
    {
        return $this->hasOne(Food::class, 'id', 'foodID');
    }

    // epoch UNIX time
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->timestamp;
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->timestamp;
    }
}

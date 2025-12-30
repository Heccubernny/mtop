<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CkTransaction extends Model
{
    use HasFactory;

    protected $table = 'ck_transactions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'request_id',
        'order_id',
        'provider',
        'type',
        'network',
        'plan',
        'mobile',
        'amount',
        'status',
        'additional_info',
        'response_body',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'amount' => 'float',
        'response_body' => 'array',
        'additional_info' => 'array',
    ];

    /**
     * User relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

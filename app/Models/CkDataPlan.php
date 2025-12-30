<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CkDataPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'mobile_network_id',
        'plan_code',
        'description',
        'price'
    ];

    public function network()
    {
        return $this->belongsTo(CkMobileNetwork::class);
    }
}

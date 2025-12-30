<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CkMobileNetwork extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'code', 'slug'];

    public function plans()
    {
        return $this->hasMany(CkDataPlan::class);
    }
}

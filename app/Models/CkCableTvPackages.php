<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CkCableTvPackages extends Model
{
    use HasFactory;
    protected $fillable = ['cable_tv', 'package_code', 'description', 'price'];

    public function network()
    {
        return $this->belongsTo(CkCableTvPackages::class);
    }
}

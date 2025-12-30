<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CkCableTv extends Model
{
    use HasFactory;
    use HasFactory;
    protected $fillable = ['name', 'code', 'slug'];

    public function plans()
    {
        return $this->hasMany(related: CkCableTvPackages::class);
    }
}

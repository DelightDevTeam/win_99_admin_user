<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
    ];

    protected $appends = ['img_url'];

    public function getImgUrlAttribute()
    {
        return asset('https://win99mm.online/assets/img/banners/'.$this->image);
    }

}

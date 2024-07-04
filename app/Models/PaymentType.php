<?php

namespace App\Models;

use App\Models\UserPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'image'];

    protected $appends = ['img_url'];

    public function getImgUrlAttribute()
    {
        return asset('assets/img/paymentType/'.$this->image);
    }

    public function paymentImages()
    {
        return $this->hasMany(PaymentImage::class, 'payment_type_id');
    }

    public function userPayments(): HasMany
    {
        return $this->hasMany(UserPayment::class);
    }
}

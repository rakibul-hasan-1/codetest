<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    public function product()
    {
        return $this->belongsTo(Product::class,'id','product_id');
    }
    public function variantone(){
        return $this->hasOne(ProductVariant::class,'id','product_variant_one');
    }
    public function varianttwo(){
        return $this->hasOne(ProductVariant::class,'id','product_variant_two');
    }
    public function variantthree(){
        return $this->hasOne(ProductVariant::class,'id','product_variant_three');
    }
}

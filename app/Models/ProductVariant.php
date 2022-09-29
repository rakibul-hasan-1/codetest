<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    public function variant()
    {
        return $this->hasOne(Variant::class,'id','variant_id');
    }
    public function productimage()
    {
        return $this->hasOne(ProductImage::class,'product_id','product_id');
    }
    public function variantpriceone(){
        return $this->belongsTo(ProductVariantPrice::class,'product_variant_one');
    }
    public function variantpricetwo(){
        return $this->belongsTo(ProductVariantPrice::class,'product_variant_two');
    }
    public function variantpricethree(){
        return $this->belongsTo(ProductVariantPrice::class,'product_variant_three');
    }
}

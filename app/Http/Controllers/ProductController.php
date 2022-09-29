<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use App\Models\ProductImage;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
        $products=Product::orderBy('id','DESC')->paginate(3);
        $pv=ProductVariant::whereHas('variant',function($query){$query->groupby('title');})->groupby('variant')->orderBy('variant_id','asc')->get();
        return view('products.index',compact('products','pv'));
    }
    public function filter(Request $request){
        $title=$request->title;
        $price_from=$request->price_from;
        $price_to=$request->price_to;
        $variant=$request->variant;
        // dd($variant);
        $date=$request->date;
        if($title == null || $title == ""){
            $products=Product::whereHas('productvariantprice',function($query) use ($price_from,$price_to,$variant){$query->whereBetween('price',[$price_from,$price_to])->orWhereHas('variantone',function($query1) use ($variant){$query1->where('variant',$variant);})->orWhereHas('varianttwo',function($query2) use ($variant){$query2->where('variant',$variant);})->orWhereHas('variantthree',function($query3) use ($variant){$query3->where('variant',$variant);});})->whereDate('created_at',$date)->orderBy('id','DESC')->paginate(3);
        }else{
            $products=Product::where('title','LIKE','%'.$title.'%')->whereHas('productvariantprice',function($query) use ($price_from,$price_to,$variant){$query->whereBetween('price',[$price_from,$price_to])->orWhereHas('variantone',function($query1) use ($variant){$query1->where('variant',$variant);})->orWhereHas('varianttwo',function($query2) use ($variant){$query2->where('variant',$variant);})->orWhereHas('variantthree',function($query3) use ($variant){$query3->where('variant',$variant);});})->whereDate('created_at',$date)->orderBy('id','DESC')->paginate(3);
        }
        
        $pv=ProductVariant::whereHas('variant',function($query){$query->groupby('title');})->groupby('variant')->orderBy('variant_id','asc')->get();
        // dd($products);
        return view('products.index',compact('products','pv'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitimage(Request $request){
        if(isset($request->file) && count($request->file)>0){
            foreach($request->file as $key=>$file){
                $imageName = time().$key.'.'.$file->getClientOriginalExtension();
                $file->move(public_path('images'), $imageName);    
                $name[]=$imageName;
            }
        }
             
    	return response()->json(['name'=>$name]);
    }
    public function store(Request $request)
    {
        
        //  return $request->product_variant_prices;
         $product=new Product();
         $product->title=$request->title;
         $product->sku=$request->sku;
         $product->description=$request->description;
         $product->save();

         if(isset($request->product_image) && count($request->product_image)>0){
            foreach($request->product_image as $image){
                $product_image=new ProductImage();
                $product_image->product_id=$product->id;
                $product_image->file_path = $image;
                $product_image->thumbnail =$image;
                $product_image->save();
            }
         }
         if(isset($request->product_variant) && count($request->product_variant)>0){
            foreach($request->product_variant as $pv){
                foreach($pv['tags'] as $item){
                    $vv=new ProductVariant();
                    $vv->variant=$item;
                    $vv->variant_id=$pv['option'];
                    $vv->product_id=$product->id;
                    $vv->save();
                }
            }
         }
         if(isset($request->product_variant_prices) && count($request->product_variant_prices)>0){
            foreach($request->product_variant_prices as $key=>$vprice){
                $productvrprice=new ProductVariantPrice();
                $title=explode('/',$vprice['title']);
                if(isset($title[0])){
                    $vv=ProductVariant::where('variant',$title[0])->where('product_id',$product->id)->first();
                    $productvrprice->product_variant_one=$vv->id ?? null;
                }
                if(isset($title[1])){
                    $vv1=ProductVariant::where('variant',$title[1])->where('product_id',$product->id)->first();
                    $productvrprice->product_variant_two=$vv1->id ?? null;
                }
                if(isset($title[2])){
                    $vv2=ProductVariant::where('variant',$title[2])->where('product_id',$product->id)->first();
                    $productvrprice->product_variant_three=$vv2->id ?? null;
                }
                $productvrprice->price=$vprice['price'];
                $productvrprice->stock=$vprice['stock'];
                $productvrprice->product_id=$product->id;
                $productvrprice->save();
            }
         }
         
         return response()->json([
            'product'=>$product
        ]);

    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function getpvprice(Request $request){
        $productprice=ProductVariantPrice::where('product_id',$request->id)->get();
        foreach($productprice as $key=>$vr){
            $pvprice[$key]['id']=$vr->id;
            if(isset($vr->product_variant_one) && isset($vr->product_variant_two) && isset($vr->product_variant_three)){
                $pvprice[$key]['title']=$vr->variantone->variant.'/'.$vr->varianttwo->variant.'/'.$vr->variantthree->variant.'/';
            }elseif(isset($vr->product_variant_one) && isset($vr->product_variant_two) && $vr->product_variant_three==null){
                $pvprice[$key]['title']=$vr->variantone->variant.'/'.$vr->varianttwo->variant.'/';
            
            }elseif(isset($vr->product_variant_one) && $vr->product_variant_two==null && $vr->product_variant_three==null){
                $pvprice[$key]['title']=$vr->variantone->variant.'/';
            }elseif(isset($vr->product_variant_one) && $vr->product_variant_two==null && isset($vr->product_variant_three)){
                $pvprice[$key]['title']=$vr->variantone->variant.'/'.$vr->variantthree->variant.'/';
            }
            $pvprice[$key]['price']=$vr->price;
            $pvprice[$key]['stock']=$vr->stock;
        }
        return $pvprice;
    }
    public function getpicture(Request $request){
        $productimage=ProductImage::where('product_id',$request->id)->get();
        return $productimage;
    }
    public function edit(Product $product)
    {
        $variants = Variant::all();
        $productvariant=ProductVariant::where('product_id',$product->id)->groupBy('variant_id')->get();
        
        return view('products.edit', compact('variants','product','productvariant'));
    }
    public function gettags(Request $request){
        $tags=ProductVariant::where('product_id',$request->product_id)->where('variant_id',$request->id)->select('variant')->get();
        foreach($tags as $tsg){
            $tg[]=$tsg->variant;
        }
        return $tg;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }
    public function updateproduct(Request $request){
        $product=Product::find($request->id);
        $product->title=$request->title;
        $product->sku=$request->sku;
        $product->description=$request->description;
        $product->update();
        ProductVariant::where('product_id',$request->id)->delete();
        ProductVariantPrice::where('product_id',$request->id)->delete();
        if(isset($request->product_image) && count($request->product_image)>0){
            foreach($request->product_image as $image){
                $product_image=new ProductImage();
                $product_image->product_id=$product->id;
                $product_image->file_path = $image;
                $product_image->thumbnail =$image;
                $product_image->save();
            }
         }
         if(isset($request->product_variant) && count($request->product_variant)>0){
            foreach($request->product_variant as $pv){
                foreach($pv['tags'] as $item){
                    $vv=new ProductVariant();
                    $vv->variant=$item;
                    $vv->variant_id=$pv['option'];
                    $vv->product_id=$product->id;
                    $vv->save();
                }
            }
         }
         if(isset($request->product_variant_prices) && count($request->product_variant_prices)>0){
            foreach($request->product_variant_prices as $key=>$vprice){
                $productvrprice=new ProductVariantPrice();
                $title=explode('/',$vprice['title']);
                if(isset($title[0])){
                    $vv=ProductVariant::where('variant',$title[0])->where('product_id',$product->id)->first();
                    $productvrprice->product_variant_one=$vv->id ?? null;
                }
                if(isset($title[1])){
                    $vv1=ProductVariant::where('variant',$title[1])->where('product_id',$product->id)->first();
                    $productvrprice->product_variant_two=$vv1->id ?? null;
                }
                if(isset($title[2])){
                    $vv2=ProductVariant::where('variant',$title[2])->where('product_id',$product->id)->first();
                    $productvrprice->product_variant_three=$vv2->id ?? null;
                }
                $productvrprice->price=$vprice['price'];
                $productvrprice->stock=$vprice['stock'];
                $productvrprice->product_id=$product->id;
                $productvrprice->save();
            }
         }
         
         return response()->json([
            'product'=>$product
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}

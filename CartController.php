<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use App\AppUser;
use App\user_profiles;
use DB;
use Auth;
use App\user_carts;
use App\cart_addons;
use App\cart_addon_items;
use Carbon;
use Log;
// use Illuminate\Support\Facades\Log;

class CartController extends Controller {

//===============ADD TO CART API=================//  
    public function cartcount(Request $request) {
        if (Auth::check()) {
            $uid = Auth::user()->uid;
             $validator = Validator::make($request->all(), [
                            'outlet_uid'=>'required'
                ]);
                
        //if product_id is null 
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 403)->header('status', 403);
          }
            $check_cart_count = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->count();
            if ($check_cart_count > 0) {
                return response()->json(['success' => true, "count" => $check_cart_count], 200)->header('status', 200);
            } else {
                return response()->json(["success" => false, 'message' => "Your cart is empty."], 202)->header('status', 202);
            }
        }
    }

//===============ADD TO CART API===================//
    public function add_to_cart(Request $request) {
Log::emergency($request);
        //dd($request);
        if (Auth::check()) {
            $user = Auth::user();
            $uid = Auth::user()->uid;

            $validator = Validator::make($request->all(), [
                        'product_id' => 'required',
                        'qty' => 'required|numeric',
                        'outlet_uid'=>'required',
                        'varient_id'=>'required'
            ]);
            if ($validator->fails()) {
                // return redirect()->back()->with('errors',$validator->errors())->withInput($request->only('phone', 'remember'));
                return response()->json(['message' => $validator->errors()], 403);
            } else {
                

                $cart_item_uid = 'cart_item_uid_' . uniqid(time() . mt_rand());
                $products = DB::table('outlet_products')->where('outlet_uid', $request->outlet_uid)->where('product_id',$request->product_id)->get();
                $product_url = config('app.web_url') . '/' . str_slug($products[0]->product_name) . '/a/' . $products[0]->product_web_id;
                 //-------------------FIND TAX PERCENTAGE START HERE-----------------------------//
                 if (!empty($products[0]->tax_slab_id) || ($products[0]->tax_slab_id != null)) {
                    $check_tax_id = DB::table('tax_settings')->where('id', $products[0]->tax_slab_id)->first();

                    if ($check_tax_id != null) {
                        $item_tax_percentage = DB::table('tax_settings')->where('id', $products[0]->tax_slab_id)->value('percentage');
                    }
                } else {
                    $item_tax_percentage = 0;
                }
                $product_offer_status = $products[0]->offer_id;
                //---------------------FIND TAX PERCENTAGE END HERE--------------------------//
               
                if ($product_offer_status != null) {

                    $checkitemsinoffer = DB::table('items_in_offers')->where('outlet_uid',$request->outlet_uid)->where('product_id', $product_data->product_id)->count();
                    //if item found in offer
                    if ($checkitemsinoffer > 0) {
                        $item_offer_amount = DB::table('items_in_offers')->where('outlet_uid',$request->outlet_uid)->where('product_id', $product[0]->product_id)->value('offer_amount');
                        $offer_times = DB::table('offer_times')->where('outlet_uid',$request->outlet_uid)->where('id', $product[0]->offer_id)->first();

                        $offer_name = $offer_times->offer_name;
                        $offer_status = $offer_times->status;
                        $offer_percentage = $item_offer_amount;
                        $offer_end_time = $offer_times->offer_end_time;
                    } else {
                        $offer_name = null;
                        $offer_status = null;
                        $offer_percentage = 0;
                        $offer_end_time = null;
                    }

                    //else item is not in offer
  
                } else {
                    $offer_name = null;
                    $offer_status = null;
                    $offer_percentage = 0;
                    $offer_end_time = null;
                }
                $product_varient = DB::table('current_product_sizes')->where('size_id', $request->varient_id)->where('outlet_uid', $request->outlet_uid)->where('product_id', $request->product_id)->get();

                $size_data_final = null;
                $color_data_final = null;
                if ($product_varient->count() >0 ) {
                    $size_data = DB::table('current_product_sizes')->where('size_id', $request->varient_id)->where('outlet_uid', $request->outlet_uid)->where('product_id', $request->product_id)->get();
                    foreach ($size_data as $size) {
                        $size_data_final[] = array(
                            "id" => $size->size_id,
                            "name" => $size->size_name,
                            "mrp" => $size->mrp,
                            "sp" => $size->sp,
                            "stock" => $size->stock,
                            "status" => $size->status,
                            "primary_image" => $request->root() . '/' . $size->primary_image,
                            "variant_cart_status" => 0,
                            "item_qty" => 0
                        );
                    }
                } 
                // elseif ( $products[0]->variant_status == 0 &&  $products[0]->color_status == 1) {
        
                //     $color_data = DB::table('current_product_colors')->where('outlet_uid', $request->outlet_uid)->where('product_id', $request->product_id)->get();
                //         foreach ($color_data as $color) {
                //             $color_data_final[] = array(
                //                 "id" => $color->color_id,
                //                 "color_code" => $color->color_code,
                //                 "name" => $color->color_name,
                //                 "mrp" => $color->mrp,
                //                 "sp" => $color->sp,
                //                 "stock" => $color->stock,
                //                 "status" => $color->status,
                //                 "primary_image" => Request::root() . '/' . $color->primary_image,
                //                 "variant_cart_status" => 0,
                //                 "item_qty" => 0
                //             );
                //         }

        
     
                // }
        
        

                $response = [
                    "id"=>$request->product_id,
                    "product_name"=>$products[0]->product_name,
                    "cart_item_uid"=> $cart_item_uid,
                    "stock"=> $products[0]->stock,
                    "stock_status"=> $products[0]->stock_status,
                    "stock_message"=> $products[0]->stock_message,
                    "product_url"=> $product_url,
                    "primary_image"=> asset($products[0]->primary_image),
                    "product_mrp"=> $products[0]->product_mrp,
                    "product_sp"=> $products[0]->product_sp,
                    "product_limit"=> $products[0]->product_limit,
                    "product_status"=> $products[0]->product_status,
                    "offer_name"=> $offer_name,
                    "offer_status"=>  $offer_status,
                    "offer_percentage"=> $offer_percentage,
                    "offer_end_time"=> $offer_end_time,
                    "item_qty"=> $request->qty,
                    "size"=>$size_data_final ,
                    "colors"=> $color_data_final,
                    "tax_percentage"=> $item_tax_percentage
                ];
                if (!empty($request->variant_id)) {

                    //   getting cart of user to check where the item is alredy present in there or not 
                    $user_cart_check = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->get();
                    //dd('am here');
                    if ($user_cart_check->count() > 0) {

                        //checking cart data to comapring the sent item with dara 
                        foreach ($user_cart_check as $cart) {

                            //if product id is matching then see variant id and color id else its a new product 
                            if(($cart->product_id == $request->product_id) && ($cart->outlet_uid == $request->outlet_uid)) {
                                //Log::emergency("a");
                                $insert_cart = false;

                                //checking for the size part 
                                if ($cart->variant_id == $request->variant_id) {
                                    Log::emergency("b");
                                    $update_cart = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->where('variant_id', $request->variant_id)->where('product_id', $request->product_id)->update(["quantity" => $request->qty]);
                                    return response()->json(['message' => "Cart updated successfully.","Cart_item"=>$response], 202)->header('status', 202);
                                } else {
                                    $insert_cart = true;
                                }
                            } else {
                                $insert_cart = true;
                            }
                        }

                        if ($insert_cart == true) {
                            //Log::emergency("c");
                            $insert_in_cart_size_case = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->insert(["uid" => $uid,
                                                                                                                                   "variant_id" => $request->variant_id,
                                                                                                                                   "product_id" => $request->product_id,
                                                                                                                                   "cart_item_uid" => $cart_item_uid,
                                                                                                                                    'outlet_uid'=>$request->outlet_uid]);

                            //geting new count of cart 
                            $cart_count = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->count();
                            
                            $old_count=DB::table('outlet_products')->where('product_id',$request->product_id)->where('outlet_uid',$request->outlet_uid)->value('cart_analytics');
                                    $new_count= intval($old_count)+ 1;
                                    $increase_order_count=DB::table('outlet_products')->where('product_id',$request->product_id)->where('outlet_uid',$request->outlet_uid)->update(["cart_analytics"=>$new_count]);

                            return response()->json(['message' => "Item added to your cart successfully.","Cart_item"=>$response], 200)->header('status', 200);
                        }
                    } else {
                        //if cart is empty then just insert it in database 
//Log::emergency("d");


                        $insert_in_cart_size_case = DB::table('user_carts')->insert(["uid" => $uid,
                            "variant_id" => $request->variant_id,
                            "product_id" => $request->product_id,
                            "cart_item_uid" => $cart_item_uid,
                            'outlet_uid'=>$request->outlet_uid]);

                        //geting new count of cart 
                        $cart_count = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->count();
                        
                        $old_count=DB::table('outlet_products')->where('product_id',$request->product_id)->where('outlet_uid',$request->outlet_uid)->value('cart_analytics');
                                    $new_count= intval($old_count)+ 1;
                                    $increase_order_count=DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id',$request->product_id)->update(["cart_analytics"=>$new_count]);


                        return response()->json(['message' => "Item added to your cart successfully.","Cart_item"=>$response], 200)->header('status', 200);
                    }
                }



                //case 2nd for the cart 

                if (!empty($request->color_id)) {
                    //   getting cart of user to check where the item is alredy present in there or not 
                    $user_cart_check = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->get();

                    if ($user_cart_check->count() > 0) {

                        //checking cart data to comapring the sent item with dara 
                        foreach ($user_cart_check as $cart) {

                            //if product id is matching then see variant id and color id else its a new product 
                            if(($cart->product_id == $request->product_id) && ($cart->outlet_uid == $request->outlet_uid)) {
                                $insert_cart = false;

                                //checking for the size part 
                                if ($cart->color_id == $request->color_id) {
                                    $update_cart = DB::table('user_carts')->where('uid', $uid)->where('outlet_uid',$request->outlet_uid)->where('color_id', $request->color_id)->where('product_id', $request->product_id)->update(["quantity" => $request->qty]);
                                    return response()->json(['message' => "Cart updated successfully.","Cart_item"=>$response], 202)->header('status', 202);
                                } else {
                                    $insert_cart = true;
                                }
                            } else {
                                $insert_cart = true;
                            }
                        }

                        if ($insert_cart == true) {
                            $insert_in_cart_size_case = DB::table('user_carts')->insert(["uid" => $uid,
                                "color_id" => $request->color_id,
                                "product_id" => $request->product_id,
                                "cart_item_uid" => $cart_item_uid,
                            'outlet_uid'=>$request->outlet_uid]);

                            //geting new count of cart 
                            $cart_count = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->count();
                            $old_count=DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id',$request->product_id)->value('cart_analytics');
                                    $new_count= intval($old_count)+ 1;
                                    $increase_order_count=DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id',$request->product_id)->update(["cart_analytics"=>$new_count]);


                            return response()->json(['message' => "Item added to your cart successfully.","Cart_item"=>$response], 200)->header('status', 200);
                        }
                    } else {
                        //if cart is empty then just insert it in database 



                        $insert_in_cart_size_case = DB::table('user_carts')->insert(["uid" => $uid,
                            "color_id" => $request->color_id,
                            "product_id" => $request->product_id,
                            "cart_item_uid" => $cart_item_uid,
                            'outlet_uid'=>$request->outlet_uid]);

                        //geting new count of cart 
                        $cart_count = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->count();
                        
                        $old_count=DB::table('outlet_products')->where('product_id',$request->product_id)->where('outlet_uid',$request->outlet_uid)->value('cart_analytics');
                                    $new_count= intval($old_count)+ 1;
                                    $increase_order_count=DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id',$request->product_id)->update(["cart_analytics"=>$new_count]);


                        return response()->json(['message' => "Item added to your cart successfully.","Cart_item"=>$response], 200)->header('status', 200);
                    }
                }

                //case 3rd 
                if (empty($request->color_id) && empty($request->variant_id)) {


                    //   getting cart of user to check where the item is alredy present in there or not 
                    $user_cart_check = DB::table('user_carts')->where('uid', $uid)->get();

                    if ($user_cart_check->count() > 0) {

                        //checking cart data to comapring the sent item with dara 
                        foreach ($user_cart_check as $cart) {

                            //if product id is matching then see variant id and color id else its a new product 
                            if(($cart->product_id == $request->product_id) && ($cart->outlet_uid == $request->outlet_uid)) {

                                $update_cart = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->where('product_id', $request->product_id)->update(["quantity" => $request->qty]);
                                return response()->json(['message' => "Cart updated successfully.","Cart_item"=>$response], 202)->header('status', 202);
                            } else {
                                $insert_cart = true;
                            }
                        }

                        if ($insert_cart == true) {
                            $insert_in_cart_size_case = DB::table('user_carts')->insert(["uid" => $uid,
                                "product_id" => $request->product_id,
                                "cart_item_uid" => $cart_item_uid,
                            'outlet_uid'=>$request->outlet_uid]);

                            //geting new count of cart 
                            $cart_count = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->count();
                            
                            $old_count=DB::table('outlet_products')->where('product_id',$request->product_id)->where('outlet_uid',$request->outlet_uid)->value('cart_analytics');
                                    $new_count= intval($old_count)+ 1;
                                    $increase_order_count=DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id',$request->product_id)->update(["cart_analytics"=>$new_count]);


                            return response()->json(['message' => "Item added to your cart successfully.","Cart_item"=>$response], 200)->header('status', 200);
                        }
                    } else {
                        //if cart is empty then just insert it in database 



                        $insert_in_cart_size_case = DB::table('user_carts')->insert(["uid" => $uid,
                            "product_id" => $request->product_id,
                            "cart_item_uid" => $cart_item_uid,
                            'outlet_uid'=>$request->outlet_uid]);

                        //geting new count of cart 
                        $cart_count = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->count();
                        
                        $old_count=DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id',$request->product_id)->value('cart_analytics');
                                    $new_count= intval($old_count)+ 1;
                                    $increase_order_count=DB::table('outlet_products')->where('product_id',$request->product_id)->update(["cart_analytics"=>$new_count]);


                        return response()->json(['message' => "Item added to your cart successfully.","Cart_item"=>$response], 200)->header('status', 200);
                    }
                }
            }
        }
    }

//===============REMOVE FROM CART API START HERE======================//
    public function remove_from_cart(Request $request) {

//Log::emergency($request);
        if (Auth::check()) {
            $user = Auth::user();
            $uid = Auth::user()->uid;

            $validator = Validator::make($request->all(), [
                        'product_id' => 'required',
                        'outlet_uid'=>'required'
            ]);
            if ($validator->fails()) {

                return response()->json(['message' => $validator->errors()], 403);
            } else {


                $check_cart = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->get();
                if ($check_cart->count() > 0) {


                    // if variant product 
                    if (!empty($request->selected_var)) {
                        $remove_from_cart = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->where('product_id', $request->product_id)->where('variant_id', $request->selected_var)->delete();
                    }

                    //if color product 
                    if (!empty($request->color_id)) {
                        $remove_from_cart = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->where('product_id', $request->product_id)->where('color_id', $request->color_id)->delete();
                    }

                    //if simple product 
                    if (empty($request->color_id) && empty($request->selected_var)) {
                        $remove_from_cart = DB::table('user_carts')->where('uid', $uid)->where('product_id', $request->product_id)->delete();
                    }




                    $cart_count = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->count();

                    if ($remove_from_cart == 1) {
                        $old_count = DB::table('outlet_products')->where('product_id', $request->product_id)->where('outlet_uid',$request->outlet_uid)->value('cart_analytics');
                        if ($old_count > 0) {
                            $new_count = intval($old_count) - 1;
                        } else {
                            $new_count = 0;
                        }


                        $update_cart_analytics_count = DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id',$request->product_id)->update(["cart_analytics" => $new_count]);

                        return response()->json(['message' => "Item removed from your cart successfully.", "cart_count" => $cart_count], 200)->header('status', 200);
                    } else {
                        return response()->json(['message' => "Id not associated to your cart."], 202)->header('status', 202);
                    }
                } else {

                    return response()->json(['message' => "No item left in cart.", "cart_count" => 0], 202)->header('status', 202);
                }
            }
        }
    }

//============VIEW CART API START HERE=======================//

    public function viewcart(Request $request) {
        Log::emergency($request);
        if (version_compare(phpversion(), '7.1', '>=')) {
            ini_set('serialize_precision', -1);
        }
        if (Auth::check()) {
    $validator = Validator::make($request->all(), [
                            'outlet_uid'=>'required'
                ]);
                
        //if product_id is null 
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 403)->header('status', 403);
          }
            $user = Auth::user();
            $uid = Auth::user()->uid;

            //get user cart data
            $get_user_cart_data = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->orderBy('id', 'DESC')->get();


  
              if ($get_user_cart_data->count() > 0) {
                  foreach ($get_user_cart_data as $user_cart_data) {
                      $product_data = DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id', $user_cart_data->product_id)->where('publish_status', 1)->first();
                      if(($product_data->is_size==0) && ($product_data->is_color==0)){
                           $update_user_cart_data = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->where('product_id', $user_cart_data->product_id)->update(['color_id'=>null,'variant_id'=>null]);
                      
                          
                      }elseif((($product_data->is_size==1) && ($user_cart_data->variant_id !=null)) && ($product_data->is_color==0)){
                         $size = DB::table('current_product_sizes')->where('outlet_uid',$request->outlet_uid)->where('product_id', $user_cart_data->product_id)->where('size_id', $user_cart_data->variant_id)->first(); 
                         if($size ==null){
                             $update_user_cart_data = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->where('product_id', $user_cart_data->product_id)->where('variant_id', $user_cart_data->variant_id)->delete();
 
                         }
                      }elseif(($product_data->is_size==0) && (($product_data->is_color==1) && ($user_cart_data->color_id != null))){
                         $size = DB::table('current_product_colors')->where('outlet_uid',$request->outlet_uid)->where('product_id', $user_cart_data->product_id)->where('color_id', $user_cart_data->color_id)->first(); 
                         if($size ==null){
                             $update_user_cart_data = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->where('product_id', $user_cart_data->product_id)->where('color_id', $user_cart_data->color_id)->delete();
 
                         }
                  }
              }
              
              }

            //if user has data
            if ($get_user_cart_data->count() > 0) {


                //user cart items loop start here        
                foreach ($get_user_cart_data as $user_cart_data) {




                    $size_data_final = null;
                    $color_data_final = null;
                    $qty = 0;
                    $quantity = 0;

                    $product_data = DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id', $user_cart_data->product_id)->where('publish_status', 1)->first();

                    $quantity = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->where('product_id', $product_data->product_id)->value('quantity');


                    //----------------- PRODUCT IS IN OFFER OR NOT PART START HERE-------------------------//
                    $product_offer_status = $product_data->offer_id;

                    //if item is in offer
                    if ($product_offer_status != null) {

                        $checkitemsinoffer = DB::table('items_in_offers')->where('outlet_uid',$request->outlet_uid)->where('product_id', $product_data->product_id)->count();
                        //if item found in offer
                        if ($checkitemsinoffer > 0) {
                            $item_offer_amount = DB::table('items_in_offers')->where('outlet_uid',$request->outlet_uid)->where('product_id', $product_data->product_id)->value('offer_amount');
                            $offer_times = DB::table('offer_times')->where('outlet_uid',$request->outlet_uid)->where('id', $product_data->offer_id)->first();

                            $offer_name = $offer_times->offer_name;
                            $offer_status = $offer_times->status;
                            $offer_percentage = $item_offer_amount;
                            $offer_end_time = $offer_times->offer_end_time;
                        } else {
                            $offer_name = null;
                            $offer_status = null;
                            $offer_percentage = 0;
                            $offer_end_time = null;
                        }

                        //else item is not in offer
                    } else {
                        $offer_name = null;
                        $offer_status = null;
                        $offer_percentage = 0;
                        $offer_end_time = null;
                    }

                    //-----------------PRODUCT IS IN OFFER OR NOT END HERE-------------------------//
                    //-------------------FIND TAX PERCENTAGE START HERE-----------------------------//
                    if (!empty($product_data->tax_slab_id) || ($product_data->tax_slab_id != null)) {
                        $check_tax_id = DB::table('tax_settings')->where('id', $product_data->tax_slab_id)->first();

                        if ($check_tax_id != null) {
                            $item_tax_percentage = DB::table('tax_settings')->where('id', $product_data->tax_slab_id)->value('percentage');
                        }
                    } else {
                        $item_tax_percentage = 0;
                    }
                    //---------------------FIND TAX PERCENTAGE END HERE--------------------------//
                    //---------------------CASE 1: WHEN PRODUCT HAS VARIANTS-------------------------------------------//
                    if ($user_cart_data->variant_id != null && $user_cart_data->color_id == null) {
                        $size = DB::table('current_product_sizes')->where('outlet_uid',$request->outlet_uid)->where('product_id', $user_cart_data->product_id)->where('size_id', $user_cart_data->variant_id)->first();
                        $qty = DB::table('user_carts')->where('uid', $uid)->where('outlet_uid',$request->outlet_uid)->where('product_id', $product_data->product_id)->where('variant_id', $size->size_id)->value('quantity');
                        $size_data_final = array("id" => $size->size_id,
                            "name" => $size->size_name,
                            "mrp" => $size->mrp,
                            "sp" => $size->sp,
                            "stock" => $size->stock,
                             "status"=>$size->status,
                            "primary_image" => asset($size->primary_image),
                            "item_qty" => $qty);
                    } elseif ($user_cart_data->variant_id == null && $user_cart_data->color_id != null) {

                        //---------------------CASE 2: WHEN PRODUCT HAS COLOR-------------------------------------------//
                        $color = DB::table('current_product_colors')->where('outlet_uid',$request->outlet_uid)->where('product_id', $user_cart_data->product_id)->where('color_id', $user_cart_data->color_id)->first();
                        $qty = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->where('product_id', $product_data->product_id)->where('color_id', $color->color_id)->value('quantity');
                        $color_data_final = array("id" => $color->color_id,
                            "color_code" => $color->color_code,
                            "name" => $color->color_name,
                            "mrp" => $color->mrp,
                            "sp" => $color->sp,
                            "stock" => $color->stock,
                            "status"=>$color->status,
                            "primary_image" => asset($color->primary_image),
                            "item_qty" => $qty);
                    }

                    $product_url = config('app.web_url') . '/' . str_slug($product_data->product_name) . '/a/' . $product_data->product_web_id;

                    $finalitemdata[] = array("id" => $product_data->product_id,
                        "product_name" => $product_data->product_name,
                        "cart_item_uid" => $user_cart_data->cart_item_uid,
                        "stock"=>$product_data->stock,
                        "stock_status" => intval($product_data->stock_status),
                        "stock_message" => $product_data->stock_message,
                        "product_url" => $product_url,
                        "primary_image" => asset($product_data->primary_image),
                        "product_mrp" => $product_data->product_mrp,
                        "product_sp" => $product_data->product_sp,
                        "product_limit" => intval($product_data->product_limit),
                        "product_status" => intval($product_data->product_status),
                        "offer_name" => $offer_name,
                        "offer_status" => intval($offer_status),
                        "offer_percentage" => $offer_percentage,
                        "offer_end_time" => $offer_end_time,
                        "item_qty" => $quantity,
                        "size" => $size_data_final,
                        "colors" => $color_data_final,
                        "tax_percentage"=>$item_tax_percentage);

                    $quantity = 0;
                }

                //user credit points
                $credits = DB::table('user_credits')->where('uid', $uid)->value('amount');

                //user referral points
                $referral = DB::table('user_referrals')->where('uid', $uid)->value('amount');


                // cart points %age
                $cart_points_percentage = DB::table('referral_settings')->value('cart_points');

                // max points
                $cart_max_points = DB::table('referral_settings')->value('cart_max_points');

                // free delivery above 
                $app_settings = DB::table('outlet_app_details')->where('outlet_uid',$request->outlet_uid)->first();

                // finding address in peroriry and then getting the delivery points
                $user_address = DB::table('address_users')->where('outlet_uid',$request->outlet_uid)->where("uid", $uid)->get();

                // checking if any address is there or not
                if ($user_address->count() > 0) {


                    $pincode_check = DB::table('outlet_app_details')->where('outlet_uid',$request->outlet_uid)->value('pincode_check');

                    //=====================IF PINCODE STATUS IS ENABLE========================================================//
                    if ($pincode_check == 1) {

                        $prioritycount = DB::table('address_users')->where('outlet_uid',$request->outlet_uid)->where("uid", $uid)->where("priority", 1)->get();

                        if ($prioritycount->count() > 0) {
                            $address = DB::table('address_users')->where('outlet_uid',$request->outlet_uid)->where("uid", $uid)->where("priority", 1)->first();


                            //check pincode in pincode group table
                            $pincode_groups = DB::table('pincode_groups')->where('pincode', $address->pincode)->first();
                            if ($pincode_groups != null) {
                                $group_id = DB::table('pincode_groups')->where('pincode', $address->pincode)->value('group_id');


                                //find delivery charges in pincode grouplist table
                                $delivery_charges = DB::table('pincode_grouplist')->where('id', $group_id)->value('delivery_charges');

                                // checking for free delivery 
                                $app_details = DB::table('outlet_app_details')->where('outlet_uid',$request->outlet_uid)->first();
                            } else {


                                $app_details = DB::table('outlet_app_details')->where('outlet_uid',$request->outlet_uid)->first();
                                $delivery_charges = $app_details->delivery;
                            }
                        } else {
                            $latestaddress = DB::table('address_users')->where('outlet_uid',$request->outlet_uid)->where("uid", $uid)->orderBy('id', 'DESC')->first();

                            //check pincode in pincode group table
                            $pincode_groups = DB::table('pincode_groups')->where('pincode', $latestaddress->pincode)->first();
                            if ($pincode_groups != null) {
                                $group_id = DB::table('pincode_groups')->where('pincode', $latestaddress->pincode)->value('group_id');


                                //find delivery charges in pincode grouplist table
                                $delivery_charges = DB::table('pincode_grouplist')->where('id', $group_id)->value('delivery_charges');

                                // checking for free delivery 
                                $app_details = DB::table('outlet_app_details')->where('outlet_uid',$request->outlet_uid)->first();
                            } else {


                                $delivery_charges = 0;
                            }
                        }
                        // ======================================= IF PINCODE STATUS IS DISABLE=======================================================================//
                    } else {
                        $prioritycount = DB::table('address_users')->where('outlet_uid',$request->outlet_uid)->where("uid", $uid)->where("priority", 1)->get();

                        if ($prioritycount->count() > 0) {
                            $address = DB::table('address_users')->where('outlet_uid',$request->outlet_uid)->where("uid", $uid)->where("priority", 1)->first();
                            // checking for free delivery 
                            $app_details = DB::table('outlet_app_details')->where('outlet_uid',$request->outlet_uid)->first();

                            $delivery_charges = $app_details->delivery;



                            //if address is not in priority
                        } else {
                            $latestaddress = DB::table('address_users')->where('outlet_uid',$request->outlet_uid)->where("uid", $uid)->orderBy('id', 'DESC')->first();
                            $app_details = DB::table('outlet_app_details')->where('outlet_uid',$request->outlet_uid)->first();

                            $delivery_charges = $app_details->delivery;
                        }
                    }
                } else {
                    $delivery_charges = 0;
                }


                $timeslotscount = DB::table('time_slots')->where('outlet_uid',$request->outlet_uid)->where('status', 1)->count();
                if ($timeslotscount > 0) {
                    $timeslotdata = DB::table('time_slots')->where('outlet_uid',$request->outlet_uid)->where('status', 1)->get();
                } else {
                    $timeslotdata = null;
                }

                      $user_data=DB::table('user_profiles')->where('uid',$uid)->first();
    return response()->json(["name"=>$user_data->name,
                             "phone"=>$user_data->phone,
                            "credits" => number_format((float) $credits, 2, '.', ''),
                            "referral_points" => number_format((float) $referral, 2, '.', ''),
                            "referral_cart_percentage" => $cart_points_percentage,
                            "max_referral_on_cart" => $cart_max_points,
                            "delivery_charges" => $delivery_charges,
                            "free_delivery_above" => $app_settings->freedelivery,
                            "min_price_order" => $app_settings->min_order,
                            "timeslots" => $timeslotdata,
                            'data' => $finalitemdata], 200)->header('status', 200);


                //if user cart is empty    
            } else {
                return response()->json(["success" => false, 'message' => "Your cart is empty."], 202)->header('status', 202);
            }
        }
    }

// ==============sync-cart START HERE ====================================//
    public function sync_cart(Request $request) {
        // echo'<pre>';
       // Log::emergency($request);
        if (Auth::check()) {
            
             // Validating Input//////
            $validator = Validator::make($request->all(), [
                            // 'food_item_id' => 'required|numeric',
                            'outlet_uid'=>'required'
            ]);

            ////Validation response if fail
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 403)->header('status', 403);
            }
            $user = Auth::user();
            $uid = Auth::user()->uid;
            
            if(!empty($request->payload)){
            $data = json_decode($request->payload, true);


            $validator = Validator::make($data, [
                        '*.product_id' => 'required|numeric|min:1', //Must be a number and length of value is 8
                        '*.qty' => 'required|integer|min:1',
                        '*.size_id' => 'nullable|integer|min:1',
                        '*.color_id' => 'nullable|integer|min:1'
            ]);
            if ($validator->passes()) {
                //TODO Handle your data
                //echo 'am passed';

                $size_id = array();
                $color_id = array();
                $product_id = array();
                $size_data = null;
                $color_data = null;
                $insert_cart = false;
                $update_cart_status = false;
                $variant_id=null;
                $color_id  =null;



                $user_cart_check = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->get();
                if ($user_cart_check->count() > 0) {


                    // new logic seprating cart id and the local product id which are simlier and just tak differ one and insert them directly then proceed to similier one buy using loop and then compare for variant etc 
                    foreach ($data as $s_data) {

                        //geting product id of local part 
                        $product_id[] = $s_data['product_id'];
                    }

                    foreach ($user_cart_check as $cart) {

                        $cart_product_id[] = $cart->product_id;
                    }


                    //dd($product_id,$cart_product_id);
                    $difffdata = array_diff($product_id, $cart_product_id);


                    //================================= processing differ data directly to cart==========================//


                    foreach ($difffdata as $d_data) {
                        foreach ($data as $s_data) {
                        if(!empty( $s_data['size_id'])){
                                $variant_id= $s_data['size_id'];
                            }
                            
                            
                            if(!empty($s_data->color_id)){
                                $color_id= $s_data['color_id'];
                            }
                            //geting product id of local part 
                            if ($d_data == $s_data['product_id']) {
                                //if cart is empty then just insert it in database 
                                $cart_item_uid = 'cart_item_uid_' . uniqid(time() . mt_rand());
                                $insert_in_cart_size_case = DB::table('user_carts')->insert(["uid" => $uid,
                                    "variant_id" => $variant_id,
                                    "color_id" => $color_id,
                                    "product_id" => $s_data['product_id'],
                                    "cart_item_uid" => $cart_item_uid,
                                    "quantity"=>$s_data['qty'],
                                    'outlet_uid'=>$request->outlet_uid]);
                                // geting new count of cart 
                                $cart_count = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->count();
                                //echo ' item added';
                                 $variant_id=null;
                                $color_id  =null;
                            }
                        }
                    }

                    // ================== now checking for the similer part =======================//

                    $array_sim = array_intersect($product_id, $cart_product_id);
                    //dd($array_sim);
                    foreach ($array_sim as $sim_data) {
                        foreach ($data as $s_data) {



                        if(!empty( $s_data['size_id'])){
                                $variant_id= $s_data['size_id'];
                            }
                            
                            
                            if(!empty($s_data->color_id)){
                                $color_id= $s_data['color_id'];
                            }
                            
                            
                            
                            //geting product id of local part 
                            if ($sim_data == $s_data['product_id']) {
                                $getdata = DB::table('user_carts')->where('uid', $uid)->where('outlet_uid',$request->outlet_uid)->where('product_id', $sim_data)->get();
                                //echo' product id matched'; 
                                // print_r($getdata);

                                foreach ($getdata as $com_data) {
                                    if ($com_data->variant_id == $s_data['size_id']) {
                                        //echo'<br>variant case <br>';
                                        $update_cart = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->where('variant_id', $s_data['size_id'])->where('product_id', $s_data['product_id'])->update(["quantity" => $s_data['qty']]);
                                        //echo'update cat case in variant'; 
                                    } elseif ($com_data->color_id == $s_data['color_id']) {

                                        //echo'<br>color case <br>';
                                        $update_cart = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->where('color_id', $s_data['color_id'])->where('product_id', $s_data['product_id'])->update(["quantity" => $s_data['qty']]);

                                        //echo'update cart case in color'; 
                                    } elseif ($com_data->product_id == $s_data['product_id']) {

                                        //echo'<br>simple case <br>';
                                        $update_cart = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->where('product_id', $s_data['product_id'])->update(["quantity" => $s_data['qty']]);

                                        //echo'update cart case in simple'; 
                                    }
                                }
                            }
                        }
                    }
                } else {

                    //============= empty cart case when cart has no item left just insert it in the db==================//
                    foreach ($data as $s_data) {


                         if(!empty( $s_data['size_id'])){
                                $variant_id= $s_data['size_id'];
                            }
                            
                            
                            if(!empty($s_data->color_id)){
                                $color_id= $s_data['color_id'];
                            }
                        //geting product id of local part 
                        //if cart is empty then just insert it in database 
                        $cart_item_uid = 'cart_item_uid_' . uniqid(time() . mt_rand());
                        $insert_in_cart_size_case = DB::table('user_carts')->insert(["uid" => $uid,
                            "variant_id" => $variant_id,
                             "color_id" => $color_id,
                            "product_id" => $s_data['product_id'],
                            "cart_item_uid" => $cart_item_uid,
                             "quantity"=>$s_data['qty'],
                             'outlet_uid'=>$request->outlet_uid]);
                        //geting new count of cart 
                        $cart_count = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->count();
                         $variant_id=null;
                         $color_id  =null;
                        //echo ' item added emty cart case ';
                    }
                }



                if (!empty($request->wishlist_data)) {

                    foreach ($request->wishlist_data as $product_id) {
                        $checkwishlist = DB::table('user_wishlists')->where('outlet_uid',$request->outlet_uid)->where('product_id', $product_id)->where('uid', $uid)->get();
                        if ($checkwishlist->count() > 0) {

                            //do nothing if item is already there  
                        } else {

                            $add_to_wishlist = DB::table('user_wishlists')->insert(['product_id' => $product_id, 'uid' => $uid,'outlet_uid'=>$request->outlet_uid]);

                            // wishlist analytics
                            $old_count = DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id', $product_id)->value('wishlist_analytics');
                            $new_count = $old_count + 1;
                            $increase_order_count = DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id', $product_id)->update(["wishlist_analytics" => $new_count]);
                        }
                    }
                }
                //final resposne 
                return response()->json(['message' => "Cart and wishlist sync successfully."], 200)->header('status', 200);
            } else {
                //TODO Handle your error
                return response()->json(['error' => $validator->errors()->all()], 403);
            }
            
            
            }
            
            if (!empty($request->wishlist_data)) {

                    foreach ($request->wishlist_data as $product_id) {
                        $checkwishlist = DB::table('user_wishlists')->where('outlet_uid',$request->outlet_uid)->where('product_id', $product_id)->where('uid', $uid)->get();
                        if ($checkwishlist->count() > 0) {

                            //do nothing if item is already there  
                        } else {

                            $add_to_wishlist = DB::table('user_wishlists')->insert(['product_id' => $product_id, 'uid' => $uid,'outlet_uid'=>$request->outlet_uid]);

                            // wishlist analytics
                            $old_count = DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id', $product_id)->value('wishlist_analytics');
                            $new_count = $old_count + 1;
                            $increase_order_count = DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id', $product_id)->update(["wishlist_analytics" => $new_count]);
                        }
                    }
                }
                
                
        }
    }

//================sync-cart START HERE =======================//
//============DELETE CART API =============//
    public function deletecart(Request $request) {

      //Log::emergency($request);
        /////check user  
        if (Auth::check()) {
            $user = Auth::user();


            ///get user unique id
            $uid = Auth::user()->uid;


            //// validator for checking  cart_attribute_uid 
            $validator = Validator::make($request->all(), [
                        'cart_item_uid' => 'required',
                        'outlet_uid'=>'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 403);
            } else {

                // checking if item is there or not 
                $user_cart = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where("uid", $uid)->get();
                if ($user_cart->count() > 0) {



                    // checking that cart value is there or not 
                    $cart_item_check = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('cart_item_uid', $request->cart_item_uid)->first();

                    if ($cart_item_check!=null) {

                        // cart analytics
                        $old_count = DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id', $cart_item_check->product_id)->value('cart_analytics');
                        if ($old_count > 0) {
                            $new_count = intval($old_count) - 1;
                        } else {
                            $new_count = 0;
                        }


                        $update_cart_analytics_count = DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id', $cart_item_check->product_id)->update(["cart_analytics" => $new_count]);


                        // delete cart data 
                        $delcart = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('cart_item_uid', $request->cart_item_uid)->delete();

                        $count = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('uid', $uid)->count();


                        return response()->json(['message' => "Item removed from your cart successfully", "cart_count" => $count], 200)->header('status', 200);
                    } else {
                        return response()->json(['message' => "Item already removed from your cart"], 205)->header('status', 205);
                    }
                } else {
                    return response()->json(['message' => "Your cart is empty"], 202)->header('status', 202);
                }
            }
        }
    }

//===========UPDATE CART===========//
    /*
     * UPDATE CART API 
     *
     *
     * @params AUTH TOKEN , cart item uid , quantity
     *
     *
     *
     */

    public function updatecart(Request $request) {
        if (Auth::check()) {
            $user = Auth::user();


            ///get user unique id
            $uid = Auth::user()->uid;

            // validator for empty values
            $validator = Validator::make($request->all(), [
                        'quantity' => 'required|numeric',
                        'cart_item_uid' => 'required',
                        'outlet_uid'=>'required'
            ]);

            ////Validation response if fail
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 403)->header('status', 403);
            } else {
                // checking if item is there or not 
                $user_cart = DB::table('user_carts')->where("uid", $uid)->get();
                if ($user_cart->count() > 0) {


                    //get item
                    $cartdata = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('cart_item_uid', $request->cart_item_uid)->where('uid', $uid)->first();

                    // if item found of requested cart_item_uid
                    if ($cartdata != null) {

                        $product_limit = DB::table('outlet_products')->where('outlet_uid',$request->outlet_uid)->where('product_id', $cartdata->product_id)->where('publish_status', 1)->value('product_limit');



                        //if requested quantity of product is greater than product limit
                        if (intval($request->quantity) > intval($product_limit)) {
                            
                            $update_cart = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('cart_item_uid', $request->cart_item_uid)->update(["quantity" => $product_limit]);
                       
                            return response()->json(['success' => true, 'message' => 'Qty adjusted to limit.','qty'=>$product_limit], 200)->header('status', 200);
                            //return response()->json(["message" => 'You have reached maximum limit per order'], 205)->header('status', 205);
                        }


                        $update_cart = DB::table('user_carts')->where('outlet_uid',$request->outlet_uid)->where('cart_item_uid', $request->cart_item_uid)->update(["quantity" => $request->quantity]);
                        return response()->json(['success' => true, 'message' => 'Cart updated successfully','qty'=>intval($request->quantity)], 200)->header('status', 200);


                        //if requested cart_item_uid not found
                    } else {
                        return response()->json(['message' => "Product not found"], 400)->header('status', 400);
                    }


                    //if user cart is empty  
                } else {
                    return response()->json(['message' => "Your cart is empty"], 202)->header('status', 202);
                }
            }
        }
    }

}
?>
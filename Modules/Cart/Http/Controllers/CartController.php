<?php

namespace Modules\Cart\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use DB;
use Helper;
use Carbon\Carbon;
use Modules\Product\Entities\Brand;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductBundle;
use Modules\Product\Entities\MainCategory;
use Modules\Product\Entities\SubCategory;
use Modules\Product\Entities\Stock;
use Modules\Product\Entities\Sample;
use Modules\Product\Entities\Voucher;
use Modules\Frontend\Entities\Offer;
use Modules\Cart\Entities\Cart;
use Modules\Cart\Entities\Wishlist;
use Modules\Cart\Entities\Coupon;

class CartController extends Controller
{

    public function addToWishlist(Request $request)
    {

        $productId = $request->product;
        $user = Auth::user();
        if ($user) {
            $customerId = $user->customer_id;
            $wishlistRecord = Wishlist::where('product_id', $productId)
                ->where('customer_id', $customerId)
                ->first();
            if ($wishlistRecord) {
                return response()->json(["result" => false, "message" => "Item is already saved in your love list.", "data" => ""]);
            } else {
                Wishlist::create([
                    'customer_id' => $customerId,
                    'product_id' => $productId,
                    'added_date' => now(),
                ]);
                $wishlistCount = Helper::wishlistCount();
                return response()->json(["result" => true, "message" => "Item is now saved to your saved love list.", "data" => $wishlistCount]);
            }

        } else {
            return response()->json(["result" => false, "message" => "Please sign in to save this item in your loves list.", "data" => ""]);
        }

    }

    public function removeFromWishlist(Request $request)
    {

        $wishlistId = $request->wishlistId;
        $wishlistRecord = Wishlist::find($wishlistId);
        if ($wishlistRecord) {
            $wishlistRecord->delete();
            $wishlistCount = Helper::wishlistCount();
            return response()->json(["result" => true, "message" => "Item is now removed from your saved love list.", "data" => $wishlistCount]);

        }
        return response()->json(["result" => false, "message" => "No record found.", "data" => ""]);
    }

    public function addToCart(Request $request)
    {

        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $currentDate = now()->format('Y-m-d');
        $currentDay = strtolower(now()->format('D'));

        $productId = $request->product;
        $quantity = $request->quantity;
        $productType = $request->product_type;
        $productKey = $request->product_key;
        $isBundle = $productType == 'bundle' ? 1 : 0;
        $isVoucher = $productType == 'voucher' ? 1 : 0;
        $offer = $request->offer ?? 0;
        $sendObject = '';
        $cartEditProductId = (int)$request->cart_edit_product ?? 0;

        if ($productType == "voucher") {
            if ($request->send_medium && $request->send_medium != '') {
                $sendMessage = $request->send_message;
                $sendType = $request->send_type;
                $sendTo = $request->send_to;
                $sendFrom = $request->send_from;
                $sendEmail = $request->send_email;
                $sendPhone = $request->send_phone;
                $sendMedium = $request->send_medium;
                $sendOn = $request->send_on;
                $sendDate = $request->send_date;

                $sendArray = [
                    "send_message" => $sendMessage,
                    "send_type" => $sendType,
                    "send_to" => $sendTo,
                    "send_from" => $sendFrom,
                    "send_email" => $sendEmail,
                    "send_phone" => $sendPhone,
                    "send_on" => $sendOn,
                    "send_date" => $sendDate,
                    "send_medium" => $sendMedium
                ];

                $sendObject = json_encode($sendArray);
                $quantity = 1;
            }
        }

        $user = Auth::user();

        //if edit cart product event
        if ($cartEditProductId !== 0) {

            if ($user) {
                $customerId = $user->customer_id;
                $cartRecord = Cart::where('product_id', $cartEditProductId)
                    ->where('customer_id', $customerId)
                    ->where('is_bundle', $isBundle)
                    ->first();
                if ($cartRecord) {
                    $appliedOffer = $cartRecord->product_offer;
                    $cartRecord->delete();

                    $cartItems = Cart::where('product_offer', $appliedOffer)
                        ->where('customer_id', $customerId)
                        ->get();

                    if ($cartItems->isNotEmpty()) {
                        $cartItems->each(function ($cartItem) use ($customerId) {
                            if ($cartItem->is_gift == 1) {
                                Cart::where('gift_for', $customerId)->delete();
                            } else {
                                $cartItem->update([
                                    'product_offer' => 0,
                                    'added_date' => now(),
                                ]);
                            }
                        });
                    }
                }
            } else {
                $product = Product::select('product_id', 'product_no')
                    ->where('product_id', $cartEditProductId)
                    ->first();
                $cartKey = $product->product_no;
                if (Session::has('gc_cart') && !empty(Session::get('gc_cart'))) {
                    foreach (Session::get('gc_cart') as $k => $cartItem) {
                        if ($cartItem['product_offer'] == Session::get("gc_cart.$cartKey.product_offer")) {
                            session(['gc_cart.' . $k . '.product_offer' => '']);
                        }
                        if ($cartItem['is_gift'] == '1' && $cartItem['gift_for'] == $cartEditProductId) {
                            Session::forget("gc_cart.$k");
                        }
                    }

                    if (Session::has("gc_cart.$cartKey")) {
                        Session::forget("gc_cart.$cartKey");
                    }
                }
            }
        }


        if ($user) {
            $customerId = $user->customer_id;
            $customerOrganization = $user->organization;

            switch ($productType) {
                case 'voucher':
                    $product = Product::select('barcode', 'product_no')->where('product_id', $productId)->first();
                    $stockQuery = Voucher::where('status', 'GENERATED')
                        ->where('barcode', $product->barcode);
                    if ($sendObject != '') {
                        $stockQuery->where('partner_id', 8);
                    } else {
                        $stockQuery->where('partner_id', 7);
                    }
                    $stock = $stockQuery->count();
                    $qryCartRecord = Cart::where('product_id', $productId)
                        ->where('customer_id', $customerId)
                        ->where('is_gift', 0)
                        ->get();
                    $numCartRecord = $qryCartRecord->count();
                    $cartKey = $product->product_no ?? 0;
                    break;
                case 'bundle':
                    $stock = 1;
                    $qryCartRecord = Cart::where('product_id', $productId)
                        ->where('customer_id', $customerId)
                        ->where('is_gift', 0)
                        ->where('is_bundle', 1)
                        ->get();
                    $numCartRecord = $qryCartRecord->count();
                    $product = ProductBundle::select('bundle_barcode as product_no', 'product_id')->where('product_id', $productId)->first();
                    $cartKey = $product->product_no ?? 0;
                    break;
                default:
                    $qryCartRecord = Cart::where('product_id', $productId)
                        ->where('customer_id', $customerId)
                        ->where('is_gift', 0)
                        ->where('is_bundle', 0)
                        ->get();
                    $numCartRecord = $qryCartRecord->count();
                    $product = Product::select(['product_id', 'product_no', 'sub_cat_id', 'brand_id', 'func_flag', 'has_gift'])->find($productId);
                    $cartKey = $product->product_no ?? 0;
                    $stockRecord = Stock::where('product_id', $product->product_id)->sum('qty');
                    $stock = $stockRecord ?? 0;
                    if ($productKey != '' && $productType == "offer") {
                        $offerRecord = Offer::where('offer_slug', $productKey)->first();
                        if ($offerRecord) {
                            $offer = $offerRecord->offer_id;
                        }
                    } else {
                        $subCat = $product->sub_cat_id;
                        $subCategory = Subcategory::with('category')->find($subCat);
                        $cat = $subCategory->cat_id;
                        $mainCat = $subCategory->category->main_cat_id;
                        $brand = $product->brand_id;
                        $brandDetails = Brand::find($brand);
                        $distributor = $brandDetails->distributor;
                        $flag = $product->func_flag;
                        $item = $product->product_id;

                        // Fetching discount offer by store
                        $storeOfferRecord = DB::table('offer_tbl as o')
                            ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
                            ->where('o.auto_apply', '=', '1')
                            ->where('o.status', '=', '1')
                            ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                            ->where('o.offer_type', '=', 'store')
                            ->whereIn('o.offer_for', ['1', '2'])
                            ->whereRaw("FIND_IN_SET(?, (SELECT os.offer_store FROM offer_tbl as os WHERE os.offer_id = o.offer_id))", [50])
                            ->where(function ($query) use ($currentDate, $currentDay) {
                                $query->where(function ($q) use ($currentDate) {
                                    $q->whereBetween(DB::raw("'" . $currentDate . "'"), ['o.from_date', 'o.to_date'])
                                        ->orWhere(function ($q) use ($currentDate) {
                                            $q->where('o.from_date', '<=', DB::raw("'" . $currentDate . "'"))
                                                ->where('o.to_date', '=', DB::raw("'0000-00-00 00:00:00'"));
                                        });
                                })->orWhere(function ($q) use ($currentDay) {
                                    $q->whereRaw("FIND_IN_SET(?, (SELECT o1.week_days FROM offer_tbl as o1 WHERE o1.offer_id = o.offer_id))", ["'$currentDay'"]);
                                });
                            })
                            ->where(function ($query) use ($distributor) {
                                $query->where('o.item_distributor', '=', '')
                                    ->orWhere('o.item_distributor', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                            })
                            ->where(function ($query) use ($mainCat) {
                                $query->where('o.item_main_category', '=', '')
                                    ->orWhere('o.item_main_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                            })
                            ->where(function ($query) use ($cat) {
                                $query->where('o.item_category', '=', '')
                                    ->orWhere('o.item_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                            })
                            ->where(function ($query) use ($subCat) {
                                $query->where('o.item_sub_category', '=', '')
                                    ->orWhere('o.item_sub_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                            })
                            ->where(function ($query) use ($brand) {
                                $query->where('o.item_brand', '=', '')
                                    ->orWhere('o.item_brand', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                            })
                            ->where(function ($query) use ($flag) {
                                $query->where('o.item_flag', '=', '')
                                    ->orWhere('o.item_flag', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                            })
                            ->where(function ($query) use ($item) {
                                $query->where('o.items', '=', '')
                                    ->orWhere('o.items', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                            })
                            ->first();
                        if ($storeOfferRecord) {
                            $offer = $storeOfferRecord->offer_id;
                        }

                        // Fetching offer by customer,organization
                        $customerOfferRecord = DB::table('offer_tbl as o')
                            ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
                            ->where('o.auto_apply', '=', '1')
                            ->where('o.status', '=', '1')
                            ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                            ->whereIn('o.offer_type', ['location', 'organization'])
                            ->whereIn('o.offer_for', ['1', '2'])
                            ->where(function ($query) use ($customerId, $customerOrganization) {
                                $query->orWhereRaw('FIND_IN_SET(?, (SELECT oc.`offer_location` FROM `offer_tbl` as `oc` WHERE oc.`offer_id`=`o`.`offer_id`))', [$customerId])
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT oo.`offer_organization` FROM `offer_tbl` as `oo` WHERE oo.`offer_id`=`o`.`offer_id`))', [$customerOrganization]);
                            })
                            ->where(function ($query) use ($currentDate, $currentDay) {
                                $query->where(function ($q) use ($currentDate) {
                                    $q->whereBetween(DB::raw("'" . $currentDate . "'"), ['o.from_date', 'o.to_date'])
                                        ->orWhere(function ($q) use ($currentDate) {
                                            $q->where('o.from_date', '<=', DB::raw("'" . $currentDate . "'"))
                                                ->where('o.to_date', '=', DB::raw("'0000-00-00 00:00:00'"));
                                        });
                                })->orWhere(function ($q) use ($currentDay) {
                                    $q->whereRaw("FIND_IN_SET(?, (SELECT o1.week_days FROM offer_tbl as o1 WHERE o1.offer_id = o.offer_id))", ["'$currentDay'"]);
                                });
                            })
                            ->where(function ($query) use ($distributor) {
                                $query->where('o.item_distributor', '=', '')
                                    ->orWhere('o.item_distributor', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                            })
                            ->where(function ($query) use ($mainCat) {
                                $query->where('o.item_main_category', '=', '')
                                    ->orWhere('o.item_main_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                            })
                            ->where(function ($query) use ($cat) {
                                $query->where('o.item_category', '=', '')
                                    ->orWhere('o.item_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                            })
                            ->where(function ($query) use ($subCat) {
                                $query->where('o.item_sub_category', '=', '')
                                    ->orWhere('o.item_sub_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                            })
                            ->where(function ($query) use ($brand) {
                                $query->where('o.item_brand', '=', '')
                                    ->orWhere('o.item_brand', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                            })
                            ->where(function ($query) use ($flag) {
                                $query->where('o.item_flag', '=', '')
                                    ->orWhere('o.item_flag', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                            })
                            ->where(function ($query) use ($item) {
                                $query->where('o.items', '=', '')
                                    ->orWhere('o.items', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                            })
                            ->first();
                        if ($customerOfferRecord) {
                            $offer = $customerOfferRecord->offer_id;
                        }
                    }
                    break;
            }

            if ($numCartRecord > 0) {
                $cartRecord = $qryCartRecord->first();
                $quantity += (int)$cartRecord->product_qty;

                if ($quantity > $stock) {
                    return response()->json(["result" => false, "message" => "Quantity exceeds the current stock. Please try a different item or decrease the quantity.", "data" => ""]);
                } else {
                    $cartRecord->product_qty = $quantity;
                    $cartRecord->product_offer = $offer;
                    $cartRecord->added_date = now();
                    $cartRecord->save();
                }
            } else {

                if ($quantity > $stock) {
                    return response()->json(["result" => false, "message" => "Quantity exceeds the current stock. Please try a different item or decrease the quantity.", "data" => ""]);
                } else {
                    Cart::create([
                        'cart_key' => $cartKey,
                        'customer_id' => $customerId,
                        'product_id' => $productId,
                        'product_qty' => $quantity,
                        'product_offer' => $offer,
                        'is_bundle' => $isBundle,
                        'is_voucher' => $isVoucher,
                        'evoucher_det' => $sendObject,
                        'added_date' => now(),
                    ]);

                }
            }
            // check if product has any gift
            $hasGift = $product->has_gift;

            if ($hasGift != '0') {
                $giftProduct = Product::select('product_id', 'product_no')
                    ->where('product_id', $hasGift)
                    ->first();

                if ($giftProduct) {
                    Cart::create([
                        'cart_key' => $giftProduct->product_no . '_' . $cartKey,
                        'customer_id' => $customerId,
                        'evoucher_det' => '',
                        'product_id' => $giftProduct->product_id,
                        'product_qty' => 1,
                        'product_offer' => $offer,
                        'is_gift' => 1,
                        'gift_for' => $product->product_id,
                        'added_date' => now(),
                    ]);
                }
            }
        } else {

            $cartItem = [
                'product_id' => $productId,
                'product_qty' => (int)$quantity,
                'product_offer' => $offer,
                'is_bundle' => $isBundle,
                'is_gift' => 0,
                'gift_for' => 0,
                'is_voucher' => 0,
            ];
            switch ($productType) {
                case 'bundle':
                    $product = ProductBundle::select('bundle_barcode as product_no', 'product_id')->where('product_id', $productId)->first();
                    $cartKey = $product->product_no ?? 0;
                    break;
                default:
                    $product = Product::find($productId);
                    $cartKey = $product->product_no ?? 0;
                    if ($productKey != '' && $productType == "offer") {
                        $offer = Offer::where('offer_slug', $productKey)->first();
                        $cartItem['product_offer'] = $offer->offer_id;
                    } else {
                        $subCat = $product->sub_cat_id;
                        $subCategory = Subcategory::with('category')->find($subCat);
                        $cat = $subCategory->cat_id;
                        $mainCat = $subCategory->category->main_cat_id;
                        $brand = $product->brand_id;
                        $brandDetails = Brand::find($brand);
                        $distributor = $brandDetails->distributor;
                        $flag = $product->func_flag;
                        $item = $product->product_id;

                        // Fetching discount offer by store
                        $storeOfferRecord = DB::table('offer_tbl as o')
                            ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
                            ->where('o.auto_apply', '=', '1')
                            ->where('o.status', '=', '1')
                            ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                            ->where('o.offer_type', '=', 'store')
                            ->whereIn('o.offer_for', ['1', '2'])
                            ->whereRaw("FIND_IN_SET(?, (SELECT os.offer_store FROM offer_tbl as os WHERE os.offer_id = o.offer_id))", [50])
                            ->where(function ($query) use ($currentDate, $currentDay) {
                                $query->where(function ($q) use ($currentDate) {
                                    $q->whereBetween(DB::raw("'" . $currentDate . "'"), ['o.from_date', 'o.to_date'])
                                        ->orWhere(function ($q) use ($currentDate) {
                                            $q->where('o.from_date', '<=', DB::raw("'" . $currentDate . "'"))
                                                ->where('o.to_date', '=', DB::raw("'0000-00-00 00:00:00'"));
                                        });
                                })->orWhere(function ($q) use ($currentDay) {
                                    $q->whereRaw("FIND_IN_SET(?, (SELECT o1.week_days FROM offer_tbl as o1 WHERE o1.offer_id = o.offer_id))", ["'$currentDay'"]);
                                });
                            })
                            ->where(function ($query) use ($distributor) {
                                $query->where('o.item_distributor', '=', '')
                                    ->orWhere('o.item_distributor', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                            })
                            ->where(function ($query) use ($mainCat) {
                                $query->where('o.item_main_category', '=', '')
                                    ->orWhere('o.item_main_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                            })
                            ->where(function ($query) use ($cat) {
                                $query->where('o.item_category', '=', '')
                                    ->orWhere('o.item_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                            })
                            ->where(function ($query) use ($subCat) {
                                $query->where('o.item_sub_category', '=', '')
                                    ->orWhere('o.item_sub_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                            })
                            ->where(function ($query) use ($brand) {
                                $query->where('o.item_brand', '=', '')
                                    ->orWhere('o.item_brand', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                            })
                            ->where(function ($query) use ($flag) {
                                $query->where('o.item_flag', '=', '')
                                    ->orWhere('o.item_flag', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                            })
                            ->where(function ($query) use ($item) {
                                $query->where('o.items', '=', '')
                                    ->orWhere('o.items', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                            })
                            ->first();

                        if ($storeOfferRecord) {
                            $cartItem['product_offer'] = $storeOfferRecord->offer_id;
                        }
                    }
                    break;
            }
            //dd($cartItem);
            if (!session()->has('gc_cart')) {
                session(['gc_cart' => [$cartKey => $cartItem]]);
            } else {
                if (!array_key_exists($cartKey, session('gc_cart'))) {
                    session(['gc_cart.' . $cartKey => $cartItem]);
                } else {
                    switch ($productType) {
                        case 'bundle':
                        case 'voucher':
                            $stock = 1;
                            break;
                        default:
                            $stockRecord = Stock::where('product_id', $product->product_id)->sum('qty');
                            $stock = $stockRecord ?? 0;
                            break;
                    }
                    $newQuantity = session('gc_cart.' . $cartKey . '.product_qty', 0) + $quantity;
                    if ($newQuantity > $stock) {
                        return response()->json(["result" => false, "message" => "Quantity exceeds the current stock. Please try a different item or decrease the quantity.", "data" => ""]);
                    } else {
                        session(['gc_cart.' . $cartKey . '.product_qty' => (int)$newQuantity]);
                    }
                }
            }

            // check if product has any gift
            $hasGift = $product->has_gift;

            if ($hasGift != '0') {
                $giftProduct = Product::select('product_id', 'product_no')
                    ->where('product_id', $hasGift)
                    ->first();

                if ($giftProduct) {
                    $cartItem = [
                        'product_id' => $giftProduct->product_id,
                        'product_qty' => 1,
                        'product_offer' => $offer,
                        'is_gift' => 1,
                        'gift_for' => $product->product_id,
                    ];

                    // Store the gift product details in the session cart
                    session(['gc_cart.' . $giftProduct->product_no . '_' . $cartKey => $cartItem]);
                }
            }

//            $cart = Session::get('gc_cart', []);
//            $cart[$productId] = $cartItem;
//            Session::put('gc_cart', $cart);
        }
        //dd(Session::get('gc_cart'));
        $cartCount = Helper::cartCount();

        return response()->json(["result" => true, "message" => "", "data" => $cartCount]);
    }

    public function showCart()
    {

        //dd(Session::all());

        return view('frontend.pages.cart');
    }

    public function fetchCart(Request $request)
    {
        //dd(Session::get('gc_cart'));
        $action = $request->action;
        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $currentDate = now()->format('Y-m-d');
        $currentDay = strtolower(now()->format('D'));
        $user = Auth::user();
        $cartArray = [];
        $cartCount = 0;
        $cartTotal = 0;
        $hasSample = 0;

        if (isset($action) && $action == 'refresh') {
            if ($user) {
                $customerId = $user->customer_id;
                $cartRecords = Cart::where('customer_id', $customerId)->get();
                foreach ($cartRecords as $cartRecord) {
                    if ($cartRecord->is_gift == '1' && $cartRecord->product_offer != '0') {
                        $cartRecord->delete();
                    } else {
                        $cartRecord->update([
                            'product_offer' => 0,
                            'added_date' => now(),
                        ]);
                    }
                }
            }

            if (Session::has('gc_cart') && !empty(Session::get('gc_cart'))) {
                foreach (Session::get('gc_cart') as $k => $cartItem) {
                    if ($cartItem['is_gift'] == '1' && $cartItem['product_offer'] != '0') {
                        Session::forget("gc_cart.$k");
                    } else {
                        session(['gc_cart.' . $k . '.product_offer' => '']);
                    }
                }
            }

            Session::forget('gc_coupon');
        }


        if ($user) {
            $customerId = $user->customer_id;
            $customerOrganization = $user->organization;
            $customerTier = $user->tier;

            // Retrieve cart from session
            $sessionCart = Session::get('gc_cart', []);

            // Check if session cart is not empty
            if (!empty($sessionCart)) {
                // Insert or update cart items in the database
                foreach ($sessionCart as $cartKey => $cartItem) {
                    // Check if the cart item already exists in the database
                    $existingCartItem = Cart::where('customer_id', $customerId)
                        ->where('cart_key', $cartKey)
                        ->first();

                    if ($existingCartItem) {
                        $productType = $cartItem['is_bundle'] == 1 ? 'bundle' : 'product';
                        switch ($productType) {
                            case 'bundle':
                                $stock = 1;
                                break;
                            default:
                                $stockRecord = Stock::where('product_id', $cartItem['product_id'])->sum('qty');
                                $stock = $stockRecord ?? 0;
                                break;
                        }
                        $quantity = (int)$existingCartItem->product_qty + (int)$cartItem['product_qty'];

                        if ($quantity > $stock) {
                            $quantity = $stock;
                        }
                        $existingCartItem->update([
                            'quantity' => $quantity,
                        ]);
                    } else {
                        // Insert new cart item into the database
                        Cart::create([
                            'cart_key' => $cartKey,
                            'customer_id' => $customerId,
                            'product_id' => $cartItem['product_id'],
                            'product_qty' => $cartItem['product_qty'],
                            'product_offer' => $cartItem['product_offer'],
                            'is_bundle' => $cartItem['is_bundle'],
                            'evoucher_det' => '',
                            'added_date' => now(),
                        ]);
                    }
                    Session::forget("gc_cart.$cartKey");
                }
            }

            $cart = Cart::where('customer_id', $customerId)
                ->get()->keyBy('cart_key')->toArray();
        } else {
            $cart = Session::get('gc_cart', []);
        }

        if (!empty($cart)) {
            foreach ($cart as $cartKey => $cartItem) {
                $isBundle = $cartItem['is_bundle'];
                if ($isBundle === 1) {
                    // Your bundle logic here
                } else {
                    $product = DB::table('product_table as products')->select(
                        'domains.domain_name',
                        'products.product_id',
                        'products.product_no',
                        'products.barcode',
                        'products.sub_cat_id',
                        'products.func_flag',
                        'products.brand_id',
                        'products.is_voucher',
                        DB::raw("REPLACE(products.title, '\\\\', '') AS product_name"),
                        DB::raw("CONCAT(IFNULL(REPLACE(families.family_name, '\\\\', ''), ''), ' ', products.fam_name) AS family_name"),
                        'products.photo2',
                        'products.photo1',
                        DB::raw("ROUND(products.main_price, 1) AS product_price")
                    )
                        ->leftJoin('family_tbl as families', 'products.linepr', '=', 'families.family_id')
                        ->leftJoin('domain_tbl as domains', 'products.in_domain', '=', 'domains.domain_id')
                        ->where('products.product_id', $cartItem['product_id'])
                        ->first();

                    $webDiscount = 0;
                    $webOffer = 0;
                    $productDiscPrice = 0;
                    $productDiscPercent = 0;
                    $subCat = $product->sub_cat_id;
                    $subCategory = Subcategory::with('category')->find($subCat);
                    $cat = $subCategory->cat_id;
                    $mainCat = $subCategory->category->main_cat_id;
                    $oldCategory = $subCategory->Old_Value;
                    $brand = $product->brand_id;
                    $brandDetails = Brand::find($brand);
                    $distributor = $brandDetails->distributor;
                    $flag = $product->func_flag;
                    $item = $product->product_id;
                    if (!isset($cartItem['product_offer']) || $cartItem['product_offer'] == '' || $cartItem['product_offer'] == 0) {

                        // Fetching discount offer by store
                        $storeOffer = DB::table('offer_tbl as o')
                            ->select('o.minimum_type', 'o.minimum_value', 'o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
                            ->where('o.auto_apply', '=', '1')
                            ->where('o.status', '=', '1')
                            ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                            ->where('o.offer_type', '=', 'store')
                            ->whereIn('o.offer_for', ['1', '2'])
                            ->whereRaw("FIND_IN_SET(?, (SELECT os.offer_store FROM offer_tbl as os WHERE os.offer_id = o.offer_id))", [50])
                            ->where(function ($query) use ($currentDate, $currentDay) {
                                $query->where(function ($q) use ($currentDate) {
                                    $q->whereBetween(DB::raw("'" . $currentDate . "'"), ['o.from_date', 'o.to_date'])
                                        ->orWhere(function ($q) use ($currentDate) {
                                            $q->where('o.from_date', '<=', DB::raw("'" . $currentDate . "'"))
                                                ->where('o.to_date', '=', DB::raw("'0000-00-00 00:00:00'"));
                                        });
                                })->orWhere(function ($q) use ($currentDay) {
                                    $q->whereRaw("FIND_IN_SET(?, (SELECT o1.week_days FROM offer_tbl as o1 WHERE o1.offer_id = o.offer_id))", ["'$currentDay'"]);
                                });
                            })
                            ->where(function ($query) use ($distributor) {
                                $query->where('o.item_distributor', '=', '')
                                    ->orWhere('o.item_distributor', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                            })
                            ->where(function ($query) use ($mainCat) {
                                $query->where('o.item_main_category', '=', '')
                                    ->orWhere('o.item_main_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                            })
                            ->where(function ($query) use ($cat) {
                                $query->where('o.item_category', '=', '')
                                    ->orWhere('o.item_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                            })
                            ->where(function ($query) use ($subCat) {
                                $query->where('o.item_sub_category', '=', '')
                                    ->orWhere('o.item_sub_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                            })
                            ->where(function ($query) use ($brand) {
                                $query->where('o.item_brand', '=', '')
                                    ->orWhere('o.item_brand', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                            })
                            ->where(function ($query) use ($flag) {
                                $query->where('o.item_flag', '=', '')
                                    ->orWhere('o.item_flag', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                            })
                            ->where(function ($query) use ($item) {
                                $query->where('o.items', '=', '')
                                    ->orWhere('o.items', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                            })
                            ->first();
                        if ($storeOffer) {
                            switch ($storeOffer->gift_discount_type) {
                                case 'amount':
                                    $webDiscount = !empty($storeOffer->gift_discount_value) ? (float)$storeOffer->gift_discount_value : 0;
                                    break;
                                case 'percentage':
                                    $webDiscount = !empty($rowCartRecord->product_price) && !empty($storeOffer->gift_discount_value) ?
                                        (float)$rowCartRecord->product_price * (float)$storeOffer->gift_discount_value / 100 : 0;
                                    break;
                                default:
                                    break;
                            }

                            if ($storeOffer->minimum_type == 'quantity' && $storeOffer->minimum_value == '1') {
                                $webOffer = $storeOffer->offer_id;
                            }

                            if ($storeOffer->minimum_type == 'amount' && (float)$product->product_price > (float)$storeOffer->minimum_value) {
                                $webOffer = $storeOffer->offer_id;
                            }

                            $cartItem['product_offer'] = $webOffer;
                        }


                        if ($user) {
                            //fetching auto apply offer by customer's tier
                            $tierOffer = DB::table('offer_tbl as o')
                                ->select('o.minimum_type', 'o.minimum_value', 'o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
                                ->where('o.auto_apply', '=', '1')
                                ->where('o.status', '=', '1')
                                ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                                ->where('o.offer_type', 'tier')
                                ->whereIn('o.offer_for', ['1', '2'])
                                ->whereRaw('FIND_IN_SET(?, (SELECT ot.`offer_tier` FROM `offer_tbl` as `ot` WHERE ot.`offer_id`=`o`.`offer_id`))', [$customerTier])
                                ->where(function ($query) use ($currentDate, $currentDay) {
                                    $query->where(function ($q) use ($currentDate) {
                                        $q->whereBetween(DB::raw("'" . $currentDate . "'"), ['o.from_date', 'o.to_date'])
                                            ->orWhere(function ($q) use ($currentDate) {
                                                $q->where('o.from_date', '<=', DB::raw("'" . $currentDate . "'"))
                                                    ->where('o.to_date', '=', DB::raw("'0000-00-00 00:00:00'"));
                                            });
                                    })->orWhere(function ($q) use ($currentDay) {
                                        $q->whereRaw("FIND_IN_SET(?, (SELECT o1.week_days FROM offer_tbl as o1 WHERE o1.offer_id = o.offer_id))", ["'$currentDay'"]);
                                    });
                                })
                                ->where(function ($query) use ($distributor) {
                                    $query->where('o.item_distributor', '=', '')
                                        ->orWhere('o.item_distributor', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                                })
                                ->where(function ($query) use ($mainCat) {
                                    $query->where('o.item_main_category', '=', '')
                                        ->orWhere('o.item_main_category', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                                })
                                ->where(function ($query) use ($cat) {
                                    $query->where('o.item_category', '=', '')
                                        ->orWhere('o.item_category', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                                })
                                ->where(function ($query) use ($subCat) {
                                    $query->where('o.item_sub_category', '=', '')
                                        ->orWhere('o.item_sub_category', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                                })
                                ->where(function ($query) use ($brand) {
                                    $query->where('o.item_brand', '=', '')
                                        ->orWhere('o.item_brand', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                                })
                                ->where(function ($query) use ($flag) {
                                    $query->where('o.item_flag', '=', '')
                                        ->orWhere('o.item_flag', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                                })
                                ->where(function ($query) use ($item) {
                                    $query->where('o.items', '=', '')
                                        ->orWhere('o.items', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                                })
                                ->first();
                                if ($tierOffer) {
                                    switch ($tierOffer->gift_discount_type) {
                                        case 'amount':
                                            $webDiscount = !empty($tierOffer->gift_discount_value) ? (float)$tierOffer->gift_discount_value : 0;
                                            break;
                                        case 'percentage':
                                            $webDiscount = !empty($rowCartRecord->product_price) && !empty($tierOffer->gift_discount_value) ?
                                                (float)$rowCartRecord->product_price * (float)$tierOffer->gift_discount_value / 100 : 0;
                                            break;
                                        default:
                                            break;
                                    }

                                    if ($tierOffer->minimum_type == 'quantity' && $tierOffer->minimum_value == '1') {
                                        $webOffer = $tierOffer->offer_id;
                                    }

                                    if ($tierOffer->minimum_type == 'amount' && (float)$product->product_price > (float)$tierOffer->minimum_value) {
                                        $webOffer = $tierOffer->offer_id;
                                    }

                                    $cartItem['product_offer'] = $webOffer;
                                }

                            //fetching auto apply offer by customer's id or organization
                            $customerOffer = DB::table('offer_tbl as o')
                                ->select('o.minimum_type', 'o.minimum_value', 'o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
                                ->where('o.auto_apply', '=', '1')
                                ->where('o.status', '=', '1')
                                ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                                ->whereIn('o.offer_type', ['location', 'organization'])
                                ->whereIn('o.offer_for', ['1', '2'])
                                ->where(function ($query) use ($customerId, $customerOrganization) {
                                    $query->orWhereRaw('FIND_IN_SET(?, (SELECT oc.`offer_location` FROM `offer_tbl` as `oc` WHERE oc.`offer_id`=`o`.`offer_id`))', [$customerId])
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT oo.`offer_organization` FROM `offer_tbl` as `oo` WHERE oo.`offer_id`=`o`.`offer_id`))', [$customerOrganization]);
                                })
                                ->where(function ($query) use ($currentDate, $currentDay) {
                                    $query->where(function ($q) use ($currentDate) {
                                        $q->whereBetween(DB::raw("'" . $currentDate . "'"), ['o.from_date', 'o.to_date'])
                                            ->orWhere(function ($q) use ($currentDate) {
                                                $q->where('o.from_date', '<=', DB::raw("'" . $currentDate . "'"))
                                                    ->where('o.to_date', '=', DB::raw("'0000-00-00 00:00:00'"));
                                            });
                                    })->orWhere(function ($q) use ($currentDay) {
                                        $q->whereRaw("FIND_IN_SET(?, (SELECT o1.week_days FROM offer_tbl as o1 WHERE o1.offer_id = o.offer_id))", ["'$currentDay'"]);
                                    });
                                })
                                ->where(function ($query) use ($distributor) {
                                    $query->where('o.item_distributor', '=', '')
                                        ->orWhere('o.item_distributor', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                                })
                                ->where(function ($query) use ($mainCat) {
                                    $query->where('o.item_main_category', '=', '')
                                        ->orWhere('o.item_main_category', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                                })
                                ->where(function ($query) use ($cat) {
                                    $query->where('o.item_category', '=', '')
                                        ->orWhere('o.item_category', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                                })
                                ->where(function ($query) use ($subCat) {
                                    $query->where('o.item_sub_category', '=', '')
                                        ->orWhere('o.item_sub_category', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                                })
                                ->where(function ($query) use ($brand) {
                                    $query->where('o.item_brand', '=', '')
                                        ->orWhere('o.item_brand', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                                })
                                ->where(function ($query) use ($flag) {
                                    $query->where('o.item_flag', '=', '')
                                        ->orWhere('o.item_flag', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                                })
                                ->where(function ($query) use ($item) {
                                    $query->where('o.items', '=', '')
                                        ->orWhere('o.items', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                                })
                                ->first();


                            if ($customerOffer) {
                                switch ($customerOffer->gift_discount_type) {
                                    case 'amount':
                                        $webDiscount = !empty($customerOffer->gift_discount_value) ? (float)$customerOffer->gift_discount_value : 0;
                                        break;
                                    case 'percentage':
                                        $webDiscount = !empty($rowCartRecord->product_price) && !empty($customerOffer->gift_discount_value) ?
                                            (float)$rowCartRecord->product_price * (float)$customerOffer->gift_discount_value / 100 : 0;
                                        break;
                                    default:
                                        break;
                                }

                                if ($customerOffer->minimum_type == 'quantity' && $customerOffer->minimum_value == '1') {
                                    $webOffer = $customerOffer->offer_id;
                                }

                                if ($customerOffer->minimum_type == 'amount' && (float)$product->product_price > (float)$customerOffer->minimum_value) {
                                    $webOffer = $customerOffer->offer_id;
                                }

                                $cartItem['product_offer'] = $webOffer;
                            }


                        }
                    }

                    //promo code respect of item
                    $hasCoupon = '';
                    $couponRecord = DB::table('gc_coupons as c')
                        ->select(DB::raw('COALESCE(GROUP_CONCAT(`c`.id), "") as id'))
                        ->where('c.status', '1')
                        ->whereRaw("FIND_IN_SET(?, c.in_domain)", [$domainId])
                        ->where('c.start_date', '<=', DB::raw("'" . $currentDate . "'"))
                        ->where('c.end_date', '>=', DB::raw("'" . $currentDate . "'"))
                        ->where(function ($query) use ($distributor) {
                            $query->where('c.distributors', '=', '')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT `cd`.distributors FROM gc_coupons as `cd` WHERE `cd`.`id` = `c`.`id`))', [$distributor]);
                        })
                        ->where(function ($query) use ($brand) {
                            $query->where('c.brands', '=', '')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT `cb`.brands FROM gc_coupons as `cb` WHERE `cb`.`id`= `c`.`id`))', [$brand]);
                        })
                        ->where(function ($query) use ($oldCategory) {
                            $query->where('c.departments', '=', '')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT `cde`.departments FROM gc_coupons as `cde` WHERE `cde`.`id` = `c`.`id`))', [$oldCategory]);
                        })
                        ->where(function ($query) use ($item) {
                            $query->where('c.items', '=', '')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT `ci`.items FROM gc_coupons as `ci` WHERE `ci`.`id` = `c`.`id`))', [$item]);
                        })->first();

                    if ($couponRecord) {
                        $hasCoupon = $couponRecord->id;
                    }

                    $itemTotalPrice = (float)$product->product_price * (int)$cartItem['product_qty'];

                    // default loyalty points
                    $productLoyalty = 0;
//                    $offerRecord = Offer::where('offer_id', '56')
//                        ->where('status', '1')
//                        ->first();
//
//                    if ($offerRecord) {
//                        $itemTotal = (float)$rowCartRecord['product_price'] * (int)$cartItem['product_qty'];
//                        $productLoyalty = round((float)$offerRecord['gift_points'] * (float)$itemTotal);
//                    }

                    // If user chooses any offer
                    if (isset($cartItem['product_offer']) && $cartItem['product_offer'] != '' && $cartItem['product_offer'] != '0') {
                        $offerRecord = Offer::where('offer_id', $cartItem['product_offer'])->first();

                        if ($offerRecord) {
                            $discountPercent = 0;
                            $discountPrice = 0;
                            $itemDiscountPrice = 0;

                            if (!empty($offerRecord['gift_quantity'])) {
                                if ($cartItem['is_gift'] == '1') {
                                    if ($offerRecord['gift_discount_type'] == 'percentage') {
                                        $discountPercent = $offerRecord['gift_discount_value'];
                                    }

                                    if ($offerRecord['gift_discount_type'] == 'amount') {
                                        $discountPrice = $offerRecord['gift_discount_value'];
                                        $discountPercent = ((float)$discountPrice / (float)$itemTotalPrice) * 100;
                                    }
                                }
                            } else {
                                if (!empty($offerRecord['gift_discount_type']) && !empty($offerRecord['gift_discount_value'])) {
                                    if ($offerRecord['gift_discount_type'] == 'percentage') {
                                        $discountPercent = $offerRecord['gift_discount_value'];
                                    }

                                    if ($offerRecord['gift_discount_type'] == 'amount') {
                                        $discountPrice = $offerRecord['gift_discount_value'];
                                        $discountPercent = ((float)$discountPrice / (float)$itemTotalPrice) * 100;
                                    }
                                }
                            }

                            if ($discountPercent > 0) {
                                $itemDiscountPrice = (float)$cartItem['product_qty'] * ((float)$product->product_price * (float)$discountPercent / 100);
                                $productDiscPrice = number_format($itemDiscountPrice, 2, '.', '');
                                $productDiscPercent = number_format($discountPercent, 2, '.', '');
                            }

                            $itemTotal = (floatval($product->product_price) * intval(abs($cartItem['product_qty']))) - floatval($productDiscPrice);
                            $productLoyalty = round((float)$offerRecord['gift_points'] * (float)$itemTotal);
                        }
                    }

                    // Special offer
                    if ($webDiscount > 0) {
                        $itemDiscountPrice = (float)$cartItem['product_qty'] * (float)$webDiscount;
                        $productDiscPrice = number_format($itemDiscountPrice, 2, '.', '');
                        $productDiscPercent = number_format(((float)$webDiscount / (float)$product->product_price) * 100, 2, '.', '');
                        $itemTotal = (floatval($product->product_price) * intval(abs($cartItem['product_qty']))) - floatval($productDiscPrice);
                        $productLoyalty = 0;
                    }

                    // If promo code applied, remove points gained
                    $appliedCoupon = '';
                    if (Session::has('gc_coupon') && !empty(Session::get('gc_coupon'))) {
                        if ($hasCoupon != '') {
                            $hasCouponArray = explode(',', $hasCoupon);
                            foreach ($hasCouponArray as $coupon) {
                                if (array_key_exists($coupon, Session::get('gc_coupon'))) {
                                    $couponDiscount = Session::get('gc_coupon')[$coupon]['discount'];
                                    $productDiscPrice = (float)$itemTotalPrice * (float)$couponDiscount / 100;
                                    $productDiscPercent = number_format($couponDiscount, 2, '.', '');
                                    $appliedCoupon = $coupon;
                                    $hasCoupon = '';
                                    $productLoyalty = 0;
                                }
                            }
                        }
                    }

                    if (array_key_exists($product->product_no, $cartArray)) {
                        $cartArray[$product->product_no]['product_qty'] = (int)$cartItem['product_qty'] + (int)$cartArray[$product->product_no]['product_qty'];
                    } else {
                        $resImgPr = DB::table('product_more_pic_tbl')->where('product_id', $product->product_id)->whereNotNull('aws')->orderBy('id')->limit(1)->first();

                        $productImg = $product->photo1 == '' ? config('app.no_image_url') : $product->photo1;

                        if ($resImgPr) {
                            $productImg = $resImgPr->aws != '' ? $resImgPr->aws : $productImg;
                        }
                        $cartArray[$cartKey] = [
                            'product_id' => $product->product_id,
                            'product_img' => $productImg,
                            'product_no' => $product->product_no,
                            'barcode' => $product->barcode,
                            'product_name' => $product->product_name,
                            'brand_name' => $brandDetails->name,
                            'family_name' => trim($product->family_name) != '' ? ucwords(strtolower($product->family_name)) : ucwords(strtolower($product->product_name)),
                            'product_price' => $product->product_price,
                            'product_qty' => $cartItem['product_qty'],
                            'product_loyalty' => $productLoyalty,
                            'product_disc_price' => $productDiscPrice,
                            'product_disc_perc' => number_format($productDiscPercent, 2, '.', ''),
                            'product_offer' => $cartItem['product_offer'],
                            'is_gift' => $cartItem['is_gift'],
                            'is_bundle' => $cartItem['is_bundle'],
                            'has_coupon' => $hasCoupon,
                            'applied_coupon' => $appliedCoupon,
                            'domain' => $product->domain_name,
                            'is_voucher' => $product->is_voucher,
                        ];
                    }
                }

            }
        }

        if (!empty($cartArray)) {
            foreach ($cartArray as $cartItem) {
                $cartTotal += ($cartItem['product_price'] * $cartItem['product_qty']) - (float)$cartItem['product_disc_price'];
            }
        }

        $sampleRecord = Sample::whereRaw("FIND_IN_SET(?, `domain`) AND `min_purchase` <= ? AND `max_purchase` >= ?", [$domainId, $cartTotal, $cartTotal])
            ->select('sample_qty')
            ->first();

        if ($sampleRecord) {
            $hasSample = (int)$sampleRecord->sample_qty;
        }

        $cartCount = Helper::cartCount();
        $data = [
            "cart" => $cartArray,
            "cart_count" => $cartCount,
            "has_sample" => $hasSample,
        ];

        return response()->json(["result" => true, "message" => "", "data" => $data]);

    }

    public function updateCart(Request $request)
    {
        $cartKey = $request->cart_key;
        $productId = $request->product_id;
        $isBundle = $request->is_bundle ?? 0;
        $quantity = $request->quantity ?? 1;
        $user = Auth::user();


        switch ($isBundle) {
            case '1':
                $stock = 10;
                break;
            default:
                $stockRecord = Stock::where('product_id', $productId)->sum('qty');
                $stock = (int)$stockRecord ?? 0;
        }

        if ($user) {
            $customerId = $user->customer_id;
            $cartRecord = Cart::where('product_id', $productId)
                ->where('customer_id', $customerId)
                ->where('is_bundle', $isBundle)
                ->first();

            if ($cartRecord) {
                $appliedOffer = $cartRecord->product_offer;

                if ($quantity > $stock) {
                    return response()->json(["result" => false, "message" => "Quantity exceeds the current stock. Please try a different item or decrease the quantity.", "data" => $stock]);
                } else {
                    $cartRecord->update([
                        'product_offer' => 0,
                        'product_qty' => $quantity,
                        'added_date' => now(),
                    ]);
                }

                $itemRecord = Cart::where('product_offer', $appliedOffer)
                    ->where('customer_id', $customerId)
                    ->get();

                if ($itemRecord->isNotEmpty()) {
                    $itemRecord->each(function ($item) {
                        if ($item->is_gift == 1) {
                            $item->delete();
                        } else {
                            $item->update([
                                'product_offer' => 0,
                                'added_date' => now(),
                            ]);
                        }
                    });
                }
            }
        } else {
            $newQuantity = $quantity;
            if ($newQuantity > $stock) {
                return response()->json(["result" => false, "message" => "Quantity exceeds the current stock. Please try a different item or decrease the quantity.", "data" => $stock]);
            } else {
                session(['gc_cart.' . $cartKey . '.product_qty' => (int)$newQuantity]);
            }
        }


        return response()->json(["result" => true, "message" => "Item quantity updated in the cart.", "data" => ""]);
    }

    public function deleteCart(Request $request)
    {
        $cartKey = $request->cart_key;
        $productId = $request->product_id;
        $isBundle = $request->is_bundle ?? 0;
        $user = Auth::user();


        if ($user) {
            $customerId = $user->customer_id;
            $cartRecord = Cart::where('product_id', $productId)
                ->where('customer_id', $customerId)
                ->where('is_bundle', $isBundle)
                ->first();

            if ($cartRecord) {
                $appliedOffer = $cartRecord->product_offer;
                $cartRecord->delete();

                $cartItems = Cart::where('product_offer', $appliedOffer)
                    ->where('customer_id', $customerId)
                    ->get();
                if ($cartItems->isNotEmpty()) {
                    $cartItems->each(function ($cartItem) use ($productId) {
                        if ($cartItem->is_gift == 1) {
                            //Cart::where('gift_for', $productId)->delete();
                            $cartItem->delete();
                        } else {
                            $cartItem->update([
                                'product_offer' => 0,
                                'added_date' => now(),
                            ]);
                        }
                    });
                }
            }
        } else {
            if (Session::has('gc_cart') && !empty(Session::get('gc_cart'))) {
                foreach (Session::get('gc_cart') as $key => $cartItem) {
                    if ($cartItem['product_offer'] == Session::get("gc_cart.$key.product_offer")) {
                        session(['gc_cart.' . $key . '.product_offer' => 0]);
                    }
                    if ($cartItem['is_gift'] == '1') {
                        Session::forget("gc_cart.$key");
                    }
                }

                if (Session::has("gc_cart.$cartKey")) {
                    Session::forget("gc_cart.$cartKey");
                }
            }
        }

        return response()->json(["result" => true, "message" => "", "data" => ""]);
    }

    public function fetchOffers(Request $request)
    {

        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $currentDate = now()->format('Y-m-d');
        $currentDay = strtolower(now()->format('D'));
        $user = Auth::user();
        $cartArray = [];
        $cartCount = 0;
        $cartTotal = 0;
        $hasSample = 0;

        if ($user) {
            $customerId = $user->customer_id;
            $customerOrganization = $user->organization;
            $customerTier = $user->tier;
            $cart = Cart::where('customer_id', $customerId)
                ->get()->keyBy('cart_key')->toArray();
        } else {
            $cart = Session::get('gc_cart', []);
        }

        //dd($cartArray);
        $resultArray = [];
        if (!empty($cart)) {
            foreach ($cart as $cartKey => $cartItem) {
                // checking if not gift
                if (!Str::contains($cartKey, '_')) {
                    if (!isset($cartItem['product_offer']) || $cartItem['product_offer'] == '' || $cartItem['product_offer'] == 0) {
                        $product = DB::table('product_table as products')->select(
                            'domains.domain_name',
                            'products.product_id',
                            'products.product_no',
                            'products.barcode',
                            'products.sub_cat_id',
                            'products.func_flag',
                            'products.brand_id',
                            'products.is_voucher',
                            DB::raw("REPLACE(products.title, '\\\\', '') AS product_name"),
                            DB::raw("CONCAT(IFNULL(REPLACE(families.family_name, '\\\\', ''), ''), ' ', products.fam_name) AS family_name"),
                            'products.photo2',
                            'products.photo1',
                            DB::raw("ROUND(products.main_price, 1) AS product_price")
                        )
                            ->leftJoin('family_tbl as families', 'products.linepr', '=', 'families.family_id')
                            ->leftJoin('domain_tbl as domains', 'products.in_domain', '=', 'domains.domain_id')
                            ->where('products.product_id', $cartItem['product_id'])
                            ->first();

                        $subCat = $product->sub_cat_id;
                        $subCategory = Subcategory::with('category')->find($subCat);
                        $cat = $subCategory->cat_id;
                        $mainCat = $subCategory->category->main_cat_id;
                        $brand = $product->brand_id;
                        $brandDetails = Brand::find($brand);
                        $distributor = $brandDetails->distributor;
                        $flag = $product->func_flag;
                        $item = $product->product_id;


                        // Fetching discount offer by store
                        $sOffers = DB::table('offer_tbl as o')
                            ->select('o.*')
                            ->where('o.auto_apply', '=', '0')
                            ->where('o.status', '=', '1')
                            ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                            ->where('o.offer_type', '=', 'store')
                            ->whereIn('o.offer_for', ['1', '2'])
                            ->whereRaw("FIND_IN_SET(?, (SELECT os.offer_store FROM offer_tbl as os WHERE os.offer_id = o.offer_id))", [50])
                            ->where(function ($query) use ($currentDate, $currentDay) {
                                $query->where(function ($q) use ($currentDate) {
                                    $q->whereBetween(DB::raw("'" . $currentDate . "'"), ['o.from_date', 'o.to_date'])
                                        ->orWhere(function ($q) use ($currentDate) {
                                            $q->where('o.from_date', '<=', DB::raw("'" . $currentDate . "'"))
                                                ->where('o.to_date', '=', DB::raw("'0000-00-00 00:00:00'"));
                                        });
                                })->orWhere(function ($q) use ($currentDay) {
                                    $q->whereRaw("FIND_IN_SET(?, (SELECT o1.week_days FROM offer_tbl as o1 WHERE o1.offer_id = o.offer_id))", ["'$currentDay'"]);
                                });
                            })
                            ->where(function ($query) use ($distributor) {
                                $query->where('o.item_distributor', '=', '')
                                    ->orWhere('o.item_distributor', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                            })
                            ->where(function ($query) use ($mainCat) {
                                $query->where('o.item_main_category', '=', '')
                                    ->orWhere('o.item_main_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                            })
                            ->where(function ($query) use ($cat) {
                                $query->where('o.item_category', '=', '')
                                    ->orWhere('o.item_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                            })
                            ->where(function ($query) use ($subCat) {
                                $query->where('o.item_sub_category', '=', '')
                                    ->orWhere('o.item_sub_category', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                            })
                            ->where(function ($query) use ($brand) {
                                $query->where('o.item_brand', '=', '')
                                    ->orWhere('o.item_brand', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                            })
                            ->where(function ($query) use ($flag) {
                                $query->where('o.item_flag', '=', '')
                                    ->orWhere('o.item_flag', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                            })
                            ->where(function ($query) use ($item) {
                                $query->where('o.items', '=', '')
                                    ->orWhere('o.items', '=', 'all')
                                    ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                            })
                            ->get();
                        if ($sOffers->isNotEmpty()) {
                            foreach ($sOffers as $sOffer) {
                                $offerId = $sOffer->offer_id;
                                $idString = (string)$cartItem['product_id'];

                                if (!empty($resultArray) && array_key_exists($offerId, $resultArray)) {
                                    $offerItem = $resultArray[$offerId]->offer_item;

                                    if ($offerItem !== '') {
                                        $offerItemArr = explode(',', $offerItem);

                                        if (!in_array($idString, $offerItemArr)) {
                                            $resultArray[$offerId]->offer_item = $offerItem . ',' . $idString;
                                        }
                                    }
                                } else {
                                    $sOffer->offer_item = $idString;
                                    $sOffer->item_type = 'offer';
                                    $resultArray[$offerId] = $sOffer;
                                }
                            }
                        }
                        if ($user) {
                            // Fetching offer by tier
                            $tOffers = DB::table('offer_tbl as o')
                                ->select('o.*')
                                ->where('o.auto_apply', '=', '0')
                                ->where('o.status', '=', '1')
                                ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                                ->where('o.offer_type', 'tier')
                                ->whereIn('o.offer_for', ['1', '2'])
                                ->whereRaw('FIND_IN_SET(?, (SELECT ot.`offer_tier` FROM `offer_tbl` as `ot` WHERE ot.`offer_id`=`o`.`offer_id`))', [$customerTier])
                                ->where(function ($query) use ($currentDate, $currentDay) {
                                    $query->where(function ($q) use ($currentDate) {
                                        $q->whereBetween(DB::raw("'" . $currentDate . "'"), ['o.from_date', 'o.to_date'])
                                            ->orWhere(function ($q) use ($currentDate) {
                                                $q->where('o.from_date', '<=', DB::raw("'" . $currentDate . "'"))
                                                    ->where('o.to_date', '=', DB::raw("'0000-00-00 00:00:00'"));
                                            });
                                    })->orWhere(function ($q) use ($currentDay) {
                                        $q->whereRaw("FIND_IN_SET(?, (SELECT o1.week_days FROM offer_tbl as o1 WHERE o1.offer_id = o.offer_id))", ["'$currentDay'"]);
                                    });
                                })
                                ->where(function ($query) use ($distributor) {
                                    $query->where('o.item_distributor', '=', '')
                                        ->orWhere('o.item_distributor', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                                })
                                ->where(function ($query) use ($mainCat) {
                                    $query->where('o.item_main_category', '=', '')
                                        ->orWhere('o.item_main_category', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                                })
                                ->where(function ($query) use ($cat) {
                                    $query->where('o.item_category', '=', '')
                                        ->orWhere('o.item_category', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                                })
                                ->where(function ($query) use ($subCat) {
                                    $query->where('o.item_sub_category', '=', '')
                                        ->orWhere('o.item_sub_category', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                                })
                                ->where(function ($query) use ($brand) {
                                    $query->where('o.item_brand', '=', '')
                                        ->orWhere('o.item_brand', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                                })
                                ->where(function ($query) use ($flag) {
                                    $query->where('o.item_flag', '=', '')
                                        ->orWhere('o.item_flag', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                                })
                                ->where(function ($query) use ($item) {
                                    $query->where('o.items', '=', '')
                                        ->orWhere('o.items', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                                })
                                ->get();
                            if ($tOffers->isNotEmpty()) {
                                foreach ($tOffers as $tOffer) {
                                    $offerId = $tOffer->offer_id;
                                    $idString = (string)$cartItem['product_id'];

                                    if (!empty($resultArray) && array_key_exists($offerId, $resultArray)) {
                                        $offerItem = $resultArray[$offerId]->offer_item;

                                        if ($offerItem !== '') {
                                            $offerItemArr = explode(',', $offerItem);

                                            if (!in_array($idString, $offerItemArr)) {
                                                $resultArray[$offerId]->offer_item = $offerItem . ',' . $idString;
                                            }
                                        }
                                    } else {
                                        $tOffer->offer_item = $idString;
                                        $tOffer->item_type = 'offer';
                                        $resultArray[$offerId] = $tOffer;
                                    }
                                }
                            }

                            // Fetching offer by customer,organization
                            $cOffers = DB::table('offer_tbl as o')
                                ->select('o.*')
                                ->where('o.auto_apply', '=', '0')
                                ->where('o.status', '=', '1')
                                ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                                ->whereIn('o.offer_type', ['location', 'organization'])
                                ->whereIn('o.offer_for', ['1', '2'])
                                ->where(function ($query) use ($customerId, $customerOrganization) {
                                    $query->orWhereRaw('FIND_IN_SET(?, (SELECT oc.`offer_location` FROM `offer_tbl` as `oc` WHERE oc.`offer_id`=`o`.`offer_id`))', [$customerId])
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT oo.`offer_organization` FROM `offer_tbl` as `oo` WHERE oo.`offer_id`=`o`.`offer_id`))', [$customerOrganization]);
                                })
                                ->where(function ($query) use ($currentDate, $currentDay) {
                                    $query->where(function ($q) use ($currentDate) {
                                        $q->whereBetween(DB::raw("'" . $currentDate . "'"), ['o.from_date', 'o.to_date'])
                                            ->orWhere(function ($q) use ($currentDate) {
                                                $q->where('o.from_date', '<=', DB::raw("'" . $currentDate . "'"))
                                                    ->where('o.to_date', '=', DB::raw("'0000-00-00 00:00:00'"));
                                            });
                                    })->orWhere(function ($q) use ($currentDay) {
                                        $q->whereRaw("FIND_IN_SET(?, (SELECT o1.week_days FROM offer_tbl as o1 WHERE o1.offer_id = o.offer_id))", ["'$currentDay'"]);
                                    });
                                })
                                ->where(function ($query) use ($distributor) {
                                    $query->where('o.item_distributor', '=', '')
                                        ->orWhere('o.item_distributor', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                                })
                                ->where(function ($query) use ($mainCat) {
                                    $query->where('o.item_main_category', '=', '')
                                        ->orWhere('o.item_main_category', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                                })
                                ->where(function ($query) use ($cat) {
                                    $query->where('o.item_category', '=', '')
                                        ->orWhere('o.item_category', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                                })
                                ->where(function ($query) use ($subCat) {
                                    $query->where('o.item_sub_category', '=', '')
                                        ->orWhere('o.item_sub_category', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                                })
                                ->where(function ($query) use ($brand) {
                                    $query->where('o.item_brand', '=', '')
                                        ->orWhere('o.item_brand', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                                })
                                ->where(function ($query) use ($flag) {
                                    $query->where('o.item_flag', '=', '')
                                        ->orWhere('o.item_flag', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                                })
                                ->where(function ($query) use ($item) {
                                    $query->where('o.items', '=', '')
                                        ->orWhere('o.items', '=', 'all')
                                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                                })
                                ->get();
                            if ($cOffers->isNotEmpty()) {
                                foreach ($cOffers as $cOffer) {
                                    $offerId = $cOffer->offer_id;
                                    $idString = (string)$cartItem['product_id'];

                                    if (!empty($resultArray) && array_key_exists($offerId, $resultArray)) {
                                        $offerItem = $resultArray[$offerId]->offer_item;

                                        if ($offerItem !== '') {
                                            $offerItemArr = explode(',', $offerItem);

                                            if (!in_array($idString, $offerItemArr)) {
                                                $resultArray[$offerId]->offer_item = $offerItem . ',' . $idString;
                                            }
                                        }
                                    } else {
                                        $cOffer->offer_item = $idString;
                                        $cOffer->item_type = 'offer';
                                        $resultArray[$offerId] = $cOffer;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return response()->json(["result" => true, "message" => "", "data" => $resultArray]);

    }

    public function checkOffer(Request $request)
    {

        $user = Auth::user();
        $offerId = $request->offer_id;
        $offerItemString = $request->offer_item;
        $offerItemArray = explode(',', $offerItemString);
        $totalAmount = 0;
        $totalQuantity = 0;

        if ($user) {
            $customerId = $user->customer_id;
            $cart = Cart::where('customer_id', $customerId)
                ->get()->keyBy('cart_key')->toArray();
        } else {
            $cart = Session::get('gc_cart', []);
        }

        $offerRecord = Offer::where('offer_id', $offerId)->first();
        // Total amount
        if (!empty($cart)) {
            foreach ($cart as $cartKey => $value) {
                // Checking if not a gift
                if (!str_contains($cartKey, '_')) {
                    // Checking if any offer already applied
                    if (empty($value['product_offer']) || $value['product_offer'] === 0) {
                        // Checking if the product is in the applicable item list
                        if (in_array($value['product_id'], $offerItemArray)) {
                            $product = Product::select('main_price as product_price')->where('product_id', $value['product_id'])->first()->toArray();
                            $discountPercentage = 0;
                            $itemDiscount = floatval($product['product_price']) * (floatval($discountPercentage) / 100);
                            $itemTotal = (floatval($product['product_price']) - floatval($itemDiscount)) * intval(abs($value['product_qty']));
                            $totalAmount += $itemTotal;
                            $totalQuantity += (int)$value['product_qty'];
                        }
                    }
                }
            }
        }

        // Checking if justifying the offer
        if ($offerRecord->minimum_type == 'amount' && $offerRecord->minimum_value > $totalAmount) {
            return response()->json(["result" => false, "message" => "Require minimum amount to apply the offer.", "data" => ""]);

        } elseif ($offerRecord->minimum_type == 'quantity' && $offerRecord->minimum_value > $totalQuantity) {
            return response()->json(["result" => false, "message" => "Require minimum quantity to apply the offer.", "data" => ""]);
        }

        return response()->json(["result" => true, "message" => "", "data" => $totalQuantity]);

    }

    public function applyOffer(Request $request)
    {

        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $currentDate = now()->format('Y-m-d');
        $currentDay = strtolower(now()->format('D'));
        $user = Auth::user();
        $offerId = $request->offer_id;
        $offerItemString = $request->offer_item;
        $applicableCartKeys = [];
        $offerItemArray = explode(',', $offerItemString);
        $totalAmount = 0;
        $totalQuantity = 0;
        $hasSample = 0;

        if ($user) {
            $customerId = $user->customer_id;
            $cart = Cart::where('customer_id', $customerId)
                ->get()->keyBy('cart_key')->toArray();
        } else {
            $cart = Session::get('gc_cart', []);
        }

        $offerRecord = Offer::where('offer_id', $offerId)->first();

        // Total amount
        if (!empty($cart)) {
            foreach ($cart as $cartKey => $value) {
                // Checking if not a gift
                if (!Str::contains($cartKey, '_') && $value['is_gift'] != 1) {
                    // Checking if any offer already applied
                    if (empty($value['product_offer']) || $value['product_offer'] === '0') {
                        // Checking if the product is in the applicable item list
                        if (in_array($value['product_id'], $offerItemArray)) {
                            $applicableCartKeys[] = $cartKey;
                            $product = Product::select('main_price as product_price')->where('product_id', $value['product_id'])->first()->toArray();
                            $discountPercentage = 0;
                            $itemDiscount = floatval($product['product_price']) * (floatval($discountPercentage) / 100);
                            $itemTotal = (floatval($product['product_price']) - floatval($itemDiscount)) * intval(abs($value['product_qty']));
                            $totalAmount += $itemTotal;
                            $totalQuantity += intval(abs($value['product_qty']));
                        }
                    }
                }
            }
        }

        // Checking if justifying the offer
        if ($offerRecord->minimum_type == 'amount' && $offerRecord->minimum_value > $totalAmount) {
            return response()->json(["result" => false, "message" => "Require minimum amount to apply the offer.", "data" => ""]);

        } elseif ($offerRecord->minimum_type == 'quantity' && $offerRecord->minimum_value > $totalQuantity) {
            return response()->json(["result" => false, "message" => "Require minimum quantity to apply the offer.", "data" => ""]);
        }
        $productOffer = $offerRecord->offer_id;
        if (!empty($cart)) {
            foreach ($cart as $cartKey => $value) {
                // Only applicable items
                if (in_array($cartKey, $applicableCartKeys)) {
                    if ($user) {
                        $customerId = $user->customer_id;
                        Cart::where('product_id', $value['product_id'])
                            ->where('customer_id', $customerId)
                            ->update([
                                'product_offer' => $productOffer,
                                'added_date' => Carbon::now(),
                            ]);
                    }
                    $product = Product::select('product_no')->where('product_id', $value['product_id'])->first();
                    $productKey = $product->product_no;
                    if (Session::has('gc_cart') && !empty(Session::get('gc_cart'))) {
                        session(['gc_cart.' . $productKey . '.product_offer' => $productOffer]);
                    }
                }
            }
        }

        return response()->json(["result" => true, "message" => "", "data" => $totalQuantity]);

    }

    public function fetchGifts(Request $request)
    {
        $offerId = $request->offer_id;
        $offerItem = $request->offer_item;

        $product = Product::select(DB::raw('FORMAT(main_price,0) AS main_price'))->where('product_id', $offerItem)->first();

        $productPrice = (float)$product->main_price;
        $offer = Offer::where('offer_id', $offerId)->first();

        $giftProductsQuery = DB::table('product_table as pt')
            ->leftJoin('stock_table as st', 'pt.product_id', '=', 'st.product_id')
            ->leftJoin('family_tbl as f', 'pt.linepr', '=', 'f.family_id')
            ->leftJoin('brand_table as b', 'pt.brand_id', '=', 'b.id')
            ->select(
                'pt.product_id',
                'pt.product_no',
                'pt.photo1',
                DB::raw('FORMAT(pt.main_price,0) AS main_price'),
                DB::raw("IF(pt.type_flag = '2', CONCAT(SUBSTR(REPLACE(pt.product_name, '\\\',''),1,60),IF(CHAR_LENGTH(REPLACE(pt.product_name, '\\\','')) > 60,'..','')), CONCAT(SUBSTR(REPLACE(f.family_name, '\\\',''),1,60),IF(CHAR_LENGTH(REPLACE(f.family_name, '\\\','')) > 60,'..',''))) AS family_name"),
                'pt.fam_name AS family_desc',
            )
            ->where('pt.web_status', '1')
            ->where('st.qty', '>', '0')
            ->where('pt.main_price', '<=', $productPrice);
        if ($offer->gift_distributor != '' && $offer->gift_distributor != 'all') {
            $giftProductsQuery->whereIn('b.distributor', explode(',', $offer->gift_distributor));
        }
        if ($offer->gift_brand != '' && $offer->gift_brand != 'all') {
            $giftProductsQuery->whereIn('pt.brand_id', explode(',', $offer->gift_brand));
        }
        if ($offer->gift_main_category != '' && $offer->gift_main_category != 'all') {
            $mainCategories = explode(',', $offer->gift_main_category);

            // Fetch sub_categories of corresponding main_categories
            $subCategoryIds = SubCategory::whereIn('cat_id', function ($query) use ($mainCategories) {
                $query->select('cat_id')
                    ->from(with(new Category)->getTable())
                    ->whereIn('main_cat_id', $mainCategories);
            })->pluck('id')->toArray();

            // Now, filter based on sub_categories
            $giftProductsQuery->whereIn('pt.sub_cat_id', $subCategoryIds);
        }
        if ($offer->gift_category != '' && $offer->gift_category != 'all') {
            $categoryIds = explode(',', $offer->gift_category);

            // Fetch sub_categories of corresponding categories
            $subCategoryIds = SubCategory::whereIn('cat_id', function ($query) use ($categoryIds) {
                $query->select('id')
                    ->from(with(new Category)->getTable())
                    ->whereIn('cat_id', $categoryIds);
            })->pluck('id')->toArray();

            // Now, filter based on sub_categories
            $giftProductsQuery->whereIn('pt.sub_cat_id', $subCategoryIds);
        }
        if ($offer->gift_sub_category != '' && $offer->gift_sub_category != 'all') {
            $giftProductsQuery->whereIn('pt.sub_cat_id', explode(',', $offer->gift_sub_category));
        }
        if ($offer->gifts != '' && $offer->gifts != 'all') {
            $giftProductsQuery->whereIn('pt.product_id', explode(',', $offer->gifts));
        }

        $giftProducts = $giftProductsQuery->groupBy('pt.product_id')
            ->orderBy('pt.web_price', 'ASC')
            ->get();

        $resultArray = [];
        if ($giftProducts->isNotEmpty()) {
            foreach ($giftProducts as $giftProduct) {
                $resImgPr = DB::table('product_more_pic_tbl')->where('product_id', $giftProduct->product_id)->orderBy('id')->limit(1)->first();

                $productImg = $giftProduct->photo1 == '' ? config('app.no_image_url') : $giftProduct->photo1;

                if ($resImgPr) {
                    $productImg = $resImgPr->aws != '' ? $resImgPr->aws : $productImg;
                }
                $giftProduct->product_img = $productImg;
                $resultArray[] = $giftProduct;
            }
        }

        return response()->json(["result" => true, "message" => "", "data" => $resultArray]);

    }

    public function addGiftToCart(Request $request)
    {


        $offerId = $request->offer;
        $productQuantity = $request->product_qty;
        $productId = $request->product_gift;


        $user = Auth::user();

        $offer = Offer::where('offer_id', $offerId)->first();

        if ($offer->minimum_type == 'quantity') {
            $n = (int)($productQuantity / $offer->minimum_value);
            $giftQuantity = (int)$offer->gift_quantity * $n;
        } else {
            $giftQuantity = (int)$offer->gift_quantity == "1" ? $offer->gift_quantity : 1;
        }
        $product = Product::select('product_no')->find($productId);
        $cartKey = $product->product_no;
        $stockRecord = Stock::where('product_id', $productId)->sum('qty');
        $stock = $stockRecord ?? 0;

        if ($user) {
            $customerId = $user->customer_id;
            $qryCartRecord = Cart::where('product_id', $productId)
                ->where('customer_id', $customerId)
                ->where('is_gift', 0)
                ->where('is_bundle', 0)
                ->get();

            $numCartRecord = $qryCartRecord->count();
            if ($numCartRecord > 0) {
                $cartRecord = $qryCartRecord->first();
                $giftQuantity += (int)$cartRecord->product_qty;

                if ($giftQuantity > $stock) {
                    return response()->json(["result" => false, "message" => "Quantity exceeds the current stock. Please try a different item or decrease the quantity.", "data" => ""]);
                } else {
                    $cartRecord->product_qty = $giftQuantity;
                    $cartRecord->product_offer = $offer;
                    $cartRecord->added_date = now();
                    $cartRecord->save();
                }
            } else {
                if ($giftQuantity > $stock) {
                    return response()->json(["result" => false, "message" => "Quantity exceeds the current stock. Please try a different item or decrease the quantity.", "data" => ""]);
                } else {
                    Cart::create([
                        'cart_key' => $cartKey,
                        'customer_id' => $customerId,
                        'product_id' => $productId,
                        'product_qty' => $giftQuantity,
                        'product_offer' => (int)$offerId,
                        'is_bundle' => 0,
                        'is_gift' => 1,
                        'evoucher_det' => '',
                        'added_date' => now(),
                    ]);

                }
            }
        } else {
            $cartItem = [
                'product_id' => $productId,
                'product_qty' => $giftQuantity,
                'product_offer' => (int)$offerId,
                'is_bundle' => 0,
                'is_gift' => 1,
                'gift_for' => 0,
            ];


            if (!session()->has('gc_cart')) {
                session(['gc_cart' => [$cartKey => $cartItem]]);
            } else {
                if (!array_key_exists($cartKey, session('gc_cart'))) {
                    session(['gc_cart.' . $cartKey => $cartItem]);
                } else {

                    $newQuantity = session('gc_cart.' . $cartKey . '.product_qty', 0) + $giftQuantity;
                    if ($newQuantity > $stock) {
                        return response()->json(["result" => false, "message" => "Quantity exceeds the current stock. Please try a different item or decrease the quantity.", "data" => ""]);
                    } else {
                        session(['gc_cart.' . $cartKey . '.product_qty' => (int)$newQuantity]);
                    }
                }
            }
        }


        return response()->json(["result" => true, "message" => "", "data" => ""]);
    }

    public function applyCoupon(Request $request)
    {
        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $currentDate = now()->format('Y-m-d');
        $couponDiscountAmount = 0;

        $couponCode = $request->input('coupon_code');
        $cartArray = (array)$request->input('cart_obj');

        $user = Auth::user();


        if ($couponCode === "") {
            return response()->json(["result" => false, "message" => "Please enter coupon code.", "data" => ""]);
        }

        $coupon = Coupon::where('status', '1')
            ->whereRaw("FIND_IN_SET(?, in_domain)", [$domainId])
            ->where('code', $couponCode)
            ->where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->first();

        if (!$coupon) {
            return response()->json(["result" => false, "message" => "Please enter a valid coupon code.", "data" => ""]);
        }
        if ($coupon->check_purchase == '1') {
            if (!$user) {
                return response()->json(["result" => false, "message" => "Please sign in to apply this code.", "data" => ""]);
            }
            $customerId = $user->customer_id;
            $salesRecord = DB::table('sales_table_item_online')
                ->select(DB::raw('ROUND(SUM(IF(qty<0, (retail_price * qty) + discount_price, (retail_price * qty) - discount_price)), 3) AS total_purchase_amount'))
                ->where('company_id', 5)
                ->where('customer_no', $customerId)
                ->groupBy('customer_no')
                ->first();

            if ($salesRecord && (float)$salesRecord->total_purchase_amount > 0) {
                return response()->json(["result" => false, "message" => "This coupon is not applicable for you.", "data" => ""]);
            }
            Session::put('gc_coupon.' . $coupon->id, [
                'is_applied' => 1,
                'discount' => (float)$coupon->discount,
                'code' => $couponCode,
                'delivery_free' => $coupon->delivery_free
            ]);
        } else {
            $applicableQuantity = 0;
            if (!empty($cartArray)) {
                foreach ($cartArray as $cart) {
                    if ($cart['has_coupon'] !== '' && $cart['applied_coupon'] === 0) {
                        $hasCouponArr = explode(',', $cart['has_coupon']);
                        if (in_array($coupon->id, $hasCouponArr)) {
                            $applicableQuantity += (int)$cart['product_qty'];
                        }
                    }
                }
            }

            switch ($coupon->expression) {
                case 4:
                    $errorCondition = $applicableQuantity <= $coupon->qty;
                    break;
                case 3:
                    $errorCondition = $applicableQuantity < $coupon->qty;
                    break;
                case 2:
                    $errorCondition = $applicableQuantity !== $coupon->qty;
                    break;
                default:
                    $errorCondition = false;
                    break;
            }

            if ($errorCondition) {
                return response()->json(["result" => false, "message" => "You need a minimum quantity of " . ($coupon->qty + 1) . " to apply code", "data" => ""]);
            } else {
                Session::put('gc_coupon.' . $coupon->id, [
                    'is_applied' => 1,
                    'discount' => (float)$coupon->discount,
                    'code' => $couponCode,
                    'delivery_free' => $coupon->delivery_free
                ]);

                // Return a success response if needed
                return response()->json(["result" => true, "message" => "Coupon applied successfully", "data" => 0]);
            }

        }
    }

    public function reorder(Request $request)
    {
        $invoice = $request->invoice;
        $user = Auth::user();

        if (!$user) {
            return response()->json(["result" => false, "message" => "Please sign in to reorder your previous order.", "data" => ""]);
        }

        $customerId = $user->customer_id;
        $invoiceRecords = DB::table('sales_table_item_online')
            ->select('product_id', 'qty as quantity')
            ->where('sub_invoice', $invoice)
            ->get();

        $numInvoiceRecords = count($invoiceRecords);

        if ($numInvoiceRecords === 0) {
            return response()->json(["result" => false, "message" => "No record found for this order.", "data" => ""]);
        }
        foreach ($invoiceRecords as $rowInvoiceRecord) {
            $productId = $rowInvoiceRecord->product_id;
            $quantity = 1;
            $qryCartRecord = Cart::where('product_id', $productId)
                ->where('customer_id', $customerId)
                ->where('is_gift', 0)
                ->where('is_bundle', 0)
                ->get();
            $numCartRecord = $qryCartRecord->count();
            $product = Product::select(['product_id', 'product_no', 'sub_cat_id', 'brand_id', 'func_flag', 'has_gift'])->find($productId);
            $cartKey = $product->product_no ?? 0;
            $stockRecord = Stock::where('product_id', $product->product_id)->sum('qty');
            $stock = $stockRecord ?? 0;
            if($stock>0){
                if ($quantity > $stock) {
                    $quantity = $stock;
                }
                if ($numCartRecord > 0) {
                    $cartRecord = $qryCartRecord->first();
                    $quantity += (int)$cartRecord->product_qty;
                    $cartRecord->product_qty = $quantity;
                    $cartRecord->product_offer = 0;
                    $cartRecord->added_date = now();
                    $cartRecord->save();
                } else {
                    Cart::create([
                        'cart_key' => $cartKey,
                        'customer_id' => $customerId,
                        'product_id' => $productId,
                        'product_qty' => $quantity,
                        'product_offer' => 0,
                        'is_bundle' => 0,
                        'evoucher_det' => '',
                        'added_date' => now(),
                    ]);
                }
            }
        }

        $cartCount = Helper::cartCount();

        return response()->json(["result" => true, "message" => "", "data" => $cartCount]);
    }

    public function proceedToCheckout(Request $request)
    {
        $is_guest = $request->guest;
        $user = Auth::user();
        $outStockItems = [];

        if(!$user){
            if ($is_guest == 'true') {
                session(['is_guest' => 'G-' . time()]);
            } else {
                return response()->json(["result" => false, "message" => "Please sign in", "data" => ""]);
            }
        }

        if ($user) {
            $customerId = $user->customer_id;

            $cart = Cart::where('customer_id', $customerId)
                ->get()->keyBy('cart_key')->toArray();
        } else {
            $cart = Session::get('gc_cart', []);
        }
        if (!empty($cart)) {
            foreach ($cart as $cartItem) {
                $stock = 0;
                $quantity = $cartItem['product_qty']; // Assuming 'product_qty' is a key in the $cartItem array
                if ($cartItem['is_bundle'] == 1) {
                    $stock = 1;
                } elseif ($cartItem['is_voucher'] == 1) {
                    $product = Product::select('barcode')
                        ->where('product_id', $cartItem['product_id'])
                        ->first();
                    $voucherType = !empty($cartItem['evoucher_det']) ? 8 : 7;
                    $stock = Voucher::where('status', 'GENERATED')
                        ->where('partner_id', $voucherType)
                        ->where('barcode', $product->barcode)
                        ->count();
                } else {
                    $stock = Stock::where('product_id', $cartItem['product_id'])->sum('qty'); // Assuming 'product_id' is a key in the $cartItem array
                }

                if ($stock < $quantity) {
                    $product = DB::table('product_table as pt')
                        ->select(DB::raw("IF(pt.type_flag = '2', CONCAT(SUBSTR(REPLACE(pt.product_name, '\\\\', ''),1,60), IF(CHAR_LENGTH(REPLACE(pt.product_name, '\\\\', '')) > 60, '..', '')), CONCAT(SUBSTR(REPLACE(f.family_name, '\\\\', ''),1,60), IF(CHAR_LENGTH(REPLACE(f.family_name, '\\\\', '')) > 60, '..', ''))) AS family_name"))
                        ->leftJoin('family_tbl as f', 'pt.linepr', '=', 'f.family_id')
                        ->where('pt.product_id', $cartItem['product_id']) // Assuming 'product_id' is a key in the $cartItem array
                        ->first();
                    $outStockItems[] = Str::title($product->family_name);
                }
            }
        }

        if (!empty($outStockItems)) {
            return response()->json(["result" => false, "message" => "Warning :The below items are out of stock , please delete.", "data" => $outStockItems]);
        }
        return response()->json(["result" => true, "message" => "", "data" => 0]);
    }


    private function sendTestEmail($email)
    {
        $text = 'This is a test email. If you receive this, your email setup is working.';

        Mail::raw($text, function ($message) use ($email) {
            $message->to($email)
                ->subject('Test Email');
        });
    }
}

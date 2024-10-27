<?php

namespace Modules\Order\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Modules\Cart\Entities\Cart;
use Modules\Frontend\Entities\Offer;
use Modules\Frontend\Entities\Tier;
use Modules\Frontend\Entities\Reward;
use Modules\Order\Entities\Area;
use Modules\Order\Entities\City;
use Modules\Order\Entities\DeliveryAddress;
use Modules\Order\Entities\DeliveryInvoice;
use Modules\Order\Entities\Store;
use Modules\Order\Entities\PaymentType;
use Modules\Order\Entities\Payment;
use Modules\Order\Entities\WebInvoice;
use Modules\Order\Entities\SalePos;
use Modules\Order\Entities\SaleOnline;
use Modules\Order\Entities\Purchase;
use Modules\Order\Entities\EVoucher;
use Modules\Order\Entities\MontypayPayment;
use Modules\Product\Entities\Brand;
use Modules\Product\Entities\Category;
use Modules\Product\Entities\Family;
use Modules\Product\Entities\MainCategory;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\SubCategory;
use Modules\Product\Entities\Voucher;
use Modules\Product\Entities\Stock;
use Modules\Product\Entities\Sample;
use Modules\User\Entities\User;
use Modules\User\Entities\UserLoyaltyPoint;
use Helper;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class OrderController extends Controller
{

    public function checkout(Request $request)
    {


        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $evoucherArray = [];
        $shippingAddresses = [];
        $billingAddress = ['bill_name' => '', 'bill_country' => 'Jordan', 'bill_phone' => '', 'bill_phone_code' => '962', 'bill_email' => ''];
        $user = Auth::user();

        if (!$user && empty(Session::get('is_guest'))) {
            return redirect()->to('/my-basket');
        }
        if ($user) {
            $customerId = $user->customer_id;
            $cart = Cart::where('customer_id', $customerId)
                ->get()->keyBy('cart_key')->toArray();
        } else {
            $cart = Session::get('gc_cart', []);
        }

        $payableAmount = 0;
        if (!empty($cart)) {
            foreach ($cart as $cartItem) {
                if ($cartItem['is_voucher'] == 1 && $cartItem['evoucher_det'] != '') {
                    $product = Product::select('barcode')
                        ->where('product_id', $cartItem['product_id'])
                        ->first();
                    $voucher = Voucher::where('status', 'GENERATED')
                        ->where('partner_id', 8)
                        ->where('barcode', $product->barcode)
                        ->first();
                    $evoucherArray[] = array(
                        "code" => $voucher->code,
                        "send_det" => $cartItem['evoucher_det']
                    );
                }

                $product = Product::select(DB::raw("ROUND(main_price, 1) AS product_price"))->where('product_id', $cartItem['product_id'])->first();
                $payableAmount += (float)$product->product_price * (int)$cartItem['product_qty'];
            }
        }
        if (!empty($cart) && !empty($evoucherArray) && count($cart) == count($evoucherArray)) {
            return redirect()->to('/payment');
        }
        if ($user) {
            $customerId = $user->customer_id;
            // Fetching delivery addresses
            $deliveryAddresses = DeliveryAddress::where('customer_id', '=', $customerId)->get();
            foreach ($deliveryAddresses as $deliveryAddress) {
                // Fetching city record for each delivery address
                $city = Area::where('name', $deliveryAddress->city)->first(['id', 'name', 'ship_charge']);
                $deliveryAddress->ship_charge = optional($city)->ship_charge ?? 0.000;
                $shippingAddresses[$deliveryAddress->id] = $deliveryAddress;
            }

            $billingAddress = ['bill_name' => $user->customer_name, 'bill_country' => $user->country, 'bill_phone' => $user->phone, 'bill_phone_code' => $user->phone_code, 'bill_email' => $user->email];
        }
        $cities = City::where('country_id', 111)
            ->orderBy('name')
            ->get(['id', 'name as label', 'name as value']);

        $areas = Area::where('state_id', 1978)
            ->orderBy('name')
            ->get(['id', 'name', 'ship_charge', 'name as label', 'name as value']);

        $stores = Store::where('web', '=', '1')
            ->whereRaw('FIND_IN_SET(?, domain_id)', [$domainId])
            ->where('address', '!=', '')
            ->where('ph_no', '!=', '')
            ->where('store_pickup', '=', '1')
            ->orderBy('store_name')
            ->get(['store_id', 'store_name', 'address', 'ph_no', 'latitude', 'longitude', 'extension', 'opening_hours']);


        return view('frontend.pages.checkout')
            ->with('cities', $cities)
            ->with('areas', $areas)
            ->with('stores', $stores)
            ->with('shippingAddresses', $shippingAddresses)
            ->with('billingAddress', $billingAddress)
            ->with('payableAmount', $payableAmount);
    }

    public function setDelivery(Request $request)
    {
        $user = Auth::user();
        $validator = $this->validateRequest($request);

        if ($validator->fails()) {
            return response()->json([
                "result" => false,
                "message" => $validator->errors()->first(),
                "data" => "",
            ]);
        }

        // Store billing information in session
        Session::put('gc_billing', $request->only([
            'bill_name', 'bill_country', 'bill_phone_code', 'bill_phone', 'bill_email'
        ]));

        // Store shipping information in session
        Session::put('gc_shipping', $request->only([
            'ship_name', 'ship_address', 'ship_street_number', 'ship_city', 'ship_state', 'ship_zip_code',
            'ship_charge', 'delivery_date', 'ship_phone', 'ship_email', 'pickfrm_store_id',
        ]));

        if ($request->pickfrm_store_id == '0') {
            $shipCharge = $request->ship_charge;

            $deliveryDate = Carbon::createFromFormat('Y-m-d', $request->delivery_date);
            $currentDate = Carbon::now();
            $daysDifference = $currentDate->diffInDays($deliveryDate);

            if ($request->input('ship_state') == 'Amman') {
                if ($daysDifference < 1) {
                    $deliveryDate->addDays(1);
                }
            } else {
                if ($daysDifference < 2) {
                    $deliveryDate->addDays(2);
                }
            }

            Session::put('gc_shipping.ship_charge', $shipCharge);
            Session::put('gc_shipping.delivery_date', $deliveryDate->format('Y-m-d'));
        }


        if ($user && $request->pickfrm_store_id == '0') {
            $customerId = $user->customer_id;
            $deliveryAddressId = $request->ship_address_id;

            // Set other rows' is_default to 0 if defaultAddress is 1
            if ($request->default_address == '1') {
                DeliveryAddress::where('customer_id', $customerId)->update(['is_default' => 0]);
            }

            // Create or update delivery address
            $deliveryAddressData = [
                'customer_id' => $customerId,
                'customer_name' => $request->ship_name,
                'customer_phone' => $request->ship_phone,
                'customer_email' => $request->ship_email,
                'address' => $request->ship_address,
                'street_number' => $request->ship_street_number,
                'street' => $request->ship_street,
                'city' => $request->ship_city,
                'state' => $request->ship_state,
                'zip_code' => $request->ship_zip_code,
                'country' => 'Jordan',
                'is_default' => $request->default_address ?? 0,
            ];
            DeliveryAddress::updateOrCreate(['id' => $deliveryAddressId], $deliveryAddressData);

        }

        return response()->json(["result" => true, "message" => 'Delivery address saved successfully', "data" => '']);

    }

    private function validateRequest(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        // Define the initial validation rules
        $rules = [
            'ship_name' => 'nullable|string',
            'ship_phone' => 'nullable|string',
            'ship_email' => 'nullable|email',
            'ship_city' => 'nullable|string',
            'ship_state' => 'nullable|string',
            'ship_street' => 'nullable|string',
            'ship_street_number' => 'nullable|string',
            'ship_zip_code' => 'nullable|string',
            'delivery_date' => 'required|date',
            'pickfrm_store_id' => 'nullable|numeric',
            'default_address' => 'nullable|boolean',
            'ship_charge' => 'nullable|numeric|min:0',
        ];

// Check if pickfrm_store_id is not 0
        if ($request->filled('pickfrm_store_id') && $request->input('pickfrm_store_id') != 0) {
            // If pickfrm_store_id is not 0, make ship_name, ship_phone, etc. nullable
            foreach (['ship_name', 'ship_phone', 'ship_city', 'ship_state'] as $field) {
                $rules[$field] .= '|nullable';
            }
        } else {
            // Otherwise, make ship_name, ship_phone, etc. required
            foreach (['ship_name', 'ship_phone', 'ship_city', 'ship_state'] as $field) {
                $rules[$field] .= '|required';
            }
        }

// Perform validation
        $validator = Validator::make($request->all(), $rules);

// Return the validator instance
        return $validator;
    }

    public function payment(Request $request)
    {
        //$montypayPaymentId = Session::get('montypay_payment_id');
        //session()->forget('montypay_payment_id');
        //dd(Session::all());

        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $currentDate = now()->format('Y-m-d');
        $currentDay = strtolower(now()->format('D'));
        $user = Auth::user();


//        if (!Session::has('reload_payments') || Session::get('reload_payments') !== true) {
//            Session::forget('is_redeemed');
//            Session::forget('redeemed_amount');
//        }

        Session::forget('is_redeemed');
        Session::forget('redeemed_amount');

        if (Session::has('voucher') && Session::get('voucher') === true) {
            foreach (Session::get('voucher') as $vk => $voucher) {
                if ($voucher['type'] == "exchange") {
                    Session::forget('voucher' . $vk);
                }
            }
        }
//        dd(session('voucher'));
        if (!$user && empty(Session::get('is_guest'))) {
            return redirect()->to('/my-basket');
        }


        if ($user) {
            $customerId = $user->customer_id;
            $customerOrganization = $user->organization;
            $customerTier = $user->tier;
            $customerLocationNo = $user->location_no;
            $customerLocationName = $user->location_name;
            $cart = Cart::where('customer_id', $customerId)
                ->get()->keyBy('cart_key')->toArray();
        } else {
            $cart = Session::get('gc_cart', []);
        }
        if (empty($cart)) {
            return redirect()->to('/my-basket');
        }

        $isCouponApplicable = 0;
        $isRedeemApplicable = 0;
        $minimumPurchase = 0;
        $freeDeliveryAmount = (float)Helper::getSiteConfig('free_delivery');

        if ($freeDeliveryAmount > 0) {
            $minimumPurchase = $freeDeliveryAmount;
        }

        $payableAmount = 0;
        $evouchers = [];


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

                if ($cartItem['is_voucher'] == 1 && $cartItem['evoucher_det'] != '') {
                    $productDetail = Product::select('barcode')
                        ->where('product_id', $cartItem['product_id'])
                        ->first();
                    $voucher = Voucher::where('status', 'GENERATED')
                        ->where('partner_id', 8)
                        ->where('barcode', $productDetail->barcode)
                        ->first();
                    $evouchers[] = array(
                        "code" => $voucher->code,
                        "send_det" => $cartItem['evoucher_det']
                    );
                }

                $payableAmount += ((float)$product->product_price * (int)$cartItem['product_qty']) - (float)$productDiscPrice;

            }

        }


        /**
         * Calculate shipping charge based on various factors:
         * - Customer's tier
         * - Total order amount
         * - Location (pickup from store)
         * - Coupon's free delivery (promo code)
         *
         * @author Sushobhon Sahoo
         */

        // Retrieve shipping charge from session or set it to 0
        $shipCharge = Session::get('gc_shipping.ship_charge', 0);

        // Check if shipping charge is set and not empty
        if (!empty($shipCharge)) {
            // Retrieve customer tier (assuming user authentication)
            $customerTier = $user ? $user->tier : '';

            // Set shipping charge according to customer's tier
            if (!empty($customerTier)) {
                $tier = Tier::find($customerTier);
                if ($tier) {
                    $shipCharge = (float)$tier->ship_charge;
                    $freeShipping = (float)$tier->free_shipping;
                    // Check if order amount qualifies for free shipping
                    if (!empty($freeShipping) && $freeShipping > 0 && (float)$payableAmount >= (float)$freeShipping) {
                        $shipCharge = 0;
                    }
                }
            }

            // Set shipping charge according to minimum order amount
            if ($minimumPurchase > 0 && (float)$payableAmount >= $minimumPurchase) {
                $shipCharge = 0;
            }

            // Set shipping charge according to location (pickup from store)
            $pickFromStoreId = Session::get('gc_shipping.pickfrm_store_id', 0);
            if (!empty($pickFromStoreId)) {
                $shipCharge = 0;
            }

            // Set shipping charge according to coupon's free delivery (promo code)
            if (Session::has('gc_coupon')) {
                $freeDelivery = false;
                foreach (Session::get('gc_coupon') as $coupon) {
                    if ($coupon['delivery_free'] == 1) {
                        $freeDelivery = true;
                        break;
                    }
                }
                if ($freeDelivery) {
                    $shipCharge = 0;
                }
            }

            // Update payable amount with shipping charge
            $payableAmount += $shipCharge;
        }

        $hideCashOnDelivery = 0;
        if (!empty($evouchers) && count($cart) === count($evouchers)) {
            $hideCashOnDelivery = 1;
        } else {
            if (!Session::has('gc_shipping.ship_charge') || empty(Session::get('gc_shipping.ship_charge'))) {
                return redirect()->to('/my-basket');
            }
        }
//        if (!Session::has('gc_shipping') || Session::get('gc_shipping') === null) {
//            return redirect()->to('/checkout');
//        }
        //dd($evouchers);
        $redeemedAmount = 0;
        if (Session::has('redeemed_amount') && (float)Session::get('redeemed_amount') > 0) {
            $redeemedAmount = (float)Session::get('redeemed_amount');
            $payableAmount -= $redeemedAmount;
        }

        $voucherDiscount = 0;
        $vouchers = Session::get('voucher', []);
        if (!empty($vouchers)) {
            foreach ($vouchers as $voucher) {
                $voucherDiscount += (float)$voucher['amount'];
            }
            $payableAmount -= $voucherDiscount;
        }

        if ($user) {
            if (Session::has('redeemed_amount') && (float)Session::get('redeemed_amount') > 0) {
                Session::put('user_point', (int)Session::get('user_point') - (int)Session::get('redeemed_amount'));
            }
            $isRedeemApplicable = 1;
        }

        $paymentTypes = PaymentType::whereRaw("FIND_IN_SET('web', platform)")->get();

        $stockErrorFlag = 0;
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
                    $voucherType = !empty($cartItem['evoucher_det']) ? 8 : 7; // Assuming 'evoucher_det' is a key in the $cartItem array
                    $stock = Voucher::where('status', 'GENERATED')
                        ->where('partner_id', $voucherType)
                        ->where('barcode', $product->barcode) // Assuming 'barcode' is a key in the $cartItem array
                        ->count();
                } else {
                    $stock = Stock::where('product_id', $cartItem['product_id'])->sum('qty'); // Assuming 'product_id' is a key in the $cartItem array
                }

                if ($stock < $quantity) {
                    $stockErrorFlag++;
                }
            }
        }

        $ePayOrderId = "CC" . Carbon::now()->format('YmdHisv');
        $ePayOrderAmount = number_format($payableAmount, 3, '.', '');
        $ePaySessionId = Helper::createMasterCardPaySession($ePayOrderId, $ePayOrderAmount);
        $ePayJSUrl = config('services.mc_payment.checkout_url');
        $montyPayUrl = Helper::createMontyPaySession($ePayOrderId, $ePayOrderAmount);


        return view('frontend.pages.payment', [
            'stockErrorFlag' => $stockErrorFlag,
            'payableAmount' => $payableAmount,
            'loyaltyPoints' => 0,
            'isCouponApplicable' => 0,
            'isRedeemApplicable' => $isRedeemApplicable,
            'hideCashOnDelivery' => $hideCashOnDelivery,
            'minimumPurchase' => $minimumPurchase,
            'voucherDiscount' => $voucherDiscount,
            'shipCharge' => $shipCharge,
            'redeemedAmount' => $redeemedAmount,
            'vouchers' => $vouchers,
            'ePayOrderId' => $ePayOrderId,
            'ePayOrderAmount' => $ePayOrderAmount,
            'ePaySessionId' => $ePaySessionId,
            'ePayJSUrl' => $ePayJSUrl,
            'paymentTypes' => $paymentTypes,
            'montyPayUrl' => $montyPayUrl,
        ]);
    }

    public function fetchCities(Request $request)
    {
        $id = $request->input('id');

        // Fetch area records based on city ID
        $cities = Area::where('state_id', $id)->orderBy('name')->get();

        if ($cities->isNotEmpty()) {
            $data = $cities;
            $result = true;
            $message = '';
        } else {
            $data = [];
            $result = false;
            $message = 'No cities found.';
        }

        return response()->json([
            'result' => $result,
            'message' => $message,
            'data' => $data
        ]);
    }

    public function deleteDeliveryAddress(Request $request)
    {
        $id = $request->input('id');

        $deliveryAddress = DeliveryAddress::find($id);

        if ($deliveryAddress) {
            $deliveryAddress->delete();
            $data = '';
            $result = true;
            $message = '';
        } else {
            $data = '';
            $result = false;
            $message = 'No record found.';
        }

        return response()->json([
            'result' => $result,
            'message' => $message,
            'data' => $data
        ]);
    }

    public function sendRedeemOtp(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $customerId = $user->customer_id;

            $customer = User::where('customer_id', $customerId)->first();
            if ($customer) {
                $otp = Helper::generateOTP(6);
                $customer->loyalty_OTP = $otp;
                $customer->save();

                $message = urlencode($otp . " is your verification code from Gifts Center for redeeming loyalty points. Please do not share it with anyone.");
                $phoneNumber = $customer->phone_code . $customer->phone;
                Helper::sendSMS($phoneNumber, $message);

                if ($customer->email) {
                    $content = $otp . ' is your verification code from Gifts Center for redeeming loyalty points. Please do not share it with anyone.';
                    Helper::sendMail($customer->email, $customer->customer_name, 'Redeem Loyalty Points OTP', $content);
                }
                return response()->json(['result' => true, 'message' => 'OTP sent successfully', 'data' => '']);
            }
        }
        return response()->json(['result' => false, 'message' => 'Failed to send OTP', 'data' => '']);
    }

    public function verifyRedeemOtp(Request $request)
    {
        $otp = $request->input('otp');

        if (empty($otp)) {
            return response()->json(['result' => false, 'message' => 'Please provide your confirmation code', 'data' => '']);
        }

        $user = Auth::user();
        $customerId = $user->customer_id;

        $customer = User::select('loyalty_point')
            ->where('customer_id', $customerId)
            ->where('loyalty_OTP', $otp)
            ->first();

        if ($customer) {
            return response()->json(['result' => true, 'message' => 'Thank you for your patience', 'data' => $customer->loyalty_point]);
        } else {
            return response()->json(['result' => false, 'message' => 'Provided confirmation code is not valid', 'data' => '']);
        }
    }

    public function redeemLoyaltyPoints(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $customerId = $user->customer_id;
        }

        // Get input data
        $redeemPoints = $request->input('redeem_points');
        $ePayOrderAmount = $request->input('ePayOrderAmount');
        $cartArr = (array)$request->input('cart_obj');

        // Fetch customer's loyalty points from the database
        $custLoyaltyPoints = UserLoyaltyPoint::where('customer_id', $customerId)
            ->orderBy('added_date', 'desc')
            ->orderBy('id', 'desc')
            ->value('post_balance');

        // Calculate payable total from cart data
        $payableTotal = 0;
        foreach ($cartArr as $item) {
            $itemNetTotal = ((float)$item['product_price'] * abs($item['product_qty'])) - (float)$item['product_disc_price'];
            $payableTotal += $itemNetTotal;
        }

        if ($redeemPoints > $custLoyaltyPoints) {
            return response()->json(['result' => false, 'message' => 'Redeem points exceed your remaining points.', 'data' => '']);
        }

        $previousRedeemedAmount = (float)Session::get('redeemed_amount') ?? 0;
        $paymentType = PaymentType::find(10);
        $redeemedAmount = (float)$redeemPoints * (float)$paymentType->pay_ex_rate;
        session(['is_redeemed' => $redeemPoints]);
        session(['redeemed_amount' => $redeemedAmount]);
        session(['reload_payments' => true]);

        $ePayOrderId = "CC" . Carbon::now()->format('YmdHisv');
        $ePayOrderAmount += $previousRedeemedAmount;
        $ePayOrderAmount -= $redeemedAmount;
        if ($ePayOrderAmount < 0) {
            return response()->json(['result' => false, 'message' => 'Your payment amount cannot be negative.', 'data' => '']);
        }
        $ePayOrderAmount = number_format($ePayOrderAmount, 3, '.', '');
        $ePaySessionId = Helper::createMasterCardPaySession($ePayOrderId, $ePayOrderAmount);
        $montyPayUrl = Helper::createMontyPaySession($ePayOrderId, $ePayOrderAmount);
        $data = [
            'ePayOrderId' => $ePayOrderId,
            'ePayOrderAmount' => $ePayOrderAmount,
            'ePaySessionId' => $ePaySessionId,
            'montyPayUrl' => $montyPayUrl,
            'redeemedAmount' => $redeemedAmount,
        ];
        return response()->json(['result' => true, 'message' => $previousRedeemedAmount, 'data' => $data]);
    }

    public function verifyVoucherCode(Request $request)
    {

        $code = $request->input('code');
        $ePayOrderAmount = $request->input('ePayOrderAmount');
        $cart = (array)$request->input('cart_obj');
        $payableTotal = 0;
        $discountFlag = 0;

        $payableTotal = collect($cart)->map(function ($item) use (&$disFlag) {
            $productPrice = (float)$item['product_price'];
            $productQty = abs($item['product_qty']);
            $productDiscountPrice = (float)$item['product_disc_price'];

            if ($productDiscountPrice > 0) {
                $discountFlag = 1;
            }

            return ($productPrice * $productQty) - $productDiscountPrice;
        })->sum();

        // Retrieve shipping charge from session or set it to 0
        $shipCharge = Session::get('gc_shipping.ship_charge', 0);

        // Check if shipping charge is set and not empty
        if (!empty($shipCharge)) {
            $isRedeemApplicable = 0;
            $minimumPurchase = 0;
            $freeDeliveryAmount = (float)Helper::getSiteConfig('free_delivery');

            if ($freeDeliveryAmount > 0) {
                $minimumPurchase = $freeDeliveryAmount;
            }

            // Set shipping charge according to minimum order amount
            if ($minimumPurchase > 0 && (float)$payableTotal >= $minimumPurchase) {
                $shipCharge = 0;
            }

            // Set shipping charge according to location (pickup from store)
            $pickFromStoreId = Session::get('gc_shipping.pickfrm_store_id');
            if ($pickFromStoreId) {
                $shipCharge = 0;
            }

            // Update payable amount with shipping charge
            $payableTotal += $shipCharge;
        }


        $redeemedAmount = 0;
        if (Session::has('redeemed_amount') && (float)Session::get('redeemed_amount') > 0) {
            $redeemedAmount = (float)Session::get('redeemed_amount');
            $payableTotal -= $redeemedAmount;
        }
        $couponDiscountAmount = 0;
        $vouchers = Session::get('voucher', []);
        if (!empty($vouchers)) {
            foreach ($vouchers as $voucher) {
                $couponDiscountAmount += (float)$voucher['amount'];
            }
            $payableTotal -= $couponDiscountAmount;
        }
//        Session::forget('voucher');
//       dd(session('voucher'));
        $voucher = Voucher::select('voucher_id', 'code', 'barcode', 'price', 'VALIDITY', 'status', 'min_purchase')
            ->where('code', $code)
            ->first();
        if (!$voucher) {
            return response()->json(['result' => false, 'message' => 'Sorry! Either voucher is used or invalid.', 'data' => '']);
        }
        if ($voucher->status == 'ACTIVATED') {
            $current_date = now()->toDateString();

            if ($voucher->VALIDITY == '0000-00-00' || ($voucher->VALIDITY != '0000-00-00' && $current_date <= $voucher->VALIDITY)) {
                if ((float)$voucher->min_purchase > 0 && (float)$voucher->min_purchase > $payableTotal) {
                    return response()->json(['result' => false, 'message' => 'Sorry! Your minimum purchase must be " . $voucher->min_purchase . " JOD.', 'data' => '']);
                } else if (round($voucher->price) > $payableTotal) {
                    return response()->json(['result' => false, 'message' => 'Redeem voucher amount is higher than your payable amount.', 'data' => '']);
                } else if ((float)$voucher->min_purchase > 0 && $discountFlag === 1) {
                    return response()->json(['result' => false, 'message' => 'Please clear any discount to apply voucher.', 'data' => '']);
                } else {
                    if (session()->has('voucher') && !empty(session('voucher')) && array_key_exists($voucher->voucher_id, session('voucher'))) {
                        return response()->json(['result' => false, 'message' => 'The voucher is already applied to your payment.', 'data' => '']);
                    } else {
                        session()->put('voucher.' . $voucher->voucher_id, [
                            'amount' => round($voucher->price),
                            'code' => $voucher->code,
                            'barcode' => $voucher->barcode,
                            'type' => 'exchange'
                        ]);
                        $voucherDiscount = $voucher->price;
                        $ePayOrderId = "CC" . Carbon::now()->format('YmdHisv');
                        $ePayOrderAmount -= (float)$voucherDiscount;
                        if ($ePayOrderAmount < 0) {
                            return response()->json(['result' => false, 'message' => 'Your payment amount cannot be negative.', 'data' => '']);
                        }
                        $ePayOrderAmount = number_format($ePayOrderAmount, 3, '.', '');
                        $ePaySessionId = Helper::createMasterCardPaySession($ePayOrderId, $ePayOrderAmount);
                        $montyPayUrl = Helper::createMontyPaySession($ePayOrderId, $ePayOrderAmount);
                        $data = [
                            'ePayOrderId' => $ePayOrderId,
                            'ePayOrderAmount' => $ePayOrderAmount,
                            'ePaySessionId' => $ePaySessionId,
                            'montyPayUrl' => $montyPayUrl,
                            'voucher' => [
                                'amount' => round($voucher->price),
                                'code' => $voucher->code,
                                'barcode' => $voucher->barcode,
                                'type' => 'exchange'
                            ]];
                        return response()->json(['result' => true, 'message' => '', 'data' => $data]);
                    }
                }
            } else {
                return response()->json(['result' => false, 'message' => 'Sorry! Voucher validity expires.', 'data' => '']);
            }
        } else if ($voucher->status == 'GENERATED') {
            return response()->json(['result' => false, 'message' => 'Sorry! Voucher is not activated yet.', 'data' => '']);
        } else {
            return response()->json(['result' => false, 'message' => 'Sorry! Voucher is already used.', 'data' => '']);
        }
    }

    public function montyPayCallback(Request $request)
    {
        //dd($request->all());
        //dd(session('montypay_session'));
        //if ($request->type == 'sale' && $request->status == 'success') {
        $status = $request->type == 'sale' && $request->status == 'success' ? 1 : 0;
        //if (session()->has('montypay_session')) {
        //$montypaySession = session('montypay_session');
        //if($montypaySession['order_number'] == $request->order_number){
        MontypayPayment::insert([
            'payment_id' => $request->id,
            'order_number' => $request->order_number,
            'data' => json_encode($request->all()),
            'status' => $status,
            'transaction_type' => $request->type,
            'transaction_status' => $request->status,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        //}
        //}
        //}
    }

    public function orderProcessing(Request $request)
    {


        return view('frontend.pages.order-processing');
    }

    public function orderPlaced(Request $request)
    {

        $newVouchers = [];
        $isGuest = 0;
        $referralUrl = "";
        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $currentDate = now()->format('Y-m-d');
        $currentDay = strtolower(now()->format('D'));
        $user = Auth::user();
        $montypaySession = null;
        $montypayPaymentId = null;
        $paymentType = $request->input('payment_type', '');


        if ($paymentType == 'Pay Online') {
            if (session()->has('montypay_session')) {
                $montypaySession = session('montypay_session');
                session()->forget('montypay_session');
            }
            $isSuccess = MontypayPayment::where('order_number', $montypaySession['order_number'])
                ->where('status', 1)
                ->where('transaction_type', 'sale')
                ->where('transaction_status', 'success')
                ->first();
            if ($isSuccess) {
                $montypayPaymentId = $isSuccess->payment_id;
            } else {
                return redirect()->to('/payment');
            }
        }

        if ($user) {
            $customerId = $user->customer_id;
        }

        if (session()->has('referral_url') && session('referral_url') != "") {
            $referralUrl = session('referral_url');
            session()->forget('referral_url');
        }

        // if user is guest save user and cart items in database
        if (session()->has('is_guest') && session('is_guest') != "") {
            $isGuest = 1;
            $phone = preg_replace("/^0/", "", session('gc_billing.bill_phone'));
            $customer = User::where('phone', $phone)->first();
            if ($customer) {
                $customerId = $customer->customer_id;

                if (session('gc_billing.bill_email') != '') {
                    $customer->email = session('gc_billing.bill_email');
                    $customer->save();
                }

                $customer->is_guest = $isGuest;
                $customer->save();
            } else {
                $sequence = User::select(DB::raw("MAX(CONVERT(SUBSTRING_INDEX(customer_id,'-',-1),UNSIGNED INTEGER)) AS sequence"))
                    ->where('customer_id', 'LIKE', '%GC%')
                    ->orderBy('sequence', 'DESC')
                    ->limit(1)
                    ->value('sequence');
                $sequence = $sequence ?? 100;
                $customerId = 'GC-' . ($sequence + 1);

                $customer = new User();
                $customer->date_added = now();
                $customer->customer_name = Helper::cleanString(session('gc_billing.bill_name'));
                $customer->country = session('gc_billing.bill_country');
                $customer->phone_code = session('gc_billing.bill_phone_code');
                $customer->phone = session('gc_billing.bill_phone');
                $customer->email = session('gc_billing.bill_email');
                $customer->bill_address = session('gc_billing.bill_address');
                $customer->bill_street_number = session('gc_billing.bill_street_number');
                $customer->bill_street = session('gc_billing.bill_street');
                $customer->bill_city = session('gc_billing.bill_city');
                $customer->bill_state = session('gc_billing.bill_state');
                $customer->bill_zip_code = session('gc_billing.bill_zip_code');
                $customer->ship_address = session('gc_shipping.ship_address');
                $customer->ship_street_number = session('gc_shipping.ship_street_number');
                $customer->ship_street = session('gc_shipping.ship_street');
                $customer->ship_city = session('gc_shipping.ship_city');
                $customer->ship_state = session('gc_shipping.ship_state');
                $customer->ship_zip_code = session('gc_shipping.ship_zip_code');
                $customer->ship_country = session('gc_shipping.ship_country');
                $customer->reg_flag = 2;
                $customer->customer_id = $customerId;
                $customer->store_id = 600;
                $customer->status = 'Active';
                $customer->create_date = now();
                $customer->is_guest = $isGuest;
                $customer->save();
            }

            if (session()->has('gc_cart') && !empty(session('gc_cart'))) {
                foreach (session('gc_cart') as $cart_key => $cart_item) {
                    $quantity = $cart_item['product_qty'];
                    $isBundle = $cart_item['is_bundle'] == '1' ? 1 : 0;

                    $cart = new Cart();
                    $cart->cart_key = $cart_key;
                    $cart->customer_id = $customerId;
                    $cart->product_id = $cart_item['product_id'];
                    $cart->product_qty = $quantity;
                    $cart->product_offer = $cart_item['product_offer'];
                    $cart->is_bundle = $isBundle;
                    $cart->added_date = now();
                    $cart->is_guest = 1;
                    $cart->save();

                    session()->forget('gc_cart.' . $cart_key);
                }
            }
        }

        $deliveryDate = session('gc_shipping.delivery_date');
        $deliveryDateTime = Carbon::parse($deliveryDate . ' 13:00:00')->format('Y-m-d H:i:s');

        $customer = User::select('customer_id', 'customer_name', 'organization', 'tier', 'email', 'is_guest', 'phone_code', 'phone', 'loyalty_point', 'tier_updated_date')
            ->where('customer_id', $customerId)
            ->first();

        $customerOrganization = $customer->organization ?? '0';
        $customerTier = $customer->tier ?? '1';
        $customerTierUpdatedDate = $customer->tier_updated_date;
        $customerLoyaltyPoints = $customer->loyalty_point;
        $customerEmail = $customer->email;
        $customerPhoneCode = $customer->phone_code;
        $customerPhone = $customer->phone;
        $customerName = $customer->customer_name;

        $payableAmount = 0;
        $cartArray = [];
        $cart = Cart::where('customer_id', $customerId)->where('is_guest', $isGuest)
            ->get()->keyBy('cart_key')->toArray();

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

                        $productImg = $product->photo1 == '' ? url('/') . "/public/img/no-img-available.jpg" : $product->photo1;

                        if ($resImgPr) {
                            $productImg = $resImgPr->aws != '' ? $resImgPr->aws : $productImg;
                        }

                        $evoucherArray = [];
                        $patchString = '';
                        if ($cartItem['is_voucher'] == '1') {
                            $patchArray = [];

                            $voucherQuery = Voucher::where('barcode', $product->barcode)
                                ->where('status', 'GENERATED');

                            if ($cartItem['evoucher_det'] != '') {
                                $voucherQuery->where('partner_id', '8');
                            } else {
                                $voucherQuery->where('partner_id', '7');
                            }

                            $vouchers = $voucherQuery->orderBy('voucher_id', 'desc')
                                ->limit($cartItem['product_qty'])
                                ->get();

                            foreach ($vouchers as $voucher) {
                                $patchArray[] = $voucher->code;
                                if ($cartItem['evoucher_det'] != '') {
                                    $evoucherArray[] = [
                                        "code" => $voucher->code,
                                        "send_det" => json_decode($cartItem['evoucher_det'], true)
                                    ];
                                }

                                $validityDate = $voucher->valid_days > 0 ? now()->addDays($voucher->valid_days)->format('Y-m-d') : $voucher->VALIDITY;

                                $sendDetails = json_decode($cartItem['evoucher_det'], true);
                                if ($sendDetails) {
                                    $sendDetails["send_message"] = trim($sendDetails["send_message"]);
                                    $sendObj = json_encode($sendDetails);
                                } else {
                                    $sendObj = '';
                                }
                                $newVouchers[] = [
                                    'validity_date' => $validityDate,
                                    'e_details' => $sendObj,
                                    'code' => $voucher->code
                                ];
                            }

                            $patchString = implode(',', $patchArray);
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
                            'product_price_usd' => $product->product_price * 0.7,
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
                            'discount' => $webDiscount,
                            'coupon_discount' => $productDiscPrice,
                            'product_patch' => $patchString,
                        ];
                    }
                    $payableAmount += ((float)$product->product_price * (int)$cartItem['product_qty']) - (float)$productDiscPrice;
                }

            }
        }

        $cartTotal = $payableAmount;
        $phone = preg_replace("/^0/", "", session('gc_billing.bill_phone'));
        $customer = User::where('phone', $phone)->first();

        // Remove leading '0' from the phone number
        $deliveryPhone = ltrim(session('gc_shipping.ship_phone'), '0');
        if ($deliveryPhone != '') {
            // Check if customer exists with the given phone number
            $existingCustomer = User::where('phone', $deliveryPhone)->exists();
            if (!$existingCustomer) {
                $sequence = User::select(DB::raw("MAX(CONVERT(SUBSTRING_INDEX(customer_id,'-',-1),UNSIGNED INTEGER)) AS sequence"))
                    ->where('customer_id', 'LIKE', '%GC%')
                    ->orderBy('sequence', 'DESC')
                    ->limit(1)
                    ->value('sequence');
                $sequence = $sequence ?? 100;
                $newCustomerId = 'GC-' . ($sequence + 1);

                $customer = new User();
                $customer->date_added = now();
                $customer->customer_name = Helper::cleanString(session('gc_shipping.ship_name'));
                $customer->phone_code = '962';
                $customer->phone = $deliveryPhone;
                $customer->email = session('gc_shipping.ship_email');
                $customer->bill_address = session('gc_shipping.ship_address');
                $customer->bill_street_number = session('gc_shipping.ship_street_number');
                $customer->bill_street = session('gc_shipping.ship_street');
                $customer->bill_city = session('gc_shipping.ship_city');
                $customer->bill_state = session('gc_shipping.ship_state');
                $customer->bill_zip_code = session('gc_shipping.ship_zip_code');
                $customer->bill_country = 'Jordan';
                $customer->country = 'Jordan';
                $customer->reg_flag = 2;
                $customer->customer_id = $newCustomerId;
                $customer->store_id = 600;
                $customer->status = 'Active';
                $customer->create_date = now();
                $customer->is_guest = '0';
                $customer->save();
            }
        }


        switch ($paymentType) {
            case 'Pay Online':
                $paymentTypeNote = 'Pay Online';
                break;
            case 'Credit Online':
                $paymentTypeNote = 'Credit Card';
                break;
            case 'Card web':
                $paymentTypeNote = 'Card On Delivery';
                break;
            default:
                $paymentTypeNote = 'Cash On Delivery';
                break;
        }

        $giftWrap = $request->input('gift_wrap', 0);
        $giftBox = $request->input('gift_box', 0);
        $giftMessage = $request->input('gift_message', '');
        //$giftMessage = Helper::cleanEmojis($giftMessage);
        $storeId = session('gc_shipping.pickfrm_store_id') != 0 ? session('gc_shipping.pickfrm_store_id') : 50;
        $pickFromStoreId = (int)Session::get('gc_shipping.pickfrm_store_id', 0);

        $serialNoQuery = WebInvoice::where('domain_id', $domainId)->select('sl_no')->first();

        if ($serialNoQuery) {
            $serialNumber = $serialNoQuery->sl_no + 1;
            $invoiceNumber = 'W' . now()->format('Ymd') . $serialNumber;
        } else {
            $invoiceNumber = 'W' . now()->format('Ymd') . '1';
            $serialNumber = 1;
        }

        // Update the serial number in the database
        WebInvoice::updateOrCreate(['domain_id' => $domainId], ['sl_no' => $serialNumber]);

        // Loyalty points
        $gainedLoyalty = 0;
        $redeemedLoyalty = session('is_redeemed', 0);
        session()->forget('is_redeemed');

        $redeemedAmount = session('redeemed_amount', 0);
        session()->forget('redeemed_amount');
        session()->forget('reload_payments');

        // insert redeem loyalty point as payment type
        if ($redeemedAmount > 0) {
            Payment::create([
                'paymnt_invoice' => $invoiceNumber,
                'paymnt_type' => 'Redeem Points',
                'paymnt_amount' => $redeemedAmount,
                'paymnt_card' => '',
                'paymnt_name' => '',
                'paymnt_date' => now(),
                'store_id' => $storeId,
                'company_id' => 4,
                'paymnt_date_char' => now()->toDateString(),
                'all_update' => 0,
                'posted_date' => now()->toDateString(),
                'customer' => $customerId,
                'old_invoice' => $invoiceNumber,
            ]);
        }

        // activate the vouchers

        if (!empty($newVouchers)) {
            foreach ($newVouchers as $newVoucher) {
                $voucher = Voucher::where('code', $newVoucher['code'])->first();
                $voucher->status = 'ACTIVATED';
                $voucher->VALIDITY = $newVoucher['validity_date'];
                $voucher->customer_id = $customerId;
                $voucher->activate_date = now();
                $voucher->edetails = $newVoucher['e_details'];
                $voucher->save();
            }
        }

        $minimumPurchase = 0;
        $freeDeliveryAmount = (float)Helper::getSiteConfig('free_delivery');

        if ($freeDeliveryAmount > 0) {
            $minimumPurchase = $freeDeliveryAmount;
        }
        /**
         * Calculate shipping charge based on various factors:
         * - Customer's tier
         * - Total order amount
         * - Location (pickup from store)
         * - Coupon's free delivery (promo code)
         *
         * @author Sushobhon Sahoo
         */

        $shipCharge = Session::get('gc_shipping.ship_charge', 0);
        // Check if shipping charge is set and not empty
        if (!empty($shipCharge)) {

            // Set shipping charge according to customer's tier
            if (!empty($customerTier)) {
                $tier = Tier::find($customerTier);
                if ($tier) {
                    $shipCharge = (float)$tier->ship_charge;
                    $freeShipping = (float)$tier->free_shipping;
                    // Check if order amount qualifies for free shipping
                    if (!empty($freeShipping) && $freeShipping > 0 && (float)$payableAmount >= (float)$freeShipping) {
                        $shipCharge = 0;
                    }
                }
            }

            // Set shipping charge according to minimum order amount
            if ($minimumPurchase > 0 && (float)$payableAmount >= $minimumPurchase) {
                $shipCharge = 0;
            }

            // Set shipping charge according to location (pickup from store)
            if ($pickFromStoreId !== 0) {
                $shipCharge = 0;
            }

            // Set shipping charge according to coupon's free delivery (promo code)
            if (Session::has('gc_coupon')) {
                $freeDelivery = false;
                foreach (Session::get('gc_coupon') as $coupon) {
                    if ($coupon['delivery_free'] == 1) {
                        $freeDelivery = true;
                        break;
                    }
                }
                if ($freeDelivery) {
                    $shipCharge = 0;
                }
            }

            // Update payable amount with shipping charge
            $payableAmount += $shipCharge;
        }

        if (session()->has('voucher') && !empty(session('voucher'))) {
            foreach (session('voucher') as $voucher) {
                if ($voucher['type'] == "exchange") {
                    $voucherData = Voucher::where('code', $voucher['code'])->first();
                    if ($voucherData) {
                        $productData = Product::select('product_id', 'product_no', 'main_price',)->where('barcode', $voucherData->barcode)->first();
                        if ($productData && !in_array($productData->product_id, $cartArray)) {
                            $cartArray[] = [
                                'product_id' => $productData->product_id,
                                'product_no' => $productData->product_no,
                                'product_price' => $productData->main_price,
                                'product_qty' => -1,
                                'product_loyalty' => 0,
                                'product_disc_perc' => 0,
                                'product_disc_price' => 0,
                                'product_offer' => '',
                                'is_bundle' => '',
                                'coupon_discount' => '',
                                'applied_coupon' => '',
                                'product_patch' => $voucher['code'],
                            ];
                        } else {
                            $patchArray = explode(",", $cartArray[$productData->product_id]['product_patch']);
                            $patchArray[] = $voucher['code'];
                            $cartArray[$productData->product_id]['product_patch'] = implode(",", $patchArray);
                            $cartArray[$productData->product_id]['product_qty'] += -1;
                        }
                        $voucherData->update(['status' => 'USED']);
                    }
                }
            }
        }

        if ($redeemedAmount > 0) {
            $payableAmount = (float)$payableAmount - (float)$redeemedAmount;
        }

        if ($payableAmount > 0) {
            Payment::create([
                'paymnt_invoice' => $invoiceNumber,
                'paymnt_type' => $paymentType,
                'paymnt_amount' => $payableAmount,
                'paymnt_card' => '',
                'paymnt_name' => '',
                'paymnt_date' => now(),
                'store_id' => $storeId,
                'company_id' => 4,
                'paymnt_date_char' => now()->toDateString(),
                'all_update' => 0,
                'posted_date' => now()->toDateString(),
                'customer' => $customerId,
                'old_invoice' => $invoiceNumber,
            ]);
        }

        // Redeemed loyalty per item
        $redeemedLoyaltyPointsPerItem = 0;
        $redeemedQuantity = count($cartArray);

        if ($redeemedQuantity > 0) {
            $redeemedLoyaltyPointsPerItem = (int)$redeemedAmount / $redeemedQuantity;
        }

        $productsArray = [];

        if (!empty($cartArray)) {
            foreach ($cartArray as $value) {

           //     $product = Product::select('product_id', 'product_no', 'title', 'sub_cat_id', 'brand_id', 'Adv_flag', 'tax', 'barcode', 'Ref_no', 'func_flag')->where('product_no', $value['product_no'])->first();
                $product = Product::select('product_id', 'product_no', 'title', 'sub_cat_id', 'brand_id', 'Adv_flag', 'tax', 'barcode', 'Ref_no', 'func_flag')->where('product_id', $value['product_id'])->first();
                $productsArray[] = $product->title;

                $brand = Brand::select('name', 'ax_id', 'distributor', 'supplier_id')->where('id', $product->brand_id)->first();
                $axe = DB::table('axes_table')->select('ax_name')->where('ax_id', $brand->ax_id)->first();
                $subCategory = SubCategory::select('sub_cat_name', 'cat_id', 'Old_value')->where('sub_cat_id', $product->sub_cat_id)->first();
                $categoryId = $subCategory->cat_id;
                $oldCategory = $subCategory->Old_value;
                $category = Category::select('cat_name', 'main_cat_id')->where('cat_id', $subCategory->cat_id)->first();
                $mainCategoryId = $category->main_cat_id;
                $mainCategory = MainCategory::select('main_cat_name')->where('main_cat_id', $category->main_cat_id)->first();

                $categoryName = $mainCategory->main_cat_name . '->' . $category->cat_name . '->' . $subCategory->sub_cat_name;

                $adv = $product->Adv_flag == 6 ? 1 : 0;
                $taxPrice = $product->tax * 1 / 100;
                $wholesalePrice = number_format($value['product_price'] * (1 - $value['product_disc_perc'] / 100) / (1 + ($product->tax * 1 / 100)), 3, '.', '');

                $accNo = '4394';
                $accId = '4394';

                $location = DB::table('sister_account')->select('location')->where('store_id', $storeId)->where('sister', 'N')->first();
                $inventory = DB::table('test_inventory_table')->select('id')->where('old_location', 'like', '%' . $location->location . '%')->first();
                $inventoryId = $inventory->id;

                $salesman = DB::table('hrms_employees as e')
                    ->join('hrms_employee_general_info as i', 'e.id', '=', 'i.employee_id')
                    ->select(
                        'e.id',
                        'e.code',
                        DB::raw("CONCAT_WS(' ', i.eng_first_name, i.eng_middle_name, i.eng_last_name, i.eng_family_name) as name")
                    )
                    ->where('e.id', 1207)
                    ->first();
                $salesPerson = $salesman->name;
                $salesPersonId = $salesman->id;

                $salesType = 'web';

                // Create and save sale item pos
                $this->createSalePos($product, $oldCategory, $categoryName, $categoryId, $mainCategoryId, $adv, $value, $storeId, $invoiceNumber, $brand, $axe, $taxPrice, $wholesalePrice, $accNo, $accId, $inventoryId, $salesPerson, $salesPersonId, $customerName, $customerId, $customerPhone, $paymentType, $paymentTypeNote, $deliveryDateTime, $shipCharge, $salesType, $redeemedLoyaltyPointsPerItem);

                // Create and save sale item online
                $this->createSaleOnline($product, $oldCategory, $categoryName, $categoryId, $mainCategoryId, $adv, $value, $storeId, $invoiceNumber, $brand, $axe, $taxPrice, $wholesalePrice, $accNo, $accId, $inventoryId, $salesPerson, $salesPersonId, $customerName, $customerId, $customerPhone, $paymentType, $paymentTypeNote, $deliveryDateTime, $shipCharge, $salesType, $redeemedLoyaltyPointsPerItem);


                $gainedLoyalty += (float)$value['product_loyalty'];
            }
        }

        // add sample(free) quantity according cart total in delivery table
        $sample = 0;
        $sampleRecord = Sample::where('domain', 'like', '%' . $domainId . '%')
            ->where('min_purchase', '<=', $cartTotal)
            ->where('max_purchase', '>=', $cartTotal)
            ->first();
        if ($sampleRecord) {
            $sample = (int)$sampleRecord->sample_qty;
        }

        // Create and save delivery invoice
        $this->createDeliveryInvoice($invoiceNumber, $customerPhone, $customerName, $payableAmount, $deliveryDateTime, $deliveryPhone, $giftWrap, $giftBox, $giftMessage, $pickFromStoreId, $sample, $referralUrl, $montypayPaymentId);

        $customerLoyaltyPoints = Helper::loyaltyPoints();

        if ($redeemedLoyalty > 0) {
            $preBalance = UserLoyaltyPoint::where('customer_id', $customerId)
                ->latest('id')
                ->value('post_balance');
            $preBalance = $preBalance ?? 0;
            $postBalance = $preBalance - $redeemedLoyalty;

            // Insert new record for redeeming loyalty
            $newCustomerPoint = new UserLoyaltyPoint();
            $newCustomerPoint->customer_id = $customerId;
            $newCustomerPoint->point_in = 0;
            $newCustomerPoint->point_out = $redeemedLoyalty;
            $newCustomerPoint->point_type = 'redeem';
            $newCustomerPoint->note = $invoiceNumber;
            $newCustomerPoint->location = 'web';
            $newCustomerPoint->pre_balance = $preBalance;
            $newCustomerPoint->post_balance = $postBalance;
            $newCustomerPoint->added_date = Carbon::now();
            $newCustomerPoint->save();

            // Deduct redeemed points from soonest expiring points
            $redeemedPoints = $redeemedLoyalty;
            $noteArr = [];
            $note1Arr = [];

            $customerPoints = UserLoyaltyPoint::where('valid_date', '>=', $currentDate)
                ->where('valid_points', '>', 0)
                ->where('customer_id', $customerId)
                ->orderBy('valid_date')
                ->get();

            foreach ($customerPoints as $point) {
                if ($redeemedPoints > 0) {
                    if ($point->valid_points <= $redeemedPoints) {
                        $redeemedPoints -= $point->valid_points;
                        $point->valid_points = 0;
                    } else {
                        $point->valid_points -= $redeemedPoints;
                        $redeemedPoints = 0;
                    }
                    $point->save();
                }
            }

            $noteStr = implode(',', $noteArr);
            $note1Str = implode(',', $note1Arr);
            UserLoyaltyPoint::where('id', $newCustomerPoint->id)
                ->update(['note' => $noteStr, 'note1' => $note1Str]);
        }

        // Update customer points for gained loyalty
        if ($gainedLoyalty > 0) {
            // Fetching reward details
            $reward = Reward::where('slug', 'purchase')
                ->where('tier_id', $customerTier)
                ->where('status', '=', '1')
                ->first();

            $pointExpiry = $reward ? $reward->expiry : 365;
            $pointType = $reward ? $reward->slug : 'purchase';
            $pointValue = $reward ? (float)$reward->points : 0;

            $gainedPoints = $gainedLoyalty * $pointValue;
            $preBalance = UserLoyaltyPoint::where('customer_id', $customerId)
                ->latest('id')
                ->value('post_balance');

            $preBalance = $preBalance ?? 0;
            $postBalance = $preBalance + $gainedPoints;

            // Insert new record for gained loyalty
            $customerPoints = new UserLoyaltyPoint();
            $customerPoints->customer_id = $customerId;
            $customerPoints->point_in = $gainedPoints;
            $customerPoints->point_out = 0;
            $customerPoints->point_type = $pointType;
            $customerPoints->invoice_no = $invoiceNumber;
            $customerPoints->note = $invoiceNumber;
            $customerPoints->location = 'web';
            $customerPoints->pre_balance = $preBalance;
            $customerPoints->post_balance = $postBalance;
            $customerPoints->added_date = Carbon::now();
            $customerPoints->valid_points = $gainedPoints;
            $customerPoints->valid_date = Carbon::now()->addDays($pointExpiry);
            $customerPoints->valid_days = $pointExpiry;
            $customerPoints->save();
        }

        // Calculate loyalty points
        $loyaltyPoints = ((float)$customerLoyaltyPoints - (float)$redeemedLoyalty) + (float)$gainedLoyalty;

        // Get first purchase date
        $firstPurchaseDate = $currentDate;
        $firstPurchaseDateQuery = User::where('customer_id', $customerId)->value('first_purchase_date');
        if ($firstPurchaseDateQuery != '0000-00-00') {
            $firstPurchaseDate = $firstPurchaseDateQuery;
        }

        // Update customer table
        User::where('customer_id', $customerId)
            ->update([
                'loyalty_point' => $loyaltyPoints,
                'date_added' => $currentDate,
                'first_purchase_date' => $firstPurchaseDate,
                'last_purchase_date' => $currentDate
            ]);

        // Set user's loyalty points in session
        //session(['user_point' => $loyaltyPoints]);
        //Helper::sendNotification($customerPhoneCode, $customerPhone, );
        $ikascoUrl = config('app.ikasco_url');
        $baseUrl = url('/');

        $phoneNumber = $customerPhoneCode . $customerPhone;
        $message = "Thank you for shopping at gifts center. Click the link to view your order: " . $baseUrl . "/orders/" . $customerId;
        Helper::sendSMS($phoneNumber, $message);

        if ($customerEmail) {
            $mailSubject = "Gifts-Center Order: " . $invoiceNumber;
            $mailContent = '<body style="background:#f1f1f1;">
<div style="max-width:600px; height:auto; margin:45px auto 0;">
  <table style="border-collapse:collapse; background:#fff; box-shadow:0 0 7px #999; text-align:center;" width="100%" border="0">
    <tbody><tr style="background:#001a72;">
      <td colspan="2" style="padding:15px; text-align:center;"><a href="#"><img src="' . $ikascoUrl . 'images/emaillogo.png" alt=""></a></td>
    </tr>
    <tr>
      <td colspan="2" style="padding:50px 20px 10px 20px;"><img style="" src="' . $ikascoUrl . 'images/tickemail.png" alt="">
        <p style="font:normal 17px/22px Arial, Helvetica, sans-serif; color:#262626; margin:20px 0 10px 0;">Thank you for your order.</p></td>
    </tr>
    <tr>
        <td colspan="2" style="text-align:left; padding:20px;"><p style="color: #002855;font: normal 17px/22px Arial, Helvetica, sans-serif;">Hello ' . $customerName . ',</p></td>
    </tr>
    <tr>
        <td colspan="2" style="text-align:left; padding:20px;"><h2 style="color: #ff003c;font: bold 19px/22px Arial, Helvetica, sans-serif;">Order Details</h2></td>
    </tr>
    <tr>
      <td colspan="2" style="text-align:left; padding:20px;">
        <p style="font:normal 17px/22px Arial, Helvetica, sans-serif; color:#444; margin:0 0 10px 0;">View your order details <a style="color:#3c8dbc;" href="' . $ikascoUrl . 'customer-invoice/' . $invoiceNumber . '">click here</a></p>
        <p style="font:normal 17px/22px Arial, Helvetica, sans-serif; color:#444; margin:0 0 10px 0;">View your exchange receipt <a style="color:#3c8dbc;" href="h' . $ikascoUrl . 'customer-exchange-invoice/' . $invoiceNumber . '">click here</a></p>
        <p style="font:normal 17px/22px Arial, Helvetica, sans-serif; color:#444; margin:0 0 10px 0;">View your order history <a style="color:#3c8dbc;" href="' . $baseUrl . '/orders/' . $customerId . '">click here</a></p>    
       </td>
    </tr>
    <tr style="background:#001a72; padding:7px 0;">
      <td style="float:left; padding:7px 7px 7px 30px; text-align:left;"><p style="font:normal 12px/18px Arial, Helvetica, sans-serif; color:#fff; margin:0;">Copyright @ Gifts Center 2017.<br>
          All rights reserved.</p></td>
      <td style="width:50%; padding:7px 30px 7px 7px; text-align:left;"><p style="font:normal 12px/18px Arial, Helvetica, sans-serif; color:#fff; margin:0;">Contact</p>
        <p style="font:normal 12px/18px Arial, Helvetica, sans-serif; color:#fff; margin:0;"><a style="color:#fff;" href="mailto:info@gifts-center.com">info@gifts-center.com</a></p>
        <p style="font:normal 12px/18px Arial, Helvetica, sans-serif; color:#fff; margin:15px 0 0 0;"><a style="color:#fff; text-decoration:none;" href="tel:+962%207%2098889966">+962 7 98889966 ext 504</a></p></td>
    </tr>
  </tbody></table>
</div>
</body>';

            $systemEmails = DB::table('emailmanagement')->where('domain_id', $domainId)->get(['email', 'name'])->toArray();

            Helper::sendMail($customerEmail, $customerName, $mailSubject, $mailContent, $systemEmails);
        }

        if (!empty($evoucherArray)) {
            foreach ($evoucherArray as $voucher) {
                if (!empty($voucher["send_det"])) {
                    $sendDetails = $voucher["send_det"];

                    if ($sendDetails["send_on"] == "later") {
                        $sendDetails["send_message"] = trim($sendDetails["send_message"]);

                        // Insert into gc_voucher table
                        EVoucher::create([
                            'send_date' => $sendDetails["send_date"],
                            'send_details' => $voucher["send_det"],
                            'voucher' => $voucher['code']
                        ]);
                    } else {
                        if ($sendDetails["send_medium"] == "sms") {

                            // Send SMS
                            $longUrl = config('app.ikasco_url') . "e-voucher/" . $voucher['code'];
                            $message = ($sendDetails["send_type"] == "recipient") ?
                                $sendDetails['send_from'] . " gifted you e-Gift Card. Click the link to view your e-Gift Card: " . $longUrl :
                                "Click the link to view your e-gift card: " . $longUrl;


                            $phone = $sendDetails["send_phone"];
                            Helper::sendSMS($phone, $message);
                        } else {
                            // Send email
                            $voucherData = Voucher::where('code', $voucher['code'])->firstOrFail();
                            $productRecord = Product::where('barcode', $voucherData->barcode)
                                ->leftJoin('family_tbl as f', 'product_table.linepr', '=', 'f.family_id')
                                ->leftJoin('family_pic_tbl as fp', 'f.family_id', '=', 'fp.family_id')
                                ->selectRaw('IF(product_table.type_flag = 2, CONCAT("https://ikasco.com/", product_table.photo2), CONCAT("https://ikasco.com/familypic/", fp.family_pic)) AS family_pic, product_table.title AS title')
                                ->first();
                            if ($sendDetails["send_type"] == "recipient") {
                                $voucherEmail = $sendDetails["send_email"];
                                $voucherName = $sendDetails["send_to"];
                                $recipientHtml = '<td colspan="2" style="text-align:left; padding:20px;">
                                        <p style="color: #002855;font: normal 17px/22px Arial, Helvetica, sans-serif;">Hello ' . $voucherName . ',</p>
                                        <p style="font:normal 17px/22px Arial, Helvetica, sans-serif; color:#444; margin:0 0 10px 0;">' . $sendDetails['send_from'] . ' gifted you e-Gift Card.</p>            
                                    </td>';
                            } else {
                                $voucherEmail = $customerEmail;
                                $voucherName = $customerName;
                                $recipientHtml = '<td colspan="2" style="text-align:left; padding:20px;">
                                        <p style="color: #002855;font: normal 17px/22px Arial, Helvetica, sans-serif;">Hello ' . $voucherName . ',</p>
                                    </td>';
                            }

                            $voucherMailSubject = "Gifts-Center e-Gift Card";
                            $voucherMailContent = '<body style="background:#f1f1f1;">
                            <div style="max-width:600px; height:auto; margin:45px auto 0;">
                              <table style="border-collapse:collapse; background:#fff; box-shadow:0 0 7px #999; text-align:center;" width="100%" border="0">
                                <tbody><tr style="background:#001a72;">
                                  <td colspan="2" style="padding:15px; text-align:center;"><a href="#"><img src="' . $ikascoUrl . 'images/emaillogo.png" alt=""></a></td>
                                </tr>
                                <tr>' . $recipientHtml . '</tr>
                                <tr><td colspan="2" style="text-align:center; padding:10px;"><h2 style="color: #ff003c;font: bold 19px/22px Arial, Helvetica, sans-serif;">e-Gift Card</h2></td></tr>
                                <tr><td colspan="2" style="text-align:center; padding:10px;"><img src="' . $productRecord->family_pic . '" alt="' . $productRecord->title . '" title="' . $productRecord->title . '" width="350"></td></tr>
                                <tr>
                                  <td colspan="2" style="text-align:left; padding:20px;">
                                    <p style="font:normal 17px/22px Arial, Helvetica, sans-serif; color:#444; margin:0 0 10px 0;">' . $sendDetails['send_message'] . '</p>   
                                    <p style="font:normal 17px/22px Arial, Helvetica, sans-serif; color:#444; margin:0 0 10px 0;">View your gift card <a style="color:#3c8dbc;" href="' . $ikascoUrl . 'e-voucher/' . $voucherData->code . '">click here</a></p>
                                   </td>
                                </tr>
                                <tr style="background:#001a72; padding:7px 0;">
                                  <td style="float:left; padding:7px 7px 7px 30px; text-align:left;"><p style="font:normal 12px/18px Arial, Helvetica, sans-serif; color:#fff; margin:0;">Copyright @ Gifts Center 2017.<br>
                                      All rights reserved.</p></td>
                                  <td style="width:50%; padding:7px 30px 7px 7px; text-align:left;"><p style="font:normal 12px/18px Arial, Helvetica, sans-serif; color:#fff; margin:0;">Contact</p>
                                    <p style="font:normal 12px/18px Arial, Helvetica, sans-serif; color:#fff; margin:0;"><a style="color:#fff;" href="mailto:info@gifts-center.com">info@gifts-center.com</a></p>
                                    <p style="font:normal 12px/18px Arial, Helvetica, sans-serif; color:#fff; margin:15px 0 0 0;"><a style="color:#fff; text-decoration:none;" href="tel:+962%207%2098889966">+962 7 98889966 ext 504</a></p></td>
                                </tr>
                              </tbody></table>
                            </div>
                            </body>';

                            Helper::sendMail($voucherEmail, $voucherName, $voucherMailSubject, $voucherMailContent);
                        }
                    }
                }
            }
        }


        // Check if the purchase exists
        $purchase = Purchase::where('invoice_no', $invoiceNumber)
            ->where('customer_no', $customerId)
            ->first();

        // If the purchase does not exist, create a new one
        if (!$purchase) {
            $purchase = new Purchase();
            $purchase->unique_no = Str::uuid();
            $purchase->invoice_no = $invoiceNumber;
            $purchase->customer_no = $customerId;
            $purchase->store_no = '50';
            $purchase->ordered_date = now();
            $purchase->updated_date = now();
            $purchase->save();
        }

        /**
         * Clearing customer's order related details:
         * - Cart
         * - Total order amount
         * - Location (pickup from store)
         * - Coupon's free delivery (promo code)
         *
         * @author Sushobhon Sahoo
         */
        $cartRecords = Cart::where('customer_id', $customerId)
            ->where('is_guest', $isGuest)
            ->get();

        // Delete the fetched cart records
        foreach ($cartRecords as $cartRecord) {
            $cartRecord->delete();
        }

        if ($customerId) {
            $totalPurchase = SaleOnline::where('customer_no', $customerId)
                ->whereBetween('sale_date', [$customerTierUpdatedDate, now()->toDateString()])
                ->sum(DB::raw('wholesale_price * (1 + tax / 100) * qty'));

            if ($totalPurchase > 0 && empty($customerOrganization)) {
                $tierRecord = Tier::where('min_purchase', '<=', $totalPurchase)
                    ->where('max_purchase', '>=', $totalPurchase)
                    ->where('status', 1)
                    ->first();

                if ($tierRecord && (int)$tierRecord->id >= (int)$customerTier) {
                    User::where('customer_id', $customer)
                        ->update(['tier' => $tierRecord->id]);
                }
            }
        }

        Session::forget([
            'coupon',
            'voucher',
            'gc_billing',
            'gc_shipping',
            'payment_type',
            'gift_wrap',
            'gift_box',
            'gift_message',
            'order',
        ]);

        if (session()->has('epay_session')) {
            Session::forget('epay_session');
        }

        if (session()->has('is_guest')) {
            Session::forget(['is_guest']);
        }


        // Fetching rewards
        $ratingReward = 0;
        $rewardRecord = Reward::where('slug', 'rating')
            ->where('tier_id', $customerTier)
            ->where('status', 1)
            ->first();

        if ($rewardRecord) {
            $ratingReward = (int)$rewardRecord->points;
        }

        $shareReward = 0;
        $srewardRecord = Reward::where('slug', 'share-your-purchase')
            ->where('tier_id', $customerTier)
            ->where('status', 1)
            ->first();

        if ($srewardRecord) {
            $shareReward = (int)$srewardRecord->points;
        }

        $referReward = 0;
        $frewardRecord = Reward::where('slug', 'refer-a-friend')
            ->where('tier_id', $customerTier)
            ->where('status', 1)
            ->first();

        if ($frewardRecord) {
            $referReward = (int)$frewardRecord->points;
        }

        // Fetching survey sources
        $surveySources = DB::table('gc_survey_sources')->orderBy('id', 'asc')->get();


        //og-details
        $ogUrl = url('/');
        $ogTitle = 'I just bought:' . implode(',', $productsArray);
        $ogDescription = 'Discover the latest in beauty & Cosmetics at Gifts Center. Explore our selection of fragrances, makeup, skin care, watches & jewelry.';
        $ogImage = url('/') . 'images/logo.svg';
        $payableAmountUSD = $payableAmount * 0.7;
        return view('frontend.pages.order-placed', [
            'ogUrl' => $ogUrl,
            'ogTitle' => $ogTitle,
            'ogDescription' => $ogDescription,
            'ogImage' => $ogImage,
            'surveySources' => $surveySources,
            'ratingReward' => $ratingReward,
            'shareReward' => $shareReward,
            'referReward' => $referReward,
            'invoiceNumber' => $invoiceNumber,
            'customerId' => $customerId,
            'isGuest' => $isGuest,
            'payableAmount' => $payableAmount,
            'payableAmountUSD' => $payableAmountUSD,
            'cartArray' => $cartArray,
            'user' => $user,
        ]);
    }

    public function testOrderPlaced(Request $request)
    {
        return view('frontend.pages.test-order-placed');
    }

    private function createSalePos($product, $oldCategory, $categoryName, $categoryId, $mainCategoryId, $adv, $value, $storeId, $invoiceNumber, $brand, $axe, $taxPrice, $wholesalePrice, $accNo, $accId, $inventoryId, $salesPerson, $salesPersonId, $customerName, $customerId, $customerPhone, $paymentType, $paymentTypeNote, $deliveryDateTime, $shipCharge, $salesType, $redeemedLoyaltyPointsPerItem): void
    {
        $salesTableItemPos = new SalePos();

        // Set attributes
        $salesTableItemPos->product_id = $product->product_id;
        $salesTableItemPos->product_no = $product->product_no;
        $salesTableItemPos->title = $product->title;
        $salesTableItemPos->category = $categoryName;
        $salesTableItemPos->tax = $product->tax;
        $salesTableItemPos->adv = $adv;
        $salesTableItemPos->qty = $value['product_qty'];
        $salesTableItemPos->rec_qty = $value['product_qty'];
        $salesTableItemPos->retail_price = $value['product_price'];
        $salesTableItemPos->wholesale_price = $wholesalePrice;
        $salesTableItemPos->sales_person = $salesPerson;
        $salesTableItemPos->sales_person_id = $salesPersonId;
        $salesTableItemPos->store_id = $storeId;
        $salesTableItemPos->company_id = 4;
        $salesTableItemPos->acc_no = $accNo;
        $salesTableItemPos->acc_id = $accId;
        $salesTableItemPos->location = $inventoryId;
        $salesTableItemPos->post = 1;
        $salesTableItemPos->posted_date = now()->toDateString();
        $salesTableItemPos->brand = $brand->name;
        $salesTableItemPos->supplier = '';
        $salesTableItemPos->brand_code = '';
        $salesTableItemPos->supp_code = '';
        $salesTableItemPos->brand_id = $product->brand_id;
        $salesTableItemPos->supplier_id = $brand->supplier_id;
        $salesTableItemPos->sub_store = '';
        $salesTableItemPos->barcode = $product->barcode;
        $salesTableItemPos->ref_no = $product->Ref_no;
        $salesTableItemPos->transaction_no = $invoiceNumber;
        $salesTableItemPos->sale_date_time = now();
        $salesTableItemPos->sale_date = now()->toDateString();
        $salesTableItemPos->main_cat_id = $mainCategoryId;
        $salesTableItemPos->cat_id = $categoryId;
        $salesTableItemPos->sub_cat_id = $product->sub_cat_id;
        $salesTableItemPos->func_flag = $product->func_flag;
        $salesTableItemPos->distributor = $brand->distributor;
        $salesTableItemPos->division = $axe->ax_name;
        $salesTableItemPos->tax_price = $taxPrice;
        $salesTableItemPos->old_invoice = $invoiceNumber;
        $salesTableItemPos->customer_name = $customerName;
        $salesTableItemPos->customer_no = $customerId;
        $salesTableItemPos->phone = $customerPhone;
        $salesTableItemPos->Webform = 1;
        $salesTableItemPos->sub_invoice = $invoiceNumber;
        $salesTableItemPos->delphone = $customerPhone;
        $salesTableItemPos->wraps = '';
        $salesTableItemPos->old_category = $oldCategory;
        $salesTableItemPos->loyalty = $value['product_loyalty'];
        $salesTableItemPos->discount = $value['product_disc_perc'];
        $salesTableItemPos->discount_price = $value['product_disc_price'];
        $salesTableItemPos->point_discount = 0;
        $salesTableItemPos->point_discount_price = 0;
        $salesTableItemPos->payment_type = $paymentType;
        $salesTableItemPos->note = $paymentTypeNote;
        $salesTableItemPos->customer_date = $deliveryDateTime;
        $salesTableItemPos->sales_type = $salesType;
        $salesTableItemPos->shipping_charge = $shipCharge;
        $salesTableItemPos->coupon_discount = $value['coupon_discount'];
        $salesTableItemPos->coupon_code = $value['applied_coupon'];
        $salesTableItemPos->redeemed_amount = $redeemedLoyaltyPointsPerItem;
        $salesTableItemPos->status = 0;
        $salesTableItemPos->patch = $value['product_patch'];
        $salesTableItemPos->bundle = $value['is_bundle'];

        // Save the record
        $salesTableItemPos->save();
    }

    private function createSaleOnline($product, $oldCategory, $categoryName, $categoryId, $mainCategoryId, $adv, $value, $storeId, $invoiceNumber, $brand, $axe, $taxPrice, $wholesalePrice, $accNo, $accId, $inventoryId, $salesPerson, $salesPersonId, $customerName, $customerId, $customerPhone, $paymentType, $paymentTypeNote, $deliveryDateTime, $shipCharge, $salesType, $redeemedLoyaltyPointsPerItem): void
    {
        $salesTableItemOnline = new SaleOnline();

        // Set each property individually
        $salesTableItemOnline->product_id = $product->product_id;
        $salesTableItemOnline->product_no = $product->product_no;
        $salesTableItemOnline->title = $product->title;
        $salesTableItemOnline->category = $categoryName;
        $salesTableItemOnline->tax = $product->tax;
        $salesTableItemOnline->adv = $adv;
        $salesTableItemOnline->qty = $value['product_qty'];
        $salesTableItemOnline->rec_qty = $value['product_qty'];
        $salesTableItemOnline->retail_price = $value['product_price'];
        $salesTableItemOnline->wholesale_price = $wholesalePrice;
        $salesTableItemOnline->sales_person = $salesPerson;
        $salesTableItemOnline->sales_person_id = $salesPersonId;
        $salesTableItemOnline->store_id = $storeId;
        $salesTableItemOnline->company_id = 4;
        $salesTableItemOnline->acc_no = $accNo;
        $salesTableItemOnline->acc_id = $accId;
        $salesTableItemOnline->location = $inventoryId;
        $salesTableItemOnline->post = 1;
        $salesTableItemOnline->posted_date = now()->toDateString();
        $salesTableItemOnline->brand = $brand->name;
        $salesTableItemOnline->supplier = '';
        $salesTableItemOnline->brand_code = '';
        $salesTableItemOnline->supp_code = '';
        $salesTableItemOnline->brand_id = $product->brand_id;
        $salesTableItemOnline->supplier_id = $brand->supplier_id;
        $salesTableItemOnline->sub_store = '';
        $salesTableItemOnline->barcode = $product->barcode;
        $salesTableItemOnline->ref_no = $product->Ref_no;
        $salesTableItemOnline->transaction_no = $invoiceNumber;
        $salesTableItemOnline->sale_date_time = now();
        $salesTableItemOnline->sale_date = now()->toDateString();
        $salesTableItemOnline->main_cat_id = $mainCategoryId;
        $salesTableItemOnline->cat_id = $categoryId;
        $salesTableItemOnline->sub_cat_id = $product->sub_cat_id;
        $salesTableItemOnline->func_flag = $product->func_flag;
        $salesTableItemOnline->distributor = $brand->distributor;
        $salesTableItemOnline->division = $axe->ax_name;
        $salesTableItemOnline->tax_price = $taxPrice;
        $salesTableItemOnline->old_invoice = $invoiceNumber;
        $salesTableItemOnline->customer_name = $customerName;
        $salesTableItemOnline->customer_no = $customerId;
        $salesTableItemOnline->phone = $customerPhone;
        $salesTableItemOnline->Webform = 1;
        $salesTableItemOnline->sub_invoice = $invoiceNumber;
        $salesTableItemOnline->delphone = $customerPhone;
        $salesTableItemOnline->wraps = '';
        $salesTableItemOnline->old_category = $oldCategory;
        $salesTableItemOnline->loyalty = $value['product_loyalty'];
        $salesTableItemOnline->discount = $value['product_disc_perc'];
        $salesTableItemOnline->discount_price = $value['product_disc_price'];
        $salesTableItemOnline->point_discount = 0;
        $salesTableItemOnline->point_discount_price = 0;
        $salesTableItemOnline->payment_type = $paymentType;
        $salesTableItemOnline->note = $paymentTypeNote;
        $salesTableItemOnline->customer_date = $deliveryDateTime;
        $salesTableItemOnline->sales_type = $salesType;
        $salesTableItemOnline->shipping_charge = $shipCharge;
        $salesTableItemOnline->coupon_discount = $value['coupon_discount'];
        $salesTableItemOnline->coupon_code = $value['applied_coupon'];
        $salesTableItemOnline->redeemed_amount = $redeemedLoyaltyPointsPerItem;
        $salesTableItemOnline->status = 0;
        $salesTableItemOnline->posted_invoice = 0;
        $salesTableItemOnline->patch = $value['product_patch'];
        $salesTableItemOnline->bundle = $value['is_bundle'];

// Save the record
        $salesTableItemOnline->save();
    }

    private function createDeliveryInvoice($invoiceNumber, $customerPhone, $customerName, $payableAmount, $deliveryDateTime, $deliveryPhone, $giftWrap, $giftBox, $giftMessage, $pickFromStoreId, $sample, $referralUrl, $montypayPaymentId = null): void
    {
        $deliveryAddress = new DeliveryInvoice();
        $deliveryAddress->address = session('gc_shipping.ship_address');
        $deliveryAddress->street_number = session('gc_shipping.ship_street_number');
        $deliveryAddress->street = session('gc_shipping.ship_street');
        $deliveryAddress->city = session('gc_shipping.ship_city');
        $deliveryAddress->state = session('gc_shipping.ship_state');
        $deliveryAddress->zip_code = session('gc_shipping.ship_zip_code');
        $deliveryAddress->country_name = session('gc_shipping.ship_country');
        $deliveryAddress->invoice_no = $invoiceNumber;
        $deliveryAddress->phone = $customerPhone;
        $deliveryAddress->customer_name = $customerName;
        $deliveryAddress->delphone = $customerPhone;
        $deliveryAddress->total_amount = $payableAmount;
        $deliveryAddress->delsms = 1;
        $deliveryAddress->confsms = 1;
        $deliveryAddress->bill_address = session('gc_billing.bill_address');
        $deliveryAddress->bill_street_number = session('gc_billing.bill_street_number');
        $deliveryAddress->bill_street = session('gc_billing.bill_street');
        $deliveryAddress->bill_city = session('gc_billing.bill_city');
        $deliveryAddress->bill_state = session('gc_billing.bill_state');
        $deliveryAddress->bill_zip_code = session('gc_billing.bill_zip_code');
        $deliveryAddress->bill_country = session('gc_billing.bill_country');
        $deliveryAddress->delivery_date = $deliveryDateTime;
        $deliveryAddress->delivery_phone = $deliveryPhone;
        $deliveryAddress->gift_wrap = $giftWrap;
        $deliveryAddress->gift_box = $giftBox;
        $deliveryAddress->gift_message = $giftMessage;
        $deliveryAddress->pickfrm_store = $pickFromStoreId;
        $deliveryAddress->sample = $sample;
        $deliveryAddress->referral_url = $referralUrl;
        $deliveryAddress->delivery_status = 'Purchased';
        $deliveryAddress->referral_trnsctn_no = $montypayPaymentId;

        $deliveryAddress->save();
    }

    public function orderPlacedTest(Request $request)
    {
        $ogUrl = url('/');
        $ogTitle = 'I just bought products from Giftscenter';
        $ogDescription = 'Discover the latest in beauty & Cosmetics at Gifts Center. Explore our selection of fragrances, makeup, skin care, watches & jewelry.';
        $ogImage = url('/') . 'images/logo.svg';

        return view('frontend.pages.order-placed', [
            'ogUrl' => $ogUrl,
            'ogTitle' => $ogTitle,
            'ogDescription' => $ogDescription,
            'ogImage' => $ogImage,
            'surveySources' => '',
            'ratingReward' => '',
            'shareReward' => '',
            'referReward' => '',
            'invoiceNumber' => '',
            'customerId' => '',
            'isGuest' => '',
            'payableAmount' => '100',
        ]);
    }

    public function addSurvey(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'source' => 'required|integer',
            'customerId' => 'required|string',
        ]);

        try {
            $source = $request->input('source');
            $customerId = $request->input('customerId');

            // Check if survey record already exists for the user
            $survey = Survey::where('customer_id', $customerId)->first();

            if ($survey) {
                // Update existing survey record
                $survey->source = $source;
                $survey->save();
            } else {
                // Create new survey record
                Survey::create([
                    'customer_id' => $customerId,
                    'source' => $source,
                ]);
            }

            // Return success response with data
            return response()->json([
                'result' => true,
                'message' => 'Survey data has been successfully updated.',
                'data' => $survey // Return updated/created survey data if needed
            ]);
        } catch (\Exception $e) {
            // Return error response
            return response()->json([
                'result' => false,
                'message' => 'Failed to update survey data. Please try again later.',
                'data' => null
            ], 500);
        }
    }

    public function orderReview(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'rating' => 'required',
            'review' => 'required',
            'customerId' => 'required',
            'invoiceNumber' => 'required',
        ]);

        try {
            $rating = $request->input('rating');
            $review = $request->input('review');
            $customerId = $request->input('customerId');
            $invoiceNumber = $request->input('invoiceNumber');

            // Check if all required data is provided
            if (empty($rating) || empty($review) || empty($customerId) || empty($invoiceNumber)) {
                throw new \InvalidArgumentException('Incomplete data provided.');
            }

            // Check if the purchase exists
            $purchase = Purchase::where('invoice_no', $invoiceNumber)
                ->where('customer_no', $customerId)
                ->first();

            if (!$purchase) {
                throw new \Exception('Purchase not found.');
            }

            // Update purchase with rating and review
            $purchase->rating = $rating;
            $purchase->review = $review;
            $purchase->is_rated = 1;
            $purchase->updated_date = now();
            $purchase->save();

            $customer = User::where('customer_id', $customerId)->first();
            $customerTier = $customer->tier;

            // Fetch reward details for order review
            $reward = Reward::where('slug', 'rating')
                ->where('tier_id', $customerTier)
                ->where('status', '=', '1')
                ->first();

            // If reward found, add points to the customer
            if ($reward) {

                $loyaltyPoints = $customer->loyalty_point + $reward->points;
                $customer->loyalty_point = $loyaltyPoints;
                $customer->date_added = now();
                $customer->save();

                // Add point transaction
                $customerPoint = new UserLoyaltyPoint();
                $customerPoint->customer_id = $customerId;
                $customerPoint->point_in = $reward->points;
                $customerPoint->point_out = 0;
                $customerPoint->point_type = $reward->slug;
                $customerPoint->pre_balance = $customer->loyalty_point - $reward->points;
                $customerPoint->post_balance = $customer->loyalty_point;
                $customerPoint->added_date = now();
                $customerPoint->valid_points = $reward->points;
                $customerPoint->valid_date = now()->addDays($reward->expiry);
                $customerPoint->valid_days = $reward->expiry;
                $customerPoint->save();
            }

            return response()->json([
                'result' => true,
                'message' => 'Rating and review have been successfully added.',
                'data' => [],
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'result' => false,
                'message' => 'Failed to update rating and review due to a database error.',
                'data' => [],
            ], 500);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'result' => false,
                'message' => 'Incomplete data provided.',
                'data' => [],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
                'data' => [],
            ], 500);
        }
    }

    public function orderShare(Request $request)
    {
        $request->validate([
            'customerId' => 'required',
            'invoiceNumber' => 'required',
        ]);

        try {
            $customerId = $request->input('customerId');
            $invoiceNumber = $request->input('invoiceNumber');

            // Check if all required data is provided
            if (empty($customerId) || empty($invoiceNumber)) {
                throw new \InvalidArgumentException('Incomplete data provided.');
            }

            // Check if the purchase exists
            $purchase = Purchase::where('invoice_no', $invoiceNumber)
                ->where('customer_no', $customerId)
                ->first();

            if (!$purchase) {
                throw new \Exception('Purchase not found.');
            }

            // Update purchase with is_shared flag
            $purchase->is_shared = true;
            $purchase->updated_date = now();
            $purchase->save();

            // Fetch customer details
            $customer = Customer::select('loyalty_point', 'tier')->where('customer_id', $customerId);
            $customerTier = $customer->tier;
            $customerLoyaltyPoints = $customer->loyalty_point;

            // Fetch reward details for sharing purchase
            $reward = Reward::where('slug', 'share-your-purchase')
                ->where('tier_id', $customerTier)
                ->where('status', true)
                ->first();

            // If reward found, add points to the customer
            if ($reward) {
                // Calculate loyalty points and update customer
                $loyaltyPoints = $customerLoyaltyPoints + $reward->points;
                $customer->loyalty_point = $loyaltyPoints;
                $customer->date_added = now();
                $customer->save();

                // Add point transaction
                $customerPoint = new UserLoyaltyPoint();
                $customerPoint->customer_id = $customerId;
                $customerPoint->point_in = $reward->points;
                $customerPoint->point_out = 0;
                $customerPoint->point_type = $reward->slug;
                $customerPoint->pre_balance = $customerLoyaltyPoints;
                $customerPoint->post_balance = $loyaltyPoints;
                $customerPoint->added_date = now();
                $customerPoint->valid_points = $reward->points;
                $customerPoint->valid_date = now()->addDays($reward->expiry);
                $customerPoint->valid_days = $reward->expiry;
                $customerPoint->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Purchase has been successfully shared.',
                'data' => [],
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to share purchase due to a database error.',
                'data' => [],
            ], 500);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Incomplete data provided.',
                'data' => [],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
                'data' => [],
            ], 500);
        }
    }

    public function orderList()
    {
        if (!auth()->check()) {
            // If the user is not logged in, redirect to the home page
            return redirect()->route('home');
        }

        $aggridKeyRecord = DB::table('aggridkey_tbl')->select('keys')->first();
        $aggridKey = $aggridKeyRecord ? $aggridKeyRecord->keys : null;

        $customer = User::find(auth()->id());

        if (!$customer) {
            // Handle the case where the user is not found
            // You might want to redirect to a login page or show an error message
            // For now, let's assume a default value for locationNo and locationName
            return view('frontend.pages.order-history', [
                'loyaltyPoint' => 0,
                'locationNo' => 'Default Location No',
                'locationName' => 'Default Location Name',
                'aggridKey' => $aggridKey,
            ]);
        }

        $loyaltyPoint = 0;

        $pointRecord = DB::table('customer_points')
            ->where('customer_id', $customer->customer_id)
            ->orderByDesc('added_date')
            ->orderByDesc('id')
            ->first();

        if ($pointRecord) {
            $loyaltyPoint = (int)$pointRecord->post_balance;
        }

        return view('frontend.pages.order-history', [
            'loyaltyPoint' => $loyaltyPoint,
            'locationNo' => $customer->location_no,
            'locationName' => $customer->location_name,
            'aggridKey' => $aggridKey,
        ]);
    }

    public function orderListById($customerId)
    {
        $existingCustomer = User::where('customer_id', $customerId)->exists();
        if (!$existingCustomer) {
            return redirect()->route('home');
        }

        $aggridKeyRecord = DB::table('aggridkey_tbl')->select('keys')->first();
        $aggridKey = $aggridKeyRecord ? $aggridKeyRecord->keys : null;

        $customer = User::where('customer_id', $customerId)->select('customer_name', 'customer_id', 'phone', 'location_no', 'location_name')->first();

        if (!$customer) {
            // Handle the case where the user is not found
            // You might want to redirect to a login page or show an error message
            // For now, let's assume a default value for locationNo and locationName
            return view('frontend.pages.order-history', [
                'loyaltyPoint' => 0,
                'locationNo' => 'Default Location No',
                'locationName' => 'Default Location Name',
                'aggridKey' => $aggridKey,
            ]);
        }

        $loyaltyPoint = 0;

        $pointRecord = UserLoyaltyPoint::where('customer_id', $customer->customer_id)
            ->orderByDesc('added_date')
            ->orderByDesc('id')
            ->first('post_balance');


        if ($pointRecord) {
            $loyaltyPoint = (int)$pointRecord->post_balance;
        }

        return view('frontend.pages.orders', [
            'loyaltyPoint' => $loyaltyPoint,
            'customerId' => $customerId,
            'phone' => $customer->phone,
            'customerName' => $customer->customer_name,
            'locationNo' => $customer->location_no,
            'locationName' => $customer->location_name,
            'aggridKey' => $aggridKey,
        ]);
    }

    public function fetchOrders(Request $request)
    {

        $user = Auth::user();

        if ($user) {
            $customerId = $user->customer_id;
            $customerLocationNo = $user->location_no;
            $customerLocationName = $user->location_name;
        }

        $customerId = $request->input('customerId') ?? $customerId;
        if ($customerId) {
            $existingCustomer = User::select('customer_id', 'location_no', 'location_name')->where('customer_id', $customerId)->first();
            if (!$existingCustomer) {
                return redirect()->route('home');
            }
            $customerId = $existingCustomer->customer_id;
            $customerLocationNo = $existingCustomer->location_no;
            $customerLocationName = $existingCustomer->location_name;
        }

        $orders = DB::table('sales_table_item_online as s')
            ->select([
                's.id',
                's.sub_invoice',
                's.store_id',
                's.sale_date_time',
                's.payment_type',
                's.sales_type',
                DB::raw('ROUND(SUM((ABS(retail_price) - ABS(ABS(retail_price) * discount/100)) * qty),3) as amount'),
                DB::raw('SUM(qty) as quantity'),
                DB::raw('ROUND(SUM(loyalty),3) as point_in'),
                DB::raw('FORMAT(ROUND(SUM(point_discount_price + redeemed_amount)),3) as point_out'),
                'st.store_name',
                'st.store_no',
                DB::raw("IFNULL(d.delivery_status, 'Purchased') AS delivery_status"),
            ])
            ->leftJoin(DB::raw('(SELECT sub_invoice, ROUND(SUM((ABS(retail_price) - ABS(ABS(retail_price) * discount/100)) * qty),3) as amount, SUM(qty) as quantity, ROUND(SUM(loyalty),3) as point_in, FORMAT(ROUND(SUM(point_discount_price + redeemed_amount)),3) as point_out FROM sales_table_item_online GROUP BY sub_invoice) as a'), 's.sub_invoice', '=', 'a.sub_invoice')
            ->leftJoin('store_table as st', 's.store_id', '=', 'st.store_id')
            ->leftJoin('deliver_invoice_addr as d', 's.transaction_no', '=', 'd.invoice_no')
            ->where('s.customer_no', '=', $customerId)
            ->groupBy('s.sub_invoice')
            ->orderByRaw('s.sale_date_time DESC')
            ->get();

        $data = [];

        foreach ($orders as $order) {
            switch ($order->payment_type) {
                case 'Cash Online':
                    $paymentType = 'Cash On Delivery';
                    break;
                case 'Card web':
                    $paymentType = 'Card On Delivery';
                    break;
                case 'Credit Online':
                    $paymentType = 'Credit Card';
                    break;
                default:
                    $paymentRecord = Payment::select('paymnt_type')->where('paymnt_invoice', $order->sub_invoice)->groupBy('paymnt_invoice')->first();
                    if (!$paymentRecord) {
                        $paymentType = '';
                    } else {
                        switch ($paymentRecord->paymnt_type) {
                            case 'Cash USD':
                            case 'Cash JOD':
                                $paymentType = 'Cash';
                                break;
                            default:
                                $paymentType = 'Card';
                        }

                    }

            }

            switch ($order->sales_type) {
                case 'web':
                    $purchasedFrom = 'Website';
                    $orderStatus = $order->delivery_status;
                    break;
                case 'app':
                    $purchasedFrom = 'App';
                    $orderStatus = $order->delivery_status;
                    break;
                default:
                    $purchasedFrom = $order->sales_type;
                    $orderStatus = 'Delivered';
            }

            $order->sale_date = date('d/m/Y', strtotime($order->sale_date_time));
            $order->sale_time = date('h:i A', strtotime($order->sale_date_time));
            $order->order_status = $orderStatus;
            $order->purchased_from = $purchasedFrom;
            $order->payment_type = $paymentType;
            // Add location details if available
            $order->location_no = $customerLocationNo;
            $order->location_name = $customerLocationName;

            $data[] = $order;
        }

        return $data;
    }

    public function pointList()
    {
        if (!auth()->check()) {
            // If the user is not logged in, redirect to the home page
            return redirect()->route('home');
        }

        $aggridKeyRecord = DB::table('aggridkey_tbl')->select('keys')->first();
        $aggridKey = $aggridKeyRecord ? $aggridKeyRecord->keys : null;

        $customer = User::find(auth()->id());

        $loyaltyPoint = 0;

        $pointRecord = UserLoyaltyPoint::where('customer_id', $customer->customer_id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($pointRecord) {
            $loyaltyPoint = (int)$pointRecord->post_balance;
        }

        return view('frontend.pages.point-transactions', [
            'loyaltyPoint' => $loyaltyPoint,
            'customerId' => $customer->customer_id,
            'customerName' => $customer->customer_name,
            'locationNo' => $customer->location_no,
            'locationName' => $customer->location_name,
            'aggridKey' => $aggridKey,
        ]);
    }

    public function pointListById(string $customerId)
    {
        $existingCustomer = User::where('customer_id', $customerId)->exists();
        if (!$existingCustomer) {
            return redirect()->route('home');
        }

        $aggridKeyRecord = DB::table('aggridkey_tbl')->select('keys')->first();
        $aggridKey = $aggridKeyRecord ? $aggridKeyRecord->keys : null;

        $customer = User::where('customer_id', $customerId)->select('customer_name', 'customer_id', 'phone', 'location_no', 'location_name')->first();

        $loyaltyPoint = 0;

        $pointRecord = UserLoyaltyPoint::where('customer_id', $customer->customer_id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($pointRecord) {
            $loyaltyPoint = (int)$pointRecord->post_balance;
        }

        return view('frontend.pages.point-transactions', [
            'loyaltyPoint' => $loyaltyPoint,
            'customerId' => $customer->customer_id,
            'customerName' => $customer->customer_name,
            'locationNo' => $customer->location_no,
            'locationName' => $customer->location_name,
            'aggridKey' => $aggridKey,
        ]);
    }

    public function fetchPoints(Request $request)
    {

        $existingCustomer = User::where('customer_id', $request->customer_id)->exists();
        if (!$existingCustomer) {
            return response()->json([]);
        }

        $customerId = $request->customer_id;
        $customerPoints = UserLoyaltyPoint::where('customer_id', $customerId)
            ->where('id', '>=', function ($query) use ($customerId) {
                $query->select('id')
                    ->from('customer_points')
                    ->whereDate('added_date', '>=', '2020-11-13')
                    ->where('customer_id', $customerId)
                    ->orderBy('id')
                    ->limit(1);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $data = $customerPoints->map(function ($point) {
            return [
                'id' => $point->id,
                'date' => Carbon::parse($point->added_date)->format('d/m/Y H:i'),
                'point_type' => $point->point_type,
                'invoice_no' => $point->note,
                'point_in' => $point->point_in,
                'point_out' => $point->point_out,
                'point_balance' => $point->post_balance,
                'location' => $point->location,
                'valid_points' => $point->point_type != 'redeem' ? $point->valid_points : '',
                'valid_date' => $point->valid_days > 0 ? Carbon::parse($point->valid_date)->format('d/m/Y H:i') : '',
                'valid_days' => $point->point_type != 'redeem' ? $point->valid_days : '',
            ];
        });

        return response()->json($data);
    }

    public function exportExcel(Request $request)
    {
        $error_msg = '';
        $invoice = $request->invoice;
        $today = now()->format('d-m-Y');

        $orderArray = DB::table('sales_table_item_online')
            ->select('product_id', 'retail_price', 'wholesale_price', 'discount', 'tax', 'qty')
            ->where('sub_invoice', $invoice)
            ->get()
            ->toArray();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(0);
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle("Products");

        // Define styles
        $headerStyle = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['type' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D0C0D8']],
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'borders' => ['allborders' => ['style' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];

        $greenStyle = [
            'fill' => ['type' => Fill::FILL_SOLID, 'color' => ['rgb' => 'baf7b4']],
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'borders' => ['allborders' => ['style' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];

        $currentRow = 1;
        $columnHeaders = ['Item No', 'Ref No', 'Product Name', 'Description', 'Unit', 'Category', 'Brand', 'Qty', 'Adv', 'Retail Price', 'Disc%', 'U. Whole Sale', 'TOT. Whole Sale', 'Tax%', 'Tax Val', 'Total WT', 'Date', 'Barcode', 'Photo1', 'Photo2', 'Photo3'];

        // Set column headers
        foreach ($columnHeaders as $column => $data) {
            $worksheet->setCellValueByColumnAndRow($column + 1, $currentRow, $data);
            $worksheet->getStyleByColumnAndRow($column + 1, $currentRow)->applyFromArray($headerStyle);
        }
        foreach (range('A', 'U') as $columnID) {
            $worksheet->getStyle($columnID . '1')->applyFromArray($headerStyle);
        }

        $currentRow++;

        $subTotal = 0;
        $taxTotal = 0;
        $taxValueTotal = 0;
        $grandTotal = 0;
        $qtyTotal = 0;

        if (!empty($orderArray)) {
            foreach ($orderArray as $order) {
                $item_id = $order->product_id;
                // Fetch product details from the database
                $productDetails = Product::from('product_table as pt')
                    ->select(
                        'pt.product_no',
                        'pt.Ref_no',
                        'pt.title',
                        'pt.tax',
                        'pt.main_price',
                        'pt.barcode',
                        'pt.unit',
                        'pt.sub_cat_id',
                        'pt.linepr',
                        'pt.Adv_flag',
                        'pt.brand_id',
                        DB::raw("CONCAT('" . config('app.ikasco_url') . "', pt.photo2) AS pic"),
                        DB::raw("CONCAT('" . config('app.ikasco_url') . "familypic/', fp.family_pic) AS family_pic"),
                        DB::raw("GROUP_CONCAT(CONCAT('" . config('app.ikasco_url') . "moreproductpic/', mp.product_pic) SEPARATOR ',') AS more_pic")
                    )
                    ->leftJoin(DB::raw('(SELECT family_id, family_pic FROM family_pic_tbl) as fp'), 'fp.family_id', '=', 'pt.linepr')
                    ->leftJoin(DB::raw('(SELECT product_id, GROUP_CONCAT(product_pic) as product_pic FROM product_more_pic_tbl GROUP BY product_id) as mp'), 'pt.product_id', '=', 'mp.product_id')
                    ->where('pt.product_id', $item_id)
                    ->first();

                if ($productDetails) {
                    $adv = ($productDetails->Adv_flag == 6) ? 1 : 0;
                    $tax = $order->tax;
                    $disc = number_format((float)$order->discount, 2, '.', '');
                    $U_wholesale_price = number_format((float)$order->wholesale_price, 2, '.', '');
                    $TOT_wholesale_price = number_format((float)$order->wholesale_price * (int)$order->qty, 3, '.', '');
                    $Tax_Value = number_format(($TOT_wholesale_price * $tax / 100), 3, '.', '');
                    $total = number_format(($TOT_wholesale_price + $Tax_Value), 3, '.', '');

                    $qtyTotal += (int)$order->qty;
                    $subTotal += $TOT_wholesale_price;
                    $taxTotal += $tax;
                    $taxValueTotal += (float)$Tax_Value;
                    $grandTotal = (float)$subTotal + (float)$taxValueTotal;
                    $subCategoryId = $productDetails->sub_cat_id;
                    $subCategory = Subcategory::with('category')->find($subCategoryId);
                    $mainCategory = MainCategory::where('main_cat_id', $subCategory->category->main_cat_id)->value('main_cat_name');
                    $brand = Brand::find($productDetails->brand_id)->first();
                    $familyName = Family::where('family_id', $productDetails->linepr)->value('family_name');
                    $familyName = str_replace('\\', '', $familyName);

                    $tmpArray = [
                        $productDetails->product_no,
                        $productDetails->Ref_no,
                        $productDetails->family_name, // Assuming $productDetails has 'family_name' field
                        $productDetails->title,
                        $productDetails->unit,
                        $mainCategory,
                        ucwords(strtolower($brand->brand_name)),
                        $order->qty,
                        $adv,
                        $order->retail_price,
                        $disc,
                        $U_wholesale_price,
                        $TOT_wholesale_price,
                        $tax,
                        $Tax_Value,
                        $total,
                        $today,
                        $productDetails->barcode,
                        $productDetails->pic,
                        $productDetails->family_pic,
                        $productDetails->more_pic,
                    ];

                    foreach ($tmpArray as $column => $data) {
                        $worksheet->setCellValueExplicitByColumnAndRow($column + 1, $currentRow, $data, DataType::TYPE_STRING);
                    }
                    $currentRow++;
                }
            }
        }
        $tmpArray = [
            '', '', '', '', '', '', '', $qtyTotal, '', '', '', '', $subTotal, $taxTotal, $taxValueTotal, $grandTotal, '', '', '', '', '',
        ];
        foreach ($tmpArray as $column => $data) {
            $worksheet->setCellValueExplicitByColumnAndRow($column + 1, $currentRow, $data, DataType::TYPE_STRING);
        }
        foreach (range('A', 'U') as $columnID) {
            $worksheet->getStyle($columnID . $currentRow)->applyFromArray($greenStyle);
        }

        $absolutePath = public_path();
        $writer = new Xlsx($spreadsheet);
        $filename = "order_" . $invoice . ".xlsx";
        $filePath = $absolutePath . "/uploads/excel/" . $filename;
        $writer->save($filePath);

        return response()->json(["result" => true, "message" => '', "data" => $filename]);
    }

    public function orderDetails($orderId)
    {
        $orders = SaleOnline::where('sub_invoice', $orderId)
            ->get();

        if (!$orders) {
            return redirect()->route('your-orders');
        }

        $saleDateTime = Carbon::parse($orders->first()->sale_date_time);
        $saleDateTime = $saleDateTime->format('D, jS M, Y h:i A');

        if (!auth()->check()) {
            return redirect()->route('home');
        }

        $customer = User::select('customer_id', 'customer_name', 'loyalty_point')
            ->where('id', auth()->id())
            ->first();

        $paymentRecords = Payment::where('paymnt_invoice', $orderId)
            ->get();

        $usdCash = 0;
        $jodCash = 0;
        $card = 0;
        $cod = 0;
        $credit = 0;
        $redeemedLoyalty = 0;

        if ($paymentRecords->isEmpty()) {
            $orderRecord = DB::table('sales_table_item_online as s')
                ->select([
                    DB::raw('ROUND(SUM((ABS(retail_price) - ABS(ABS(retail_price) * discount/100)) * qty),3) as amount')
                ])
                ->leftJoin(DB::raw('(SELECT sub_invoice, ROUND(SUM((ABS(retail_price) - ABS(ABS(retail_price) * discount/100)) * qty),3) as amount, SUM(qty) as quantity, ROUND(SUM(loyalty),3) as point_in, FORMAT(ROUND(SUM(point_discount_price + redeemed_amount)),3) as point_out FROM sales_table_item_online GROUP BY sub_invoice) as a'), 's.sub_invoice', '=', 'a.sub_invoice')
                ->where('s.sub_invoice', '=', $orderId)
                ->groupBy('s.sub_invoice')
                ->orderByRaw('DATE(s.sale_date_time) DESC')
                ->first();
            $card += (float)$orderRecord->amount;
        } else {
            foreach ($paymentRecords as $paymentRecord) {
                if ($paymentRecord->paymnt_type == 'Cash JOD') {
                    $jodCash += (float)$paymentRecord->paymnt_amount;
                } elseif ($paymentRecord->paymnt_type == 'Cash USD') {
                    $usdCash += (float)$paymentRecord->paymnt_amount;
                } elseif ($paymentRecord->paymnt_type == 'Redeem Points') {
                    $redeemedLoyalty += (float)$paymentRecord->paymnt_amount;
                } elseif ($paymentRecord->paymnt_type == 'Credit') {
                    $credit += (float)$paymentRecord->paymnt_amount;
                } elseif ($paymentRecord->paymnt_type == 'Cash Online') {
                    $cod += (float)$paymentRecord->paymnt_amount;
                } else {
                    $card += (float)$paymentRecord->paymnt_amount;
                }
            }
        }

        $payment['USDCash'] = $usdCash != 0 ? number_format($usdCash / 0.7, 3) : '';
        $payment['USDToJODCash'] = $usdCash != 0 ? number_format($usdCash, 3) : '';
        $payment['JODCash'] = $jodCash != 0 ? number_format($jodCash, 3) : '';
        $payment['Card'] = $card != 0 ? number_format($card, 3) : '';
        $payment['COD'] = $cod != 0 ? number_format($cod, 3) : '';
        $payment['Credit'] = $credit != 0 ? number_format($credit, 3) : '';
        $payment['Loyalty'] = $redeemedLoyalty != 0 ? number_format($redeemedLoyalty, 3) : '';

        $orderTotal = 0;
        $gainedLoyalty = 0;
        if (!empty($orders)) {
            foreach ($orders as $order) {
                $rowCategory = SubCategory::select('Old_Value')
                    ->where('sub_cat_id', $order->sub_cat_id)
                    ->first();
                $order->old_id = $rowCategory->Old_Value;

                if ($order->qty < 0) {
                    $orderItemQuantity = 1;
                } else {
                    $orderItemQuantity = (int)$order->qty;
                }

                $orderItemRetail = (float)$order->retail_price;
                $orderItemDiscount = (float)(($order->discount * $order->retail_price) / 100) * $order->qty;

                $orderItemTotal = $orderItemRetail * $orderItemQuantity;
                $orderItemNet = $orderItemTotal - $orderItemDiscount;

                $order->total = number_format($orderItemNet, 3);
                $orderTotal += $orderItemNet;
                $gainedLoyalty += (int)$order->loyalty;
            }
        }

        $store = Store::select('store_name', 'store_no')
            ->where('store_id', $orders->first()->store_id)
            ->first();

        $storeName = $store->store_name;
        $storeNumber = $store->store_no;
        $deliveryStatus = DeliveryInvoice::where('invoice_no', $orderId)
            ->value('delivery_status');
        $deliveryStatus = $deliveryStatus ?: 'Purchased';


        return view('frontend.pages.order-details', [
            'customer' => $customer,
            'payment' => $payment,
            'orders' => $orders,
            'orderTotal' => $orderTotal,
            'gainedLoyalty' => $gainedLoyalty,
            'redeemedLoyalty' => $redeemedLoyalty,
            'storeName' => $storeName,
            'storeNumber' => $storeNumber,
            'deliveryStatus' => $deliveryStatus,
            'invoiceNumber' => $orderId,
            'saleDateTime' => $saleDateTime,
        ]);

    }

    public function orderDetailsById($customerId, $orderId)
    {
        $orders = SaleOnline::where('sub_invoice', $orderId)
            ->get();

        if ($orders->isEmpty()) {
            return redirect()->url('orders/' . $customerId);
        }

        $customer = User::where('customer_id', $customerId)->select('customer_name', 'customer_id', 'phone', 'loyalty_point', 'location_no', 'location_name')->first();

        if (!$customer) {
            return redirect()->route('home');
        }

        $saleDateTime = Carbon::parse($orders->first()->sale_date_time);
        $saleDateTime = $saleDateTime->format('D, jS M, Y h:i A');

        $paymentRecords = Payment::where('paymnt_invoice', $orderId)
            ->get();

        $usdCash = 0;
        $jodCash = 0;
        $card = 0;
        $cod = 0;
        $credit = 0;
        $redeemedLoyalty = 0;

        if ($paymentRecords->isEmpty()) {
            $orderRecord = DB::table('sales_table_item_online as s')
                ->select([
                    DB::raw('ROUND(SUM((ABS(retail_price) - ABS(ABS(retail_price) * discount/100)) * qty),3) as amount')
                ])
                ->leftJoin(DB::raw('(SELECT sub_invoice, ROUND(SUM((ABS(retail_price) - ABS(ABS(retail_price) * discount/100)) * qty),3) as amount, SUM(qty) as quantity, ROUND(SUM(loyalty),3) as point_in, FORMAT(ROUND(SUM(point_discount_price + redeemed_amount)),3) as point_out FROM sales_table_item_online GROUP BY sub_invoice) as a'), 's.sub_invoice', '=', 'a.sub_invoice')
                ->where('s.sub_invoice', '=', $orderId)
                ->groupBy('s.sub_invoice')
                ->orderByRaw('DATE(s.sale_date_time) DESC')
                ->first();
            $card += (float)$orderRecord->amount;
        } else {
            foreach ($paymentRecords as $paymentRecord) {
                if ($paymentRecord->paymnt_type == 'Cash JOD') {
                    $jodCash += (float)$paymentRecord->paymnt_amount;
                } elseif ($paymentRecord->paymnt_type == 'Cash USD') {
                    $usdCash += (float)$paymentRecord->paymnt_amount;
                } elseif ($paymentRecord->paymnt_type == 'Redeem Points') {
                    $redeemedLoyalty += (float)$paymentRecord->paymnt_amount;
                } elseif ($paymentRecord->paymnt_type == 'Credit') {
                    $credit += (float)$paymentRecord->paymnt_amount;
                } elseif ($paymentRecord->paymnt_type == 'Cash Online') {
                    $cod += (float)$paymentRecord->paymnt_amount;
                } else {
                    $card += (float)$paymentRecord->paymnt_amount;
                }
            }
        }


        $payment['USDCash'] = $usdCash != 0 ? number_format($usdCash / 0.7, 3) : '';
        $payment['USDToJODCash'] = $usdCash != 0 ? number_format($usdCash, 3) : '';
        $payment['JODCash'] = $jodCash != 0 ? number_format($jodCash, 3) : '';
        $payment['Card'] = $card != 0 ? number_format($card, 3) : '';
        $payment['COD'] = $cod != 0 ? number_format($cod, 3) : '';
        $payment['Credit'] = $credit != 0 ? number_format($credit, 3) : '';
        $payment['Loyalty'] = $redeemedLoyalty != 0 ? number_format($redeemedLoyalty, 3) : '';

        $orderTotal = 0;
        $gainedLoyalty = 0;
        if (!empty($orders)) {
            foreach ($orders as $order) {
                $product = Product::select('product_id', 'product_no', 'title', 'sub_cat_id', 'brand_id')->where('product_id', $order->product_id)->first();
                $brand = Brand::select('name', 'ax_id', 'distributor', 'supplier_id')->where('id', $product->brand_id)->first();
                $subCategory = SubCategory::select('sub_cat_name', 'cat_id', 'Old_value')->where('sub_cat_id', $product->sub_cat_id)->first();
                $oldCategory = $subCategory->Old_value;
                $category = Category::select('cat_name', 'main_cat_id')->where('cat_id', $subCategory->cat_id)->first();
                $mainCategory = MainCategory::select('main_cat_name')->where('main_cat_id', $category->main_cat_id)->first();

                $categoryName = $mainCategory->main_cat_name . '->' . $category->cat_name . '->' . $subCategory->sub_cat_name;
                $order->product_no = $product->product_no;
                $order->title = $product->title;
                $order->old_id = $oldCategory;
                $order->brand = $brand->name;
                $order->category = $categoryName;

                if ($order->qty < 0) {
                    $orderItemQuantity = 1;
                } else {
                    $orderItemQuantity = (int)$order->qty;
                }

                $orderItemRetail = (float)$order->retail_price;
                $orderItemDiscount = (float)(($order->discount * $order->retail_price) / 100) * $order->qty;

                $orderItemTotal = $orderItemRetail * $orderItemQuantity;
                $orderItemNet = $orderItemTotal - $orderItemDiscount;

                $order->total = number_format($orderItemNet, 3);
                $orderTotal += $orderItemNet;
                $gainedLoyalty += (int)$order->loyalty;
            }
        }

        $store = Store::select('store_name', 'store_no')
            ->where('store_id', $orders->first()->store_id)
            ->first();

        $storeName = $store->store_name;
        $storeNumber = $store->store_no;
        $deliveryStatus = DeliveryInvoice::where('invoice_no', $orderId)
            ->value('delivery_status');
        $deliveryStatus = $deliveryStatus ?: 'Purchased';


        return view('frontend.pages.order-details-by-id', [
            'customer' => $customer,
            'payment' => $payment,
            'orders' => $orders,
            'orderTotal' => $orderTotal,
            'gainedLoyalty' => $gainedLoyalty,
            'redeemedLoyalty' => $redeemedLoyalty,
            'storeName' => $storeName,
            'storeNumber' => $storeNumber,
            'deliveryStatus' => $deliveryStatus,
            'invoiceNumber' => $orderId,
            'saleDateTime' => $saleDateTime,
        ]);

    }

    private function processOrderRecords($orderRecords, $customer)
    {
        $data = [];

        foreach ($orderRecords as $rowOrderRecord) {
            // Process each order record and add to $data array
            $paymentType = $this->resolvePaymentType($rowOrderRecord);
            $purchasedFrom = $this->resolvePurchasedFrom($rowOrderRecord);

            // Example: Convert dates, payment types, etc.
            $rowOrderRecord->sale_date = date('d/m/Y', strtotime($rowOrderRecord->sale_date_time));
            $rowOrderRecord->sale_time = date('h:i A', strtotime($rowOrderRecord->sale_date_time));

            $data[] = [
                'id' => $rowOrderRecord->id,
                'sub_invoice' => $rowOrderRecord->sub_invoice,
                'store_id' => $rowOrderRecord->store_id,
                'sale_date_time' => $rowOrderRecord->sale_date_time,
                'payment_type' => $paymentType,
                'sales_type' => $purchasedFrom,
                'amount' => $rowOrderRecord->amount,
                'quantity' => $rowOrderRecord->quantity,
                'store_name' => $rowOrderRecord->store_name,
                'store_no' => $rowOrderRecord->store_no,
                'delivery_status' => $rowOrderRecord->delivery_status, // Fix variable name here
                'sale_date' => date('d/m/Y', strtotime($rowOrderRecord->sale_date_time)),
                'sale_time' => date('h:i A', strtotime($rowOrderRecord->sale_date_time)),
                'order_status' => $rowOrderRecord->delivery_status, // Keep it consistent here
                'purchased_from' => $purchasedFrom,
                'payment_type' => $paymentType,
                'location_no' => $customer->location_no,
                'location_name' => $customer->location_name,
                // Add other fields as needed
            ];
        }

        return $data;
    }

    private function resolvePaymentType($rowOrderRecord)
    {
        switch ($rowOrderRecord->payment_type) {
            case 'COD':
                return 'Cash On Delivery';
            case 'CCOD':
                return 'Credit Card On Delivery';
            case 'CC':
                return 'Credit Card';
            default:
                $paymentType = DB::table('payment_table')
                    ->where('paymnt_invoice', $rowOrderRecord->sub_invoice)
                    ->groupBy('paymnt_invoice')
                    ->value('paymnt_type');

                switch ($paymentType) {
                    case 'Cash USD':
                    case 'Cash JOD':
                        return 'Cash';
                    default:
                        return 'Card';
                }
        }
    }

    private function resolvePurchasedFrom($rowOrderRecord)
    {
        switch ($rowOrderRecord->sales_type) {
            case 'WEB':
                return 'Website';
            case 'APP':
                return 'App';
            default:
                return $rowOrderRecord->sales_type;
        }
    }
}
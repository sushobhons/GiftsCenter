<?php

namespace Modules\Frontend\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;

use Illuminate\Routing\Controller;
use DB;
use Helper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Modules\Frontend\Entities\Banner;
use Modules\Frontend\Entities\Article;
use Modules\Frontend\Entities\GcNewsletter;
use Modules\Frontend\Entities\Offer;
use Modules\Frontend\Entities\Reward;
use Modules\User\Entities\User;
use Modules\User\Entities\UserLoyaltyPoint;
use Modules\Product\Entities\Brand;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\Family;
use Modules\Product\Entities\MainCategory;
use Modules\Product\Entities\Category;
use Modules\Product\Entities\SubCategory;
use Modules\Product\Entities\Segment;
use Modules\Product\Entities\Stock;
use Modules\Product\Entities\Collection;
use Modules\Product\Entities\Concern;
use Modules\Product\Entities\Review;
use Modules\Order\Entities\Store;
use Modules\Order\Entities\EVoucher;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;

class FrontendController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {

        $banners = [];
        $mbannerArr = [];
        $currentDate = Carbon::now()->toDateString();
        $currentDay = strtolower(now()->format('D'));
        $bannerCategory = request()->input('cat_type') == "1" ? 2 : 1;

        $bannerQuery = Banner::whereRaw(
            "FIND_IN_SET(?, domain_id)
        AND banner_type = 'home'
        AND banner_location = '1'
        AND status = '1'
        AND banner_category = ?
        AND ((? BETWEEN from_date AND to_date) OR (from_date <= ? AND to_date='0000-00-00'))"
            , [1, $bannerCategory, $currentDate, $currentDate])
            ->orderBy('banner_id', 'DESC')
            ->get();

        foreach ($bannerQuery as $rowBanner) {
            $banners[] = $rowBanner;
            if (!empty($rowBanner['mbanner'])) {
                $mbannerArr[] = $rowBanner;
            }
        }

        $featured = [];
        $posts = [];

        $products = [];
        $category = [];
        return view('frontend.index')
            ->with('featured', $featured)
            ->with('posts', $posts)
            ->with('banners', $banners)
            ->with('product_lists', $products)
            ->with('category_lists', $category);
    }

    public function home()
    {

        $user = Auth::user();
        if ($user) {
            $customerId = $user->customer_id;
            $customerOrganization = $user->organization;
            $customerTier = $user->tier;
        }
        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
//        $newArrivals = Helper::getHomeNewArrivals();
//        dd($newArrivals);
        $groupSegments = Segment::select('id', 'title', 'slug', 'advertisement', 'view_by')
            ->where('group_by', 1)
            ->where('status', '=', '1')
            ->where('is_hidden', '=', '0')
            ->where('domain', '=', $domainId)
            ->where(function ($query) {
                $query->orWhereRaw('FIND_IN_SET(?, location)', [1])
                    ->orWhereRaw('FIND_IN_SET(?, location)', [2]);
            })
            ->orderBy('order_key', 'ASC')
            ->get();


        if ($groupSegments->isNotEmpty()) {
            foreach ($groupSegments as $segment) {
                $segment->url = in_array($segment->slug, ['new-arrival', 'best-seller']) ? route('products.' . $segment->slug) : route('products.shop', ['segmentSlug' => $segment->slug]);
            }
        }
        //dd(Helper::getHomeBrands());
        //$featuredBrands = Helper::getHomeBrands();
        $featuredBrands = Brand::select(
            'id as brand_id',
            'name as brand_name',
            'brand_slug',
            'logo',
            'brand_description',
            'banner_image',
        )->where('is_featured', 1)->where('web_status', 1)->where('domain_id', $domainId)->orderBy('brand_name')->get();
        //$featuredBrands = [];
        $newArrivals = [];
        $banners = [];
        $mobileBanners = [];
        $currentDate = Carbon::now()->toDateString();
        $currentDay = strtolower(now()->format('D'));
        $bannerCategory = request()->input('cat_type') == "1" ? 2 : 1;

        $banners = Banner::whereRaw(
            "FIND_IN_SET(?, domain_id)
        AND banner_type = 'home'
        AND banner_location = '1'
        AND status = '1'
        AND banner_category = ?
        AND ((? BETWEEN from_date AND to_date) OR (from_date <= ? AND to_date='0000-00-00'))"
            , [$domainId, $bannerCategory, $currentDate, $currentDate])
            ->orderBy('banner_id', 'DESC')
            ->get();

        $mobileBanners = $banners->filter(function ($banner) {
            return !empty($banner->mbanner);
        });

        //dynamic sections
        $homeSectionArray = [];
        $homeSegments = Segment::select('id', 'title', 'type', 'advertisement', 'slug', 'dynamic_mysql', 'items', 'view_by')
            ->where('group_by', 0)
            ->where('status', '=', '1')
            ->where('is_hidden', '=', '0')
            ->where('domain', '=', $domainId)
            ->where(function ($query) {
                $query->orWhereRaw('FIND_IN_SET(?, location)', [1])
                    ->orWhereRaw('FIND_IN_SET(?, location)', [2]);
            })
            ->orderBy('order_key', 'ASC')
            ->get();
        if ($homeSegments->isNotEmpty()) {
            foreach ($homeSegments as $segment) {
                if ($segment->type == 'bundle') {

                } else {

                    $productsRecord = DB::table('product_table as pt')
                        ->select([
                            'pt.type_flag as product_type',
                            'pt.product_id',
                            DB::raw("IF(pt.type_flag = '2', pt.seo_url, f.seo_url) as seo_url"),
                            'pt.sub_cat_id',
                            'pt.func_flag',
                            'br.brand_id',
                            'br.brand_name',
                            'pt.linepr',
                            'pt.has_gift',
                            DB::raw("ROUND(pt.main_price, 0) as main_price"),
                            DB::raw("ROUND(MAX(pt.main_price), 0) as max_price"),
                            DB::raw("ROUND(MIN(NULLIF(pt.main_price, 0)), 0) as min_price"),
                            DB::raw("IF(pt.type_flag = '2', SUBSTR(REPLACE(pt.product_name, '\\\\', ''), 1, 60) , SUBSTR(REPLACE(f.family_name, '\\\\', ''), 1, 60)) as family_name"),
                            DB::raw("IF(pt.type_flag = '2', pt.photo1, fp.aws) as family_pic"),
                            'st.stock',
                            DB::raw("IFNULL(SUBSTRING_INDEX(sg.title, ' ', 1), '') as segment"),
                            DB::raw("IFNULL(sg.slug, '') as segment_slug"),
                            DB::raw("IFNULL(sg.type, '') as segment_type"),
                            DB::raw("IFNULL(o.offer_id, '') as has_offer"),
                            DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) as unique_key"),
                        ])
                        ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as br'), 'pt.brand_id', '=', 'br.brand_id')
                        ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
                        ->leftJoin(DB::raw('(SELECT family_id, family_name, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
                        ->leftJoin(DB::raw('(SELECT family_id,aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
                        ->leftJoin(DB::raw("(SELECT id, title, slug, type FROM gc_segments WHERE slug IN ('new-arrival', 'best-seller') AND domain = '" . $domainId . "') as sg"), function ($join) {
                            $join->on(DB::raw('FIND_IN_SET(sg.id, pt.in_segment)'), '>', DB::raw('0'));
                        })
                        ->leftJoin(DB::raw("(SELECT offer_id, items FROM offer_tbl WHERE status = '1' AND FIND_IN_SET('" . $domainId . "', in_domain) AND items != '' AND offer_for IN ('1','2') AND ((('" . $currentDate . "' BETWEEN from_date AND to_date) OR (from_date <= '" . $currentDate . "' AND to_date = '0000-00-00 00:00:00')) OR (FIND_IN_SET('" . $currentDay . "', week_days))) ) as o"), function ($join) {
                            $join->on(DB::raw('FIND_IN_SET(pt.product_id, o.items)'), '>', DB::raw('0'));
                        })
                        ->where('pt.web_status', '=', '1')
                        ->where('pt.main_price', '>', 0)
                        ->whereRaw('FIND_IN_SET(?, pt.in_domain)', [$domainId]);

                    if ($segment && $segment->dynamic_mysql) {
                        $productsRecord->whereRaw($segment->dynamic_mysql);
                    } else {
                        $productsRecord->whereIn('pt.in_segment', [$segment->id]);
                    }
                    $productsRecord->whereNotNull('st.stock')->groupBy(DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr)"));
                    $productsRecord->orderBy('pt.product_name', 'asc');
                    $productsRecord->limit(20);
                    $products = $productsRecord->get();
                    if ($products->isNotEmpty()) {
                        foreach ($products as $product) {
                            $subCat = $product->sub_cat_id;
                            $subCategory = Subcategory::with('category')->find($subCat);
                            $cat = $subCategory->cat_id;
                            $mainCat = $subCategory->category->main_cat_id;
                            $brand = $product->brand_id;
                            $brandDetails = Brand::find($brand);
                            $distributor = $brandDetails->distributor;
                            $flag = $product->func_flag;
                            $item = $product->product_id;
                            $webDiscount = 0;
                            $webDiscountType = '';
                            $webDiscountOffer = '';

                            // Fetching discount offer by store
                            $offer = DB::table('offer_tbl as o')
                                ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name')
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
                                ->limit(1)
                                ->get();

                            if ($offer->isNotEmpty()) {
                                $rowOfferRecord = $offer->first();
                                $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                                $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                                $webDiscountOffer = $rowOfferRecord->offer_name;
                            }

                            if ($user) {
                                //fetching auto apply offer by customer's tier
                                $offer = DB::table('offer_tbl as o')
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
                                    ->limit(1)
                                    ->get();


                                if ($offer->isNotEmpty()) {
                                    $rowOfferRecord = $offer->first();
                                    $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                                    $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                                    $webDiscountOffer = $rowOfferRecord->offer_name;
                                }

                                //fetching auto apply offer by customer's id or organization
                                $offer = DB::table('offer_tbl as o')
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
                                    ->limit(1)
                                    ->get();


                                if ($offer->isNotEmpty()) {
                                    $rowOfferRecord = $offer->first();
                                    $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                                    $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                                    $webDiscountOffer = $rowOfferRecord->offer_name;
                                }


                            }

                            switch ($webDiscountType) {
                                case 'amount':
                                    $product->dmain_price = $webDiscount > 0 ? round((float)$product->main_price - (float)$webDiscount) : '';
                                    $product->dmax_price = $webDiscount > 0 ? round((float)$product->max_price - (float)$webDiscount) : '';
                                    $product->dmin_price = $webDiscount > 0 ? round((float)$product->min_price - (float)$webDiscount) : '';
                                    break;
                                case 'percentage':
                                    $product->dmain_price = $webDiscount > 0 ? round((float)$product->main_price - ((float)$product->main_price * (float)$webDiscount / 100)) : '';
                                    $product->dmax_price = $webDiscount > 0 ? round((float)$product->max_price - ((float)$product->max_price * (float)$webDiscount / 100)) : '';
                                    $product->dmin_price = $webDiscount > 0 ? round((float)$product->min_price - ((float)$product->min_price * (float)$webDiscount / 100)) : '';
                                    break;
                                default:
                                    $product->dmain_price = '';
                                    $product->dmax_price = '';
                                    $product->dmin_price = '';
                                    break;
                            }

                            $product->offer_name = $webDiscountOffer;
                            $product->family_name = ucwords(strtolower($product->family_name));
                            $product->offer_sql = '';
                        }
                    }
                    $segmentUrl = in_array($segment->slug, ['new-arrival', 'best-seller']) ? route('products.' . $segment->slug) : route('products.shop', ['segmentSlug' => $segment->slug]);
                    $homeSectionArray[] = [
                        "id" => $segment->id,
                        "type" => $segment->type,
                        "slug" => $segment->slug,
                        "title" => $segment->title,
                        "view_by" => $segment->view_by,
                        "advertisement" => $segment->advertisement,
                        "products" => $products, // Assuming 'id' is the primary key of your products table
                        "product_count" => count($products),
                        "segment_url" => $segmentUrl
                    ];
                }
            }
        }

        return view('frontend.index')
            ->with('banners', $banners)
            ->with('mobileBanners', $mobileBanners)
            ->with('newArrivals', $newArrivals)
            ->with('groupSegments', $groupSegments)
            ->with('featuredBrands', $featuredBrands)->with('homeSections', $homeSectionArray);
    }

    public function fetchHomeSegments(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $customerId = $user->customer_id;
            $customerOrganization = $user->organization;
            $customerTier = $user->tier;
        }
        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $currentDate = now()->format('Y-m-d');
        $currentDay = strtolower(now()->format('D'));

        $homeSectionArray = [];
        $segmentSlug = $request->segmentSlug;
        $segment = Segment::where('slug', $segmentSlug)
            ->where('domain', $domainId)
            ->first();
        $segmentId = $segment->id;
        $mCategoryQry = Product::from('product_table as pt')
            ->with(['mainCategory', 'category', 'subCategory'])
            ->select(
                'main_cat_table.main_cat_id',
                'main_cat_table.main_cat_name',
                'main_cat_table.main_cat_slug',
            )
            ->join('stock_table', function ($join) {
                $join->on('pt.product_id', '=', 'stock_table.product_id')
                    ->where('stock_table.qty', '>', 0);
            })
            ->leftJoin('sub_cat_table', 'pt.sub_cat_id', '=', 'sub_cat_table.sub_cat_id')
            ->leftJoin('cat_table', 'sub_cat_table.cat_id', '=', 'cat_table.cat_id')
            ->leftJoin('main_cat_table', 'cat_table.main_cat_id', '=', 'main_cat_table.main_cat_id');

        if ($segment && $segment->dynamic_mysql) {
            $mCategoryQry->whereRaw($segment->dynamic_mysql);
        } else {
            $mCategoryQry->whereRaw("FIND_IN_SET(?, pt.in_segment)", [$segment->id]);
        }
        $mCategoryQry->where('pt.web_status', '=', 1)
            ->where('pt.main_price', '>', 0)
            ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
            ->whereNotNull('stock_table.qty')
            ->groupBy('main_cat_table.main_cat_id')
            ->orderBy('main_cat_table.main_cat_index');
        $mCategoryRecords = $mCategoryQry->get();

        $mCategoryArr = [];

        foreach ($mCategoryRecords as $record) {
            $mainCategoryId = $record->main_cat_id;
            $subCategoryIds = SubCategory::whereIn('cat_id', function ($query) use ($mainCategoryId) {
                $query->select('cat_id')
                    ->from('cat_table')
                    ->where('main_cat_id', $mainCategoryId);
            })->pluck('sub_cat_id');
            $productsQuery = Product::from('product_table as pt')
                ->select([
                    'pt.product_id',
                    DB::raw("ROUND(pt.main_price, 0) as main_price"),
                    DB::raw("ROUND(MAX(pt.main_price), 0) as max_price"),
                    DB::raw("ROUND(MIN(NULLIF(pt.main_price, 0)), 0) as min_price"),
                    DB::raw("IF(pt.type_flag = '2', pt.seo_url, f.seo_url) as seo_url"),
                    DB::raw("IF(pt.`type_flag` = '2',
                        CONCAT(
                            SUBSTR(REPLACE(pt.`product_name`, '\\\', ''), 1, 60),
                            IF(
                                CHAR_LENGTH(REPLACE(pt.`product_name`, '\\\', '')) > 60,
                                '..',
                                ''
                            )
                        ),
                        CONCAT(
                            SUBSTR(REPLACE(`f`.`family_name`, '\\\', ''), 1, 60),
                            IF(
                                CHAR_LENGTH(REPLACE(`f`.`family_name`, '\\\', '')) > 60,
                                '..',
                                ''
                            )
                        )
                    ) AS `family_name`"),
                    DB::raw("IF(pt.type_flag = '2', pt.photo1, fp.aws) as family_pic"),
                    DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) as unique_key"),
                    'br.brand_name',
                    'pt.sub_cat_id',
                    'pt.brand_id',
                    'pt.func_flag',
                ])
                ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
                ->leftJoin(DB::raw('(SELECT family_id, family_name, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
                ->leftJoin(DB::raw('(SELECT family_id,aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
                ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as br'), 'pt.brand_id', '=', 'br.brand_id');
            if ($segment && $segment->dynamic_mysql) {
                $productsQuery->whereRaw($segment->dynamic_mysql);
            } else {
                $productsQuery->whereRaw("FIND_IN_SET(?, pt.in_segment)", [$segment->id]);
            }
            $productsQuery->where('pt.web_status', '=', '1')
                ->where('pt.main_price', '>', 0)
                ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
                ->whereIn('pt.sub_cat_id', $subCategoryIds)
                ->whereNotNull('st.stock')
                ->groupBy(DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr)"))
                ->inRandomOrder()->limit(20);
            $productsRecord = $productsQuery->get();
            foreach ($productsRecord as $rowProductsRecord) {
                $subCat = $rowProductsRecord->sub_cat_id;
                $subCategory = Subcategory::with('category')->find($subCat);
                $cat = $subCategory->cat_id;
                $mainCat = $subCategory->category->main_cat_id;
                $brand = $rowProductsRecord->brand_id;
                $brandDetails = Brand::find($brand);
                $distributor = $brandDetails->distributor;
                $rowProductsRecord->distributor = $distributor;
                $flag = $rowProductsRecord->func_flag;
                $item = $rowProductsRecord->product_id;
                $webDiscount = 0;
                $webDiscountType = '';
                $webDiscountOffer = '';

                // Fetching discount offer by store
                $storeOffer = DB::table('offer_tbl as o')
                    ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
                    ->where('o.auto_apply', '1')
                    ->where('o.status', '1')
                    ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                    ->where('o.offer_type', 'store')
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
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o8.items FROM offer_tbl as o8 WHERE o8.offer_id=o.offer_id))', [$item]);
                    })
                    ->limit(1)
                    ->get();
                //dd($storeOffer);
                if ($storeOffer->isNotEmpty()) {
                    $rowOfferRecord = $storeOffer->first();
                    $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                    $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                    $webDiscountOffer = $rowOfferRecord->offer_name;
                }

                if ($user) {
                    //fetching auto apply offer by customer's tier
                    $tierOffer = DB::table('offer_tbl as o')
                        ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
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
                        ->limit(1)
                        ->get();


                    if ($tierOffer->isNotEmpty()) {
                        $rowOfferRecord = $tierOffer->first();
                        $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                        $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                        $webDiscountOffer = $rowOfferRecord->offer_name;
                    }

                    //fetching auto apply offer by customer's id or organization
                    $customerOffer = DB::table('offer_tbl as o')
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
                        ->limit(1)
                        ->get();


                    if ($customerOffer->isNotEmpty()) {
                        $rowOfferRecord = $customerOffer->first();
                        $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                        $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                        $webDiscountOffer = $rowOfferRecord->offer_name;
                    }


                }

                switch ($webDiscountType) {
                    case 'amount':
                        $rowProductsRecord->dmain_price = $webDiscount > 0 ? round((float)$rowProductsRecord->main_price - (float)$webDiscount) : '';
                        $rowProductsRecord->dmax_price = $webDiscount > 0 ? round((float)$rowProductsRecord->max_price - (float)$webDiscount) : '';
                        $rowProductsRecord->dmin_price = $webDiscount > 0 ? round((float)$rowProductsRecord->min_price - (float)$webDiscount) : '';
                        break;
                    case 'percentage':
                        $rowProductsRecord->dmain_price = $webDiscount > 0 ? round((float)$rowProductsRecord->main_price - ((float)$rowProductsRecord->main_price * (float)$webDiscount / 100)) : '';
                        $rowProductsRecord->dmax_price = $webDiscount > 0 ? round((float)$rowProductsRecord->max_price - ((float)$rowProductsRecord->max_price * (float)$webDiscount / 100)) : '';
                        $rowProductsRecord->dmin_price = $webDiscount > 0 ? round((float)$rowProductsRecord->min_price - ((float)$rowProductsRecord->min_price * (float)$webDiscount / 100)) : '';
                        break;
                    default:
                        $rowProductsRecord->dmain_price = '';
                        $rowProductsRecord->dmax_price = '';
                        $rowProductsRecord->dmin_price = '';
                        break;
                }

                $rowProductsRecord->offer_name = $webDiscountOffer;
            }
            $record->products = $productsRecord;

        }

        return [
            "slug" => $segment->slug,
            "categories" => $mCategoryRecords,
        ];

    }

    public function fetchBrandProducts(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $customerId = $user->customer_id;
            $customerOrganization = $user->organization;
            $customerTier = $user->tier;
        }
        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $currentDate = now()->format('Y-m-d');
        $currentDay = strtolower(now()->format('D'));

        $brandId = $request->brandId;

        $productsRecord = DB::table('product_table as pt')
            ->select([
                'pt.product_id',
                DB::raw("ROUND(pt.main_price, 0) as main_price"),
                DB::raw("ROUND(MAX(pt.main_price), 0) as max_price"),
                DB::raw("ROUND(MIN(NULLIF(pt.main_price, 0)), 0) as min_price"),
                DB::raw("IF(pt.type_flag = '2', pt.seo_url, f.seo_url) as seo_url"),
                DB::raw("IF(pt.`type_flag` = '2',
                    CONCAT(
                        SUBSTR(REPLACE(pt.`product_name`, '\\\', ''), 1, 60),
                        IF(
                            CHAR_LENGTH(REPLACE(pt.`product_name`, '\\\', '')) > 60,
                            '..',
                            ''
                        )
                    ),
                    CONCAT(
                        SUBSTR(REPLACE(`f`.`family_name`, '\\\', ''), 1, 60),
                        IF(
                            CHAR_LENGTH(REPLACE(`f`.`family_name`, '\\\', '')) > 60,
                            '..',
                            ''
                        )
                    )
                ) AS `family_name`"),
                DB::raw("IF(pt.type_flag = '2', pt.photo1, fp.aws) as family_pic"),
                DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) as unique_key"),
                'br.brand_name',
                'pt.sub_cat_id',
                'pt.brand_id',
                'pt.func_flag',
            ])
            ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
            ->leftJoin(DB::raw('(SELECT family_id, family_name, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
            ->leftJoin(DB::raw('(SELECT family_id,aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
            ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as br'), 'pt.brand_id', '=', 'br.brand_id')
            ->where('pt.web_status', '=', '1')
            ->where('pt.main_price', '>', 0)
            ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
            ->where('pt.brand_id', $brandId)
            ->whereNotNull('st.stock')
            ->groupBy(DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr)"))
            ->inRandomOrder()
            ->take(8)
            ->get();
        foreach ($productsRecord as $rowProductsRecord) {
            $subCat = $rowProductsRecord->sub_cat_id;
            $subCategory = Subcategory::with('category')->find($subCat);
            $cat = $subCategory->cat_id;
            $mainCat = $subCategory->category->main_cat_id;
            $brand = $rowProductsRecord->brand_id;
            $brandDetails = Brand::find($brand);
            $distributor = $brandDetails->distributor;
            $rowProductsRecord->distributor = $distributor;
            $flag = $rowProductsRecord->func_flag;
            $item = $rowProductsRecord->product_id;
            $webDiscount = 0;
            $webDiscountType = '';
            $webDiscountOffer = '';

            // Fetching discount offer by store
            $storeOffer = DB::table('offer_tbl as o')
                ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
                ->where('o.auto_apply', '1')
                ->where('o.status', '1')
                ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                ->where('o.offer_type', 'store')
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
                        ->orWhereRaw('FIND_IN_SET(?, (SELECT o8.items FROM offer_tbl as o8 WHERE o8.offer_id=o.offer_id))', [$item]);
                })
                ->limit(1)
                ->get();
            //dd($storeOffer);
            if ($storeOffer->isNotEmpty()) {
                $rowOfferRecord = $storeOffer->first();
                $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                $webDiscountOffer = $rowOfferRecord->offer_name;
            }

            if ($user) {
                //fetching auto apply offer by customer's tier
                $tierOffer = DB::table('offer_tbl as o')
                    ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
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
                    ->limit(1)
                    ->get();


                if ($tierOffer->isNotEmpty()) {
                    $rowOfferRecord = $tierOffer->first();
                    $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                    $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                    $webDiscountOffer = $rowOfferRecord->offer_name;
                }

                //fetching auto apply offer by customer's id or organization
                $customerOffer = DB::table('offer_tbl as o')
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
                    ->limit(1)
                    ->get();


                if ($customerOffer->isNotEmpty()) {
                    $rowOfferRecord = $customerOffer->first();
                    $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                    $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                    $webDiscountOffer = $rowOfferRecord->offer_name;
                }


            }

            switch ($webDiscountType) {
                case 'amount':
                    $rowProductsRecord->dmain_price = $webDiscount > 0 ? round((float)$rowProductsRecord->main_price - (float)$webDiscount) : '';
                    $rowProductsRecord->dmax_price = $webDiscount > 0 ? round((float)$rowProductsRecord->max_price - (float)$webDiscount) : '';
                    $rowProductsRecord->dmin_price = $webDiscount > 0 ? round((float)$rowProductsRecord->min_price - (float)$webDiscount) : '';
                    break;
                case 'percentage':
                    $rowProductsRecord->dmain_price = $webDiscount > 0 ? round((float)$rowProductsRecord->main_price - ((float)$rowProductsRecord->main_price * (float)$webDiscount / 100)) : '';
                    $rowProductsRecord->dmax_price = $webDiscount > 0 ? round((float)$rowProductsRecord->max_price - ((float)$rowProductsRecord->max_price * (float)$webDiscount / 100)) : '';
                    $rowProductsRecord->dmin_price = $webDiscount > 0 ? round((float)$rowProductsRecord->min_price - ((float)$rowProductsRecord->min_price * (float)$webDiscount / 100)) : '';
                    break;
                default:
                    $rowProductsRecord->dmain_price = '';
                    $rowProductsRecord->dmax_price = '';
                    $rowProductsRecord->dmin_price = '';
                    break;
            }

            $rowProductsRecord->offer_name = $webDiscountOffer;
        }

        return $productsRecord;

    }

    public function brandList(Request $request)
    {
        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $brandRecords = Product::select(
            'brand_table.id',
            'product_table.brand_id',
            'brand_table.name',
            'brand_table.brand_slug',
            DB::raw('UPPER(LEFT(brand_table.name, 1)) AS brand_key')
        )
            ->join(DB::raw('(SELECT qty AS stock, product_id FROM stock_table WHERE qty > 0) as st'), 'product_table.product_id', '=', 'st.product_id')
            ->join('brand_table', 'product_table.brand_id', '=', 'brand_table.id')
            ->whereRaw("FIND_IN_SET(?, product_table.in_domain)", [$domainId])
            ->where('brand_table.web_status', '=', 1)
            ->where('product_table.web_status', '=', 1)
            ->where('product_table.is_voucher', '0')
            ->whereNotNull('st.stock')
            ->orderBy('brand_table.name', 'ASC')
            ->get();

        $brands = [];

        foreach ($brandRecords as $rowBrandRecord) {
            $brands[$rowBrandRecord->brand_key][$rowBrandRecord->id] = [
                'brand_id' => $rowBrandRecord->id,
                'brand_name' => $rowBrandRecord->name,
                'brand_slug' => $rowBrandRecord->brand_slug,
            ];
        }


        return view('frontend.pages.brands')->with('brands', $brands);
    }

    public function productBrand(Request $request)
    {
        $products = Brand::getProductByBrand($request->slug);
        $recent_products = Product::where('status', 'active')->orderBy('id', 'DESC')->limit(3)->get();
        if (request()->is('e-shop.loc/product-grids')) {
            return view('frontend.pages.product-grids')->with('products', $products->products)->with('recent_products', $recent_products);
        } else {
            return view('frontend.pages.product-lists')->with('products', $products->products)->with('recent_products', $recent_products);
        }

    }

    public function productList(Request $request)
    {
        $segmentSlug = $request->route()->parameter('segmentSlug') ?? null;
        $mainCategorySlug = $request->route()->parameter('mainCategorySlug') ?? null;
        $categorySlug = $request->route()->parameter('categorySlug') ?? null;
        $subCategorySlug = $request->route()->parameter('subCategorySlug') ?? null;
        $concernSlug = $request->route()->parameter('concernSlug') ?? null;
        $collectionSlug = $request->route()->parameter('collectionSlug') ?? null;
        $brandSlug = $request->route()->parameter('brandSlug') ?? null;
        $searchSlug = $request->route()->parameter('searchSlug') ?? null;
        $offerSlug = $request->route()->parameter('offerSlug') ?? null;
        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $keyType = $request->route()->parameter('keyType');
        $mainCategoryId = $request->main_category_id;
        $categoryId = $request->category_id;
        $keyTerm = '';
        $productType = '1';
        $brandFilter = '';
        $filterType = '';
        $filterWord = '';

        $segmentData = [];
        $brandData = [];
        $sizeData = [];
        $priceData = [];
        $fragranceTypesData = [];
        $concernData = [];
        $collectionData = [];
        $subCatData = [];
        $sData = [];
        $bData = [];
        $priceData = [];
        $productFilterArray = [];

        $breadcrumbs = [];

        if ($mainCategorySlug) {
            $mainCategory = MainCategory::where('main_cat_slug', $mainCategorySlug)->first();
            if ($mainCategory) {
                $mainCategoryId = $mainCategory->main_cat_id;
                if ($categorySlug) {
                    $category = Category::where('cat_slug', $categorySlug)
                        ->where('main_cat_id', $mainCategory->main_cat_id)
                        ->first();

                    $categoryId = $category->cat_id;
                }
            }
        }

        if ($keyType == 'brand') {
            $brand = Brand::select('id')->where('brand_slug', $brandSlug)->first();
            $keyWord = $brand->id;
        }
        if ($keyType == 'segment') {
            $segment = Segment::select('id')->where('slug', $segmentSlug)
                ->where('domain', $domainId)
                ->first();
            $keyWord = $segment->id;
        }

        if (isset($categoryId) && $categoryId != null) {

            $query = DB::table('product_table as pt')
                ->select(
                    'm.main_cat_id as main_cat_id',
                    'm.main_cat_name as main_cat_name',
                    'c.cat_name as cat_name',
                    'c.cat_id as cat_id',
                    's.sub_cat_id',
                    's.sub_cat_name',
                    's.sub_cat_index'
                );

            if ($keyType == 'brand') {
                $query->addSelect(DB::raw("'brand' AS key_type, '$keyWord' AS key_word, 'sub-category' AS filter_type, pt.sub_cat_id AS filter_word"));
            } elseif ($keyType == 'segment') {
                $query->addSelect(DB::raw("'segment' AS key_type, '$keyWord' AS key_word, 'sub-category' AS filter_type, pt.sub_cat_id AS filter_word"));
            } else {
                $query->addSelect(DB::raw("'sub-category' AS key_type, pt.sub_cat_id AS key_word, '' AS filter_type, '' AS filter_word"));
            }

            $query->addSelect(DB::raw("0 AS is_selected"))
                ->join(DB::raw("(SELECT qty, product_id FROM stock_table WHERE qty > 0) as st"), 'pt.product_id', '=', 'st.product_id')
                ->leftJoin('sub_cat_table as s', 'pt.sub_cat_id', '=', 's.sub_cat_id')
                ->leftJoin('cat_table as c', 'c.cat_id', '=', 's.cat_id')
                ->leftJoin('main_cat_table as m', 'm.main_cat_id', '=', 'c.main_cat_id')
                ->join(DB::raw("(SELECT id, name FROM brand_table WHERE web_status = '1') AS bd"), 'pt.brand_id', '=', 'bd.id')
                ->where('pt.web_status', '1')
                ->where('c.cat_id', $categoryId)
                ->whereRaw("FIND_IN_SET('$domainId', pt.in_domain)");

            if ($keyType == 'brand') {
                $query->where('pt.brand_id', $keyWord);
            }

            if ($filterType == 'brand') {
                $query->where('pt.brand_id', $filterWord);
            }

            if ($keyType == 'segment') {
                if (isset($rowItemRecordData['dynamic_mysql']) && $rowItemRecordData['dynamic_mysql'] != '') {
                    $query->whereRaw($rowItemRecordData['dynamic_mysql']);
                } else {
                    $query->whereRaw("FIND_IN_SET('$keyWord', pt.in_segment)");
                }
            }

            if ($filterType == 'segment') {
                $query->whereRaw("FIND_IN_SET('$filterWord', pt.in_segment)");
            }

            $query->groupBy('pt.sub_cat_id')
                ->orderBy('s.sub_cat_index', 'ASC');

            $subCatData = $query->get()->toArray();
        }

        $sqlSegments = DB::table('product_table as pt')
            ->select(
                'pt.brand_id',
                'ct.cat_id',
                'ct.cat_name',
                'ct.cat_index',
                'mt.main_cat_id',
                'mt.main_cat_name'
            );

        if ($keyType == 'segment' || $keyType == 'new_segment' || $keyType == 'brand') {
            if ($keyType == 'segment') {
                $segment = Segment::select('id')->where('slug', $segmentSlug)
                    ->where('domain', $domainId)
                    ->first();
                $keyWord = $segment->id;
            }
            $sqlSegments->addSelect(DB::raw("'{$keyType}' AS key_type, '{$keyWord}' AS key_word, 0 AS isSelected, 'category' AS filter_type, ct.cat_id AS filter_word"));
        } else {
            $sqlSegments->addSelect(DB::raw("'category' AS key_type, ct.cat_id AS key_word, 0 AS isSelected, '' AS filter_type, '' AS filter_word"));
        }

        $sqlSegments->join(DB::raw("(SELECT qty, product_id FROM stock_table WHERE qty > 0) AS st"), 'pt.product_id', '=', 'st.product_id')
            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
            ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
            ->join(DB::raw('(SELECT main_cat_id, main_cat_name FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
            ->join(DB::raw("(SELECT id, name FROM brand_table WHERE web_status = '1') AS bd"), 'pt.brand_id', '=', 'bd.id')
            ->where('pt.web_status', '1');

        if ($keyType == 'segment' || $keyType == 'new_segment') {
            $segment = Segment::select('id', 'dynamic_mysql')->where('slug', $segmentSlug)
                ->where('domain', $domainId)
                ->first();
            $keyWord = $segment->id;
            if (isset($segment->dynamic_mysql) && $segment->dynamic_mysql != '') {
                $sqlSegments->whereRaw($segment->dynamic_mysql);
            } else {
                $sqlSegments->whereRaw("FIND_IN_SET('{$keyWord}', pt.in_segment)");
            }
        }

        if (!empty($mainCategoryId) && !in_array($keyType, ['brand', 'offer']) && $filterType != 'brand' && $keyType != 'segment') {
            $sqlSegments->where('mt.main_cat_id', $mainCategoryId);
        }

        if ($keyType == 'segment' && !empty($mainCategoryId)) {
            $sqlSegments->where('mt.main_cat_id', $mainCategoryId);
        }

        if ($filterType == 'brand') {
            $sqlSegments->where('pt.brand_id', $filterWord);
        }

        $sqlSegments->whereRaw("FIND_IN_SET('{$domainId}', pt.in_domain)")
            ->groupBy('ct.cat_id')
            ->orderBy('ct.cat_index', 'ASC');

        $catData = $sqlSegments->get()->toArray();

        //dd($catData);

        //fetching concerns only for skincare
        if (isset($mainCategoryId) && $mainCategoryId == '23') {
            $concernData = Helper::getConcerns()->toArray();
        }

        //fetching collections only for makeup
        if (isset($mainCategoryId) && $mainCategoryId == '24') {
            $collectionData = Helper::getCollections()->toArray();
        }

        switch ($keyType) {
            case 'main-category':

                $mainCategory = MainCategory::where('main_cat_slug', $mainCategorySlug)->first();
                if ($mainCategory) {
                    $keyWord = $mainCategory->main_cat_id;
                    $keyTerm = $mainCategory->main_cat_name;
                    $mainCategoryId = $mainCategory->main_cat_id;
                    $pageName = 'main_category_pg';
                    $breadcrumbs = [
                        ['title' => 'Home', 'url' => route('home')],
                        ['title' => Str::title($keyTerm), 'url' => route('products.main-category', ['mainCategorySlug' => $mainCategorySlug])],
                    ];
                }
                // Fetching product segments
                $rowSegmentsData = DB::table('product_table as pt')
                    ->select(
                        'ct.cat_id',
                        'ct.cat_name',
                        'ct.cat_index',
                        'mt.main_cat_id',
                        'mt.main_cat_name'
                    )
                    ->selectRaw("'category' AS key_type")
                    ->selectRaw("ct.cat_id AS key_word")
                    ->selectRaw("'' AS filter_type")
                    ->selectRaw("'' AS filter_word")
                    ->selectRaw("0 AS isSelected")
                    ->joinSub(function ($query) {
                        $query->select('qty', 'product_id')
                            ->from('stock_table')
                            ->where('qty', '>', 0);
                    }, 'st', function ($join) {
                        $join->on('pt.product_id', '=', 'st.product_id');
                    })
                    ->joinSub(function ($query) {
                        $query->select('id', 'name')
                            ->from('brand_table')
                            ->where('web_status', 1);
                    }, 'bd', function ($join) {
                        $join->on('pt.brand_id', '=', 'bd.id');
                    })
                    ->joinSub(function ($query) {
                        $query->select('sub_cat_id', 'sub_cat_name', 'cat_id')
                            ->from('sub_cat_table');
                    }, 'sc', function ($join) {
                        $join->on('pt.sub_cat_id', '=', 'sc.sub_cat_id');
                    })
                    ->joinSub(function ($query) {
                        $query->select('cat_id', 'cat_name', 'cat_index', 'main_cat_id')
                            ->from('cat_table');
                    }, 'ct', function ($join) {
                        $join->on('sc.cat_id', '=', 'ct.cat_id');
                    })
                    ->joinSub(function ($query) {
                        $query->select('main_cat_id', 'main_cat_name')
                            ->from('main_cat_table');
                    }, 'mt', function ($join) {
                        $join->on('ct.main_cat_id', '=', 'mt.main_cat_id');
                    })
                    ->where('pt.web_status', 1)
                    ->where('mt.main_cat_id', $keyWord)
                    ->whereRaw('FIND_IN_SET(?, pt.in_domain)', [$domainId])
                    ->groupBy('ct.cat_id')
                    ->orderBy('ct.cat_index', 'ASC')
                    ->get();

                if ($rowSegmentsData->isNotEmpty()) {
                    foreach ($rowSegmentsData as $segment) {
                        $rowProductsRecordData = DB::table('product_table as pt')
                            ->select(
                                'pt.product_id',
                                'pt.seo_url',
                                'bt.brand_name',
                                'bt.brand_id',
                                'pt.usages',
                                'sg.id as in_segment',
                                'sg.title as segment_title',
                                'sg.slug as segment_slug',
                                'sg.domain as segment_status',
                                DB::raw("FORMAT(pt.main_price, 0) AS main_price"),
                                DB::raw("FORMAT(MAX(pt.main_price), 0) AS max_price"),
                                DB::raw("FORMAT(MIN(NULLIF(pt.main_price, 0)), 0) AS min_price"),
                                'st.stock',
                                DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) AS unique_key")
                            )
                            ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as bt'), 'pt.brand_id', '=', 'bt.brand_id')
                            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
                            ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
                            ->join(DB::raw('(SELECT main_cat_id, main_cat_name FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
                            ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
                            ->leftJoinSub(function ($query) use ($domainId) {
                                $query->select('*')
                                    ->from('gc_segments')
                                    ->where('status', '=', '1')
                                    ->where('type', '!=', 'bundle')
                                    ->where('is_hidden', '=', '0')
                                    ->where('view_by', '!=', '2')
                                    ->whereRaw('FIND_IN_SET(?, domain)', [$domainId])
                                    ->where(function ($query) {
                                        $query->whereRaw('FIND_IN_SET(1, location)')
                                            ->orWhereRaw('FIND_IN_SET(2, location)');
                                    });
                            }, 'sg', function ($join) {
                                $join->on(DB::raw('FIND_IN_SET(sg.id, pt.in_segment)'), '>', DB::raw('0'));
                            })
                            ->where('pt.web_status', '1')
                            ->where('pt.main_price', '>', 0)
                            ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
                            ->where('mt.main_cat_id', $keyWord)
                            ->where('ct.cat_id', $segment->cat_id)
                            ->whereNotNull('st.stock')
                            ->groupBy('unique_key')
                            ->orderBy('bt.brand_name', 'ASC')
                            ->get();

                        if ($rowProductsRecordData->isNotEmpty()) {
                            foreach ($rowProductsRecordData as $rowProductsRecord) {
                                // Fragtypes
                                if ($mainCategoryId == '10' && $rowProductsRecord->usages != '') {
                                    $fragranceTypesData[] = ['key' => $rowProductsRecord->usages, 'isSelected' => 0];
                                }
                                // Size
//                                if ($mainCategoryId == '10' && $rowProductsRecord->size != '') {
//                                    $sizeData[] = ['key' => $rowProductsRecord->size, 'isSelected' => 0];
//                                }
                                // Price
                                if (!in_array($rowProductsRecord->main_price, $priceData)) {
                                    $priceData[] = (float)str_replace(',', '', $rowProductsRecord->main_price);
                                }
                                // Segment
                                if (!empty($rowProductsRecord->in_segment)) {
                                    $segmentData[] = [
                                        'title' => $rowProductsRecord->segment_title,
                                        'open_slug' => 0,
                                        'slug' => $rowProductsRecord->segment_slug,
                                        'item' => [
                                            'in_segment' => $rowProductsRecord->in_segment,
                                            'in_segment_title' => $rowProductsRecord->segment_title,
                                            'in_segment_slug' => $rowProductsRecord->segment_slug,
                                            'isSelected' => 0
                                        ]
                                    ];
                                }
                                // Brand
                                $brandData[] = [
                                    'brand_id' => $rowProductsRecord->brand_id,
                                    'brand_name' => $rowProductsRecord->brand_name,
                                    'isSelected' => 0
                                ];
                            }
                        }
                    }
                    if (!empty($rowProductsRecordData)) {
                        $bData = array_values(array_unique($brandData, SORT_REGULAR));
                        array_multisort(array_column($bData, 'brand_name'), SORT_ASC, $bData);
                        $sData = array_values(array_unique($segmentData, SORT_REGULAR));
                        $sizeData = array_values(array_unique($sizeData, SORT_REGULAR));
                        $fragranceTypesData = array_values(array_unique($fragranceTypesData, SORT_REGULAR));
                        $productFilterArray = [
                            'category' => $rowSegmentsData->toArray(),
                            'sub_category' => $subCatData,
                            'filter_brand' => $bData,
                            'filter_segment' => $sData,
                            'filter_size' => $sizeData,
                            'filter_fragtype' => $fragranceTypesData,
                            'filter_collection' => $collectionData,
                            'filter_concern' => $concernData,
                            'min_price' => min($priceData),
                            'max_price' => max($priceData),
                        ];
                    }
                }
                break;
            case 'category':
                $mainCategory = MainCategory::where('main_cat_slug', $mainCategorySlug)->first();
                if ($mainCategory) {
                    $keyType = 'category';
                    $keyTerm = '';
                    $mainCategoryId = $mainCategory->main_cat_id;
                    $metaTitle = '';
                    $metaDesc = '';
                    $metaKeywords = '';

                    $category = Category::where('cat_slug', $categorySlug)
                        ->where('main_cat_id', $mainCategory->main_cat_id)
                        ->first();

                    $categoryId = $category->cat_id;
                    if ($category == 'by-concern' && $mainCategory->main_cat_id == 23) {
                        $keyType = 'by-concern';
                        $keyTerm = 'By Concern';
                        $metaTitle = 'By Concern';
                        // Additional logic...
                    } else {
                        $keyWord = $category->cat_id;
                        $keyTerm = $category->cat_name;
                        $metaTitle = $category->meta_title;
                        $metaDesc = $category->meta_desc;
                        $metaKeywords = $category->meta_keywords;
                        // Additional logic...
                    }
                    $pageName = 'category_pg';
                    $breadcrumbs = [
                        ['title' => 'Home', 'url' => route('home')],
                        ['title' => Str::title($mainCategory->main_cat_name), 'url' => route('products.main-category', ['mainCategorySlug' => $mainCategorySlug])],
                        ['title' => Str::title($keyTerm), 'url' => route('products.category', ['mainCategorySlug' => $mainCategorySlug, 'categorySlug' => $categorySlug])],
                    ];

                    $rowProductsRecordData = DB::table('product_table as pt')
                        ->select(
                            'pt.product_id',
                            'pt.seo_url',
                            'bt.brand_name',
                            'bt.brand_id',
                            'pt.usages',
                            'sg.id as in_segment',
                            'sg.title as segment_title',
                            'sg.slug as segment_slug',
                            'sg.domain as segment_status',
                            DB::raw("FORMAT(pt.main_price, 0) AS main_price"),
                            DB::raw("FORMAT(MAX(pt.main_price), 0) AS max_price"),
                            DB::raw("FORMAT(MIN(NULLIF(pt.main_price, 0)), 0) AS min_price"),
                            'st.stock',
                            DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) AS unique_key")
                        )
                        ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as bt'), 'pt.brand_id', '=', 'bt.brand_id')
                        ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
                        ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
                        ->join(DB::raw('(SELECT main_cat_id, main_cat_name FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
                        ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
                        ->leftJoinSub(function ($query) use ($domainId) {
                            $query->select('*')
                                ->from('gc_segments')
                                ->where('status', '=', '1')
                                ->where('type', '!=', 'bundle')
                                ->where('is_hidden', '=', '0')
                                ->where('view_by', '!=', '2')
                                ->whereRaw('FIND_IN_SET(?, domain)', [$domainId])
                                ->where(function ($query) {
                                    $query->whereRaw('FIND_IN_SET(1, location)')
                                        ->orWhereRaw('FIND_IN_SET(2, location)');
                                });
                        }, 'sg', function ($join) {
                            $join->on(DB::raw('FIND_IN_SET(sg.id, pt.in_segment)'), '>', DB::raw('0'));
                        })
                        ->where('pt.web_status', '1')
                        ->where('pt.main_price', '>', 0)
                        ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
                        ->where('ct.cat_id', $categoryId)
                        ->whereNotNull('st.stock')
                        ->groupBy('unique_key')
                        ->orderBy('pt.main_price', 'ASC')
                        ->get();


                    if ($rowProductsRecordData->isNotEmpty()) {
                        foreach ($rowProductsRecordData as $rowProductsRecord) {
                            // Fragtypes
                            if ($mainCategoryId == '10' && $rowProductsRecord->usages != '') {
                                $fragranceTypesData[] = ['key' => $rowProductsRecord->usages, 'isSelected' => 0];
                            }
                            // Size
//                            if ($mainCategoryId == '10' && $rowProductsRecord->size != '') {
//                                $sizeData[] = ['key' => $rowProductsRecord->size, 'isSelected' => 0];
//                            }
                            // Price
                            if (!in_array($rowProductsRecord->main_price, $priceData)) {
                                $priceData[] = (float)str_replace(',', '', $rowProductsRecord->main_price);
                            }
                            // Segment
                            if (!empty($rowProductsRecord->in_segment)) {
                                $segmentData[] = [
                                    'title' => $rowProductsRecord->segment_title,
                                    'open_slug' => 0,
                                    'slug' => $rowProductsRecord->segment_slug,
                                    'item' => [
                                        'in_segment' => $rowProductsRecord->in_segment,
                                        'in_segment_title' => $rowProductsRecord->segment_title,
                                        'in_segment_slug' => $rowProductsRecord->segment_slug,
                                        'isSelected' => 0
                                    ]
                                ];
                            }
                            // Brand
                            $brandData[] = [
                                'brand_id' => $rowProductsRecord->brand_id,
                                'brand_name' => $rowProductsRecord->brand_name,
                                'isSelected' => 0
                            ];
                        }

                        $bData = array_values(array_unique($brandData, SORT_REGULAR));
                        array_multisort(array_column($bData, 'brand_name'), SORT_ASC, $bData);
                        $sData = array_values(array_unique($segmentData, SORT_REGULAR));
                        $sizeData = array_values(array_unique($sizeData, SORT_REGULAR));
                        $fragranceTypesData = array_values(array_unique($fragranceTypesData, SORT_REGULAR));

                        $productFilterArray = [
                            "category" => [],
                            "sub_category" => $subCatData,
                            "filter_brand" => $bData,
                            "filter_segment" => $sData,
                            "filter_size" => $sizeData,
                            "filter_fragtype" => $fragranceTypesData,
                            "filter_collection" => $collectionData,
                            "filter_concern" => $concernData,
                            "min_price" => min($priceData),
                            "max_price" => max($priceData),
                        ];
                    }
                }

                break;
            case 'sub-category':
                $mainCategory = MainCategory::where('main_cat_slug', $mainCategorySlug)->first();
                if ($mainCategory) {
                    $keyType = 'sub-category';
                    $keyWord = '';
                    $keyTerm = '';
                    $mainCategoryId = $mainCategory->main_cat_id;
                    $metaTitle = '';
                    $metaDesc = '';
                    $metaKeywords = '';
                    $catName = '';
                    $parentCatId = '';

                    $category = Category::where('cat_slug', $categorySlug)
                        ->where('main_cat_id', $mainCategory->main_cat_id)
                        ->first();

                    if ($categorySlug == 'by-concern' && $mainCategory->main_cat_id == 23) {
                        $concern = Concern::where('slug', $subCategorySlug)->first();

                        $keyType = 'by-concern';
                        $keyWord = $concern->id;
                        $keyTerm = $concern->title;
                        $metaTitle = $concern->title;
                        $metaDesc = $concern->title;
                        $catName = 'By Concern';
                    } else {
                        $subCategory = SubCategory::where('sub_cat_slug', $subCategorySlug)
                            ->where('cat_id', $category->cat_id)
                            ->first();

                        $keyWord = $subCategory->sub_cat_id;
                        $keyTerm = $subCategory->sub_cat_name;
                        $metaTitle = $subCategory->meta_title;
                        $metaDesc = $subCategory->meta_desc;
                        $metaKeywords = $subCategory->meta_keywords;
                        $catName = $category->cat_name;
                        $parentCatId = $category->cat_id;
                    }

                    $breadcrumbs = [
                        ['title' => 'Home', 'url' => route('home')],
                        ['title' => Str::title($mainCategory->main_cat_name), 'url' => route('products.main-category', ['mainCategorySlug' => $mainCategorySlug])],
                        ['title' => Str::title($category->cat_name), 'url' => route('products.category', ['mainCategorySlug' => $mainCategorySlug, 'categorySlug' => $categorySlug])],
                        ['title' => Str::title($keyTerm), 'url' => route('products.sub-category', ['mainCategorySlug' => $mainCategorySlug, 'categorySlug' => $categorySlug, 'subCategorySlug' => $subCategorySlug])],
                    ];

                    $rowProductsRecordData = DB::table('product_table as pt')
                        ->select(
                            'pt.web_status',
                            'pt.in_domain',
                            'pt.product_id',
                            'pt.seo_url',
                            'bt.brand_name',
                            'bt.brand_id',
                            'pt.usages',
                            'sg.id as in_segment',
                            'sg.title as segment_title',
                            'sg.slug as segment_slug',
                            'sg.domain as segment_status',
                            DB::raw("FORMAT(pt.main_price, 0) AS main_price"),
                            DB::raw("FORMAT(MAX(pt.main_price), 0) AS max_price"),
                            DB::raw("FORMAT(MIN(NULLIF(pt.main_price, 0)), 0) AS min_price"),
                            'st.stock',
                            DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) AS unique_key")
                        )
                        ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as bt'), 'pt.brand_id', '=', 'bt.brand_id')
                        ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
                        ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
                        ->join(DB::raw('(SELECT main_cat_id, main_cat_name FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
                        ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
                        ->leftJoinSub(function ($query) use ($domainId) {
                            $query->select('*')
                                ->from('gc_segments')
                                ->where('status', '=', '1')
                                ->where('type', '!=', 'bundle')
                                ->where('is_hidden', '=', '0')
                                ->where('view_by', '!=', '2')
                                ->whereRaw('FIND_IN_SET(?, domain)', [$domainId])
                                ->where(function ($query) {
                                    $query->whereRaw('FIND_IN_SET(1, location)')
                                        ->orWhereRaw('FIND_IN_SET(2, location)');
                                });
                        }, 'sg', function ($join) {
                            $join->on(DB::raw('FIND_IN_SET(sg.id, pt.in_segment)'), '>', DB::raw('0'));
                        })
                        ->where('pt.web_status', '1')
                        ->where('pt.main_price', '>', 0)
                        ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
                        ->where('pt.sub_cat_id', $keyWord)
                        ->whereNotNull('st.stock')
                        ->groupBy('unique_key')
                        ->orderBy('pt.main_price', 'ASC')
                        ->get();
                    //dd($rowProductsRecordData);
                    if ($rowProductsRecordData->isNotEmpty()) {
                        foreach ($rowProductsRecordData as $rowProductsRecord) {
                            // Fragtypes
                            if ($mainCategoryId == '10' && $rowProductsRecord->usages != '') {
                                $fragranceTypesData[] = ['key' => $rowProductsRecord->usages, 'isSelected' => 0];
                            }
                            // Size
//                            if ($mainCategoryId == '10' && $rowProductsRecord->size != '') {
//                                $sizeData[] = ['key' => $rowProductsRecord->size, 'isSelected' => 0];
//                            }
                            // Price
                            if (!in_array($rowProductsRecord->main_price, $priceData)) {
                                $priceData[] = (float)str_replace(',', '', $rowProductsRecord->main_price);
                            }
                            // Segment
                            if (!empty($rowProductsRecord->in_segment)) {
                                $segmentData[] = [
                                    'title' => $rowProductsRecord->segment_title,
                                    'open_slug' => 0,
                                    'slug' => $rowProductsRecord->segment_slug,
                                    'item' => [
                                        'in_segment' => $rowProductsRecord->in_segment,
                                        'in_segment_title' => $rowProductsRecord->segment_title,
                                        'in_segment_slug' => $rowProductsRecord->segment_slug,
                                        'isSelected' => 0
                                    ]
                                ];
                            }
                            // Brand
                            $brandData[] = [
                                'brand_id' => $rowProductsRecord->brand_id,
                                'brand_name' => $rowProductsRecord->brand_name,
                                'isSelected' => 0
                            ];
                        }

                        $bData = array_values(array_unique($brandData, SORT_REGULAR));
                        array_multisort(array_column($bData, 'brand_name'), SORT_ASC, $bData);
                        $sData = array_values(array_unique($segmentData, SORT_REGULAR));
                        $sizeData = array_values(array_unique($sizeData, SORT_REGULAR));
                        $fragranceTypesData = array_values(array_unique($fragranceTypesData, SORT_REGULAR));

                        $productFilterArray = [
                            "category" => [],
                            "sub_category" => [],
                            "filter_brand" => $bData,
                            "filter_segment" => $sData,
                            "filter_size" => $sizeData,
                            "filter_fragtype" => $fragranceTypesData,
                            "filter_collection" => $collectionData,
                            "filter_concern" => $concernData,
                            "min_price" => min($priceData),
                            "max_price" => max($priceData),
                        ];
                    }
                    $pageName = 'sub_category_pg';
                }
                break;
            case 'by-concern':
                $keyType = 'by-concern';
                $keyWord = '';
                $keyTerm = 'By concern';
                $mainCategoryId = 23;
                $concern = Concern::where('slug', $concernSlug)
                    ->where('status', '1')
                    ->first();

                if ($concern) {
                    $keyType = 'by-concern';
                    $keyWord = $concern->id;
                    $keyTerm = 'By concern / ' . $concern->title;
                    $mainCategoryId = 23;
                }
                $priceData = [1, 1000];
                $productFilterArray = [
                    "category" => [],
                    "sub_category" => [],
                    "filter_brand" => $bData,
                    "filter_segment" => $sData,
                    "filter_size" => $sizeData,
                    "filter_fragtype" => $fragranceTypesData,
                    "filter_collection" => $collectionData,
                    "filter_concern" => $concernData,
                    "min_price" => min($priceData),
                    "max_price" => max($priceData),
                ];
                break;
            case 'collection':
                $collection = Collection::where('slug', $collectionSlug)
                    ->where('status', 1)
                    ->first();

                if ($collection) {
                    $keyType = 'collection';
                    $keyWord = $collection->id;
                    $keyTerm = 'Make Up';
                    $mainCategoryId = 24;
                }
                $priceData = [1, 1000];
                $productFilterArray = [
                    "category" => [],
                    "sub_category" => [],
                    "filter_brand" => $bData,
                    "filter_segment" => $sData,
                    "filter_size" => $sizeData,
                    "filter_fragtype" => $fragranceTypesData,
                    "filter_collection" => $collectionData,
                    "filter_concern" => $concernData,
                    "min_price" => min($priceData),
                    "max_price" => max($priceData),
                ];
                break;
            case 'segment':
                //dd($brandData);
                $segment = Segment::where('slug', $segmentSlug)
                    ->where('domain', $domainId)
                    ->first();

                if ($segment) {
                    $keyType = 'segment';
                    $keyWord = $segment->id;
                    $keyTerm = $segment->slug == 'new-arrival' ? 'WHAT\'S NEW' : $segment->title;
                    $mainCategoryId = '';
                    $filterType = '';
                    $filterWord = '';
                    $segmentUrl = in_array($segment->slug, ['new-arrival', 'best-seller']) ? route('products.' . $segment->slug) : route('products.shop', ['segmentSlug' => $segment->slug]);
                    $breadcrumbs = [
                        ['title' => 'Home', 'url' => route('home')],
                        ['title' => Str::title($segment->title), 'url' => $segmentUrl],
                    ];
                    if ($mainCategorySlug != '') {
                        $mainCategory = MainCategory::where('main_cat_slug', $mainCategorySlug)->first();
                        $filterType = 'main-category';
                        $filterWord = $mainCategory->main_cat_id;
                        $mainCategoryId = $mainCategory->main_cat_id;
                        $breadcrumbs = [
                            ['title' => 'Home', 'url' => route('home')],
                            ['title' => Str::title($segment->title), 'url' => route('products.' . $segment->slug)],
                            ['title' => Str::title($mainCategory->main_cat_name), 'url' => route('products.' . $segment->slug . '.main-category', ['mainCategorySlug' => $mainCategorySlug])],
                        ];
                    }
                    if ($categorySlug != '') {
                        $catData = [];
                        $category = Category::where('cat_slug', $categorySlug)->first();
                        $filterType = 'category';
                        $filterWord = $category->cat_id;
                        $breadcrumbs = [
                            ['title' => 'Home', 'url' => route('home')],
                            ['title' => Str::title($segment->title), 'url' => route('products.' . $segment->slug)],
                            ['title' => Str::title($mainCategory->main_cat_name), 'url' => route('products.' . $segment->slug . '.main-category', ['mainCategorySlug' => $mainCategorySlug])],
                            ['title' => Str::title($category->cat_name), 'url' => route('products.' . $segment->slug . '.category', ['mainCategorySlug' => $mainCategorySlug, 'categorySlug' => $categorySlug])],
                        ];
                    }
                    if ($subCategorySlug != '') {
                        $subCategory = SubCategory::where('sub_cat_slug', $subCategorySlug)
                            ->first();
                        $filterType = 'category';
                        $filterWord = $subCategory->sub_cat_id;
                        $breadcrumbs = [
                            ['title' => 'Home', 'url' => route('home')],
                            ['title' => Str::title($segment->title), 'url' => route('products.' . $segment->slug)],
                            ['title' => Str::title($mainCategory->main_cat_name), 'url' => route('products.' . $segment->slug . '.main-category', ['mainCategorySlug' => $mainCategorySlug])],
                            ['title' => Str::title($category->cat_name), 'url' => route('products.' . $segment->slug . '.category', ['mainCategorySlug' => $mainCategorySlug, 'categorySlug' => $categorySlug])],
                            ['title' => Str::title($subCategory->sub_cat_name), 'url' => route('products.' . $segment->slug . '.category', ['mainCategorySlug' => $mainCategorySlug, 'categorySlug' => $categorySlug, 'subCategorySlug' => $subCategorySlug])],
                        ];
                    }

                    $qryProductsRecordData = DB::table('product_table as pt')
                        ->select(
                            'pt.product_id',
                            'pt.seo_url',
                            'bt.brand_name',
                            'bt.brand_id',
                            'pt.usages',
                            'sg.id as in_segment',
                            'sg.title as segment_title',
                            'sg.slug as segment_slug',
                            'sg.domain as segment_status',
                            'ct.cat_id',
                            'ct.cat_name',
                            'ct.cat_slug',
                            'sct.sub_cat_id',
                            'sct.sub_cat_name',
                            'sct.sub_cat_slug',
                            DB::raw("FORMAT(pt.main_price, 0) AS main_price"),
                            DB::raw("FORMAT(MAX(pt.main_price), 0) AS max_price"),
                            DB::raw("FORMAT(MIN(NULLIF(pt.main_price, 0)), 0) AS min_price"),
                            'st.stock',
                            DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) AS unique_key")
                        )
                        ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as bt'), 'pt.brand_id', '=', 'bt.brand_id')
                        ->join(DB::raw('(SELECT sub_cat_id, sub_cat_slug, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
                        ->join(DB::raw('(SELECT cat_id, cat_slug, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
                        ->join(DB::raw('(SELECT main_cat_id, main_cat_slug, main_cat_name FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
                        ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
                        ->leftJoinSub(function ($query) use ($domainId) {
                            $query->select('*')
                                ->from('gc_segments')
                                ->where('status', '=', '1')
                                ->where('type', '!=', 'bundle')
                                ->where('is_hidden', '=', '0')
                                ->where('view_by', '!=', '2')
                                ->whereRaw('FIND_IN_SET(?, domain)', [$domainId])
                                ->where(function ($query) {
                                    $query->whereRaw('FIND_IN_SET(1, location)')
                                        ->orWhereRaw('FIND_IN_SET(2, location)');
                                });
                        }, 'sg', function ($join) {
                            $join->on(DB::raw('FIND_IN_SET(sg.id, pt.in_segment)'), '>', DB::raw('0'));
                        });
                    if (isset($segment->dynamic_mysql) && $segment->dynamic_mysql != '') {
                        $qryProductsRecordData->whereRaw($segment->dynamic_mysql);
                    } else {
                        $qryProductsRecordData->whereRaw("FIND_IN_SET('{$keyWord}', pt.in_segment)");
                    }
                    $qryProductsRecordData->where('pt.web_status', '1')
                        ->where('pt.main_price', '>', 0)
                        ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
                        ->whereNotNull('st.stock');
                    switch ($filterType) {
                        case 'main-category':
                            $mainCategoryId = $filterWord;
                            $subCategoryIds = SubCategory::whereIn('cat_id', function ($query) use ($mainCategoryId) {
                                $query->select('cat_id')
                                    ->from('cat_table')
                                    ->where('main_cat_id', $mainCategoryId);
                            })->pluck('sub_cat_id');
                            $qryProductsRecordData->whereIn('pt.sub_cat_id', $subCategoryIds);
                            break;
                        case 'category':
                            $subCategoryIds = SubCategory::where('cat_id', $filterWord)->pluck('sub_cat_id');
                            $qryProductsRecordData->whereIn('pt.sub_cat_id', $subCategoryIds);
                            break;
                        case 'sub-category':
                            $qryProductsRecordData->where('pt.sub_cat_id', '=', $filterWord);
                            break;
                        default:
                            break;
                    }
                    $qryProductsRecordData->groupBy('unique_key')->orderBy('pt.main_price', 'ASC');
                    $rowProductsRecordData = $qryProductsRecordData->get();
                    if ($rowProductsRecordData->isNotEmpty()) {
                        foreach ($rowProductsRecordData as $rowProductsRecord) {
                            // Fragtypes
                            if ($mainCategoryId == '10' && $rowProductsRecord->usages != '') {
                                $fragranceTypesData[] = ['key' => $rowProductsRecord->usages, 'isSelected' => 0];
                            }
                            // Size
//                            if ($mainCategoryId == '10' && $rowProductsRecord->size != '') {
//                                $sizeData[] = ['key' => $rowProductsRecord->size, 'isSelected' => 0];
//                            }
                            // Price
                            if (!in_array($rowProductsRecord->main_price, $priceData)) {
                                $priceData[] = (float)str_replace(',', '', $rowProductsRecord->main_price);
                            }
                            // Segment
                            if (!empty($rowProductsRecord->in_segment)) {
                                $segmentData[] = [
                                    'title' => $rowProductsRecord->segment_title,
                                    'open_slug' => 0,
                                    'slug' => $rowProductsRecord->segment_slug,
                                    'item' => [
                                        'in_segment' => $rowProductsRecord->in_segment,
                                        'in_segment_title' => $rowProductsRecord->segment_title,
                                        'in_segment_slug' => $rowProductsRecord->segment_slug,
                                        'isSelected' => 0
                                    ]
                                ];
                            }
                            // Brand
                            $brandData[] = [
                                'brand_id' => $rowProductsRecord->brand_id,
                                'brand_name' => $rowProductsRecord->brand_name,
                                'isSelected' => 0
                            ];
                        }
//                        echo '<pre>';
//                        print_r($brandData);
//                        exit;

                        $bData = array_values(array_unique($brandData, SORT_REGULAR));
                        array_multisort(array_column($bData, 'brand_name'), SORT_ASC, $bData);
                        $sData = array_values(array_unique($segmentData, SORT_REGULAR));
                        $sizeData = array_values(array_unique($sizeData, SORT_REGULAR));
                        $fragranceTypesData = array_values(array_unique($fragranceTypesData, SORT_REGULAR));

                        $productFilterArray = [
                            "category" => $catData,
                            "sub_category" => $subCatData,
                            "filter_brand" => $bData,
                            "filter_segment" => [],
                            "filter_size" => $sizeData,
                            "filter_fragtype" => $fragranceTypesData,
                            "filter_collection" => $collectionData,
                            "filter_concern" => $concernData,
                            "min_price" => min($priceData),
                            "max_price" => max($priceData),
                        ];
                    }
                }
                break;
            case 'brand':
                $brand = Brand::where('brand_slug', $brandSlug)->first();
                if ($brand) {
                    $mainCategoryData = DB::table('product_table as pt')
                        ->select(
                            'mt.main_cat_id'
                        )
                        ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as bt'), 'pt.brand_id', '=', 'bt.brand_id')
                        ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
                        ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
                        ->join(DB::raw('(SELECT main_cat_id, main_cat_name FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
                        ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
                        ->where('pt.web_status', '1')
                        ->where('pt.main_price', '>', 0)
                        ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
                        ->where('pt.brand_id', $keyWord)
                        ->whereNotNull('st.stock')
                        ->groupBy('mt.main_cat_id')
                        ->orderBy('pt.main_price', 'ASC')
                        ->first();
                    $keyType = 'brand';
                    $keyWord = $brand->id;
                    $keyTerm = $brand->name ?? ' & More';
                    $mainCategoryId = $mainCategoryData->main_cat_id;
                    $brandFilter = $brand->id;
                }
                $rowProductsRecordData = DB::table('product_table as pt')
                    ->select(
                        'mt.main_cat_id',
                        'pt.product_id',
                        'pt.seo_url',
                        'bt.brand_name',
                        'bt.brand_id',
                        'pt.usages',
                        'sg.id as in_segment',
                        'sg.title as segment_title',
                        'sg.slug as segment_slug',
                        'sg.domain as segment_status',
                        DB::raw("FORMAT(pt.main_price, 0) AS main_price"),
                        DB::raw("FORMAT(MAX(pt.main_price), 0) AS max_price"),
                        DB::raw("FORMAT(MIN(NULLIF(pt.main_price, 0)), 0) AS min_price"),
                        'st.stock',
                        DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) AS unique_key")
                    )
                    ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as bt'), 'pt.brand_id', '=', 'bt.brand_id')
                    ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
                    ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
                    ->join(DB::raw('(SELECT main_cat_id, main_cat_name FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
                    ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
                    ->leftJoinSub(function ($query) use ($domainId) {
                        $query->select('*')
                            ->from('gc_segments')
                            ->where('status', '=', '1')
                            ->where('type', '!=', 'bundle')
                            ->where('is_hidden', '=', '0')
                            ->where('view_by', '!=', '2')
                            ->whereRaw('FIND_IN_SET(?, domain)', [$domainId])
                            ->where(function ($query) {
                                $query->whereRaw('FIND_IN_SET(1, location)')
                                    ->orWhereRaw('FIND_IN_SET(2, location)');
                            });
                    }, 'sg', function ($join) {
                        $join->on(DB::raw('FIND_IN_SET(sg.id, pt.in_segment)'), '>', DB::raw('0'));
                    })
                    ->where('pt.web_status', '1')
                    ->where('pt.main_price', '>', 0)
                    ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
                    ->where('pt.brand_id', $keyWord)
                    ->whereNotNull('st.stock')
                    ->groupBy('unique_key')
                    ->orderBy('pt.main_price', 'ASC')
                    ->get();

                if ($rowProductsRecordData->isNotEmpty()) {
                    foreach ($rowProductsRecordData as $rowProductsRecord) {
                        // Fragtypes
                        if ($rowProductsRecord->main_cat_id == '10' && $rowProductsRecord->usages != '') {
                            $fragranceTypesData[] = ['key' => $rowProductsRecord->usages, 'isSelected' => 0];
                        }
                        // Size
//                            if ($mainCategoryId == '10' && $rowProductsRecord->size != '') {
//                                $sizeData[] = ['key' => $rowProductsRecord->size, 'isSelected' => 0];
//                            }
                        // Price
                        if (!in_array($rowProductsRecord->main_price, $priceData)) {
                            $priceData[] = (float)str_replace(',', '', $rowProductsRecord->main_price);
                        }
                        // Segment
                        if (!empty($rowProductsRecord->in_segment)) {
                            $segmentData[] = [
                                'title' => $rowProductsRecord->segment_title,
                                'open_slug' => 0,
                                'slug' => $rowProductsRecord->segment_slug,
                                'item' => [
                                    'in_segment' => $rowProductsRecord->in_segment,
                                    'in_segment_title' => $rowProductsRecord->segment_title,
                                    'in_segment_slug' => $rowProductsRecord->segment_slug,
                                    'isSelected' => 0
                                ]
                            ];
                        }
                        // Brand
                        $brandData[] = [
                            'brand_id' => $rowProductsRecord->brand_id,
                            'brand_name' => $rowProductsRecord->brand_name,
                            'isSelected' => 0
                        ];
                    }

                    $rowSegmentsData = DB::table('product_table as pt')
                        ->select(
                            'ct.cat_id',
                            'ct.cat_name',
                            'ct.cat_index',
                            'mt.main_cat_id',
                            'mt.main_cat_name'
                        )
                        ->selectRaw("'brand' AS key_type")
                        ->selectRaw("'{$keyWord}' AS key_word")
                        ->selectRaw("'category' AS filter_type")
                        ->selectRaw("'ct.cat_id' AS filter_word")
                        ->selectRaw("0 AS isSelected")
                        ->joinSub(function ($query) {
                            $query->select('qty', 'product_id')
                                ->from('stock_table')
                                ->where('qty', '>', 0);
                        }, 'st', function ($join) {
                            $join->on('pt.product_id', '=', 'st.product_id');
                        })
                        ->joinSub(function ($query) {
                            $query->select('id', 'name')
                                ->from('brand_table')
                                ->where('web_status', 1);
                        }, 'bd', function ($join) {
                            $join->on('pt.brand_id', '=', 'bd.id');
                        })
                        ->joinSub(function ($query) {
                            $query->select('sub_cat_id', 'sub_cat_name', 'cat_id')
                                ->from('sub_cat_table');
                        }, 'sc', function ($join) {
                            $join->on('pt.sub_cat_id', '=', 'sc.sub_cat_id');
                        })
                        ->joinSub(function ($query) {
                            $query->select('cat_id', 'cat_name', 'cat_index', 'main_cat_id')
                                ->from('cat_table');
                        }, 'ct', function ($join) {
                            $join->on('sc.cat_id', '=', 'ct.cat_id');
                        })
                        ->joinSub(function ($query) {
                            $query->select('main_cat_id', 'main_cat_name')
                                ->from('main_cat_table');
                        }, 'mt', function ($join) {
                            $join->on('ct.main_cat_id', '=', 'mt.main_cat_id');
                        })
                        ->where('pt.web_status', '1')
                        ->where('pt.brand_id', $keyWord)
                        ->whereRaw('FIND_IN_SET(?, pt.in_domain)', [$domainId])
                        ->groupBy('ct.cat_id')
                        ->orderBy('ct.cat_index', 'ASC')
                        ->get();

                    $bData = array_values(array_unique($brandData, SORT_REGULAR));
                    array_multisort(array_column($bData, 'brand_name'), SORT_ASC, $bData);
                    $sData = array_values(array_unique($segmentData, SORT_REGULAR));
                    $sizeData = array_values(array_unique($sizeData, SORT_REGULAR));
                    $fragranceTypesData = array_values(array_unique($fragranceTypesData, SORT_REGULAR));

                    $productFilterArray = [
                        'category' => $rowSegmentsData->toArray(),
                        'sub_category' => $subCatData,
                        "filter_brand" => $bData,
                        "filter_segment" => $sData,
                        "filter_size" => $sizeData,
                        "filter_fragtype" => $fragranceTypesData,
                        "filter_collection" => $collectionData,
                        "filter_concern" => $concernData,
                        "min_price" => min($priceData),
                        "max_price" => max($priceData),
                    ];
                }
                break;
            case 'search':
                $keyWord = $searchSlug;
                $keyTerm = 'Search for `' . $keyWord . '`';
                $mainCategoryId = '';
                $brandFilter = '';
                $qryProductsRecordData = DB::table('product_table as pt')
                    ->select(
                        'pt.product_id',
                        'pt.seo_url',
                        'bt.brand_name',
                        'bt.brand_id',
                        'pt.usages',
                        'sg.id as in_segment',
                        'sg.title as segment_title',
                        'sg.slug as segment_slug',
                        'sg.domain as segment_status',
                        'mt.main_cat_id',
                        'mt.main_cat_name',
                        'mt.main_cat_slug',
                        'ct.cat_id',
                        'ct.cat_name',
                        'ct.cat_slug',
                        'sct.sub_cat_id',
                        'sct.sub_cat_name',
                        'sct.sub_cat_slug',
                        DB::raw("FORMAT(pt.main_price, 0) AS main_price"),
                        DB::raw("FORMAT(MAX(pt.main_price), 0) AS max_price"),
                        DB::raw("FORMAT(MIN(NULLIF(pt.main_price, 0)), 0) AS min_price"),
                        'st.stock',
                        DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) AS unique_key")
                    )
                    ->leftJoin(DB::raw('(SELECT family_id, family_name, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
                    ->leftJoin(DB::raw('(SELECT family_id,aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
                    ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as bt'), 'pt.brand_id', '=', 'bt.brand_id')
                    ->join(DB::raw('(SELECT sub_cat_id, sub_cat_slug, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
                    ->join(DB::raw('(SELECT cat_id, cat_slug, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
                    ->join(DB::raw('(SELECT main_cat_id, main_cat_slug, main_cat_name FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
                    ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
                    ->leftJoinSub(function ($query) use ($domainId) {
                        $query->select('*')
                            ->from('gc_segments')
                            ->where('status', '=', '1')
                            ->where('type', '!=', 'bundle')
                            ->where('is_hidden', '=', '0')
                            ->where('view_by', '!=', '2')
                            ->whereRaw('FIND_IN_SET(?, domain)', [$domainId])
                            ->where(function ($query) {
                                $query->whereRaw('FIND_IN_SET(1, location)')
                                    ->orWhereRaw('FIND_IN_SET(2, location)');
                            });
                    }, 'sg', function ($join) {
                        $join->on(DB::raw('FIND_IN_SET(sg.id, pt.in_segment)'), '>', DB::raw('0'));
                    });
                $searchRecord = clone $qryProductsRecordData;
                $searchSoundexRecord = clone $qryProductsRecordData;
                $requestArray = explode(' ', $keyWord);

                if (!empty($requestArray)) {
                    foreach ($requestArray as $v) {
                        if (ctype_alpha($v)) {
                            $searchRecord->whereRaw("LOCATE('" . strtolower($v) . "',
                        LOWER(
                            CONCAT(
                                ' ',
                                bt.brand_name,
                                ' ',
                                IF(pt.type_flag = '2', pt.fam_name, f.family_name),
                                ' ',
                                mt.main_cat_name,
                                ' ',
                                ct.cat_name,
                                ' ',
                                sct.sub_cat_name,
                                ' ',
                                pt.Ref_no,
                                ' ',
                                pt.barcode,
                                ' '
                            )
                        )
                    ) > 0");
                        } else {
                            $searchRecord->whereRaw("LOCATE('" . strtolower($v) . "',
                        LOWER(
                            CONCAT(
                                ' ',
                                pt.Ref_no,
                                ' ',
                                pt.barcode,
                                ' '
                            )
                        )
                    ) > 0");
                        }
                    }
                }

//                $searchRecord->whereNotNull('st.stock')
//                    ->groupBy(DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr)"))
//                    ->orderBy('br.brand_name', 'ASC')
//                    ->orderBy((DB::raw("IF(pt.type_flag = '2', pt.fam_name, f.family_name)")), 'ASC');

                $numSearch = $searchRecord->count();

                if ($numSearch === 0) {
                    $searchSoundexRecord->where(function ($query) use ($requestArray) {
                        foreach ($requestArray as $v) {
                            if (!is_numeric($v)) {
                                $query->orWhere(function ($query) use ($v) {
                                    $query->where('bt.brand_name', 'SOUNDS LIKE', $v)
                                        ->orWhereRaw("SOUNDEX(IF(pt.type_flag = '2', pt.fam_name, f.family_name)) LIKE CONCAT(TRIM(TRAILING '0' FROM SOUNDEX('" . $v . "')),'%')");
                                });
                            }
                        }
                    });

                    $qryProductsRecordData = clone $searchSoundexRecord;
                } else {
                    $qryProductsRecordData = clone $searchRecord;
                }
                $qryProductsRecordData->where('pt.web_status', '1')
                    ->where('pt.main_price', '>', 0)
                    ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
                    ->whereNotNull('st.stock')
                    ->groupBy('unique_key')
                    ->orderBy('pt.main_price', 'ASC');
                $rowProductsRecordData = $qryProductsRecordData->get();
                $catData = [];
                $subCatData = [];
                $segmentData = [];

                if ($rowProductsRecordData->isNotEmpty()) {
                    foreach ($rowProductsRecordData as $rowProductsRecord) {
                        // Fragtypes
                        if ($mainCategoryId == '10' && $rowProductsRecord->usages != '') {
                            $fragranceTypesData[] = ['key' => $rowProductsRecord->usages, 'isSelected' => 0];
                        }
                        // Size
//                            if ($mainCategoryId == '10' && $rowProductsRecord->size != '') {
//                                $sizeData[] = ['key' => $rowProductsRecord->size, 'isSelected' => 0];
//                            }
                        // Price
                        if (!in_array($rowProductsRecord->main_price, $priceData)) {
                            $priceData[] = (float)str_replace(',', '', $rowProductsRecord->main_price);
                        }
                        // Category
                        if (!empty($rowProductsRecord->in_segment)) {
                            $catData[] = (object)[
                                'cat_id' => $rowProductsRecord->cat_id,
                                'cat_name' => $rowProductsRecord->cat_name,
                                'main_cat_id' => $rowProductsRecord->main_cat_id,
                                'main_cat_name' => $rowProductsRecord->main_cat_name,
                                'key_type' => 'search',
                                'key_word' => $keyWord,
                                "isSelected" => 0,
                                "filter_type" => "category",
                                "filter_word" => $rowProductsRecord->cat_id,
                            ];
                        }

                        // Segment
                        if (!empty($rowProductsRecord->in_segment)) {
                            $segmentData[] = [
                                'title' => $rowProductsRecord->segment_title,
                                'open_slug' => 0,
                                'slug' => $rowProductsRecord->segment_slug,
                                'item' => [
                                    'in_segment' => $rowProductsRecord->in_segment,
                                    'in_segment_title' => $rowProductsRecord->segment_title,
                                    'in_segment_slug' => $rowProductsRecord->segment_slug,
                                    'isSelected' => 0
                                ]
                            ];
                        }
                        // Brand
                        $brandData[] = [
                            'brand_id' => $rowProductsRecord->brand_id,
                            'brand_name' => $rowProductsRecord->brand_name,
                            'isSelected' => 0
                        ];
                    }
                }
                $bData = array_values(array_unique($brandData, SORT_REGULAR));
                array_multisort(array_column($bData, 'brand_name'), SORT_ASC, $bData);
                $cData = array_values(array_unique(($catData), SORT_REGULAR));
                $sData = array_values(array_unique($segmentData, SORT_REGULAR));
                $sizeData = array_values(array_unique($sizeData, SORT_REGULAR));
                $fragranceTypesData = array_values(array_unique($fragranceTypesData, SORT_REGULAR));

                $productFilterArray = [
                    "category" => $cData,
                    "sub_category" => $subCatData,
                    "filter_brand" => $bData,
                    "filter_segment" => $sData,
                    "filter_size" => $sizeData,
                    "filter_fragtype" => $fragranceTypesData,
                    "filter_collection" => $collectionData,
                    "filter_concern" => $concernData,
                    "min_price" => !empty($priceData) ? min($priceData) : 0,
                    "max_price" => !empty($priceData) ? max($priceData) : 0,
                ];
                break;
            case 'offer':
                $distributors = [];
                $brands = [];
                $mCategories = [];
                $categories = [];
                $subCategories = [];
                $flags = [];
                $products = [];
                $qryOffersRecord = Offer::where('status', '=', '1')
                    ->where('show_status', '=', '1')
                    ->whereRaw("FIND_IN_SET(?, in_domain)", [$domainId]);
                if ($offerSlug != '') {
                    $qryOffersRecord->where('offer_slug', '=', $offerSlug);
                }
                $qryOffersRecord->orderBy('offer_slug', 'ASC');

                $rowOffersRecord = $qryOffersRecord->get();

                $keyWord = $offerSlug != '' ? $rowOffersRecord->first()->offer_slug : '';
                $keyTerm = $offerSlug != '' ? 'Offer ' . $rowOffersRecord->first()->offer_desc : 'Offer';
                if ($mainCategorySlug != '') {
                    $mainCategory = MainCategory::where('main_cat_slug', $mainCategorySlug)->first();
                    $filterType = 'main-category';
                    $filterWord = $mainCategory->main_cat_id;
                    $mainCategoryId = $mainCategory->main_cat_id;
                    $breadcrumbs = [
                        ['title' => 'Home', 'url' => route('home')],
                        ['title' => 'Offers', 'url' => route('products.offer.all')],
                        ['title' => Str::title($rowOffersRecord->first()->offer_desc), 'url' => route('products.offer' , ['offerSlug' => $offerSlug])],
                        ['title' => Str::title($mainCategory->main_cat_name), 'url' => route('products.offer.main-category', ['offerSlug' => $offerSlug, 'mainCategorySlug' => $mainCategorySlug])],
                    ];
                }
                if ($categorySlug != '') {
                    $catData = [];
                    $category = Category::where('cat_slug', $categorySlug)->first();
                    $filterType = 'category';
                    $filterWord = $category->cat_id;
                    $breadcrumbs = [
                        ['title' => 'Home', 'url' => route('home')],
                        ['title' => 'Offers', 'url' => route('products.offer.all')],
                        ['title' => Str::title($rowOffersRecord->first()->offer_desc), 'url' => route('products.offer', ['offerSlug' => $offerSlug])],
                        ['title' => Str::title($mainCategory->main_cat_name), 'url' => route('products.offer.main-category', ['offerSlug' => $offerSlug, 'mainCategorySlug' => $mainCategorySlug])],
                        ['title' => Str::title($category->cat_name), 'url' => route('products.offer.category', ['offerSlug' => $offerSlug, 'mainCategorySlug' => $mainCategorySlug, 'categorySlug' => $categorySlug])],
                    ];
                }
                if ($subCategorySlug != '') {
                    $subCategory = SubCategory::where('sub_cat_slug', $subCategorySlug)
                        ->first();
                    $filterType = 'sub-category';
                    $filterWord = $subCategory->sub_cat_id;
                    $breadcrumbs = [
                        ['title' => 'Home', 'url' => route('home')],
                        ['title' => 'Offers', 'url' => route('products.offer.all')],
                        ['title' => Str::title($rowOffersRecord->first()->offer_desc), 'url' => route('products.offer', ['offerSlug' => $offerSlug])],
                        ['title' => Str::title($mainCategory->main_cat_name), 'url' => route('products.offer.main-category', ['offerSlug' => $offerSlug, 'mainCategorySlug' => $mainCategorySlug])],
                        ['title' => Str::title($category->cat_name), 'url' => route('products.offer.category', ['offerSlug' => $offerSlug, 'mainCategorySlug' => $mainCategorySlug, 'categorySlug' => $categorySlug])],
                        ['title' => Str::title($subCategory->sub_cat_name), 'url' => route('products.offer.category', ['offerSlug' => $offerSlug, 'mainCategorySlug' => $mainCategorySlug, 'categorySlug' => $categorySlug, 'subCategorySlug' => $subCategorySlug])],
                    ];
                }
                $mainCategoryId = '';
                $brandFilter = '';

                $qryProductsRecordData = DB::table('product_table as pt')
                    ->select(
                        'sct.sub_cat_id',
                        'sct.sub_cat_name',
                        'ct.cat_index',
                        'ct.cat_id',
                        'ct.cat_name',
                        'mt.main_cat_id',
                        'mt.main_cat_name',
                        'pt.product_id',
                        'pt.seo_url',
                        'bt.brand_name',
                        'bt.brand_id',
                        'pt.usages',
                        'sg.id as in_segment',
                        'sg.title as segment_title',
                        'sg.slug as segment_slug',
                        'sg.domain as segment_status',
                        DB::raw("FORMAT(pt.main_price, 0) AS main_price"),
                        DB::raw("FORMAT(MAX(pt.main_price), 0) AS max_price"),
                        DB::raw("FORMAT(MIN(NULLIF(pt.main_price, 0)), 0) AS min_price"),
                        'st.stock',
                        DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) AS unique_key")
                    )
                    ->join(DB::raw('(SELECT id as brand_id, name as brand_name, distributor FROM brand_table WHERE web_status = "1") as bt'), 'pt.brand_id', '=', 'bt.brand_id')
                    ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
                    ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
                    ->join(DB::raw('(SELECT main_cat_id, main_cat_name FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
                    ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
                    ->leftJoinSub(function ($query) use ($domainId) {
                        $query->select('*')
                            ->from('gc_segments')
                            ->where('status', '=', '1')
                            ->where('type', '!=', 'bundle')
                            ->where('is_hidden', '=', '0')
                            ->where('view_by', '!=', '2')
                            ->whereRaw('FIND_IN_SET(?, domain)', [$domainId])
                            ->where(function ($query) {
                                $query->whereRaw('FIND_IN_SET(1, location)')
                                    ->orWhereRaw('FIND_IN_SET(2, location)');
                            });
                    }, 'sg', function ($join) {
                        $join->on(DB::raw('FIND_IN_SET(sg.id, pt.in_segment)'), '>', DB::raw('0'));
                    })
                    ->where('pt.web_status', '1')
                    ->where('pt.main_price', '>', 0)
                    ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
                    ->whereNotNull('st.stock');
                switch ($filterType) {
                    case 'main-category':
                        $mainCategoryId = $filterWord;
                        $subCategoryIds = SubCategory::whereIn('cat_id', function ($query) use ($mainCategoryId) {
                            $query->select('cat_id')
                                ->from('cat_table')
                                ->where('main_cat_id', $mainCategoryId);
                        })->pluck('sub_cat_id');
                        $qryProductsRecordData->whereIn('pt.sub_cat_id', $subCategoryIds);
                        break;
                    case 'category':
                        $subCategoryIds = SubCategory::where('cat_id', $filterWord)->pluck('sub_cat_id');
                        $qryProductsRecordData->whereIn('pt.sub_cat_id', $subCategoryIds);
                        break;
                    case 'sub-category':
                        $qryProductsRecordData->where('pt.sub_cat_id', '=', $filterWord);
                        break;
                    default:
                        break;
                }
                if ($rowOffersRecord->isNotEmpty()) {
                    if ($rowOffersRecord->count() > 1) {
                        $qryProductsRecordData->where(function ($query) use ($rowOffersRecord) {
                            foreach ($rowOffersRecord as $offerRecord) {
                                $distributors = $offerRecord->item_distributor !== '' ? explode(",", $offerRecord->item_distributor) : [];
                                $brands = $offerRecord->item_brand !== '' ? explode(",", $offerRecord->item_brand) : [];
                                $mCategories = $offerRecord->item_main_category !== '' ? explode(",", $offerRecord->item_main_category) : [];
                                $categories = $offerRecord->item_category !== '' ? explode(",", $offerRecord->item_category) : [];
                                $subCategories = $offerRecord->item_sub_category !== '' ? explode(",", $offerRecord->item_sub_category) : [];
                                $flags = $offerRecord->item_flag !== '' ? explode(",", $offerRecord->item_flag) : [];
                                $products = $offerRecord->items !== '' ? explode(",", $offerRecord->items) : [];

                                $query->orWhere(function ($subQuery) use ($distributors, $brands, $mCategories, $categories, $subCategories, $flags, $products) {
                                    if (!empty($distributors)) {
                                        $subQuery->whereIn('bt.distributor', $distributors);
                                    }

                                    if (!empty($brands)) {
                                        $subQuery->whereIn('pt.brand_id', $brands);
                                    }

                                    if (!empty($mCategories)) {
                                        $subQuery->whereIn('mt.main_cat_id', $mCategories);
                                    }

                                    if (!empty($categories)) {
                                        $subQuery->whereIn('ct.cat_id', $categories);
                                    }

                                    if (!empty($subCategories)) {
                                        $subQuery->whereIn('pt.sub_cat_id', $subCategories);
                                    }

                                    if (!empty($flags)) {
                                        $subQuery->whereIn('pt.func_flag', $flags);
                                    }

                                    if (!empty($products)) {
                                        $subQuery->whereIn('pt.product_id', $products);
                                    }
                                });


                            }
                        });
                    } else {
                        $offerRecord = $rowOffersRecord->first();
                        $distributors = $offerRecord->item_distributor !== '' ? explode(",", $offerRecord->item_distributor) : [];
                        $brands = $offerRecord->item_brand !== '' ? explode(",", $offerRecord->item_brand) : [];
                        $mCategories = $offerRecord->item_main_category !== '' ? explode(",", $offerRecord->item_main_category) : [];
                        $categories = $offerRecord->item_category !== '' ? explode(",", $offerRecord->item_category) : [];
                        $subCategories = $offerRecord->item_sub_category !== '' ? explode(",", $offerRecord->item_sub_category) : [];
                        $flags = $offerRecord->item_flag !== '' ? explode(",", $offerRecord->item_flag) : [];
                        $products = $offerRecord->items !== '' ? explode(",", $offerRecord->items) : [];

                        $qryProductsRecordData->where(function ($query) use ($distributors, $brands, $mCategories, $categories, $subCategories, $flags, $products) {
                            if (!empty($distributors)) {
                                $query->whereIn('bt.distributor', $distributors);
                            }

                            if (!empty($brands)) {
                                $query->whereIn('pt.brand_id', $brands);
                            }

                            if (!empty($mCategories)) {
                                $query->whereIn('mt.main_cat_id', $mCategories);
                            }

                            if (!empty($categories)) {
                                $query->whereIn('ct.cat_id', $categories);
                            }

                            if (!empty($subCategories)) {
                                $query->whereIn('pt.sub_cat_id', $subCategories);
                            }

                            if (!empty($flags)) {
                                $query->whereIn('pt.func_flag', $flags);
                            }

                            if (!empty($products)) {
                                $query->whereIn('pt.product_id', $products);
                            }
                        });
                    }

                }

                $qryProductsRecordData->groupBy('unique_key')->orderBy('pt.main_price', 'ASC');
                $rowProductsRecordData = $qryProductsRecordData->get();
                $catData = [];
                if ($rowProductsRecordData->isNotEmpty()) {
                    foreach ($rowProductsRecordData as $rowProductsRecord) {
                        // Fragtypes
                        if ($rowProductsRecord->main_cat_id == '10' && $rowProductsRecord->usages != '') {
                            $fragranceTypesData[] = ['key' => $rowProductsRecord->usages, 'isSelected' => 0];
                        }
                        // Size
//                            if ($mainCategoryId == '10' && $rowProductsRecord->size != '') {
//                                $sizeData[] = ['key' => $rowProductsRecord->size, 'isSelected' => 0];
//                            }
                        // Price
                        if (!in_array($rowProductsRecord->main_price, $priceData)) {
                            $priceData[] = (float)str_replace(',', '', $rowProductsRecord->main_price);
                        }
                        // Segment
                        if (!empty($rowProductsRecord->in_segment)) {
                            $segmentData[] = [
                                'title' => $rowProductsRecord->segment_title,
                                'open_slug' => 0,
                                'slug' => $rowProductsRecord->segment_slug,
                                'item' => [
                                    'in_segment' => $rowProductsRecord->in_segment,
                                    'in_segment_title' => $rowProductsRecord->segment_title,
                                    'in_segment_slug' => $rowProductsRecord->segment_slug,
                                    'isSelected' => 0
                                ]
                            ];
                        }
                        // Brand
                        $brandData[] = [
                            'brand_id' => $rowProductsRecord->brand_id,
                            'brand_name' => $rowProductsRecord->brand_name,
                            'isSelected' => 0
                        ];

                        $catData[] = [
                            'cat_index' => $rowProductsRecord->cat_index,
                            'cat_id' => $rowProductsRecord->cat_id,
                            'cat_name' => $rowProductsRecord->cat_name,
                            'isSelected' => 0
                        ];
                    }

                    $cData = array_values(array_unique($catData, SORT_REGULAR));
                    array_multisort(array_column($cData, 'cat_index'), SORT_ASC, $cData);
                    $bData = array_values(array_unique($brandData, SORT_REGULAR));
                    array_multisort(array_column($bData, 'brand_name'), SORT_ASC, $bData);
                    $sData = array_values(array_unique($segmentData, SORT_REGULAR));
                    $sizeData = array_values(array_unique($sizeData, SORT_REGULAR));
                    $fragranceTypesData = array_values(array_unique($fragranceTypesData, SORT_REGULAR));

                    // Convert array of arrays to collection
                    $catCollection = collect($cData);

                    // Map each array to an object
                    $categories = $catCollection->map(function ($item) {
                        return (object)$item;
                    });

                    $productFilterArray = [
                        'category' => $categories,
                        'sub_category' => $subCatData,
                        "filter_brand" => $bData,
                        "filter_segment" => $sData,
                        "filter_size" => $sizeData,
                        "filter_fragtype" => $fragranceTypesData,
                        "filter_collection" => $collectionData,
                        "filter_concern" => $concernData,
                        "min_price" => min($priceData),
                        "max_price" => max($priceData),
                    ];
                }

                break;
            default:
                $keyWord = "";
                $keyTerm = "";
                $mainCategoryId = "";
                $pageName = '';

                $priceData = [1, 1000];
                $productFilterArray = [
                    "category" => [],
                    "sub_category" => [],
                    "filter_brand" => [],
                    "filter_segment" => [],
                    "filter_size" => $sizeData,
                    "filter_fragtype" => $fragranceTypesData,
                    "filter_collection" => $collectionData,
                    "filter_concern" => $concernData,
                    "min_price" => min($priceData),
                    "max_price" => max($priceData),
                ];
                break;
        }
//        dd($productFilterArray);
//        $keyType = 'main-category';
//        $keyWord = '10';
//        $mainCategoryId = '';
        $minPrice = $productFilterArray['min_price'] ?? 1;
        $maxPrice = $productFilterArray['max_price'] ?? 1000;

        $currentDate = Carbon::now()->format('Y-m-d');


        switch ($keyType) {
            case 'main-category':
                $qryBanner = Banner::where('status', '=', '1')
                    ->where('banner_location', '=', '1')
                    ->where('banner_type', 'category')
                    ->whereRaw("FIND_IN_SET('{$domainId}', domain_id)")
                    ->where('category', $keyWord)
                    ->where('category_type', 'main-category')
                    ->where(function ($query) use ($currentDate) {
                        $query->where(function ($query) use ($currentDate) {
                            $query->where('from_date', '<=', $currentDate)
                                ->where('to_date', '>=', $currentDate);
                        })->orWhere(function ($query) use ($currentDate) {
                            $query->where('from_date', '<=', $currentDate)
                                ->where('to_date', '=', '0000-00-00');
                        });
                    });
                break;
            case 'category':
                $qryBanner = Banner::where('status', '=', '1')
                    ->where('banner_location', '=', '1')
                    ->where('banner_type', 'category')
                    ->whereRaw("FIND_IN_SET('{$domainId}', domain_id)")
                    ->where('category', $keyWord)
                    ->where('category_type', 'category')
                    ->where(function ($query) use ($currentDate) {
                        $query->where(function ($query) use ($currentDate) {
                            $query->where('from_date', '<=', $currentDate)
                                ->where('to_date', '>=', $currentDate);
                        })->orWhere(function ($query) use ($currentDate) {
                            $query->where('from_date', '<=', $currentDate)
                                ->where('to_date', '=', '0000-00-00');
                        });
                    });
                break;
            case 'sub-category':
                $qryBanner = Banner::where('status', '=', '1')
                    ->where('banner_location', '=', '1')
                    ->where('banner_type', 'category')
                    ->whereRaw("FIND_IN_SET('{$domainId}', domain_id)")
                    ->where('category', $keyWord)
                    ->where('category_type', 'sub-category')
                    ->where(function ($query) use ($currentDate) {
                        $query->where(function ($query) use ($currentDate) {
                            $query->where('from_date', '<=', $currentDate)
                                ->where('to_date', '>=', $currentDate);
                        })->orWhere(function ($query) use ($currentDate) {
                            $query->where('from_date', '<=', $currentDate)
                                ->where('to_date', '=', '0000-00-00');
                        });
                    });
                break;
            case 'brand':
                $qryBanner = Banner::where('status', '=', '1')
                    ->where('banner_location', '=', '1')
                    ->where('banner_type', '=', 'brand')
                    ->whereRaw("FIND_IN_SET('{$domainId}', domain_id)")
                    ->where('banner_brand', $keyWord)
                    ->where(function ($query) use ($currentDate) {
                        $query->where(function ($query) use ($currentDate) {
                            $query->where('from_date', '<=', $currentDate)
                                ->where('to_date', '>=', $currentDate);
                        })->orWhere(function ($query) use ($currentDate) {
                            $query->where('from_date', '<=', $currentDate)
                                ->where('to_date', '=', '0000-00-00');
                        });
                    });
                break;
            default:
                $qryBanner = Banner::where('status', '=', '10');
                break;
        }

        $banners = $qryBanner->orderBy('banner_id', 'DESC')->get();
        $mobileBanners = $banners->filter(function ($banner) {
            return !empty($banner->mbanner);
        });

        return view('frontend.pages.product-lists')
            ->with('breadcrumbs', $breadcrumbs)
            ->with('banners', $banners)
            ->with('mobileBanners', $mobileBanners)
            ->with('keyType', $keyType)
            ->with('keyWord', $keyWord)
            ->with('filterType', $filterType)
            ->with('filterWord', $filterWord)
            ->with('keyTerm', $keyTerm)
            ->with('mainCategoryId', $mainCategoryId)
            ->with('minPrice', $minPrice)
            ->with('maxPrice', $maxPrice)
            ->with('brandFilter', $brandFilter)
            ->with('productFilterArray', $productFilterArray);
    }

    public function productFilter(Request $request)
    {

        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $data = [];
        $webDiscount = 0;
        $webDiscountOffer = '';
        $currentDate = now()->format('Y-m-d');
        $currentDay = strtolower(now()->format('D'));
        $customerId = '';
        $customerOrganization = '';
        $customerTier = '';
        $user = Auth::user();
        if ($user) {
            $customerId = $user->customer_id;
            $customerOrganization = $user->organization;
            $customerTier = $user->tier;
        }

        $productArr = [];
        $keyType = strtolower(trim(strip_tags($request->input('key_type'))));
        $keyWord = strtolower(trim(strip_tags($request->input('key_word'))));
        $prdctType = strtolower(trim(strip_tags($request->input('prdct_type'))));
        $bFilter = strtolower(trim(strip_tags($request->input('bfilter'))));
        $crFilter = trim(strip_tags($request->input('crfilter')));
        $clFilter = trim(strip_tags($request->input('clfilter')));
        $sgFilter = trim(strip_tags($request->input('sgfilter')));
        $spFilter = trim(strip_tags($request->input('spfilter')));
        $ftFilter = trim(strip_tags($request->input('ftfilter')));
        $sFilter = trim(strip_tags($request->input('sfilter')));
        $filterType = strtolower(trim(strip_tags($request->input('filter_type'))));
        $filterWord = strtolower(trim(strip_tags($request->input('filter_word'))));
        $sort = strtolower(trim(strip_tags($request->input('sort'))));
        $minPrice = $request->filled('min_price') ? (float)trim($request->input('min_price')) : 0;
        $maxPrice = $request->filled('max_price') ? (float)trim($request->input('max_price')) : 0;
        $refPage = $request->filled('ref_page') ? strtolower(trim(strip_tags($request->input('ref_page')))) : '';
        $start = $request->input('start');
        $limit = $request->input('limit');

        $bFilterArray = $bFilter != '' ? explode(',', $bFilter) : [];
//      $bFilterStr = !empty($bFilterArr) ? implode("','", $bFilterArr) : '';
        $crFilterArray = $crFilter != '' ? explode(',', $crFilter) : [];
        $clFilterArray = $clFilter != '' ? explode(',', $clFilter) : [];
        $cFilterStr = !empty($cFilterArr) ? implode("','", $cFilterArr) : '';
        $sgFilterArray = $sgFilter != '' ? explode(',', $sgFilter) : [];
        $spFilterArr = $spFilter != '' ? explode(',', $spFilter) : [];
        $spFilterStr = !empty($spFilterArr) ? implode("','", $spFilterArr) : '';
        $ftFilterArray = $ftFilter != '' ? explode(',', $ftFilter) : [];
        $sFilterArr = $sFilter != '' ? explode(',', $sFilter) : [];
        $sFilterStr = !empty($sFilterArr) ? implode("','", $sFilterArr) : '';
        $bFilterArrCount = count($bFilterArray);
        $productFilterArray = [];

        $offerWhere1 = "";
        $offerWhere2 = "";
        $offerWhere3 = "";
        $offerWhere4 = "";
        if ($keyType == "offer") {
            $offerWhere1 = " AND `oo1`.`show_status` = '1'";
            if ($keyType != '') {
                $offerWhere1 .= " AND `oo1`.`offer_slug`='" . $keyWord . "'";
            }
        }
        if ($keyType == "offer") {
            $offerWhere2 = " AND `oo2`.`show_status` = '1'";
            if ($keyType != '') {
                $offerWhere2 .= " AND `oo2`.`offer_slug`='" . $keyWord . "'";
            }
        }
        if ($keyType == "offer") {
            $offerWhere3 = " AND `oo3`.`show_status` = '1'";
            if ($keyType != '') {
                $offerWhere3 .= " AND `oo3`.`offer_slug`='" . $keyWord . "'";
            }
        }
        if ($keyType == "offer") {
            if ($keyType != '') {
                $offerWhere4 .= " AND `oo4`.`offer_slug`='" . $keyWord . "'";
            }
        }

        $productsRecord = DB::table('product_table as pt')
            ->select([
                'pt.in_segment',
                'pt.type_flag as product_type',
                'pt.product_id',
                DB::raw("IF(pt.type_flag = '2', pt.seo_url, f.seo_url) as seo_url"),
                'pt.sub_cat_id',
                'pt.func_flag',
                'br.brand_id',
                'br.brand_name',
                'pt.linepr',
                'pt.has_gift',
                DB::raw("ROUND(pt.main_price, 0) as main_price"),
                DB::raw("ROUND(MAX(pt.main_price), 0) as max_price"),
                DB::raw("ROUND(MIN(NULLIF(pt.main_price, 0)), 0) as min_price"),
                DB::raw("IF(pt.`type_flag` = '2',
                    CONCAT(
                        SUBSTR(REPLACE(pt.`product_name`, '\\\', ''), 1, 60),
                        IF(
                            CHAR_LENGTH(REPLACE(pt.`product_name`, '\\\', '')) > 60,
                            '..',
                            ''
                        )
                    ),
                    CONCAT(
                        SUBSTR(REPLACE(`f`.`family_name`, '\\\', ''), 1, 60),
                        IF(
                            CHAR_LENGTH(REPLACE(`f`.`family_name`, '\\\', '')) > 60,
                            '..',
                            ''
                        )
                    )
                ) AS `family_name`"),
                DB::raw("IF(pt.type_flag = '2', pt.photo1, fp.aws) as family_pic"),
                'st.stock',
                DB::raw("IFNULL(SUBSTRING_INDEX(sg.title, ' ', 1), '') as segment"),
                DB::raw("IFNULL(sg.slug, '') as segment_slug"),
                DB::raw("IFNULL(sg.type, '') as segment_type"),
                DB::raw("IFNULL(o.offer_id, '') as has_offer"),
                DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) as unique_key"),
                DB::raw("disc.gift_discount_value as `store_discount_value`"),
                DB::raw("disc.gift_discount_type as `store_discount_type`"),
                DB::raw("disc.offer_name as `store_offer_name`"),
                DB::raw("disc2.gift_discount_value as `customer_tier_discount`"),
                DB::raw("disc2.gift_discount_type as `customer_tier_discount_type`"),
                DB::raw("disc2.offer_name as `customer_tier_offer_name`"),
                DB::raw("disc3.gift_discount_value as `organisation_discount_value`"),
                DB::raw("disc3.gift_discount_type as `organisation_discount_type`"),
                DB::raw("disc3.offer_name as `organisation_offer_name`"),
                DB::raw("0 AS `discount_value_4`"),
                DB::raw("0 AS `max_price_4`"),
                DB::raw("0 AS `min_price_4`"),
                DB::raw("0 AS `main_price_4`"),
                DB::raw("0 AS `offer_discount_value`"),
                DB::raw("NULL AS `offer_discount_type`"),
                DB::raw("NULL AS `offer_offer_name`"),
                DB::raw("CASE WHEN disc.gift_discount_value IS NULL OR disc.gift_discount_value = '' THEN 0
            WHEN disc.gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * disc.gift_discount_value / 100)
            ELSE pt.main_price - disc.gift_discount_value END as discount_value_1"),
                DB::raw("CASE WHEN disc.gift_discount_value IS NULL OR disc.gift_discount_value = '' THEN 0
            WHEN disc.gift_discount_type = 'percentage' THEN MAX(pt.main_price) - (MAX(pt.main_price) * disc.gift_discount_value / 100)
            ELSE MAX(pt.main_price) - disc.gift_discount_value END as max_price_1"),
                DB::raw("CASE WHEN disc.gift_discount_value IS NULL OR disc.gift_discount_value = '' THEN 0
            WHEN disc.gift_discount_type = 'percentage' THEN MIN(pt.main_price) - (MIN(pt.main_price) * disc.gift_discount_value / 100)
            ELSE MIN(pt.main_price) - disc.gift_discount_value END as min_price_1"),
                DB::raw("CASE WHEN disc2.gift_discount_value IS NULL OR disc2.gift_discount_value = '' THEN 0
            WHEN disc2.gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * disc2.gift_discount_value / 100)
            ELSE pt.main_price - disc2.gift_discount_value END as discount_value_2"),
                DB::raw("CASE WHEN disc2.gift_discount_value IS NULL OR disc2.gift_discount_value = '' THEN 0
            WHEN disc2.gift_discount_type = 'percentage' THEN MAX(pt.main_price) - (MAX(pt.main_price) * disc2.gift_discount_value / 100)
            ELSE MAX(pt.main_price) - disc2.gift_discount_value END as max_price_2"),
                DB::raw("CASE WHEN disc2.gift_discount_value IS NULL OR disc2.gift_discount_value = '' THEN 0
            WHEN disc2.gift_discount_type = 'percentage' THEN MIN(pt.main_price) - (MIN(pt.main_price) * disc2.gift_discount_value / 100)
            ELSE MIN(pt.main_price) - disc2.gift_discount_value END as min_price_2"),
                DB::raw("CASE WHEN disc3.gift_discount_value IS NULL OR disc3.gift_discount_value = '' THEN 0
            WHEN disc3.gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * disc3.gift_discount_value / 100)
            ELSE pt.main_price - disc3.gift_discount_value END as discount_value_3"),
                DB::raw("CASE WHEN disc3.gift_discount_value IS NULL OR disc3.gift_discount_value = '' THEN 0
            WHEN disc3.gift_discount_type = 'percentage' THEN MAX(pt.main_price) - (MAX(pt.main_price) * disc3.gift_discount_value / 100)
            ELSE MAX(pt.main_price) - disc3.gift_discount_value END as max_price_3"),
                DB::raw("CASE WHEN disc3.gift_discount_value IS NULL OR disc3.gift_discount_value = '' THEN 0
            WHEN disc3.gift_discount_type = 'percentage' THEN MIN(pt.main_price) - (MIN(pt.main_price) * disc3.gift_discount_value / 100)
            ELSE MIN(pt.main_price) - disc3.gift_discount_value END as min_price_3"),
//                DB::raw("CAST(GREATEST(
//                    (CASE WHEN disc.gift_discount_value IS NULL OR disc.gift_discount_value = '' THEN 0
//                          WHEN disc.gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * disc.gift_discount_value / 100)
//                          ELSE pt.main_price - disc.gift_discount_value
//                     END),
//                    (CASE WHEN disc2.gift_discount_value IS NULL OR disc2.gift_discount_value = '' THEN 0
//                          WHEN disc2.gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * disc2.gift_discount_value / 100)
//                          ELSE pt.main_price - disc2.gift_discount_value
//                     END),
//                    (CASE WHEN disc3.gift_discount_value IS NULL OR disc3.gift_discount_value = '' THEN 0
//                          WHEN disc3.gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * disc3.gift_discount_value / 100)
//                          ELSE pt.main_price - disc3.gift_discount_value
//                     END),
//                    (CASE WHEN gift_discount_value4 IS NULL OR gift_discount_value4 = '' THEN 0
//                          WHEN gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * gift_discount_value4 / 100)
//                          ELSE pt.main_price - disc4.gift_discount_value
//                     END)
//                ) AS DECIMAL(20,2)) AS dmain_price"),
//                DB::raw("CAST(GREATEST(`max_price_1`,`max_price_2`,`max_price_3`,`max_price_4`) AS DECIMAL(20,2)) as dmax_price"),
//                DB::raw("CAST(GREATEST(`min_price_1`,`min_price_2`,`min_price_3`,`min_price_4`) AS DECIMAL(20,2)) as dmin_price"),
//                DB::raw("CASE GREATEST(`discount_value_1`,`discount_value_2`,`discount_value_3`,`discount_value_4`)
//                                            WHEN `discount_value_1` THEN `store_discount_type`
//                                            WHEN `discount_value_2` THEN `customer_tier_discount_type`
//                                            WHEN `discount_value_3` THEN `organisation_discount_type`
//                                            ELSE `offer_discount_type` END AS `discount_type`"),
//                DB::raw("CASE GREATEST(`discount_value_1`,`discount_value_2`,`discount_value_3`,`discount_value_4`)
//                                            WHEN `discount_value_1` THEN `store_offer_name`
//                                            WHEN `discount_value_2` THEN `customer_tier_offer_name`
//                                            WHEN `discount_value_3` THEN `organisation_offer_name`
//                                            ELSE `offer_offer_name` END AS `offer_name`"),
            ])
            ->join(DB::raw('(SELECT id as brand_id, name as brand_name, distributor FROM brand_table WHERE web_status = "1") as br'), 'pt.brand_id', '=', 'br.brand_id')
            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sc'), 'pt.sub_cat_id', '=', 'sc.sub_cat_id')
            ->join(DB::raw('(SELECT cat_id, cat_name, main_cat_id FROM cat_table) as ct'), 'sc.cat_id', '=', 'ct.cat_id')
            ->join(DB::raw('(SELECT main_cat_id, main_cat_name FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
            ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
            ->leftJoin(DB::raw('(SELECT family_id, family_name, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
            ->leftJoin(DB::raw('(SELECT family_id,aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
            ->leftJoin(DB::raw("(SELECT id, title, slug, type FROM gc_segments WHERE slug IN ('new-arrival', 'best-seller') AND domain = '" . $domainId . "') as sg"), function ($join) {
                $join->on(DB::raw('FIND_IN_SET(sg.id, pt.in_segment)'), '>', DB::raw('0'));
            })
            ->leftJoin(DB::raw("(SELECT offer_id, items FROM offer_tbl WHERE status = '1' AND FIND_IN_SET('" . $domainId . "', in_domain) AND items != '' AND offer_for IN ('1','2') AND ((('" . $currentDate . "' BETWEEN from_date AND to_date) OR (from_date <= '" . $currentDate . "' AND to_date = '0000-00-00 00:00:00')) OR (FIND_IN_SET('" . $currentDay . "', week_days))) ) as o"), function ($join) {
                $join->on(DB::raw('FIND_IN_SET(pt.product_id, o.items)'), '>', DB::raw('0'));
            })
            ->where('pt.web_status', '=', '1')
            ->where('pt.main_price', '>', 0)
            ->whereRaw('FIND_IN_SET(?, pt.in_domain)', [$domainId])
            ->leftJoin(DB::raw("(SELECT `oo1`.`item_flag`,`oo1`.`item_brand`,`oo1`.`item_sub_category`,`oo1`.`item_category`,`oo1`.`item_main_category`,`oo1`.`item_distributor`,`oo1`.`offer_id`,`oo1`.`items`,`oo1`.`gift_discount_type`,`oo1`.`gift_discount_value`,oo1.`gift_points`,oo1.`offer_name`
                                FROM `offer_tbl` as `oo1` WHERE oo1.`auto_apply`='1' AND oo1.`status`='1' AND FIND_IN_SET('" . $domainId . "',`oo1`.`in_domain`) AND `oo1`.`offer_type` = 'store' AND  `oo1`.`offer_for` IN ('1','2')
                                " . $offerWhere1 . " AND FIND_IN_SET(50, (SELECT `os1`.`offer_store` FROM `offer_tbl` as `os1` WHERE os1.`offer_id`=`oo1`.`offer_id`))
                                AND ((('" . $currentDate . "' BETWEEN oo1.`from_date` AND oo1.`to_date`) OR (oo1.`from_date` <= '" . $currentDate . "' AND oo1.`to_date`='0000-00-00 00:00:00')) OR (FIND_IN_SET('" . $currentDay . "', (SELECT oo2.`week_days` FROM `offer_tbl` as `oo2` WHERE oo2.`offer_id`=oo1.`offer_id`))))
                                ) as `disc`"), function ($join) {
                $join->on(function ($query) {
                    $query->whereRaw("(`disc`.`item_distributor`='' OR `disc`.`item_distributor`='all' OR (FIND_IN_SET(`br`.`distributor`, (SELECT `disc`.`item_distributor` FROM `offer_tbl` as `o2` WHERE o2.`offer_id`=`disc`.`offer_id`))))")
                        ->whereRaw("(`disc`.`item_main_category`='' OR `disc`.`item_main_category`='all' OR (FIND_IN_SET(`mt`.`main_cat_id`, (SELECT `disc`.`item_main_category` FROM `offer_tbl` as `o3` WHERE o3.`offer_id`=`disc`.`offer_id`))))")
                        ->whereRaw("(`disc`.`item_category`='' OR `disc`.`item_category`='all' OR (FIND_IN_SET(`ct`.`cat_id`, (SELECT `disc`.`item_category` FROM `offer_tbl` as `o5` WHERE o5.`offer_id`=`disc`.`offer_id`))))")
                        ->whereRaw("(`disc`.`item_sub_category`='' OR `disc`.`item_sub_category`='all' OR (FIND_IN_SET(`pt`.`sub_cat_id`, (SELECT `disc`.`item_sub_category` FROM `offer_tbl` as `o4` WHERE o4.`offer_id`=`disc`.`offer_id`))))")
                        ->whereRaw("(`disc`.`item_brand`='' OR `disc`.`item_brand`='all' OR (FIND_IN_SET(`pt`.`brand_id`, (SELECT `disc`.`item_brand` FROM `offer_tbl` as `o6` WHERE o6.`offer_id`=`disc`.`offer_id`))))")
                        ->whereRaw("(`disc`.`item_flag`='' OR `disc`.`item_flag`='all' OR (FIND_IN_SET(`pt`.`func_flag`, (SELECT `disc`.`item_flag` FROM `offer_tbl` as `o7` WHERE o7.`offer_id`=`disc`.`offer_id`))))")
                        ->whereRaw("(`disc`.`items`='' OR `disc`.`items`='all' OR (FIND_IN_SET(`pt`.`product_id`, (SELECT `disc`.`items` FROM `offer_tbl` as `o7` WHERE `o7`.`offer_id`=`disc`.`offer_id`))))");
                });
            })
            ->leftJoin(DB::raw("(SELECT `oo2`.`item_brand`, `oo2`.`item_flag`, `oo2`.`item_sub_category`, `oo2`.`item_distributor`, `oo2`.`item_main_category`, `oo2`.`item_category`, `oo2`.`items`, `oo2`.`offer_id`, `oo2`.`gift_discount_type`, `oo2`.`gift_discount_value`, `oo2`.`gift_points`, `oo2`.`offer_name`
                                FROM `offer_tbl` as `oo2` WHERE `oo2`.`auto_apply` = '1' AND `oo2`.`status` = '1' AND FIND_IN_SET(" . $domainId . ", `oo2`.`in_domain`) AND `oo2`.`offer_type` = 'tier' AND  `oo2`.`offer_for` IN ('1', '2')
                                 " . $offerWhere2 . " AND (FIND_IN_SET('" . $customerTier . "', (SELECT group_concat(`oo`.`offer_tier` )FROM `offer_tbl` as `oo` WHERE `oo2`.`offer_id`= `oo2`.`offer_id` )))
                                 AND ((('" . $currentDate . "' BETWEEN `oo2`.`from_date` AND `oo2`.`to_date`) OR (`oo2`.`from_date` <= '" . $currentDate . "' AND `oo2`.`to_date` = '0000-00-00 00:00:00')) OR (FIND_IN_SET('" . $currentDay . "', (SELECT `oo2`.`week_days` FROM `offer_tbl` as `o1` WHERE o1.`offer_id` = `oo2`.`offer_id`))))
                                 ) as `disc2`"), function ($join) {
                $join->on(function ($query) {
                    $query->whereRaw("(`disc2`.`item_distributor` = '' OR `disc2`.`item_distributor` = 'all' OR (FIND_IN_SET(`br`.`distributor`, (SELECT `disc2`.`item_distributor` FROM `offer_tbl` as `o2` WHERE o2.`offer_id` = `disc2`.`offer_id`))))")
                        ->whereRaw("(`disc2`.`item_main_category` = '' OR `disc2`.`item_main_category` = 'all' OR (FIND_IN_SET(`mt`.`main_cat_id`, (SELECT `disc2`.`item_main_category` FROM `offer_tbl` as `o3` WHERE o3.`offer_id` = `disc2`.`offer_id`))))")
                        ->whereRaw("(`disc2`.`item_category` = '' OR `disc2`.`item_category` = 'all' OR (FIND_IN_SET(`ct`.`cat_id`, (SELECT `disc2`.`item_category` FROM `offer_tbl` as `o5` WHERE o5.`offer_id` = `disc2`.`offer_id`))))")
                        ->whereRaw("(`disc2`.`item_sub_category` = '' OR `disc2`.`item_sub_category` = 'all' OR (FIND_IN_SET(`pt`.`sub_cat_id`, (SELECT `disc2`.`item_sub_category` FROM `offer_tbl` as `o4` WHERE o4.`offer_id` = `disc2`.`offer_id`))))")
                        ->whereRaw("(`disc2`.`item_brand` = '' OR `disc2`.`item_brand` = 'all' OR (FIND_IN_SET(`pt`.`brand_id`, (SELECT `disc2`.`item_brand` FROM `offer_tbl` as `o6` WHERE o6.`offer_id` = `disc2`.`offer_id`))))")
                        ->whereRaw("(`disc2`.`item_flag` = '' OR `disc2`.`item_flag` = 'all' OR (FIND_IN_SET(`pt`.`func_flag`, (SELECT `disc2`.`item_flag` FROM `offer_tbl` as `o7` WHERE o7.`offer_id` = `disc2`.`offer_id`))))")
                        ->whereRaw("(`disc2`.`items` = '' OR `disc2`.`items` = 'all' OR (FIND_IN_SET(`pt`.`product_id`, (SELECT `disc2`.`items` FROM `offer_tbl` as `o7` WHERE o7.`offer_id` = `disc2`.`offer_id`))))");
                });
            })
            ->leftJoin(DB::raw("(SELECT `oo3`.`item_brand`, `oo3`.`item_flag`, `oo3`.`item_sub_category`, `oo3`.`item_distributor`, `oo3`.`item_main_category`, `oo3`.`item_category`, `oo3`.`items`, `oo3`.`offer_id`, `oo3`.`gift_discount_type`, `oo3`.`gift_discount_value`, `oo3`.`gift_points`, `oo3`.`offer_name`
                                FROM `offer_tbl` as `oo3` WHERE `oo3`.`auto_apply` = '1' AND `oo3`.`status` = '1' AND FIND_IN_SET(" . $domainId . ", `oo3`.`in_domain`) AND `oo3`.`offer_type`IN ('location','organization','store') AND  `oo3`.`offer_for` IN ('1', '2') " . $offerWhere3 . " AND
                                 ((FIND_IN_SET('" . $customerTier . "', (SELECT oc.`offer_location` FROM `offer_tbl` as `oc` WHERE oc.`offer_id`=`oo3`.`offer_id`)))
                                 OR (FIND_IN_SET('" . $customerOrganization . "', (SELECT oo.`offer_organization` FROM `offer_tbl` as `oo` WHERE oo.`offer_id`=`oo3`.`offer_id`))))
                                 AND ((('" . $currentDate . "' BETWEEN `oo3`.`from_date` AND `oo3`.`to_date`) OR (`oo3`.`from_date` <= '" . $currentDate . "' AND `oo3`.`to_date` = '0000-00-00 00:00:00')) OR (FIND_IN_SET('" . $currentDay . "', (SELECT `oo3`.`week_days` FROM `offer_tbl` as `o1` WHERE o1.`offer_id` = `oo3`.`offer_id`))))
                                 ) as `disc3`"), function ($join) {
                $join->on(function ($query) {
                    $query->whereRaw("(`disc3`.`item_distributor` = '' OR `disc3`.`item_distributor` = 'all' OR (FIND_IN_SET(`br`.`distributor`, (SELECT `disc3`.`item_distributor` FROM `offer_tbl` as `o2` WHERE o2.`offer_id` = `disc3`.`offer_id`))))")
                        ->whereRaw("(`disc3`.`item_main_category` = '' OR `disc3`.`item_main_category` = 'all' OR (FIND_IN_SET(`mt`.`main_cat_id`, (SELECT `disc3`.`item_main_category` FROM `offer_tbl` as `o3` WHERE o3.`offer_id` = `disc3`.`offer_id`))))")
                        ->whereRaw("(`disc3`.`item_category` = '' OR `disc3`.`item_category` = 'all' OR (FIND_IN_SET(`ct`.`cat_id`, (SELECT `disc3`.`item_category` FROM `offer_tbl` as `o5` WHERE o5.`offer_id` = `disc3`.`offer_id`))))")
                        ->whereRaw("(`disc3`.`item_sub_category` = '' OR `disc3`.`item_sub_category` = 'all' OR (FIND_IN_SET(`pt`.`sub_cat_id`, (SELECT `disc3`.`item_sub_category` FROM `offer_tbl` as `o4` WHERE o4.`offer_id` = `disc3`.`offer_id`))))")
                        ->whereRaw("(`disc3`.`item_brand` = '' OR `disc3`.`item_brand` = 'all' OR (FIND_IN_SET(`pt`.`brand_id`, (SELECT `disc3`.`item_brand` FROM `offer_tbl` as `o6` WHERE o6.`offer_id` = `disc3`.`offer_id`))))")
                        ->whereRaw("(`disc3`.`item_flag` = '' OR `disc3`.`item_flag` = 'all' OR (FIND_IN_SET(`pt`.`func_flag`, (SELECT `disc3`.`item_flag` FROM `offer_tbl` as `o7` WHERE o7.`offer_id` = `disc3`.`offer_id`))))")
                        ->whereRaw("(`disc3`.`items` = '' OR `disc3`.`items` = 'all' OR (FIND_IN_SET(`pt`.`product_id`, (SELECT `disc3`.`items` FROM `offer_tbl` as `o7` WHERE o7.`offer_id` = `disc3`.`offer_id`))))");
                });
            })
//            ->join(DB::raw("(SELECT `oo4`.`item_brand`, `oo4`.`item_flag`, `oo4`.`item_sub_category`, `oo4`.`item_distributor`, `oo4`.`item_main_category`, `oo4`.`item_category`, `oo4`.`items`, `oo4`.`offer_id`, `oo4`.`gift_discount_type`, `oo4`.`gift_discount_value`, `oo4`.`gift_points`, `oo4`.`offer_name`
//                                FROM `offer_tbl` as `oo4` WHERE `oo4`.`show_status` = '1' " . $offerWhere4 . "
//                                 ) as `disc4`"), function ($join) {
//                $join->on(function ($query) {
//                    $query->whereRaw("(`disc4`.`item_distributor` = '' OR `disc4`.`item_distributor` = 'all' OR (FIND_IN_SET(`br`.`distributor`, (SELECT `disc4`.`item_distributor` FROM `offer_tbl` as `o2` WHERE o2.`offer_id` = `disc4`.`offer_id`))))")
//                        ->whereRaw("(`disc4`.`item_main_category` = '' OR `disc4`.`item_main_category` = 'all' OR (FIND_IN_SET(`mt`.`main_cat_id`, (SELECT `disc4`.`item_main_category` FROM `offer_tbl` as `o3` WHERE o3.`offer_id` = `disc4`.`offer_id`))))")
//                        ->whereRaw("(`disc4`.`item_category` = '' OR `disc4`.`item_category` = 'all' OR (FIND_IN_SET(`ct`.`cat_id`, (SELECT `disc4`.`item_category` FROM `offer_tbl` as `o5` WHERE o5.`offer_id` = `disc4`.`offer_id`))))")
//                        ->whereRaw("(`disc4`.`item_sub_category` = '' OR `disc4`.`item_sub_category` = 'all' OR (FIND_IN_SET(`pt`.`sub_cat_id`, (SELECT `disc4`.`item_sub_category` FROM `offer_tbl` as `o4` WHERE o4.`offer_id` = `disc4`.`offer_id`))))")
//                        ->whereRaw("(`disc4`.`item_brand` = '' OR `disc4`.`item_brand` = 'all' OR (FIND_IN_SET(`pt`.`brand_id`, (SELECT `disc4`.`item_brand` FROM `offer_tbl` as `o6` WHERE o6.`offer_id` = `disc4`.`offer_id`))))")
//                        ->whereRaw("(`disc4`.`item_flag` = '' OR `disc4`.`item_flag` = 'all' OR (FIND_IN_SET(`pt`.`func_flag`, (SELECT `disc4`.`item_flag` FROM `offer_tbl` as `o7` WHERE o7.`offer_id` = `disc4`.`offer_id`))))")
//                        ->whereRaw("(`disc4`.`items` = '' OR `disc4`.`items` = 'all' OR (FIND_IN_SET(`pt`.`product_id`, (SELECT `disc4`.`items` FROM `offer_tbl` as `o7` WHERE o7.`offer_id` = `disc4`.`offer_id`))))");
//                });
//            })
        ;

        if (!empty($crFilterArray)) {
            $productsRecord->whereIn('pt.in_concern', $crFilterArray);
        }

        if (!empty($sgFilterArray)) {
            $productsRecord->where(function ($productsRecord) use ($sgFilterArray) {
                foreach ($sgFilterArray as $sgk => $sgFilter) {
                    if ($sgk > 0) {
                        $productsRecord->orWhereRaw("FIND_IN_SET('" . $sgFilter . "', pt.in_segment)");
                    } else {
                        $productsRecord->whereRaw("FIND_IN_SET('" . $sgFilter . "', pt.in_segment)");
                    }
                }
            });
        }

        if (!empty($bFilterArray)) {
            $productsRecord->whereIn('pt.brand_id', $bFilterArray);
        }

        if (!empty($spFilterArray)) {
            $productsRecord->whereIn('pt.sun_protect', $spFilterArray);
        }

        if (!empty($ftFilterArray)) {
            $productsRecord->whereIn('pt.usages', $ftFilterArray);
        }

        if (!empty($sfilter_str)) {
            $productsRecord->whereIn('pt.size', explode(',', $sfilter_str));
        }

        $filterWordArray = $filterWord != '' ? explode(',', $filterWord) : [];
        switch ($filterType) {
            case 'main-category':
                $categoryIds = Category::whereIn('main_cat_id', $filterWordArray)->pluck('cat_id');
                $subCategoryIds = SubCategory::whereIn('cat_id', $categoryIds)->pluck('sub_cat_id');
                $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
                break;
            case 'category':
                $subCategoryIds = SubCategory::whereIn('cat_id', $filterWordArray)->pluck('sub_cat_id');
                $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
                break;
            case 'sub-category':
                $productsRecord->whereIn('pt.sub_cat_id', $filterWordArray);
                break;
        }

        if (isset($minPrice) && isset($maxPrice) && $maxPrice != 0) {
            $productsRecord->whereRaw('ROUND(pt.main_price, 0) BETWEEN ? AND ?', [$minPrice, $maxPrice]);
        }

        if (empty($ref_page)) {
            // Uncomment the line below if needed
            // $productsRecord->where('bd.web_status', '=', 1);
        }

        switch ($keyType) {
            case 'main-category':
                $mainCategoryId = $keyWord;
                $subCategoryIds = SubCategory::whereIn('cat_id', function ($query) use ($mainCategoryId) {
                    $query->select('cat_id')
                        ->from('cat_table')
                        ->where('main_cat_id', $mainCategoryId);
                })->pluck('sub_cat_id');
                $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
                break;
            case 'category':
                $subCategoryIds = SubCategory::where('cat_id', $keyWord)->pluck('sub_cat_id');
                $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
                break;
            case 'sub-category':
                $productsRecord->where('pt.sub_cat_id', '=', $keyWord);
                break;
            case 'brand':
                $productsRecord->where('pt.brand_id', '=', $keyWord);
                break;
            case 'segment':
                $segmentQuery = Segment::select('dynamic_mysql', 'id')
                    ->where('id', $keyWord);
                $row_segment = $segmentQuery->first();
                if ($row_segment && $row_segment->dynamic_mysql) {
                    $productsRecord->whereRaw($row_segment->dynamic_mysql);
                } else {
                    $productsRecord->whereIn('pt.in_segment', [$row_segment->id]);
                }

//                if ($filterType && $filterType != "" && $filterWord && $filterWord != "") {
//                    switch ($filterType) {
//                        case 'main-category':
//                            $categoryIds = Category::whereIn('main_cat_id', $filterWordArray)->pluck('cat_id');
//                            $subCategoryIds = SubCategory::whereIn('cat_id', $categoryIds)->pluck('sub_cat_id');
//                            $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
//                            break;
//                        case 'category':
//                            $subCategoryIds = SubCategory::whereIn('cat_id', $filterWordArray)->pluck('sub_cat_id');
//                            $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
//                            break;
//                        case 'sub-category':
//                            $productsRecord->where('pt.sub_cat_id', '=', $filterWord);
//                            break;
//                        default:
//                            // Additional conditions if needed
//                            break;
//                    }
//                }
                break;
            case 'search':
                $searchRecord = clone $productsRecord;
                $searchSoundexRecord = clone $productsRecord;
                $requestArray = explode(' ', $keyWord);

                if (!empty($requestArray)) {
                    foreach ($requestArray as $v) {
                        if (ctype_alpha($v)) {
                            $searchRecord->whereRaw("LOCATE('" . strtolower($v) . "',
                        LOWER(
                            CONCAT(
                                ' ',
                                br.brand_name,
                                ' ',
                                IF(pt.type_flag = '2', pt.fam_name, f.family_name),
                                ' ',
                                mt.main_cat_name,
                                ' ',
                                ct.cat_name,
                                ' ',
                                sc.sub_cat_name,
                                ' ',
                                pt.Ref_no,
                                ' ',
                                pt.barcode,
                                ' '
                            )
                        )
                    ) > 0");
                        } else {
                            $searchRecord->whereRaw("LOCATE('" . strtolower($v) . "',
                        LOWER(
                            CONCAT(
                                ' ',
                                pt.Ref_no,
                                ' ',
                                pt.barcode,
                                ' '
                            )
                        )
                    ) > 0");
                        }
                    }
                }

//                $searchRecord->whereNotNull('st.stock')
//                    ->groupBy(DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr)"))
//                    ->orderBy('br.brand_name', 'ASC')
//                    ->orderBy((DB::raw("IF(pt.type_flag = '2', pt.fam_name, f.family_name)")), 'ASC');

                $numSearch = $searchRecord->count();

                if ($numSearch === 0) {
                    $searchSoundexRecord->where(function ($query) use ($requestArray) {
                        foreach ($requestArray as $v) {
                            if (!is_numeric($v)) {
                                $query->orWhere(function ($query) use ($v) {
                                    $query->where('br.brand_name', 'SOUNDS LIKE', $v)
                                        ->orWhereRaw("SOUNDEX(IF(pt.type_flag = '2', pt.fam_name, f.family_name)) LIKE CONCAT(TRIM(TRAILING '0' FROM SOUNDEX('" . $v . "')),'%')");
                                });
                            }
                        }
                    });

                    $productsRecord = clone $searchSoundexRecord;
                } else {
                    $productsRecord = clone $searchRecord;
                }
                break;
            case 'by-concern':
                if ($keyWord != '' && $keyWord != '0') {
                    $productsRecord->where('pt.in_concern', $keyWord);
                } else {
                    $productsRecord->where('pt.in_concern', '!=', '');
                }
                break;
            case 'sale':
                // Fetching offer details
                $offerRecord = DB::table('offer_tbl')
                    ->select('*')
                    ->where('offer_slug', $keyWord)
                    ->first();

                if ($offerRecord) {
                    $products = $offerRecord->items !== '' ? explode(",", $offerRecord->items) : [];
                    $productsStr = !empty($products) ? implode("','", $products) : '';

                    $productsRecord->whereIn('pt.product_id', $products)
                        ->when($bFilterArray, function ($productsRecord) use ($bFilterArray) {
                            return $productsRecord->whereIn('pt.brand_id', $bFilterArray);
                        })
                        ->when($ftFilterArray, function ($productsRecord) use ($ftFilterArray) {
                            return $productsRecord->whereIn('pt.usages', $ftFilterArray);
                        })
                        ->when($sFilterStr, function ($productsRecord) use ($sFilterStr) {
                            return $productsRecord->whereIn('pt.size', explode(',', $sFilterStr));
                        })
                        ->when($maxPrice != 0, function ($productsRecord) use ($minPrice, $maxPrice) {
                            return $productsRecord->whereBetween(DB::raw('ROUND(pt.main_price, 0)'), [$minPrice, $maxPrice]);
                        })
                        ->when($filterType && $filterWord, function ($productsRecord) use ($filterType, $filterWord) {
                            switch ($filterType) {
                                case 'main-category':
                                    $productsRecord->where('pt.main_cat_id', $filterWord);
                                    break;
                                case 'category':
                                    $productsRecord->where('pt.cat_id', $filterWord);
                                    break;
                                case 'sub-category':
                                    $productsRecord->where('pt.sub_cat_id', $filterWord);
                                    break;
                                default:
                                    // Additional conditions if needed
                                    break;
                            }
                        });
                }
                break;
            case 'offer':
                $qryOffersRecord = Offer::where('status', '=', '1')
                    ->where('show_status', '=', '1')
                    ->whereRaw("FIND_IN_SET(?, in_domain)", [$domainId]);
                if ($keyWord != '') {
                    $qryOffersRecord->where('offer_slug', '=', $keyWord);
                }
                $qryOffersRecord->orderBy('offer_slug', 'ASC');

                $rowOffersRecord = $qryOffersRecord->get();

                if ($rowOffersRecord->isNotEmpty()) {
                    if ($rowOffersRecord->count() > 1) {
                        $productsRecord->where(function ($query) use ($rowOffersRecord) {
                            foreach ($rowOffersRecord as $offerRecord) {
                                $distributors = $offerRecord->item_distributor !== '' ? explode(",", $offerRecord->item_distributor) : [];
                                $brands = $offerRecord->item_brand !== '' ? explode(",", $offerRecord->item_brand) : [];
                                $mCategories = $offerRecord->item_main_category !== '' ? explode(",", $offerRecord->item_main_category) : [];
                                $categories = $offerRecord->item_category !== '' ? explode(",", $offerRecord->item_category) : [];
                                $subCategories = $offerRecord->item_sub_category !== '' ? explode(",", $offerRecord->item_sub_category) : [];
                                $flags = $offerRecord->item_flag !== '' ? explode(",", $offerRecord->item_flag) : [];
                                $products = $offerRecord->items !== '' ? explode(",", $offerRecord->items) : [];
                                $query->orWhere(function ($subQuery) use ($distributors, $brands, $mCategories, $categories, $subCategories, $flags, $products) {
                                    if (!empty($distributors)) {
                                        $subQuery->whereIn('pt.distributor', $distributors);
                                    }

                                    if (!empty($brands)) {
                                        $subQuery->whereIn('pt.brand_id', $brands);
                                    }

                                    if (!empty($mCategories)) {
                                        $subQuery->whereIn('mt.main_cat_id', $mCategories);
                                    }

                                    if (!empty($categories)) {
                                        $subQuery->whereIn('ct.cat_id', $categories);
                                    }

                                    if (!empty($subCategories)) {
                                        $subQuery->whereIn('pt.sub_cat_id', $subCategories);
                                    }

                                    if (!empty($flags)) {
                                        $subQuery->whereIn('pt.func_flag', $flags);
                                    }

                                    if (!empty($products)) {
                                        $subQuery->whereIn('pt.product_id', $products);
                                    }
                                });
                            }
                        });
                    } else {
                        $offerRecord = $rowOffersRecord->first();
                        $distributors = $offerRecord->item_distributor !== '' ? explode(",", $offerRecord->item_distributor) : [];
                        $brands = $offerRecord->item_brand !== '' ? explode(",", $offerRecord->item_brand) : [];
                        $mCategories = $offerRecord->item_main_category !== '' ? explode(",", $offerRecord->item_main_category) : [];
                        $categories = $offerRecord->item_category !== '' ? explode(",", $offerRecord->item_category) : [];
                        $subCategories = $offerRecord->item_sub_category !== '' ? explode(",", $offerRecord->item_sub_category) : [];
                        $flags = $offerRecord->item_flag !== '' ? explode(",", $offerRecord->item_flag) : [];
                        $products = $offerRecord->items !== '' ? explode(",", $offerRecord->items) : [];
                        $productsRecord->where(function ($query) use ($distributors, $brands, $mCategories, $categories, $subCategories, $flags, $products) {
                            if (!empty($distributors)) {
                                $query->whereIn('pt.distributor', $distributors);
                            }

                            if (!empty($brands)) {
                                $query->whereIn('pt.brand_id', $brands);
                            }

                            if (!empty($mCategories)) {
                                $query->whereIn('pt.main_cat_id', $mCategories);
                            }

                            if (!empty($categories)) {
                                $query->whereIn('pt.cat_id', $categories);
                            }

                            if (!empty($subCategories)) {
                                $query->whereIn('pt.sub_cat_id', $subCategories);
                            }

                            if (!empty($flags)) {
                                $query->whereIn('pt.func_flag', $flags);
                            }

                            if (!empty($products)) {
                                $query->whereIn('pt.product_id', $products);
                            }
                        });
                    }
                }

                // Fetching offer details
//                $offerRecordssss = Offer::select('*')
//                    ->where('offer_slug', $keyWord)
//                    ->where('show_status', '=', '1')
//                    ->whereRaw("FIND_IN_SET(?, in_domain)", [$domainId])
//                    ->first();
//
//                if ($offerRecordss) {
//                    $distributors = $offerRecord->item_distributor !== '' ? explode(",", $offerRecord->item_distributor) : [];
//                    if (!empty($brands)) {
//                        $productsRecord->whereIn('pt.distributor', $distributors);
//                    }
//
//                    $brands = $offerRecord->item_brand !== '' ? explode(",", $offerRecord->item_brand) : [];
//                    if (!empty($brands)) {
//                        $productsRecord->whereIn('pt.brand_id', $brands);
//                    }
//
//                    $mCategories = $offerRecord->item_main_category !== '' ? explode(",", $offerRecord->item_main_category) : [];
//                    if (!empty($mCategories)) {
//                        $productsRecord->whereIn('pt.main_cat_id', $mCategories);
//                    }
//
//                    $categories = $offerRecord->item_category !== '' ? explode(",", $offerRecord->item_category) : [];
//                    if (!empty($categories)) {
//                        $productsRecord->whereIn('pt.cat_id', $categories);
//                    }
//
//                    $subCategories = $offerRecord->item_sub_category !== '' ? explode(",", $offerRecord->item_sub_category) : [];
//                    if (!empty($subCategories)) {
//                        $productsRecord->whereIn('pt.sub_cat_id', $subCategories);
//                    }
//
//                    $flags = $offerRecord->item_flag !== '' ? explode(",", $offerRecord->item_flag) : [];
//                    if (!empty($flags)) {
//                        $productsRecord->whereIn('pt.func_flag', $flags);
//                    }
//
//                    $products = $offerRecord->items !== '' ? explode(",", $offerRecord->items) : [];
//                    if (!empty($products)) {
//                        $productsRecord->whereIn('pt.product_id', $products);
//                    }
//                }

                if (!empty($bFilterArray)) {
                    $productsRecord->whereIn('pt.brand_id', $bFilterArray);
                }

                if (!empty($ftFilterArray)) {
                    $productsRecord->whereIn('pt.usages', $ftFilterArray);
                }

                if ($sFilterStr != "") {
                    $productsRecord->whereIn('pt.size', explode(',', $sFilterStr));
                }

                if ($maxPrice != 0) {
                    $productsRecord->whereBetween(DB::raw('ROUND(pt.main_price, 0)'), [$minPrice, $maxPrice]);
                }

                if ($filterType && $filterWord) {
                    switch ($filterType) {
                        case 'main-category':
                            $mainCategoryId = $filterWord;
                            $subCategoryIds = SubCategory::whereIn('cat_id', function ($query) use ($mainCategoryId) {
                                $query->select('cat_id')
                                    ->from('cat_table')
                                    ->where('main_cat_id', $mainCategoryId);
                            })->pluck('sub_cat_id');
                            $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
                            break;
                        case 'category':
                            $subCategoryIds = SubCategory::where('cat_id', $filterWord)->pluck('sub_cat_id');
                            $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
                            break;
                        case 'sub-category':
                            $productsRecord->where('pt.sub_cat_id', $filterWord);
                            break;
                        default:
                            // Additional conditions if needed
                            break;
                    }
                }

                break;
            default:
                // Default case
                break;
        }
        $productsRecord->whereNotNull('st.stock')->groupBy(DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr)"));

        // Get the total number of products
        $totalProductsNum = $productsRecord->get()->count();

        switch ($sort) {
            case "pasc":
                $productsRecord->orderBy('min_price', 'ASC');
                break;
            case "pdesc":
                $productsRecord->orderBy('min_price', 'DESC');
                break;
            case "aasc":
                $productsRecord->orderByRaw("trim(family_name) ASC");
                break;
            case "adesc":
                $productsRecord->orderByRaw("trim(family_name) DESC");
                break;
            case "best-seller":
                $productsRecord->orderByRaw("CASE WHEN segment_slug = '' THEN 1 ELSE 0 END, segment_slug ASC");
                break;
            case "new-arrival":
                $productsRecord->orderByRaw("CASE WHEN segment_slug = '' THEN 1 ELSE 0 END, segment_slug DESC");
                break;
            default:
                $productsRecord->orderByRaw("trim(family_name) ASC");
//                $productsRecord->orderBy('family_name', 'ASC');
//                $productsRecord->orderBy('brand_name', 'ASC');
                break;
        }
        $productsRecord->skip($start)->take($limit);
        // Get the number of filtered products
        $filteredProductsNum = $productsRecord->get()->count();


        if ($filteredProductsNum > 0) {
            $filteredProducts = $productsRecord->get();
            foreach ($filteredProducts as $rowProductsRecord) {
                $subCat = $rowProductsRecord->sub_cat_id;
                $subCategory = Subcategory::with('category')->find($subCat);
                $cat = $subCategory->cat_id;
                $mainCat = $subCategory->category->main_cat_id;
                $brand = $rowProductsRecord->brand_id;
                $brandDetails = Brand::find($brand);
                $distributor = $brandDetails->distributor;
                $rowProductsRecord->distributor = $distributor;
                $flag = $rowProductsRecord->func_flag;
                $item = $rowProductsRecord->product_id;
                $webDiscount = 0;
                $webDiscountType = '';
                $webDiscountOffer = '';

                // Fetching discount offer by store
                $storeOffer = DB::table('offer_tbl as o')
                    ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
                    ->where('o.auto_apply', '1')
                    ->where('o.status', '1')
                    ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                    ->where('o.offer_type', 'store')
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
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o8.items FROM offer_tbl as o8 WHERE o8.offer_id=o.offer_id))', [$item]);
                    })
                    ->limit(1)
                    ->get();
                //dd($storeOffer);
                if ($storeOffer->isNotEmpty()) {
                    $rowOfferRecord = $storeOffer->first();
                    $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                    $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                    $webDiscountOffer = $rowOfferRecord->offer_name;
                }

                if ($user) {
                    //fetching auto apply offer by customer's tier
                    $tierOffer = DB::table('offer_tbl as o')
                        ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
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
                        ->limit(1)
                        ->get();


                    if ($tierOffer->isNotEmpty()) {
                        $rowOfferRecord = $tierOffer->first();
                        $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                        $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                        $webDiscountOffer = $rowOfferRecord->offer_name;
                    }

                    //fetching auto apply offer by customer's id or organization
                    $customerOffer = DB::table('offer_tbl as o')
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
                        ->limit(1)
                        ->get();


                    if ($customerOffer->isNotEmpty()) {
                        $rowOfferRecord = $customerOffer->first();
                        $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                        $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                        $webDiscountOffer = $rowOfferRecord->offer_name;
                    }


                }

//                if ($keyType == "offer") {
//                    // Fetching discount offer by store
//                    $offerRecord = DB::table('offer_tbl as o')
//                        ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name')
//                        ->where('o.offer_id', '=', $offerId)
//                        ->first();
//
//                    if ($offerRecord) {
//                        $webDiscount = !empty($offerRecord->gift_discount_value) ? (float)$offerRecord->gift_discount_value : 0;
//                        $webDiscountType = !empty($offerRecord->gift_discount_type) ? $offerRecord->gift_discount_type : '';
//                        $webDiscountOffer = $offerRecord->offer_name;
//                    }
//                }


//                    $greatestDiscountValue = max(
//                        $rowProductsRecord->discount_value_1,
//                        $rowProductsRecord->discount_value_2,
//                        $rowProductsRecord->discount_value_3,
//                        $rowProductsRecord->discount_value_4
//                    );
//
//                    $discountType = null;
//                    switch ($greatestDiscountValue) {
//                        case $rowProductsRecord->discount_value_1:
//                            $discountType = $rowProductsRecord->store_discount_type;
//                            break;
//                        case $rowProductsRecord->discount_value_2:
//                            $discountType = $rowProductsRecord->customer_tier_discount_type;
//                            break;
//                        case $rowProductsRecord->discount_value_3:
//                            $discountType = $rowProductsRecord->organisation_discount_type;
//                            break;
//                        default:
//                            $discountType = $rowProductsRecord->offer_discount_type;
//                            break;
//                    }
//
//                $rowProductsRecord->discount_type = $discountType;
//                $offerName = null;
//                switch ($greatestDiscountValue) {
//                    case $rowProductsRecord->discount_value_1:
//                        $offerName = $rowProductsRecord->store_offer_name;
//                        break;
//                    case $rowProductsRecord->discount_value_2:
//                        $offerName = $rowProductsRecord->customer_tier_offer_name;
//                        break;
//                    case $rowProductsRecord->discount_value_3:
//                        $offerName = $rowProductsRecord->organisation_offer_name;
//                        break;
//                    default:
//                        $offerName = $rowProductsRecord->offer_offer_name;
//                        break;
//                }
//
//                $rowProductsRecord->offer_name = $offerName;
//
//                $greatestDiscountValue = max(
//                    $rowProductsRecord->discount_value_1,
//                    $rowProductsRecord->discount_value_2,
//                    $rowProductsRecord->discount_value_3,
//                    $rowProductsRecord->discount_value_4
//                );
//
//                $greatestMaxPriceValue = max(
//                    $rowProductsRecord->max_price_1,
//                    $rowProductsRecord->max_price_2,
//                    $rowProductsRecord->max_price_3,
//                    $rowProductsRecord->max_price_4
//                );
//
//                $greatestMinPriceValue = max(
//                    $rowProductsRecord->min_price_1,
//                    $rowProductsRecord->min_price_2,
//                    $rowProductsRecord->min_price_3,
//                    $rowProductsRecord->min_price_4
//                );
//
//                $rowProductsRecord->dmain_price = $greatestDiscountValue;
//                $rowProductsRecord->dmax_price = $greatestMaxPriceValue;
//                $rowProductsRecord->dmin_price = $greatestMinPriceValue;


                switch ($webDiscountType) {
                    case 'amount':
                        $rowProductsRecord->dmain_price = $webDiscount > 0 ? round((float)$rowProductsRecord->main_price - (float)$webDiscount) : '';
                        $rowProductsRecord->dmax_price = $webDiscount > 0 ? round((float)$rowProductsRecord->max_price - (float)$webDiscount) : '';
                        $rowProductsRecord->dmin_price = $webDiscount > 0 ? round((float)$rowProductsRecord->min_price - (float)$webDiscount) : '';
                        break;
                    case 'percentage':
                        $rowProductsRecord->dmain_price = $webDiscount > 0 ? round((float)$rowProductsRecord->main_price - ((float)$rowProductsRecord->main_price * (float)$webDiscount / 100)) : '';
                        $rowProductsRecord->dmax_price = $webDiscount > 0 ? round((float)$rowProductsRecord->max_price - ((float)$rowProductsRecord->max_price * (float)$webDiscount / 100)) : '';
                        $rowProductsRecord->dmin_price = $webDiscount > 0 ? round((float)$rowProductsRecord->min_price - ((float)$rowProductsRecord->min_price * (float)$webDiscount / 100)) : '';
                        break;
                    default:
                        $rowProductsRecord->dmain_price = '';
                        $rowProductsRecord->dmax_price = '';
                        $rowProductsRecord->dmin_price = '';
                        break;
                }

                $rowProductsRecord->offer_name = $webDiscountOffer;
                //$rowProductsRecord->family_name = ucwords(strtolower($rowProductsRecord->family_name));
                $rowProductsRecord->offer_sql = '';
                $productArr[] = $rowProductsRecord;

            }
        }


        return response()->json(["total" => $totalProductsNum, "filter" => $filteredProductsNum, "result" => $productArr]);

    }

    public function newProductFilter(Request $request)
    {

        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $data = [];
        $webDiscount = 0;
        $webDiscountOffer = '';
        $currentDate = now()->format('Y-m-d');
        $currentDay = strtolower(now()->format('D'));
        $customerId = '';
        $customerOrganization = '';
        $customerTier = '';
        $user = Auth::user();
        if ($user) {
            $customerId = $user->customer_id;
            $customerOrganization = $user->organization;
            $customerTier = $user->tier;
        }

        $productArr = [];
        $keyType = strtolower(trim(strip_tags($request->input('key_type'))));
        $keyWord = strtolower(trim(strip_tags($request->input('key_word'))));
        $prdctType = strtolower(trim(strip_tags($request->input('prdct_type'))));
        $bFilter = strtolower(trim(strip_tags($request->input('bfilter'))));
        $crFilter = trim(strip_tags($request->input('crfilter')));
        $clFilter = trim(strip_tags($request->input('clfilter')));
        $sgFilter = trim(strip_tags($request->input('sgfilter')));
        $spFilter = trim(strip_tags($request->input('spfilter')));
        $ftFilter = trim(strip_tags($request->input('ftfilter')));
        $sFilter = trim(strip_tags($request->input('sfilter')));
        $filterType = strtolower(trim(strip_tags($request->input('filter_type'))));
        $filterWord = strtolower(trim(strip_tags($request->input('filter_word'))));
        $sort = strtolower(trim(strip_tags($request->input('sort'))));
        $minPrice = $request->filled('min_price') ? (float)trim($request->input('min_price')) : 0;
        $maxPrice = $request->filled('max_price') ? (float)trim($request->input('max_price')) : 0;
        $refPage = $request->filled('ref_page') ? strtolower(trim(strip_tags($request->input('ref_page')))) : '';
        $start = $request->input('start');
        $limit = $request->input('limit');

        $bFilterArray = $bFilter != '' ? explode(',', $bFilter) : [];
//      $bFilterStr = !empty($bFilterArr) ? implode("','", $bFilterArr) : '';
        $crFilterArray = $crFilter != '' ? explode(',', $crFilter) : [];
        $clFilterArray = $clFilter != '' ? explode(',', $clFilter) : [];
        $cFilterStr = !empty($cFilterArr) ? implode("','", $cFilterArr) : '';
        $sgFilterArray = $sgFilter != '' ? explode(',', $sgFilter) : [];
        $spFilterArr = $spFilter != '' ? explode(',', $spFilter) : [];
        $spFilterStr = !empty($spFilterArr) ? implode("','", $spFilterArr) : '';
        $ftFilterArray = $ftFilter != '' ? explode(',', $ftFilter) : [];
        $sFilterArr = $sFilter != '' ? explode(',', $sFilter) : [];
        $sFilterStr = !empty($sFilterArr) ? implode("','", $sFilterArr) : '';
        $bFilterArrCount = count($bFilterArray);
        $productFilterArray = [];

        $offerWhere1 = "";
        $offerWhere2 = "";
        $offerWhere3 = "";
        $offerWhere4 = "";
        if ($keyType == "offer") {
            $offerWhere1 = " AND `oo1`.`show_status` = '1'";
            if ($keyType != '') {
                $offerWhere1 .= " AND `oo1`.`offer_slug`='" . $keyWord . "'";
            }
        }
        if ($keyType == "offer") {
            $offerWhere2 = " AND `oo2`.`show_status` = '1'";
            if ($keyType != '') {
                $offerWhere2 .= " AND `oo2`.`offer_slug`='" . $keyWord . "'";
            }
        }
        if ($keyType == "offer") {
            $offerWhere3 = " AND `oo3`.`show_status` = '1'";
            if ($keyType != '') {
                $offerWhere3 .= " AND `oo3`.`offer_slug`='" . $keyWord . "'";
            }
        }
        if ($keyType == "offer") {
            if ($keyType != '') {
                $offerWhere4 .= " AND `oo4`.`offer_slug`='" . $keyWord . "'";
            }
        }

        $productsRecord = DB::table('product_table as pt')
            ->select([
                'pt.in_segment',
                'pt.type_flag as product_type',
                'pt.product_id',
                DB::raw("IF(pt.type_flag = '2', pt.seo_url, f.seo_url) as seo_url"),
                'pt.sub_cat_id',
                'pt.func_flag',
                'br.brand_id',
                'br.brand_name',
                'pt.linepr',
                'pt.has_gift',
                DB::raw("ROUND(pt.main_price, 0) as main_price"),
                DB::raw("ROUND(MAX(pt.main_price), 0) as max_price"),
                DB::raw("ROUND(MIN(NULLIF(pt.main_price, 0)), 0) as min_price"),
                DB::raw("IF(pt.`type_flag` = '2',
                    CONCAT(
                        SUBSTR(REPLACE(pt.`product_name`, '\\\', ''), 1, 60),
                        IF(
                            CHAR_LENGTH(REPLACE(pt.`product_name`, '\\\', '')) > 60,
                            '..',
                            ''
                        )
                    ),
                    CONCAT(
                        SUBSTR(REPLACE(`f`.`family_name`, '\\\', ''), 1, 60),
                        IF(
                            CHAR_LENGTH(REPLACE(`f`.`family_name`, '\\\', '')) > 60,
                            '..',
                            ''
                        )
                    )
                ) AS `family_name`"),
                DB::raw("IF(pt.type_flag = '2', pt.photo1, fp.aws) as family_pic"),
                'st.stock',
                DB::raw("IFNULL(SUBSTRING_INDEX(sg.title, ' ', 1), '') as segment"),
                DB::raw("IFNULL(sg.slug, '') as segment_slug"),
                DB::raw("IFNULL(sg.type, '') as segment_type"),
                DB::raw("IFNULL(o.offer_id, '') as has_offer"),
                DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) as unique_key"),
                DB::raw("disc.gift_discount_value as `store_discount_value`"),
                DB::raw("disc.gift_discount_type as `store_discount_type`"),
                DB::raw("disc.offer_name as `store_offer_name`"),
                DB::raw("disc2.gift_discount_value as `customer_tier_discount`"),
                DB::raw("disc2.gift_discount_type as `customer_tier_discount_type`"),
                DB::raw("disc2.offer_name as `customer_tier_offer_name`"),
                DB::raw("disc3.gift_discount_value as `organisation_discount_value`"),
                DB::raw("disc3.gift_discount_type as `organisation_discount_type`"),
                DB::raw("disc3.offer_name as `organisation_offer_name`"),
                DB::raw("CASE WHEN disc.gift_discount_value IS NULL OR disc.gift_discount_value = '' THEN 0
            WHEN disc.gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * disc.gift_discount_value / 100)
            ELSE pt.main_price - disc.gift_discount_value END as discount_value_1"),
                DB::raw("CASE WHEN disc.gift_discount_value IS NULL OR disc.gift_discount_value = '' THEN 0
            WHEN disc.gift_discount_type = 'percentage' THEN MAX(pt.main_price) - (MAX(pt.main_price) * disc.gift_discount_value / 100)
            ELSE MAX(pt.main_price) - disc.gift_discount_value END as max_price_1"),
                DB::raw("CASE WHEN disc.gift_discount_value IS NULL OR disc.gift_discount_value = '' THEN 0
            WHEN disc.gift_discount_type = 'percentage' THEN MIN(pt.main_price) - (MIN(pt.main_price) * disc.gift_discount_value / 100)
            ELSE MIN(pt.main_price) - disc.gift_discount_value END as min_price_1"),
                DB::raw("CASE WHEN disc2.gift_discount_value IS NULL OR disc2.gift_discount_value = '' THEN 0
            WHEN disc2.gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * disc2.gift_discount_value / 100)
            ELSE pt.main_price - disc2.gift_discount_value END as discount_value_2"),
                DB::raw("CASE WHEN disc2.gift_discount_value IS NULL OR disc2.gift_discount_value = '' THEN 0
            WHEN disc2.gift_discount_type = 'percentage' THEN MAX(pt.main_price) - (MAX(pt.main_price) * disc2.gift_discount_value / 100)
            ELSE MAX(pt.main_price) - disc2.gift_discount_value END as max_price_2"),
                DB::raw("CASE WHEN disc2.gift_discount_value IS NULL OR disc2.gift_discount_value = '' THEN 0
            WHEN disc2.gift_discount_type = 'percentage' THEN MIN(pt.main_price) - (MIN(pt.main_price) * disc2.gift_discount_value / 100)
            ELSE MIN(pt.main_price) - disc2.gift_discount_value END as min_price_2"),
                DB::raw("CASE WHEN disc3.gift_discount_value IS NULL OR disc3.gift_discount_value = '' THEN 0
            WHEN disc3.gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * disc3.gift_discount_value / 100)
            ELSE pt.main_price - disc3.gift_discount_value END as discount_value_3"),
                DB::raw("CASE WHEN disc3.gift_discount_value IS NULL OR disc3.gift_discount_value = '' THEN 0
            WHEN disc3.gift_discount_type = 'percentage' THEN MAX(pt.main_price) - (MAX(pt.main_price) * disc3.gift_discount_value / 100)
            ELSE MAX(pt.main_price) - disc3.gift_discount_value END as max_price_3"),
                DB::raw("CASE WHEN disc3.gift_discount_value IS NULL OR disc3.gift_discount_value = '' THEN 0
            WHEN disc3.gift_discount_type = 'percentage' THEN MIN(pt.main_price) - (MIN(pt.main_price) * disc3.gift_discount_value / 100)
            ELSE MIN(pt.main_price) - disc3.gift_discount_value END as min_price_3"),
//                DB::raw("CAST(GREATEST(
//                    (CASE WHEN disc.gift_discount_value IS NULL OR disc.gift_discount_value = '' THEN 0
//                          WHEN disc.gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * disc.gift_discount_value / 100)
//                          ELSE pt.main_price - disc.gift_discount_value
//                     END),
//                    (CASE WHEN disc2.gift_discount_value IS NULL OR disc2.gift_discount_value = '' THEN 0
//                          WHEN disc2.gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * disc2.gift_discount_value / 100)
//                          ELSE pt.main_price - disc2.gift_discount_value
//                     END),
//                    (CASE WHEN disc3.gift_discount_value IS NULL OR disc3.gift_discount_value = '' THEN 0
//                          WHEN disc3.gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * disc3.gift_discount_value / 100)
//                          ELSE pt.main_price - disc3.gift_discount_value
//                     END),
//                    (CASE WHEN gift_discount_value4 IS NULL OR gift_discount_value4 = '' THEN 0
//                          WHEN gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * gift_discount_value4 / 100)
//                          ELSE pt.main_price - disc4.gift_discount_value
//                     END)
//                ) AS DECIMAL(20,2)) AS dmain_price"),
//                DB::raw("CAST(GREATEST(`max_price_1`,`max_price_2`,`max_price_3`,`max_price_4`) AS DECIMAL(20,2)) as dmax_price"),
//                DB::raw("CAST(GREATEST(`min_price_1`,`min_price_2`,`min_price_3`,`min_price_4`) AS DECIMAL(20,2)) as dmin_price"),
//                DB::raw("CASE GREATEST(`discount_value_1`,`discount_value_2`,`discount_value_3`,`discount_value_4`)
//                                            WHEN `discount_value_1` THEN `store_discount_type`
//                                            WHEN `discount_value_2` THEN `customer_tier_discount_type`
//                                            WHEN `discount_value_3` THEN `organisation_discount_type`
//                                            ELSE `offer_discount_type` END AS `discount_type`"),
//                DB::raw("CASE GREATEST(`discount_value_1`,`discount_value_2`,`discount_value_3`,`discount_value_4`)
//                                            WHEN `discount_value_1` THEN `store_offer_name`
//                                            WHEN `discount_value_2` THEN `customer_tier_offer_name`
//                                            WHEN `discount_value_3` THEN `organisation_offer_name`
//                                            ELSE `offer_offer_name` END AS `offer_name`"),
            ])
            ->join(DB::raw('(SELECT id as brand_id, name as brand_name, distributor FROM brand_table WHERE web_status = "1") as br'), 'pt.brand_id', '=', 'br.brand_id')
            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sc'), 'pt.sub_cat_id', '=', 'sc.sub_cat_id')
            ->join(DB::raw('(SELECT cat_id, cat_name, main_cat_id FROM cat_table) as ct'), 'sc.cat_id', '=', 'ct.cat_id')
            ->join(DB::raw('(SELECT main_cat_id, main_cat_name FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
            ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
            ->leftJoin(DB::raw('(SELECT family_id, family_name, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
            ->leftJoin(DB::raw('(SELECT family_id,aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
            ->leftJoin(DB::raw("(SELECT id, title, slug, type FROM gc_segments WHERE slug IN ('new-arrival', 'best-seller') AND domain = '" . $domainId . "') as sg"), function ($join) {
                $join->on(DB::raw('FIND_IN_SET(sg.id, pt.in_segment)'), '>', DB::raw('0'));
            })
            ->leftJoin(DB::raw("(SELECT offer_id, items FROM offer_tbl WHERE status = '1' AND FIND_IN_SET('" . $domainId . "', in_domain) AND items != '' AND offer_for IN ('1','2') AND ((('" . $currentDate . "' BETWEEN from_date AND to_date) OR (from_date <= '" . $currentDate . "' AND to_date = '0000-00-00 00:00:00')) OR (FIND_IN_SET('" . $currentDay . "', week_days))) ) as o"), function ($join) {
                $join->on(DB::raw('FIND_IN_SET(pt.product_id, o.items)'), '>', DB::raw('0'));
            })
            ->where('pt.web_status', '=', '1')
            ->where('pt.main_price', '>', 0)
            ->whereRaw('FIND_IN_SET(?, pt.in_domain)', [$domainId])
            ->leftJoin(DB::raw("(SELECT `oo1`.`item_flag`,`oo1`.`item_brand`,`oo1`.`item_sub_category`,`oo1`.`item_category`,`oo1`.`item_main_category`,`oo1`.`item_distributor`,`oo1`.`offer_id`,`oo1`.`items`,`oo1`.`gift_discount_type`,`oo1`.`gift_discount_value`,oo1.`gift_points`,oo1.`offer_name`
                                FROM `offer_tbl` as `oo1` WHERE oo1.`auto_apply`='1' AND oo1.`status`='1' AND FIND_IN_SET('" . $domainId . "',`oo1`.`in_domain`) AND `oo1`.`offer_type` = 'store' AND  `oo1`.`offer_for` IN ('1','2')
                                " . $offerWhere1 . " AND FIND_IN_SET(50, (SELECT `os1`.`offer_store` FROM `offer_tbl` as `os1` WHERE os1.`offer_id`=`oo1`.`offer_id`))
                                AND ((('" . $currentDate . "' BETWEEN oo1.`from_date` AND oo1.`to_date`) OR (oo1.`from_date` <= '" . $currentDate . "' AND oo1.`to_date`='0000-00-00 00:00:00')) OR (FIND_IN_SET('" . $currentDay . "', (SELECT oo2.`week_days` FROM `offer_tbl` as `oo2` WHERE oo2.`offer_id`=oo1.`offer_id`))))
                                ) as `disc`"), function ($join) {
                $join->on(function ($query) {
                    $query->whereRaw("(`disc`.`item_distributor`='' OR `disc`.`item_distributor`='all' OR (FIND_IN_SET(`br`.`distributor`, (SELECT `disc`.`item_distributor` FROM `offer_tbl` as `o2` WHERE o2.`offer_id`=`disc`.`offer_id`))))")
                        ->whereRaw("(`disc`.`item_main_category`='' OR `disc`.`item_main_category`='all' OR (FIND_IN_SET(`mt`.`main_cat_id`, (SELECT `disc`.`item_main_category` FROM `offer_tbl` as `o3` WHERE o3.`offer_id`=`disc`.`offer_id`))))")
                        ->whereRaw("(`disc`.`item_category`='' OR `disc`.`item_category`='all' OR (FIND_IN_SET(`ct`.`cat_id`, (SELECT `disc`.`item_category` FROM `offer_tbl` as `o5` WHERE o5.`offer_id`=`disc`.`offer_id`))))")
                        ->whereRaw("(`disc`.`item_sub_category`='' OR `disc`.`item_sub_category`='all' OR (FIND_IN_SET(`pt`.`sub_cat_id`, (SELECT `disc`.`item_sub_category` FROM `offer_tbl` as `o4` WHERE o4.`offer_id`=`disc`.`offer_id`))))")
                        ->whereRaw("(`disc`.`item_brand`='' OR `disc`.`item_brand`='all' OR (FIND_IN_SET(`pt`.`brand_id`, (SELECT `disc`.`item_brand` FROM `offer_tbl` as `o6` WHERE o6.`offer_id`=`disc`.`offer_id`))))")
                        ->whereRaw("(`disc`.`item_flag`='' OR `disc`.`item_flag`='all' OR (FIND_IN_SET(`pt`.`func_flag`, (SELECT `disc`.`item_flag` FROM `offer_tbl` as `o7` WHERE o7.`offer_id`=`disc`.`offer_id`))))")
                        ->whereRaw("(`disc`.`items`='' OR `disc`.`items`='all' OR (FIND_IN_SET(`pt`.`product_id`, (SELECT `disc`.`items` FROM `offer_tbl` as `o7` WHERE `o7`.`offer_id`=`disc`.`offer_id`))))");
                });
            })
            ->leftJoin(DB::raw("(SELECT `oo2`.`item_brand`, `oo2`.`item_flag`, `oo2`.`item_sub_category`, `oo2`.`item_distributor`, `oo2`.`item_main_category`, `oo2`.`item_category`, `oo2`.`items`, `oo2`.`offer_id`, `oo2`.`gift_discount_type`, `oo2`.`gift_discount_value`, `oo2`.`gift_points`, `oo2`.`offer_name`
                                FROM `offer_tbl` as `oo2` WHERE `oo2`.`auto_apply` = '1' AND `oo2`.`status` = '1' AND FIND_IN_SET(" . $domainId . ", `oo2`.`in_domain`) AND `oo2`.`offer_type` = 'tier' AND  `oo2`.`offer_for` IN ('1', '2')
                                 " . $offerWhere2 . " AND (FIND_IN_SET('" . $customerTier . "', (SELECT group_concat(`oo`.`offer_tier` )FROM `offer_tbl` as `oo` WHERE `oo2`.`offer_id`= `oo2`.`offer_id` )))
                                 AND ((('" . $currentDate . "' BETWEEN `oo2`.`from_date` AND `oo2`.`to_date`) OR (`oo2`.`from_date` <= '" . $currentDate . "' AND `oo2`.`to_date` = '0000-00-00 00:00:00')) OR (FIND_IN_SET('" . $currentDay . "', (SELECT `oo2`.`week_days` FROM `offer_tbl` as `o1` WHERE o1.`offer_id` = `oo2`.`offer_id`))))
                                 ) as `disc2`"), function ($join) {
                $join->on(function ($query) {
                    $query->whereRaw("(`disc2`.`item_distributor` = '' OR `disc2`.`item_distributor` = 'all' OR (FIND_IN_SET(`br`.`distributor`, (SELECT `disc2`.`item_distributor` FROM `offer_tbl` as `o2` WHERE o2.`offer_id` = `disc2`.`offer_id`))))")
                        ->whereRaw("(`disc2`.`item_main_category` = '' OR `disc2`.`item_main_category` = 'all' OR (FIND_IN_SET(`mt`.`main_cat_id`, (SELECT `disc2`.`item_main_category` FROM `offer_tbl` as `o3` WHERE o3.`offer_id` = `disc2`.`offer_id`))))")
                        ->whereRaw("(`disc2`.`item_category` = '' OR `disc2`.`item_category` = 'all' OR (FIND_IN_SET(`ct`.`cat_id`, (SELECT `disc2`.`item_category` FROM `offer_tbl` as `o5` WHERE o5.`offer_id` = `disc2`.`offer_id`))))")
                        ->whereRaw("(`disc2`.`item_sub_category` = '' OR `disc2`.`item_sub_category` = 'all' OR (FIND_IN_SET(`pt`.`sub_cat_id`, (SELECT `disc2`.`item_sub_category` FROM `offer_tbl` as `o4` WHERE o4.`offer_id` = `disc2`.`offer_id`))))")
                        ->whereRaw("(`disc2`.`item_brand` = '' OR `disc2`.`item_brand` = 'all' OR (FIND_IN_SET(`pt`.`brand_id`, (SELECT `disc2`.`item_brand` FROM `offer_tbl` as `o6` WHERE o6.`offer_id` = `disc2`.`offer_id`))))")
                        ->whereRaw("(`disc2`.`item_flag` = '' OR `disc2`.`item_flag` = 'all' OR (FIND_IN_SET(`pt`.`func_flag`, (SELECT `disc2`.`item_flag` FROM `offer_tbl` as `o7` WHERE o7.`offer_id` = `disc2`.`offer_id`))))")
                        ->whereRaw("(`disc2`.`items` = '' OR `disc2`.`items` = 'all' OR (FIND_IN_SET(`pt`.`product_id`, (SELECT `disc2`.`items` FROM `offer_tbl` as `o7` WHERE o7.`offer_id` = `disc2`.`offer_id`))))");
                });
            })
            ->leftJoin(DB::raw("(SELECT `oo3`.`item_brand`, `oo3`.`item_flag`, `oo3`.`item_sub_category`, `oo3`.`item_distributor`, `oo3`.`item_main_category`, `oo3`.`item_category`, `oo3`.`items`, `oo3`.`offer_id`, `oo3`.`gift_discount_type`, `oo3`.`gift_discount_value`, `oo3`.`gift_points`, `oo3`.`offer_name`
                                FROM `offer_tbl` as `oo3` WHERE `oo3`.`auto_apply` = '1' AND `oo3`.`status` = '1' AND FIND_IN_SET(" . $domainId . ", `oo3`.`in_domain`) AND `oo3`.`offer_type`IN ('location','organization','store') AND  `oo3`.`offer_for` IN ('1', '2') " . $offerWhere3 . " AND
                                 ((FIND_IN_SET('" . $customerTier . "', (SELECT oc.`offer_location` FROM `offer_tbl` as `oc` WHERE oc.`offer_id`=`oo3`.`offer_id`)))
                                 OR (FIND_IN_SET('" . $customerOrganization . "', (SELECT oo.`offer_organization` FROM `offer_tbl` as `oo` WHERE oo.`offer_id`=`oo3`.`offer_id`))))
                                 AND ((('" . $currentDate . "' BETWEEN `oo3`.`from_date` AND `oo3`.`to_date`) OR (`oo3`.`from_date` <= '" . $currentDate . "' AND `oo3`.`to_date` = '0000-00-00 00:00:00')) OR (FIND_IN_SET('" . $currentDay . "', (SELECT `oo3`.`week_days` FROM `offer_tbl` as `o1` WHERE o1.`offer_id` = `oo3`.`offer_id`))))
                                 ) as `disc3`"), function ($join) {
                $join->on(function ($query) {
                    $query->whereRaw("(`disc3`.`item_distributor` = '' OR `disc3`.`item_distributor` = 'all' OR (FIND_IN_SET(`br`.`distributor`, (SELECT `disc3`.`item_distributor` FROM `offer_tbl` as `o2` WHERE o2.`offer_id` = `disc3`.`offer_id`))))")
                        ->whereRaw("(`disc3`.`item_main_category` = '' OR `disc3`.`item_main_category` = 'all' OR (FIND_IN_SET(`mt`.`main_cat_id`, (SELECT `disc3`.`item_main_category` FROM `offer_tbl` as `o3` WHERE o3.`offer_id` = `disc3`.`offer_id`))))")
                        ->whereRaw("(`disc3`.`item_category` = '' OR `disc3`.`item_category` = 'all' OR (FIND_IN_SET(`ct`.`cat_id`, (SELECT `disc3`.`item_category` FROM `offer_tbl` as `o5` WHERE o5.`offer_id` = `disc3`.`offer_id`))))")
                        ->whereRaw("(`disc3`.`item_sub_category` = '' OR `disc3`.`item_sub_category` = 'all' OR (FIND_IN_SET(`pt`.`sub_cat_id`, (SELECT `disc3`.`item_sub_category` FROM `offer_tbl` as `o4` WHERE o4.`offer_id` = `disc3`.`offer_id`))))")
                        ->whereRaw("(`disc3`.`item_brand` = '' OR `disc3`.`item_brand` = 'all' OR (FIND_IN_SET(`pt`.`brand_id`, (SELECT `disc3`.`item_brand` FROM `offer_tbl` as `o6` WHERE o6.`offer_id` = `disc3`.`offer_id`))))")
                        ->whereRaw("(`disc3`.`item_flag` = '' OR `disc3`.`item_flag` = 'all' OR (FIND_IN_SET(`pt`.`func_flag`, (SELECT `disc3`.`item_flag` FROM `offer_tbl` as `o7` WHERE o7.`offer_id` = `disc3`.`offer_id`))))")
                        ->whereRaw("(`disc3`.`items` = '' OR `disc3`.`items` = 'all' OR (FIND_IN_SET(`pt`.`product_id`, (SELECT `disc3`.`items` FROM `offer_tbl` as `o7` WHERE o7.`offer_id` = `disc3`.`offer_id`))))");
                });
            });

        if ($keyType == "offer" || ($keyType == "search")) {
            $productsRecord->join(DB::raw("(SELECT `oo4`.`item_brand`, `oo4`.`item_flag`, `oo4`.`item_sub_category`, `oo4`.`item_distributor`, `oo4`.`item_main_category`, `oo4`.`item_category`, `oo4`.`items`, `oo4`.`offer_id`, `oo4`.`gift_discount_type`, `oo4`.`gift_discount_value`, `oo4`.`gift_points`, `oo4`.`offer_name`
                                FROM `offer_tbl` as `oo4` WHERE `oo4`.`show_status` = '1' " . $offerWhere4 . "
                                 ) as `disc4`"), function ($join) {
                $join->on(function ($query) {
                    $query->whereRaw("(`disc4`.`item_distributor` = '' OR `disc4`.`item_distributor` = 'all' OR (FIND_IN_SET(`br`.`distributor`, (SELECT `disc4`.`item_distributor` FROM `offer_tbl` as `o2` WHERE o2.`offer_id` = `disc4`.`offer_id`))))")
                        ->whereRaw("(`disc4`.`item_main_category` = '' OR `disc4`.`item_main_category` = 'all' OR (FIND_IN_SET(`mt`.`main_cat_id`, (SELECT `disc4`.`item_main_category` FROM `offer_tbl` as `o3` WHERE o3.`offer_id` = `disc4`.`offer_id`))))")
                        ->whereRaw("(`disc4`.`item_category` = '' OR `disc4`.`item_category` = 'all' OR (FIND_IN_SET(`ct`.`cat_id`, (SELECT `disc4`.`item_category` FROM `offer_tbl` as `o5` WHERE o5.`offer_id` = `disc4`.`offer_id`))))")
                        ->whereRaw("(`disc4`.`item_sub_category` = '' OR `disc4`.`item_sub_category` = 'all' OR (FIND_IN_SET(`pt`.`sub_cat_id`, (SELECT `disc4`.`item_sub_category` FROM `offer_tbl` as `o4` WHERE o4.`offer_id` = `disc4`.`offer_id`))))")
                        ->whereRaw("(`disc4`.`item_brand` = '' OR `disc4`.`item_brand` = 'all' OR (FIND_IN_SET(`pt`.`brand_id`, (SELECT `disc4`.`item_brand` FROM `offer_tbl` as `o6` WHERE o6.`offer_id` = `disc4`.`offer_id`))))")
                        ->whereRaw("(`disc4`.`item_flag` = '' OR `disc4`.`item_flag` = 'all' OR (FIND_IN_SET(`pt`.`func_flag`, (SELECT `disc4`.`item_flag` FROM `offer_tbl` as `o7` WHERE o7.`offer_id` = `disc4`.`offer_id`))))")
                        ->whereRaw("(`disc4`.`items` = '' OR `disc4`.`items` = 'all' OR (FIND_IN_SET(`pt`.`product_id`, (SELECT `disc4`.`items` FROM `offer_tbl` as `o7` WHERE o7.`offer_id` = `disc4`.`offer_id`))))");
                });
            });
            $productsRecord->selectRaw("NOW() as start_date,
                                disc4.to_date as end_date,
                                disc4.gift_discount_value as offer_discount_value,
                                disc4.gift_discount_type as offer_discount_type,
                                disc4.offer_name as offer_offer_name,
                                CASE
                                    WHEN disc4.gift_discount_value IS NULL OR disc4.gift_discount_value = '' THEN 0
                                    WHEN disc4.gift_discount_type = 'percentage' THEN pt.main_price - (pt.main_price * disc4.gift_discount_value / 100)
                                    ELSE pt.main_price - disc4.gift_discount_value
                                END AS discount_value_4,
                                CASE
                                    WHEN disc4.gift_discount_value IS NULL OR disc4.gift_discount_value = '' THEN 0
                                    WHEN disc4.gift_discount_type = 'percentage' THEN MAX(pt.main_price) - (MAX(pt.main_price) * disc4.gift_discount_value / 100)
                                    ELSE MAX(pt.main_price) - disc4.gift_discount_value
                                END AS max_price_4,
                                CASE
                                    WHEN disc4.gift_discount_value IS NULL OR disc4.gift_discount_value = '' THEN 0
                                    WHEN disc4.gift_discount_type = 'percentage' THEN MIN(pt.main_price) - (MIN(pt.main_price) * disc4.gift_discount_value / 100)
                                    ELSE MIN(pt.main_price) - disc4.gift_discount_value
                                END AS min_price_4");
        } else {
            $productsRecord->selectRaw("null as start_date,
                                null as end_date,
                                0 AS discount_value_4,
                                0 AS max_price_4,
                                0 AS min_price_4,
                                0 AS offer_discount_value,
                                NULL AS offer_discount_type,
                                NULL AS offer_offer_name");
        }

        if (!empty($crFilterArray)) {
            $productsRecord->whereIn('pt.in_concern', $crFilterArray);
        }

        if (!empty($sgFilterArray)) {
            $productsRecord->where(function ($productsRecord) use ($sgFilterArray) {
                foreach ($sgFilterArray as $sgk => $sgFilter) {
                    if ($sgk > 0) {
                        $productsRecord->orWhereRaw("FIND_IN_SET('" . $sgFilter . "', pt.in_segment)");
                    } else {
                        $productsRecord->whereRaw("FIND_IN_SET('" . $sgFilter . "', pt.in_segment)");
                    }
                }
            });
        }

        if (!empty($bFilterArray)) {
            $productsRecord->whereIn('pt.brand_id', $bFilterArray);
        }

        if (!empty($spFilterArray)) {
            $productsRecord->whereIn('pt.sun_protect', $spFilterArray);
        }

        if (!empty($ftFilterArray)) {
            $productsRecord->whereIn('pt.usages', $ftFilterArray);
        }

        if (!empty($sfilter_str)) {
            $productsRecord->whereIn('pt.size', explode(',', $sfilter_str));
        }

        $filterWordArray = $filterWord != '' ? explode(',', $filterWord) : [];
        switch ($filterType) {
            case 'main-category':
                $categoryIds = Category::whereIn('main_cat_id', $filterWordArray)->pluck('cat_id');
                $subCategoryIds = SubCategory::whereIn('cat_id', $categoryIds)->pluck('sub_cat_id');
                $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
                break;
            case 'category':
                $subCategoryIds = SubCategory::whereIn('cat_id', $filterWordArray)->pluck('sub_cat_id');
                $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
                break;
            case 'sub-category':
                $productsRecord->whereIn('pt.sub_cat_id', $filterWordArray);
                break;
        }

        if (isset($minPrice) && isset($maxPrice) && $maxPrice != 0) {
            $productsRecord->whereRaw('ROUND(pt.main_price, 0) BETWEEN ? AND ?', [$minPrice, $maxPrice]);
        }

        if (empty($ref_page)) {
            // Uncomment the line below if needed
            // $productsRecord->where('bd.web_status', '=', 1);
        }

        switch ($keyType) {
            case 'main-category':
                $mainCategoryId = $keyWord;
                $subCategoryIds = SubCategory::whereIn('cat_id', function ($query) use ($mainCategoryId) {
                    $query->select('cat_id')
                        ->from('cat_table')
                        ->where('main_cat_id', $mainCategoryId);
                })->pluck('sub_cat_id');
                $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
                break;
            case 'category':
                $subCategoryIds = SubCategory::where('cat_id', $keyWord)->pluck('sub_cat_id');
                $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
                break;
            case 'sub-category':
                $productsRecord->where('pt.sub_cat_id', '=', $keyWord);
                break;
            case 'brand':
                $productsRecord->where('pt.brand_id', '=', $keyWord);
                break;
            case 'segment':
                $segmentQuery = Segment::select('dynamic_mysql', 'id')
                    ->where('id', $keyWord);
                $row_segment = $segmentQuery->first();
                if ($row_segment && $row_segment->dynamic_mysql) {
                    $productsRecord->whereRaw($row_segment->dynamic_mysql);
                } else {
                    $productsRecord->whereIn('pt.in_segment', [$row_segment->id]);
                }

//                if ($filterType && $filterType != "" && $filterWord && $filterWord != "") {
//                    switch ($filterType) {
//                        case 'main-category':
//                            $categoryIds = Category::whereIn('main_cat_id', $filterWordArray)->pluck('cat_id');
//                            $subCategoryIds = SubCategory::whereIn('cat_id', $categoryIds)->pluck('sub_cat_id');
//                            $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
//                            break;
//                        case 'category':
//                            $subCategoryIds = SubCategory::whereIn('cat_id', $filterWordArray)->pluck('sub_cat_id');
//                            $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
//                            break;
//                        case 'sub-category':
//                            $productsRecord->where('pt.sub_cat_id', '=', $filterWord);
//                            break;
//                        default:
//                            // Additional conditions if needed
//                            break;
//                    }
//                }
                break;
            case 'search':
                $searchRecord = clone $productsRecord;
                $searchSoundexRecord = clone $productsRecord;
                $requestArray = explode(' ', $keyWord);

                if (!empty($requestArray)) {
                    foreach ($requestArray as $v) {
                        if (ctype_alpha($v)) {
                            $searchRecord->whereRaw("LOCATE('" . strtolower($v) . "',
                        LOWER(
                            CONCAT(
                                ' ',
                                br.brand_name,
                                ' ',
                                IF(pt.type_flag = '2', pt.fam_name, f.family_name),
                                ' ',
                                mt.main_cat_name,
                                ' ',
                                ct.cat_name,
                                ' ',
                                sc.sub_cat_name,
                                ' ',
                                pt.Ref_no,
                                ' ',
                                pt.barcode,
                                ' '
                            )
                        )
                    ) > 0");
                        } else {
                            $searchRecord->whereRaw("LOCATE('" . strtolower($v) . "',
                        LOWER(
                            CONCAT(
                                ' ',
                                pt.Ref_no,
                                ' ',
                                pt.barcode,
                                ' '
                            )
                        )
                    ) > 0");
                        }
                    }
                }

//                $searchRecord->whereNotNull('st.stock')
//                    ->groupBy(DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr)"))
//                    ->orderBy('br.brand_name', 'ASC')
//                    ->orderBy((DB::raw("IF(pt.type_flag = '2', pt.fam_name, f.family_name)")), 'ASC');

                $numSearch = $searchRecord->count();

                if ($numSearch === 0) {
                    $searchSoundexRecord->where(function ($query) use ($requestArray) {
                        foreach ($requestArray as $v) {
                            if (!is_numeric($v)) {
                                $query->orWhere(function ($query) use ($v) {
                                    $query->where('br.brand_name', 'SOUNDS LIKE', $v)
                                        ->orWhereRaw("SOUNDEX(IF(pt.type_flag = '2', pt.fam_name, f.family_name)) LIKE CONCAT(TRIM(TRAILING '0' FROM SOUNDEX('" . $v . "')),'%')");
                                });
                            }
                        }
                    });

                    $productsRecord = clone $searchSoundexRecord;
                } else {
                    $productsRecord = clone $searchRecord;
                }
                break;
            case 'by-concern':
                if ($keyWord != '' && $keyWord != '0') {
                    $productsRecord->where('pt.in_concern', $keyWord);
                } else {
                    $productsRecord->where('pt.in_concern', '!=', '');
                }
                break;
            case 'sale':
                // Fetching offer details
                $offerRecord = DB::table('offer_tbl')
                    ->select('*')
                    ->where('offer_slug', $keyWord)
                    ->first();

                if ($offerRecord) {
                    $products = $offerRecord->items !== '' ? explode(",", $offerRecord->items) : [];
                    $productsStr = !empty($products) ? implode("','", $products) : '';

                    $productsRecord->whereIn('pt.product_id', $products)
                        ->when($bFilterArray, function ($productsRecord) use ($bFilterArray) {
                            return $productsRecord->whereIn('pt.brand_id', $bFilterArray);
                        })
                        ->when($ftFilterArray, function ($productsRecord) use ($ftFilterArray) {
                            return $productsRecord->whereIn('pt.usages', $ftFilterArray);
                        })
                        ->when($sFilterStr, function ($productsRecord) use ($sFilterStr) {
                            return $productsRecord->whereIn('pt.size', explode(',', $sFilterStr));
                        })
                        ->when($maxPrice != 0, function ($productsRecord) use ($minPrice, $maxPrice) {
                            return $productsRecord->whereBetween(DB::raw('ROUND(pt.main_price, 0)'), [$minPrice, $maxPrice]);
                        })
                        ->when($filterType && $filterWord, function ($productsRecord) use ($filterType, $filterWord) {
                            switch ($filterType) {
                                case 'main-category':
                                    $productsRecord->where('pt.main_cat_id', $filterWord);
                                    break;
                                case 'category':
                                    $productsRecord->where('pt.cat_id', $filterWord);
                                    break;
                                case 'sub-category':
                                    $productsRecord->where('pt.sub_cat_id', $filterWord);
                                    break;
                                default:
                                    // Additional conditions if needed
                                    break;
                            }
                        });
                }
                break;
            case 'offer':
                // Fetching offer details
                $offerRecord = Offer::select('*')
                    ->where('offer_slug', $keyWord)
                    ->where('show_status', '1')
                    ->whereRaw("FIND_IN_SET(?, in_domain)", [$domainId])
                    ->first();

                if ($offerRecord) {
                    $distributors = $offerRecord->item_distributor !== '' ? explode(",", $offerRecord->item_distributor) : [];
                    $distributorsStr = !empty($distributors) ? implode("','", $distributors) : '';
                    if ($distributorsStr != "") {
                        $productsRecord->whereIn('pt.distributor', explode(',', $distributorsStr));
                    }

                    $brands = $offerRecord->item_brand !== '' ? explode(",", $offerRecord->item_brand) : [];
                    $brandsStr = !empty($brands) ? implode("','", $brands) : '';
                    if ($brandsStr != "") {
                        $productsRecord->whereIn('pt.brand_id', explode(',', $brandsStr));
                    }

                    $mCategories = $offerRecord->item_main_category !== '' ? explode(",", $offerRecord->item_main_category) : [];
                    $mCategoriesStr = !empty($mCategories) ? implode("','", $mCategories) : '';
                    if ($mCategoriesStr != "") {
                        $productsRecord->whereIn('pt.main_cat_id', explode(',', $mCategoriesStr));
                    }

                    $categories = $offerRecord->item_category !== '' ? explode(",", $offerRecord->item_category) : [];
                    $categoriesStr = !empty($categories) ? implode("','", $categories) : '';
                    if ($categoriesStr != "") {
                        $productsRecord->whereIn('pt.cat_id', explode(',', $categoriesStr));
                    }

                    $subCategories = $offerRecord->item_sub_category !== '' ? explode(",", $offerRecord->item_sub_category) : [];
                    $subCategoriesStr = !empty($subCategories) ? implode("','", $subCategories) : '';
                    if ($subCategoriesStr != "") {
                        $productsRecord->whereIn('pt.sub_cat_id', explode(',', $subCategoriesStr));
                    }

                    $flags = $offerRecord->item_flag !== '' ? explode(",", $offerRecord->item_flag) : [];
                    $flagsStr = !empty($flags) ? implode("','", $flags) : '';
                    if ($flagsStr != "") {
                        $productsRecord->whereIn('pt.func_flag', explode(',', $flagsStr));
                    }

                    $products = $offerRecord->items !== '' ? explode(",", $offerRecord->items) : [];
                    $productsStr = !empty($products) ? implode("','", $products) : '';
                    if ($productsStr != "") {
                        $productsRecord->whereIn('pt.product_id', explode(',', $productsStr));
                    }

                    if (!empty($bFilterArray)) {
                        $productsRecord->whereIn('pt.brand_id', $bFilterArray);
                    }

                    if (!empty($ftFilterArray)) {
                        $productsRecord->whereIn('pt.usages', $ftFilterArray);
                    }

                    if ($sFilterStr != "") {
                        $productsRecord->whereIn('pt.size', explode(',', $sFilterStr));
                    }

                    if ($maxPrice != 0) {
                        $productsRecord->whereBetween(DB::raw('ROUND(pt.main_price, 0)'), [$minPrice, $maxPrice]);
                    }

                    if ($filterType && $filterWord) {
                        switch ($filterType) {
                            case 'main-category':
                                $mainCategoryId = $filterWord;
                                $subCategoryIds = SubCategory::whereIn('cat_id', function ($query) use ($mainCategoryId) {
                                    $query->select('cat_id')
                                        ->from('cat_table')
                                        ->where('main_cat_id', $mainCategoryId);
                                })->pluck('sub_cat_id');
                                $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
                                break;
                            case 'category':
                                $subCategoryIds = SubCategory::where('cat_id', $filterWord)->pluck('sub_cat_id');
                                $productsRecord->whereIn('pt.sub_cat_id', $subCategoryIds);
                                break;
                            case 'sub-category':
                                $productsRecord->where('pt.sub_cat_id', $filterWord);
                                break;
                            default:
                                // Additional conditions if needed
                                break;
                        }
                    }
                }
                break;
            default:
                // Default case
                break;
        }
        $productsRecord->whereNotNull('st.stock')->groupBy(DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr)"));

        // Get the total number of products
        $totalProductsNum = $productsRecord->get()->count();

        switch ($sort) {
            case "pasc":
                $productsRecord->orderBy('min_price', 'ASC');
                break;
            case "pdesc":
                $productsRecord->orderBy('min_price', 'DESC');
                break;
            case "aasc":
                $productsRecord->orderBy('family_name', 'ASC');
                break;
            case "adesc":
                $productsRecord->orderBy('family_name', 'DESC');
                break;
            case "best-seller":
                $productsRecord->orderByRaw("CASE WHEN segment_slug = '' THEN 1 ELSE 0 END, segment_slug ASC");
                break;
            case "new-arrival":
                $productsRecord->orderByRaw("CASE WHEN segment_slug = '' THEN 1 ELSE 0 END, segment_slug DESC");
                break;
            default:
                $productsRecord->orderByRaw("trim(family_name) ASC");
//                $productsRecord->orderBy('family_name', 'ASC');
//                $productsRecord->orderBy('brand_name', 'ASC');
                break;
        }
        $productsRecord->skip($start)->take($limit);
        // Get the number of filtered products
        $filteredProductsNum = $productsRecord->get()->count();


        if ($filteredProductsNum > 0) {
            $filteredProducts = $productsRecord->get();
            foreach ($filteredProducts as $rowProductsRecord) {
                $subCat = $rowProductsRecord->sub_cat_id;
                $subCategory = Subcategory::with('category')->find($subCat);
                $cat = $subCategory->cat_id;
                $mainCat = $subCategory->category->main_cat_id;
                $brand = $rowProductsRecord->brand_id;
                $brandDetails = Brand::find($brand);
                $distributor = $brandDetails->distributor;
                $rowProductsRecord->distributor = $distributor;
                $flag = $rowProductsRecord->func_flag;
                $item = $rowProductsRecord->product_id;
                $webDiscount = 0;
                $webDiscountType = '';
                $webDiscountOffer = '';

                // Fetching discount offer by store
                $storeOffer = DB::table('offer_tbl as o')
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
                    ->limit(1)
                    ->get();

                if ($storeOffer->isNotEmpty()) {
                    $rowOfferRecord = $storeOffer->first();
                    $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                    $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                    $webDiscountOffer = $rowOfferRecord->offer_name;
                }

                if ($user) {
                    //fetching auto apply offer by customer's tier
                    $tierOffer = DB::table('offer_tbl as o')
                        ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
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
                        ->limit(1)
                        ->get();


                    if ($tierOffer->isNotEmpty()) {
                        $rowOfferRecord = $tierOffer->first();
                        $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                        $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                        $webDiscountOffer = $rowOfferRecord->offer_name;
                    }

                    //fetching auto apply offer by customer's id or organization
                    $customerOffer = DB::table('offer_tbl as o')
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
                        ->limit(1)
                        ->get();


                    if ($customerOffer->isNotEmpty()) {
                        $rowOfferRecord = $customerOffer->first();
                        $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                        $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                        $webDiscountOffer = $rowOfferRecord->offer_name;
                    }


                }

//                if ($keyType == "offer") {
//                    // Fetching discount offer by store
//                    $offerRecord = DB::table('offer_tbl as o')
//                        ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name')
//                        ->where('o.offer_id', '=', $offerId)
//                        ->first();
//
//                    if ($offerRecord) {
//                        $webDiscount = !empty($offerRecord->gift_discount_value) ? (float)$offerRecord->gift_discount_value : 0;
//                        $webDiscountType = !empty($offerRecord->gift_discount_type) ? $offerRecord->gift_discount_type : '';
//                        $webDiscountOffer = $offerRecord->offer_name;
//                    }
//                }


//                    $greatestDiscountValue = max(
//                        $rowProductsRecord->discount_value_1,
//                        $rowProductsRecord->discount_value_2,
//                        $rowProductsRecord->discount_value_3,
//                        $rowProductsRecord->discount_value_4
//                    );
//
//                    $discountType = null;
//                    switch ($greatestDiscountValue) {
//                        case $rowProductsRecord->discount_value_1:
//                            $discountType = $rowProductsRecord->store_discount_type;
//                            break;
//                        case $rowProductsRecord->discount_value_2:
//                            $discountType = $rowProductsRecord->customer_tier_discount_type;
//                            break;
//                        case $rowProductsRecord->discount_value_3:
//                            $discountType = $rowProductsRecord->organisation_discount_type;
//                            break;
//                        default:
//                            $discountType = $rowProductsRecord->offer_discount_type;
//                            break;
//                    }
//
//                $rowProductsRecord->discount_type = $discountType;
//                $offerName = null;
//                switch ($greatestDiscountValue) {
//                    case $rowProductsRecord->discount_value_1:
//                        $offerName = $rowProductsRecord->store_offer_name;
//                        break;
//                    case $rowProductsRecord->discount_value_2:
//                        $offerName = $rowProductsRecord->customer_tier_offer_name;
//                        break;
//                    case $rowProductsRecord->discount_value_3:
//                        $offerName = $rowProductsRecord->organisation_offer_name;
//                        break;
//                    default:
//                        $offerName = $rowProductsRecord->offer_offer_name;
//                        break;
//                }
//
//                $rowProductsRecord->offer_name = $offerName;
//
//                $greatestDiscountValue = max(
//                    $rowProductsRecord->discount_value_1,
//                    $rowProductsRecord->discount_value_2,
//                    $rowProductsRecord->discount_value_3,
//                    $rowProductsRecord->discount_value_4
//                );
//
//                $greatestMaxPriceValue = max(
//                    $rowProductsRecord->max_price_1,
//                    $rowProductsRecord->max_price_2,
//                    $rowProductsRecord->max_price_3,
//                    $rowProductsRecord->max_price_4
//                );
//
//                $greatestMinPriceValue = max(
//                    $rowProductsRecord->min_price_1,
//                    $rowProductsRecord->min_price_2,
//                    $rowProductsRecord->min_price_3,
//                    $rowProductsRecord->min_price_4
//                );
//
//                $rowProductsRecord->dmain_price = $greatestDiscountValue;
//                $rowProductsRecord->dmax_price = $greatestMaxPriceValue;
//                $rowProductsRecord->dmin_price = $greatestMinPriceValue;


                switch ($webDiscountType) {
                    case 'amount':
                        $rowProductsRecord->dmain_price = $webDiscount > 0 ? round((float)$rowProductsRecord->main_price - (float)$webDiscount) : '';
                        $rowProductsRecord->dmax_price = $webDiscount > 0 ? round((float)$rowProductsRecord->max_price - (float)$webDiscount) : '';
                        $rowProductsRecord->dmin_price = $webDiscount > 0 ? round((float)$rowProductsRecord->min_price - (float)$webDiscount) : '';
                        break;
                    case 'percentage':
                        $rowProductsRecord->dmain_price = $webDiscount > 0 ? round((float)$rowProductsRecord->main_price - ((float)$rowProductsRecord->main_price * (float)$webDiscount / 100)) : '';
                        $rowProductsRecord->dmax_price = $webDiscount > 0 ? round((float)$rowProductsRecord->max_price - ((float)$rowProductsRecord->max_price * (float)$webDiscount / 100)) : '';
                        $rowProductsRecord->dmin_price = $webDiscount > 0 ? round((float)$rowProductsRecord->min_price - ((float)$rowProductsRecord->min_price * (float)$webDiscount / 100)) : '';
                        break;
                    default:
                        $rowProductsRecord->dmain_price = '';
                        $rowProductsRecord->dmax_price = '';
                        $rowProductsRecord->dmin_price = '';
                        break;
                }

                $rowProductsRecord->offer_name = $webDiscountOffer;
                //$rowProductsRecord->family_name = ucwords(strtolower($rowProductsRecord->family_name));
                $rowProductsRecord->offer_sql = '';
                $productArr[] = $rowProductsRecord;

            }
        }


        return response()->json(["total" => $totalProductsNum, "filter" => $filteredProductsNum, "result" => $productArr]);

    }

    public function fetchProductDetail(Request $request)
    {

        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $currentDate = now();
        $currentDay = strtolower(now()->format('D'));
        $productId = $request->input('product');
        $requestType = $request->input('prdcttype');
        $requestKey = $request->input('prdctkey');
        $user = Auth::user();
        $webDiscount = 0;

        if ($user) {
            $customerId = $user->customer_id;
            $customerOrganization = $user->organization;
            $customerTier = $user->tier;
        }

        $productRecord = Product::select('type_flag as product_type', 'product_id', 'linepr', 'seo_url', 'sub_cat_id', 'photo1', 'photo2', 'seo_url', 'brand_id', 'collection_id')
            ->where('product_id', $productId)
            ->first();

        $productType = $productRecord->product_type;
        $subCategory = Subcategory::with('category')->find($productRecord->sub_cat_id);
        $productMainCategory = $subCategory->category->main_cat_id;

// Product brand details (i.e., brand logo)
        $brandDetails = Brand::select('logo', 'name')
            ->where('id', $productRecord->brand_id)
            ->first();

        $brandLogo = $brandDetails->logo ?? '';
        $brandName = $brandDetails->name ?? '';

        if ($productType == '2') { // for product only
            $familyDetails = Product::selectRaw('REPLACE(product_name, "\\\", "") as title, long_desc, IFNULL(r.rating, 0) as rating, IFNULL(l.love, 0) as love, r.review')
                ->leftJoin(DB::raw('(SELECT product_id, ROUND(AVG(rating)) as rating, COUNT(rating) as review FROM gc_reviews GROUP BY product_id) as r'), 'product_table.product_id', '=', 'r.product_id')
                ->leftJoin(DB::raw('(SELECT product_id, COUNT(wish_id) as love FROM gc_wishlist GROUP BY product_id) as l'), 'product_table.product_id', '=', 'l.product_id')
                ->where('product_table.product_id', $productId)
                ->first();

            $familyName = $familyDetails->title ?? '';
            $familyDesc = $familyDetails ? substr(strip_tags($familyDetails->long_desc), 0, 200) . '....' : '';
        } else {
            $familyDetails = Family::selectRaw('family_name, family_desc, meta_title, meta_desc, meta_keywords, IFNULL(r.rating, 0) as rating, IFNULL(l.love, 0) as love, r.review')
                ->leftJoin(DB::raw('(SELECT product_id, ROUND(AVG(rating)) as rating, COUNT(rating) as review FROM gc_reviews GROUP BY product_id) as r'), 'family_tbl.family_id', '=', 'r.product_id')
                ->leftJoin(DB::raw('(SELECT product_id, COUNT(wish_id) as love FROM gc_wishlist GROUP BY product_id) as l'), 'family_tbl.family_id', '=', 'l.product_id')
                ->where('family_tbl.family_id', $productRecord->linepr)
                ->first();

            $familyName = $familyDetails ? ucwords(strtolower(stripslashes($familyDetails->family_name))) : '';
            $familyDesc = $familyDetails ? substr(strip_tags($familyDetails->family_desc), 0, 200) . '....' : '';
        }


        $productsArr = [];

        $productQuery = DB::table('product_table as pt')
            ->select(
                'pt.type_flag as product_type',
                'pt.product_id',
                'pt.product_no',
                'pt.fam_name',
                DB::raw('IF(pt.type_flag = "2", pt.seo_url, f.seo_url) AS seo_url'),
                DB::raw('IF(pt.type_flag = "2", CONCAT(SUBSTR(REPLACE(pt.fam_name, "\\\", ""), 1, 60), IF(CHAR_LENGTH(REPLACE(pt.fam_name, "\\\", "")) > 60, "..", "")), CONCAT(SUBSTR(REPLACE(pt.title, "\\\", ""), 1, 60), IF(CHAR_LENGTH(REPLACE(pt.title, "\\\", "")) > 60, "..", ""))) AS title'),
                'pt.sub_cat_id',
                'pt.func_flag',
                DB::raw('ROUND(pt.main_price, 1) AS main_price'),
                'pt.photo1',
                'pt.photo2',
                'pt.linepr',
                'pt.brand_id',
                'pt.has_gift',
                DB::raw('IF(pt.is_voucher = "1", vs.stock, st.stock) AS stock'),
                DB::raw('IF(pt.type_flag = "2", pt.seo_url, f.seo_url) AS seo_url'),
                DB::raw('IFNULL(r.rating, 0) AS rating'),
                DB::raw('IFNULL(l.love, 0) AS love'),
                DB::raw('IFNULL(r.count_rating, 0) AS count_rating'),
                DB::raw('IFNULL(o.offer_id, "") AS has_offer'),
                'pt.barcode',
                'pt.is_voucher'
            )
            ->leftJoin('family_tbl as f', 'pt.linepr', '=', 'f.family_id')
            ->leftJoin(DB::raw('(SELECT SUM(qty) AS stock, product_id FROM stock_table GROUP BY product_id) as st'), 'pt.product_id', '=', 'st.product_id')
            ->leftJoin(DB::raw('(SELECT COUNT(*) AS stock, barcode FROM voucher_table WHERE status="GENERATED" AND partner_id = "7" GROUP BY barcode) as vs'), 'pt.barcode', '=', 'vs.barcode')
            ->leftJoin(DB::raw('(SELECT product_id, ROUND(AVG(rating)) AS rating, COUNT(rating) AS count_rating FROM gc_reviews GROUP BY product_id) AS r'), 'pt.product_id', '=', 'r.product_id')
            ->leftJoin(DB::raw('(SELECT product_id, COUNT(wish_id) AS love FROM gc_wishlist GROUP BY product_id) AS l'), 'pt.product_id', '=', 'l.product_id')
            ->leftJoin(DB::raw('(SELECT offer_id, items FROM offer_tbl WHERE status="1" AND FIND_IN_SET("' . $domainId . '", in_domain) AND items!="" AND offer_for IN ("1","2")) AS o'), 'pt.product_id', '=', 'o.items')
            ->where('pt.web_status', '1')
            ->where('pt.main_price', '>', 0);

// Collection
        if ($requestType == 'collection' && $productRecord->in_collection != '') {
            $productQuery->where('pt.collection_id', $productRecord->in_collection);
        } elseif ($requestType == 'collection') {
            $productQuery->where('pt.collection_id', '!=', '0');
        }

// Segment
        if ($requestType == 'segment' && $requestKey != '') {
            $segment = Segment::select('id', 'dynamic_mysql')->where('slug', $requestKey)->whereRaw('FIND_IN_SET(?, domain)', [$domainId])->first();
            if ($segment && $segment->dynamic_mysql != '') {
                $productQuery->whereRaw($segment->dynamic_mysql);
            } elseif ($segment) {
                $productQuery->whereRaw('FIND_IN_SET(?, pt.in_segment)', [$segment->id]);
            }
        }

// Sale
        if ($requestType == 'sale' && $requestKey != '') {
            $offer = OfferTbl::select('items')->where('offer_slug', $requestKey)->first();
            if ($offer && $offer->items != '') {
                $productQuery->whereIn('pt.product_id', explode(",", $offer->items));
            }
        }

// Offer
        if ($requestType == 'offer' && $requestKey != '') {
            $offerRecord = OfferTbl::where('offer_slug', $requestKey)
                ->where('show_status', '1')
                ->whereRaw('FIND_IN_SET(?, in_domain)', [$domainId])
                ->first();

            if ($offerRecord) {
                $distributors = $offerRecord->item_distributor != '' ? explode(",", $offerRecord->item_distributor) : [];
                $brands = $offerRecord->item_brand != '' ? explode(",", $offerRecord->item_brand) : [];
                $mcategories = $offerRecord->item_main_category != '' ? explode(",", $offerRecord->item_main_category) : [];
                $categories = $offerRecord->item_category != '' ? explode(",", $offerRecord->item_category) : [];
                $scategories = $offerRecord->item_sub_category != '' ? explode(",", $offerRecord->item_sub_category) : [];
                $products = $offerRecord->items != '' ? explode(",", $offerRecord->items) : [];

                if (!empty($distributors)) {
                    $productQuery->whereIn('pt.distributor', $distributors);
                }
                if (!empty($brands)) {
                    $productQuery->whereIn('pt.brand_id', $brands);
                }
                if (!empty($mcategories)) {
                    $productQuery->whereIn('pt.main_cat_id', $mcategories);
                }
                if (!empty($categories)) {
                    $productQuery->whereIn('pt.cat_id', $categories);
                }
                if (!empty($scategories)) {
                    $productQuery->whereIn('pt.sub_cat_id', $scategories);
                }
                if (!empty($products)) {
                    $productQuery->whereIn('pt.product_id', $products);
                }
            }
        }

// Product Type
        if ($productType == '2') { // for product only
            $productQuery->where('pt.product_id', $productId);
        } else {
            $productQuery->where('pt.linepr', $productRecord->linepr);
        }

        $productQuery->havingRaw('stock IS NOT NULL');
        $productQuery->orderBy('main_price', 'ASC');

// Execute the query
        $products = $productQuery->get();

        $productArray = [];
        foreach ($products as $rowProductsRecord) {
            $subCat = $rowProductsRecord->sub_cat_id;
            $subCategory = Subcategory::with('category')->find($subCat);
            $cat = $subCategory->cat_id;
            $mainCat = $subCategory->category->main_cat_id;
            $brand = $rowProductsRecord->brand_id;
            $brandDetails = Brand::find($brand);
            $distributor = $brandDetails->distributor;
            $flag = $rowProductsRecord->func_flag;
            $item = $rowProductsRecord->product_id;
            $webDiscount = 0;
            $webDiscountType = '';
            $webPoints = '';

            // Fetching discount offer by store
            $offer = DB::table('offer_tbl as o')
                ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name')
                ->where('o.auto_apply', '1')
                ->where('o.status', '1')
                ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                ->where('o.offer_type', 'store')
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
                ->limit(1)
                ->get();

            if ($offer->isNotEmpty()) {
                $rowOfferRecord = $offer->first();
                $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                $webPoints = $rowOfferRecord->gift_points > 0 ? (float)$rowOfferRecord->gift_points : 0;
            }

            if ($user) {

                //fetching auto apply offer by customer's tier
                $offer = DB::table('offer_tbl as o')
                    ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name')
                    ->where('o.auto_apply', '1')
                    ->where('o.status', '1')
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
                        $query->where('o.item_distributor', '')
                            ->orWhere('o.item_distributor', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                    })
                    ->where(function ($query) use ($mainCat) {
                        $query->where('o.item_main_category', '')
                            ->orWhere('o.item_main_category', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                    })
                    ->where(function ($query) use ($cat) {
                        $query->where('o.item_category', '')
                            ->orWhere('o.item_category', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                    })
                    ->where(function ($query) use ($subCat) {
                        $query->where('o.item_sub_category', '')
                            ->orWhere('o.item_sub_category', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                    })
                    ->where(function ($query) use ($brand) {
                        $query->where('o.item_brand', '')
                            ->orWhere('o.item_brand', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                    })
                    ->where(function ($query) use ($flag) {
                        $query->where('o.item_flag', '')
                            ->orWhere('o.item_flag', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                    })
                    ->where(function ($query) use ($item) {
                        $query->where('o.items', '')
                            ->orWhere('o.items', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                    })
                    ->limit(1)
                    ->get();


                if ($offer->isNotEmpty()) {
                    $rowOfferRecord = $offer->first();
                    $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                    $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                    $webPoints = $rowOfferRecord->gift_points > 0 ? (float)$rowOfferRecord->gift_points : 0;
                }

                //fetching auto apply offer by customer's id or organization
                $offer = DB::table('offer_tbl as o')
                    ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name')
                    ->where('o.auto_apply', '1')
                    ->where('o.status', '1')
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
                        $query->where('o.item_distributor', '')
                            ->orWhere('o.item_distributor', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                    })
                    ->where(function ($query) use ($mainCat) {
                        $query->where('o.item_main_category', '')
                            ->orWhere('o.item_main_category', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                    })
                    ->where(function ($query) use ($cat) {
                        $query->where('o.item_category', '')
                            ->orWhere('o.item_category', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                    })
                    ->where(function ($query) use ($subCat) {
                        $query->where('o.item_sub_category', '')
                            ->orWhere('o.item_sub_category', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                    })
                    ->where(function ($query) use ($brand) {
                        $query->where('o.item_brand', '')
                            ->orWhere('o.item_brand', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                    })
                    ->where(function ($query) use ($flag) {
                        $query->where('o.item_flag', '')
                            ->orWhere('o.item_flag', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                    })
                    ->where(function ($query) use ($item) {
                        $query->where('o.items', '')
                            ->orWhere('o.items', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                    })
                    ->limit(1)
                    ->get();


                if ($offer->isNotEmpty()) {
                    $rowOfferRecord = $offer->first();
                    $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                    $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                    $webPoints = $rowOfferRecord->gift_points > 0 ? (float)$rowOfferRecord->gift_points : 0;
                }


            }


            switch ($webDiscountType) {
                case 'amount':
                    $rowProductsRecord->dmain_price = $webDiscount > 0 ? round((float)$rowProductsRecord->main_price - (float)$webDiscount) : '';
                    break;
                case 'percentage':
                    $rowProductsRecord->dmain_price = $webDiscount > 0 ? round((float)$rowProductsRecord->main_price - ((float)$rowProductsRecord->main_price * (float)$webDiscount / 100)) : '';
                    break;
                default:
                    $rowProductsRecord->dmain_price = '';
                    break;
            }
            // Fetching discount offer by store
            $offerRecords = DB::table('offer_tbl as o')
                ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
                ->where('o.auto_apply', '0')
                ->where('o.status', '1')
                ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                ->where('o.offer_type', 'store')
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

            $offerArray = [];

            foreach ($offerRecords as $rowOfferRecord) {
                $offerArray[$rowOfferRecord->offer_id] = (array)$rowOfferRecord;
            }

            $loyaltyPoints = 0;
            $price = $rowProductsRecord->dmain_price !== '' ? $rowProductsRecord->dmain_price : $rowProductsRecord->main_price;

            if ($webPoints > 0) {
                $loyaltyPoints = round((float)$webPoints * (float)$price);
            }

            $rowProductsRecord->lylty_pnts = $webDiscount > 0 ? 0 : $loyaltyPoints;
            $rowProductsRecord->offers = $offerArray;
//            $productArray[$rowProductsRecord->product_id] = $rowProductsRecord;
            $productArray[$rowProductsRecord->product_id] = $rowProductsRecord;

        }

        $data = [
            "main_category" => $productMainCategory,
            "brand_name" => $brandName,
            "family_name" => $familyName,
            "family_desc" => $familyDesc,
            "product_arr" => $productArray,
            "image_arr" => []
        ];

        return response()->json($data);

    }

    public function productImages(Request $request)
    {

        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $data = [];
        $currentDate = now();
        $currentDay = strtolower(now()->format('D'));
        $productId = $request->input('product');
        $productType = $request->input('product_type');


        if ($productType == 'bundle') {
            $bundleImage = DB::table('bundle_table as bd')
                ->select(DB::raw('CONCAT("' . config('app.ikasco_url') . 'uploads/", bd.image) AS image'))
                ->where('bd.id', '=', $productId)
                ->first();

            $imageArray[] = $bundleImage->image;
        } else {
            $imageArray = DB::table('product_more_pic_tbl')
                ->select('*')
                ->where('product_id', '=', $productId)
                ->whereNotNull('aws')
                ->orderBy('id', 'ASC')
                ->get()
                ->toArray();
        }

        return response()->json($imageArray);

    }

    public function productDetail(string $slug)
    {
        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $data = [];
        $currentDate = now();
        $currentDay = strtolower(now()->format('D'));
        $productId = '';
        $requestType = '';
        $requestKey = '';

        $user = Auth::user();
        $webDiscount = 0;

        if ($user) {
            $customerId = $user->customer_id;
            $customerOrganization = $user->organization;
            $customerTier = $user->tier;
        }

        $productRecord = DB::table('product_table as pt')
            ->select('pt.type_flag as product_type', 'pt.product_id', 'pt.linepr', 'pt.sub_cat_id', 'pt.brand_id')
            ->leftJoin('family_tbl as f', 'pt.linepr', '=', 'f.family_id')
            ->where('pt.web_status', '1')
            ->where(function ($query) use ($slug) {
                $query->where('pt.type_flag', '=', '2')
                    ->where('pt.seo_url', '=', $slug);
            })
            ->orWhere(function ($query) use ($slug) {
                $query->where('pt.type_flag', '!=', '2')
                    ->where('f.seo_url', '=', $slug);
            })
            ->first();
        $productId = $productRecord->product_id;
        $productType = $productRecord->product_type;
        $subCategory = Subcategory::with('category')->find($productRecord->sub_cat_id);
        $productMainCategory = $subCategory->category->main_cat_id;

// Product brand details (i.e., brand logo)
        $brandDetails = Brand::select('logo', 'name')
            ->where('id', $productRecord->brand_id)
            ->first();

        $brandLogo = $brandDetails->logo ?? '';
        $brandName = $brandDetails->name ?? '';

        if ($productType == '2') { // for product only
            $familyDetails = Product::selectRaw('REPLACE(product_name, "\\\", "") as title, long_desc, IFNULL(r.rating, 0) as rating, IFNULL(l.love, 0) as love, r.review')
                ->leftJoin(DB::raw('(SELECT product_id, ROUND(AVG(rating)) as rating, COUNT(rating) as review FROM gc_reviews GROUP BY product_id) as r'), 'product_table.product_id', '=', 'r.product_id')
                ->leftJoin(DB::raw('(SELECT product_id, COUNT(wish_id) as love FROM gc_wishlist GROUP BY product_id) as l'), 'product_table.product_id', '=', 'l.product_id')
                ->where('product_table.product_id', $productId)
                ->first();

            $familyName = $familyDetails->title ?? '';
            $familyDesc = $familyDetails ? stripslashes($familyDetails->long_desc) : '';
        } else {
            $familyDetails = Family::selectRaw('family_name, family_desc, meta_title, meta_desc, meta_keywords, IFNULL(r.rating, 0) as rating, IFNULL(l.love, 0) as love, r.review')
                ->leftJoin(DB::raw('(SELECT product_id, ROUND(AVG(rating)) as rating, COUNT(rating) as review FROM gc_reviews GROUP BY product_id) as r'), 'family_tbl.family_id', '=', 'r.product_id')
                ->leftJoin(DB::raw('(SELECT product_id, COUNT(wish_id) as love FROM gc_wishlist GROUP BY product_id) as l'), 'family_tbl.family_id', '=', 'l.product_id')
                ->where('family_tbl.family_id', $productRecord->linepr)
                ->first();

            $familyName = $familyDetails ? ucwords(strtolower(stripslashes($familyDetails->family_name))) : '';
            $familyDesc = $familyDetails ? stripslashes($familyDetails->family_desc) : '';
        }


        $productsArr = [];

        $productQuery = DB::table('product_table as pt')
            ->select(
                'pt.type_flag as product_type',
                'pt.product_id',
                'pt.product_no',
                'pt.fam_name',
                DB::raw('IF(pt.type_flag = "2", pt.seo_url, f.seo_url) AS seo_url'),
                DB::raw('IF(pt.type_flag = "2", CONCAT(SUBSTR(REPLACE(pt.fam_name, "\\\", ""), 1, 60), IF(CHAR_LENGTH(REPLACE(pt.fam_name, "\\\", "")) > 60, "..", "")), CONCAT(SUBSTR(REPLACE(pt.title, "\\\", ""), 1, 60), IF(CHAR_LENGTH(REPLACE(pt.title, "\\\", "")) > 60, "..", ""))) AS title'),
                'pt.sub_cat_id',
                'pt.func_flag',
                DB::raw('ROUND(pt.main_price, 1) AS main_price'),
                'pt.photo1',
                'pt.photo2',
                'pt.linepr',
                'pt.brand_id',
                'pt.has_gift',
                DB::raw('IF(pt.is_voucher = "1", vs.stock, st.stock) AS stock'),
                DB::raw('IF(pt.type_flag = "2", pt.seo_url, f.seo_url) AS seo_url'),
                DB::raw('IFNULL(r.rating, 0) AS rating'),
                DB::raw('IFNULL(l.love, 0) AS love'),
                DB::raw('IFNULL(r.count_rating, 0) AS count_rating'),
                DB::raw('IFNULL(o.offer_id, "") AS has_offer'),
                'pt.barcode',
                'pt.is_voucher'
            )
            ->leftJoin('family_tbl as f', 'pt.linepr', '=', 'f.family_id')
            ->leftJoin(DB::raw('(SELECT SUM(qty) AS stock, product_id FROM stock_table GROUP BY product_id) as st'), 'pt.product_id', '=', 'st.product_id')
            ->leftJoin(DB::raw('(SELECT COUNT(*) AS stock, barcode FROM voucher_table WHERE status="GENERATED" AND partner_id = "7" GROUP BY barcode) as vs'), 'pt.barcode', '=', 'vs.barcode')
            ->leftJoin(DB::raw('(SELECT product_id, ROUND(AVG(rating)) AS rating, COUNT(rating) AS count_rating FROM gc_reviews GROUP BY product_id) AS r'), 'pt.product_id', '=', 'r.product_id')
            ->leftJoin(DB::raw('(SELECT product_id, COUNT(wish_id) AS love FROM gc_wishlist GROUP BY product_id) AS l'), 'pt.product_id', '=', 'l.product_id')
            ->leftJoin(DB::raw('(SELECT offer_id, items FROM offer_tbl WHERE status="1" AND FIND_IN_SET("' . $domainId . '", in_domain) AND items!="" AND offer_for IN ("1","2")) AS o'), 'pt.product_id', '=', 'o.items')
            ->where('pt.web_status', '1')
            ->where('pt.main_price', '>', 0);

// Collection
        if ($requestType == 'collection' && $productRecord->in_collection != '') {
            $productQuery->where('pt.collection_id', $productRecord->in_collection);
        } elseif ($requestType == 'collection') {
            $productQuery->where('pt.collection_id', '!=', '0');
        }

// Segment
        if ($requestType == 'segment' && $requestKey != '') {
            $segment = Segment::select('id', 'dynamic_mysql')->where('slug', $requestKey)->whereRaw('FIND_IN_SET(?, domain)', [$domainId])->first();
            if ($segment && $segment->dynamic_mysql != '') {
                $productQuery->whereRaw($segment->dynamic_mysql);
            } elseif ($segment) {
                $productQuery->whereRaw('FIND_IN_SET(?, pt.in_segment)', [$segment->id]);
            }
        }

// Sale
        if ($requestType == 'sale' && $requestKey != '') {
            $offer = OfferTbl::select('items')->where('offer_slug', $requestKey)->first();
            if ($offer && $offer->items != '') {
                $productQuery->whereIn('pt.product_id', explode(",", $offer->items));
            }
        }

// Offer
        if ($requestType == 'offer' && $requestKey != '') {
            $offerRecord = OfferTbl::where('offer_slug', $requestKey)
                ->where('show_status', '1')
                ->whereRaw('FIND_IN_SET(?, in_domain)', [$domainId])
                ->first();

            if ($offerRecord) {
                $distributors = $offerRecord->item_distributor != '' ? explode(",", $offerRecord->item_distributor) : [];
                $brands = $offerRecord->item_brand != '' ? explode(",", $offerRecord->item_brand) : [];
                $mcategories = $offerRecord->item_main_category != '' ? explode(",", $offerRecord->item_main_category) : [];
                $categories = $offerRecord->item_category != '' ? explode(",", $offerRecord->item_category) : [];
                $scategories = $offerRecord->item_sub_category != '' ? explode(",", $offerRecord->item_sub_category) : [];
                $products = $offerRecord->items != '' ? explode(",", $offerRecord->items) : [];

                if (!empty($distributors)) {
                    $productQuery->whereIn('pt.distributor', $distributors);
                }
                if (!empty($brands)) {
                    $productQuery->whereIn('pt.brand_id', $brands);
                }
                if (!empty($mcategories)) {
                    $productQuery->whereIn('pt.main_cat_id', $mcategories);
                }
                if (!empty($categories)) {
                    $productQuery->whereIn('pt.cat_id', $categories);
                }
                if (!empty($scategories)) {
                    $productQuery->whereIn('pt.sub_cat_id', $scategories);
                }
                if (!empty($products)) {
                    $productQuery->whereIn('pt.product_id', $products);
                }
            }
        }

// Product Type
        if ($productType == '2') { // for product only
            $productQuery->where('pt.product_id', $productId);
        } else {
            $productQuery->where('pt.linepr', $productRecord->linepr);
        }

        $productQuery->havingRaw('stock IS NOT NULL');
        $productQuery->orderBy('main_price', 'ASC');

// Execute the query
        $products = $productQuery->get();

        $productArray = [];
        foreach ($products as $rowProductsRecord) {
            $subCat = $rowProductsRecord->sub_cat_id;
            $subCategory = Subcategory::with('category')->find($subCat);
            $cat = $subCategory->cat_id;
            $mainCat = $subCategory->category->main_cat_id;
            $brand = $rowProductsRecord->brand_id;
            $brandDetails = Brand::find($brand);
            $distributor = $brandDetails->distributor;
            $flag = $rowProductsRecord->func_flag;
            $item = $rowProductsRecord->product_id;
            $webDiscount = 0;
            $webDiscountType = '';
            $webPoints = '';

            // Fetching discount offer by store
            $offer = DB::table('offer_tbl as o')
                ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name')
                ->where('o.auto_apply', '1')
                ->where('o.status', '1')
                ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                ->where('o.offer_type', 'store')
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
                ->limit(1)
                ->get();

            if ($offer->isNotEmpty()) {
                $rowOfferRecord = $offer->first();
                $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                $webPoints = $rowOfferRecord->gift_points > 0 ? (float)$rowOfferRecord->gift_points : 0;
            }

            if ($user) {

                //fetching auto apply offer by customer's tier
                $offer = DB::table('offer_tbl as o')
                    ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name')
                    ->where('o.auto_apply', '1')
                    ->where('o.status', '1')
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
                        $query->where('o.item_distributor', '')
                            ->orWhere('o.item_distributor', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                    })
                    ->where(function ($query) use ($mainCat) {
                        $query->where('o.item_main_category', '')
                            ->orWhere('o.item_main_category', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                    })
                    ->where(function ($query) use ($cat) {
                        $query->where('o.item_category', '')
                            ->orWhere('o.item_category', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                    })
                    ->where(function ($query) use ($subCat) {
                        $query->where('o.item_sub_category', '')
                            ->orWhere('o.item_sub_category', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                    })
                    ->where(function ($query) use ($brand) {
                        $query->where('o.item_brand', '')
                            ->orWhere('o.item_brand', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                    })
                    ->where(function ($query) use ($flag) {
                        $query->where('o.item_flag', '')
                            ->orWhere('o.item_flag', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                    })
                    ->where(function ($query) use ($item) {
                        $query->where('o.items', '')
                            ->orWhere('o.items', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                    })
                    ->limit(1)
                    ->get();


                if ($offer->isNotEmpty()) {
                    $rowOfferRecord = $offer->first();
                    $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                    $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                    $webPoints = $rowOfferRecord->gift_points > 0 ? (float)$rowOfferRecord->gift_points : 0;
                }

                //fetching auto apply offer by customer's id or organization
                $offer = DB::table('offer_tbl as o')
                    ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name')
                    ->where('o.auto_apply', '1')
                    ->where('o.status', '1')
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
                        $query->where('o.item_distributor', '')
                            ->orWhere('o.item_distributor', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                    })
                    ->where(function ($query) use ($mainCat) {
                        $query->where('o.item_main_category', '')
                            ->orWhere('o.item_main_category', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                    })
                    ->where(function ($query) use ($cat) {
                        $query->where('o.item_category', '')
                            ->orWhere('o.item_category', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                    })
                    ->where(function ($query) use ($subCat) {
                        $query->where('o.item_sub_category', '')
                            ->orWhere('o.item_sub_category', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                    })
                    ->where(function ($query) use ($brand) {
                        $query->where('o.item_brand', '')
                            ->orWhere('o.item_brand', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                    })
                    ->where(function ($query) use ($flag) {
                        $query->where('o.item_flag', '')
                            ->orWhere('o.item_flag', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                    })
                    ->where(function ($query) use ($item) {
                        $query->where('o.items', '')
                            ->orWhere('o.items', '=', 'all')
                            ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                    })
                    ->limit(1)
                    ->get();


                if ($offer->isNotEmpty()) {
                    $rowOfferRecord = $offer->first();
                    $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                    $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                    $webPoints = $rowOfferRecord->gift_points > 0 ? (float)$rowOfferRecord->gift_points : 0;
                }


            }


            switch ($webDiscountType) {
                case 'amount':
                    $rowProductsRecord->dmain_price = $webDiscount > 0 ? round((float)$rowProductsRecord->main_price - (float)$webDiscount) : '';
                    break;
                case 'percentage':
                    $rowProductsRecord->dmain_price = $webDiscount > 0 ? round((float)$rowProductsRecord->main_price - ((float)$rowProductsRecord->main_price * (float)$webDiscount / 100)) : '';
                    break;
                default:
                    $rowProductsRecord->dmain_price = '';
                    break;
            }
            // Fetching discount offer by store
            $offerRecords = DB::table('offer_tbl as o')
                ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name', 'o.offer_id')
                ->where('o.auto_apply', '0')
                ->where('o.status', '1')
                ->whereRaw("FIND_IN_SET(?, o.in_domain)", [$domainId])
                ->where('o.offer_type', 'store')
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

            $offerArray = [];

            foreach ($offerRecords as $rowOfferRecord) {
                $offerArray[$rowOfferRecord->offer_id] = (array)$rowOfferRecord;
            }

            $loyaltyPoints = 0;
            $price = $rowProductsRecord->dmain_price !== '' ? $rowProductsRecord->dmain_price : $rowProductsRecord->main_price;

            if ($webPoints > 0) {
                $loyaltyPoints = round((float)$webPoints * (float)$price);
            }

            $rowProductsRecord->lylty_pnts = $webDiscount > 0 ? 0 : $loyaltyPoints;
            $rowProductsRecord->offers = $offerArray;
            //reviews
            $reviews = Review::select('gc_reviews.title', 'gc_reviews.description', 'gc_reviews.rating', 'gc_reviews.added_date', 'pos_customer.customer_name as customer')
                ->join('pos_customer', 'gc_reviews.customer_id', '=', 'pos_customer.customer_id')
                ->where('gc_reviews.product_id', $rowProductsRecord->product_id)
                ->orderBy('gc_reviews.added_date', 'DESC')
                ->get();
            // Access reviews as an array
            $reviewArray = $reviews->map(function ($review) {
                // Convert the added_date to a Carbon instance for date manipulation
                $addedDate = Carbon::parse($review['added_date']);

                // Calculate the difference in human-readable format (e.g., 2 hours ago)
                $timeAgo = $addedDate->diffForHumans();

                // Add the "ago" time to the array keys
                $review['added_date_ago'] = $timeAgo;

                return $review;
            })->toArray();
            $rowProductsRecord->reviews = $reviewArray;
            $productArray[$rowProductsRecord->product_id] = $rowProductsRecord;
        }
        //dd($productArray);
        $data = [
            "main_category" => $productMainCategory,
            "brand_name" => $brandName,
            "family_name" => $familyName,
            "family_desc" => $familyDesc,
            "products_arr" => $productArray,
            "image_arr" => []
        ];
        return view('frontend.pages.product-detail')->with($data);
    }

    public function fetchRelatedProducts(Request $request)
    {

        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $data = [];
        $webDiscount = 0;
        $webDiscountOffer = '';
        $currentDate = now()->format('Y-m-d');
        $currentDay = strtolower(now()->format('D'));
        $user = Auth::user();
        if ($user) {
            $customerId = $user->customer_id;
            $customerOrganization = $user->organization;
            $customerTier = $user->tier;
        }
        $product = Product::find($request->input('product'));
        $brandId = $product->brand_id;
        $familyId = $product->linepr;
        $subCat = $product->sub_cat_id;
        $subCategory = Subcategory::with('category')->find($subCat);
        $cat = $subCategory->cat_id;
        $mainCategoryId = $subCategory->category->main_cat_id;
        $mainCategory = MainCategory::find($mainCategoryId);
        $subCategories = $mainCategory->categories->flatMap(function ($category) {
            return $category->subCategories->pluck('sub_cat_id');
        });

        $productsRecord = DB::table('product_table as pt')
            ->select([
                'pt.type_flag as product_type',
                'pt.product_id',
                DB::raw("IF(pt.type_flag = '2', pt.seo_url, f.seo_url) as seo_url"),
                'pt.sub_cat_id',
                'pt.func_flag',
                'br.brand_id',
                'br.brand_name',
                'pt.linepr',
                'pt.has_gift',
                DB::raw("ROUND(pt.main_price, 0) as main_price"),
                DB::raw("ROUND(MAX(pt.main_price), 0) as max_price"),
                DB::raw("ROUND(MIN(NULLIF(pt.main_price, 0)), 0) as min_price"),
                DB::raw("IF(pt.type_flag = '2', SUBSTR(REPLACE(pt.product_name, '\\\\', ''), 1, 60) , SUBSTR(REPLACE(f.family_name, '\\\\', ''), 1, 60)) as family_name"),
                DB::raw("IF(pt.type_flag = '2', pt.photo1, fp.aws) as family_pic"),
                'st.stock',
                DB::raw("IFNULL(SUBSTRING_INDEX(sg.title, ' ', 1), '') as segment"),
                DB::raw("IFNULL(sg.slug, '') as segment_slug"),
                DB::raw("IFNULL(sg.type, '') as segment_type"),
                DB::raw("IFNULL(o.offer_id, '') as has_offer"),
                DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) as unique_key"),
            ])
            ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as br'), 'pt.brand_id', '=', 'br.brand_id')
            ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
            ->leftJoin(DB::raw('(SELECT family_id, family_name, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
            ->leftJoin(DB::raw('(SELECT family_id,aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
            ->leftJoin(DB::raw("(SELECT id, title, slug, type FROM gc_segments WHERE slug IN ('new-arrival', 'best-seller') AND domain = '" . $domainId . "') as sg"), function ($join) {
                $join->on(DB::raw('FIND_IN_SET(sg.id, pt.in_segment)'), '>', DB::raw('0'));
            })
            ->leftJoin(DB::raw("(SELECT offer_id, items FROM offer_tbl WHERE status = '1' AND FIND_IN_SET('" . $domainId . "', in_domain) AND items != '' AND offer_for IN ('1','2') AND ((('" . $currentDate . "' BETWEEN from_date AND to_date) OR (from_date <= '" . $currentDate . "' AND to_date = '0000-00-00 00:00:00')) OR (FIND_IN_SET('" . $currentDay . "', week_days))) ) as o"), function ($join) {
                $join->on(DB::raw('FIND_IN_SET(pt.product_id, o.items)'), '>', DB::raw('0'));
            })
            ->where('pt.web_status', '=', '1')
            ->where('pt.main_price', '>', 0)
            ->whereRaw('FIND_IN_SET(?, pt.in_domain)', [$domainId])
            ->where('pt.brand_id', $brandId)
            ->whereIn('pt.sub_cat_id', $subCategories)
            ->where('pt.linepr', '!=', $familyId);


        $productsRecord->whereNotNull('st.stock')->groupBy(DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr)"));
        $productsRecord->orderBy('pt.product_name', 'asc')->limit(10);

        // Get the number of filtered products
        $filteredProductsNum = $productsRecord->get()->count();


        if ($filteredProductsNum > 0) {
            $filteredProducts = $productsRecord->get();
            foreach ($filteredProducts as $rowProductsRecord) {
                $subCat = $rowProductsRecord->sub_cat_id;
                $subCategory = Subcategory::with('category')->find($subCat);
                $cat = $subCategory->cat_id;
                $mainCat = $subCategory->category->main_cat_id;
                $brand = $rowProductsRecord->brand_id;
                $brandDetails = Brand::find($brand);
                $distributor = $brandDetails->distributor;
                $flag = $rowProductsRecord->func_flag;
                $item = $rowProductsRecord->product_id;
                $webDiscount = 0;
                $webDiscountType = '';
                $webDiscountOffer = '';

                // Fetching discount offer by store
                $offer = DB::table('offer_tbl as o')
                    ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name')
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
                    ->limit(1)
                    ->get();

                if ($offer->isNotEmpty()) {
                    $rowOfferRecord = $offer->first();
                    $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                    $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                    $webDiscountOffer = $rowOfferRecord->offer_name;
                }

                if ($user) {

                    //fetching auto apply offer by customer's tier
                    $offer = DB::table('offer_tbl as o')
                        ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name')
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
                            $query->where('o.item_distributor', '')
                                ->orWhere('o.item_distributor', '=', 'all')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                        })
                        ->where(function ($query) use ($mainCat) {
                            $query->where('o.item_main_category', '')
                                ->orWhere('o.item_main_category', '=', 'all')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                        })
                        ->where(function ($query) use ($cat) {
                            $query->where('o.item_category', '')
                                ->orWhere('o.item_category', '=', 'all')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                        })
                        ->where(function ($query) use ($subCat) {
                            $query->where('o.item_sub_category', '')
                                ->orWhere('o.item_sub_category', '=', 'all')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                        })
                        ->where(function ($query) use ($brand) {
                            $query->where('o.item_brand', '')
                                ->orWhere('o.item_brand', '=', 'all')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                        })
                        ->where(function ($query) use ($flag) {
                            $query->where('o.item_flag', '')
                                ->orWhere('o.item_flag', '=', 'all')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                        })
                        ->where(function ($query) use ($item) {
                            $query->where('o.items', '')
                                ->orWhere('o.items', '=', 'all')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                        })
                        ->limit(1)
                        ->get();


                    if ($offer->isNotEmpty()) {
                        $rowOfferRecord = $offer->first();
                        $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                        $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                        $webDiscountOffer = $rowOfferRecord->offer_name;
                    }

                    //fetching auto apply offer by customer's id or organization
                    $offer = DB::table('offer_tbl as o')
                        ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name')
                        ->where('o.auto_apply', '1')
                        ->where('o.status', '1')
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
                            $query->where('o.item_distributor', '')
                                ->orWhere('o.item_distributor', '=', 'all')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT o2.item_distributor FROM offer_tbl as o2 WHERE o2.offer_id=o.offer_id))', [$distributor]);
                        })
                        ->where(function ($query) use ($mainCat) {
                            $query->where('o.item_main_category', '')
                                ->orWhere('o.item_main_category', '=', 'all')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT o3.item_main_category FROM offer_tbl as o3 WHERE o3.offer_id=o.offer_id))', [$mainCat]);
                        })
                        ->where(function ($query) use ($cat) {
                            $query->where('o.item_category', '')
                                ->orWhere('o.item_category', '=', 'all')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT o5.item_category FROM offer_tbl as o5 WHERE o5.offer_id=o.offer_id))', [$cat]);
                        })
                        ->where(function ($query) use ($subCat) {
                            $query->where('o.item_sub_category', '')
                                ->orWhere('o.item_sub_category', '=', 'all')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT o4.item_sub_category FROM offer_tbl as o4 WHERE o4.offer_id=o.offer_id))', [$subCat]);
                        })
                        ->where(function ($query) use ($brand) {
                            $query->where('o.item_brand', '')
                                ->orWhere('o.item_brand', '=', 'all')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT o6.item_brand FROM offer_tbl as o6 WHERE o6.offer_id=o.offer_id))', [$brand]);
                        })
                        ->where(function ($query) use ($flag) {
                            $query->where('o.item_flag', '')
                                ->orWhere('o.item_flag', '=', 'all')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.item_flag FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$flag]);
                        })
                        ->where(function ($query) use ($item) {
                            $query->where('o.items', '')
                                ->orWhere('o.items', '=', 'all')
                                ->orWhereRaw('FIND_IN_SET(?, (SELECT o7.items FROM offer_tbl as o7 WHERE o7.offer_id=o.offer_id))', [$item]);
                        })
                        ->limit(1)
                        ->get();


                    if ($offer->isNotEmpty()) {
                        $rowOfferRecord = $offer->first();
                        $webDiscount = $rowOfferRecord->gift_discount_value != '' ? (float)$rowOfferRecord->gift_discount_value : 0;
                        $webDiscountType = $rowOfferRecord->gift_discount_type != '' ? $rowOfferRecord->gift_discount_type : '';
                        $webDiscountOffer = $rowOfferRecord->offer_name;
                    }


                }

//                if ($keyType == "offer") {
//                    // Fetching discount offer by store
//                    $offerRecord = DB::table('offer_tbl as o')
//                        ->select('o.gift_discount_type', 'o.gift_discount_value', 'o.gift_points', 'o.offer_name')
//                        ->where('o.offer_id', '=', $offerId)
//                        ->first();
//
//                    if ($offerRecord) {
//                        $webDiscount = !empty($offerRecord->gift_discount_value) ? (float)$offerRecord->gift_discount_value : 0;
//                        $webDiscountType = !empty($offerRecord->gift_discount_type) ? $offerRecord->gift_discount_type : '';
//                        $webDiscountOffer = $offerRecord->offer_name;
//                    }
//                }

                switch ($webDiscountType) {
                    case 'amount':
                        $rowProductsRecord->dmain_price = $webDiscount > 0 ? round((float)$rowProductsRecord->main_price - (float)$webDiscount) : '';
                        $rowProductsRecord->dmax_price = $webDiscount > 0 ? round((float)$rowProductsRecord->max_price - (float)$webDiscount) : '';
                        $rowProductsRecord->dmin_price = $webDiscount > 0 ? round((float)$rowProductsRecord->min_price - (float)$webDiscount) : '';
                        break;
                    case 'percentage':
                        $rowProductsRecord->dmain_price = $webDiscount > 0 ? round((float)$rowProductsRecord->main_price - ((float)$rowProductsRecord->main_price * (float)$webDiscount / 100)) : '';
                        $rowProductsRecord->dmax_price = $webDiscount > 0 ? round((float)$rowProductsRecord->max_price - ((float)$rowProductsRecord->max_price * (float)$webDiscount / 100)) : '';
                        $rowProductsRecord->dmin_price = $webDiscount > 0 ? round((float)$rowProductsRecord->min_price - ((float)$rowProductsRecord->min_price * (float)$webDiscount / 100)) : '';
                        break;
                    default:
                        $rowProductsRecord->dmain_price = '';
                        $rowProductsRecord->dmax_price = '';
                        $rowProductsRecord->dmin_price = '';
                        break;
                }

                $rowProductsRecord->offer_name = $webDiscountOffer;
                $rowProductsRecord->family_name = ucwords(strtolower($rowProductsRecord->family_name));
                $rowProductsRecord->offer_sql = '';
                $productArr[] = $rowProductsRecord;

            }
        }


        return response()->json(["result" => $productArr]);

    }

    public function productReview(Request $request)
    {

        $user = Auth::user();
        if (!$user) {
            return response()->json(["result" => false, "message" => "Please sign in to write review for this item.", "data" => []]);
        }

        $customerId = $user->customer_id;
        $customerTier = $user->tier;
        $productId = $request->input('product');
        $productType = $request->input('product_type');
        $isBundle = $productType == 'bundle' ? '1' : '0';
        $title = $request->input('title');
        $description = $request->input('description');
        $rating = $request->input('rating');


        if (!$productId) {
            return response()->json(['result' => false, 'message' => 'Something went wrong, please contact the administrator.']);
        }

        $numProductRecord = Review::where('product_id', $productId)
            ->where('customer_id', $customerId)
            ->where('is_bundle', $isBundle)
            ->count();

        if ($numProductRecord > 0) {
            return response()->json(['result' => false, 'message' => 'You already reviewed this product earlier.']);
        }

        try {
            $addedDate = Carbon::now();

            Review::create([
                'added_date' => $addedDate,
                'product_id' => $productId,
                'is_bundle' => $isBundle,
                'customer_id' => $customerId,
                'title' => $title,
                'description' => $description,
                'rating' => $rating,
            ]);


            $rewardRecord = Reward::where('slug', 'write-a-review')
                ->where('tier_id', $customerTier)
                ->where('status', 1)
                ->first();

            $reward = $rewardRecord ? $rewardRecord->points : 0;
            $pexpiry = $rewardRecord ? $rewardRecord->expiry : 365;
            $ptype = $rewardRecord ? $rewardRecord->slug : 'write-a-review';

            $customer = User::where('customer_id', $customerId)->first();

            if ($reward) {
                $preBalance = $customer->loyalty_point ?? 0;
                $postBalance = $preBalance + $reward->points;
                $customer->loyalty_point = $postBalance;
                $customer->date_added = now();
                $customer->save();

                UserLoyaltyPoint::insert([
                    'customer_id' => $customerId,
                    'point_in' => $reward,
                    'point_out' => 0,
                    'point_type' => $ptype,
                    'location' => 'web',
                    'pre_balance' => $preBalance,
                    'post_balance' => $postBalance,
                    'added_date' => $addedDate,
                    'valid_points' => $reward,
                    'valid_date' => $addedDate->addDays($pexpiry),
                    'valid_days' => $pexpiry,
                ]);
            }
            return response()->json(['result' => true, 'message' => 'You have successfully reviewed this product.']);
        } catch (\Exception $e) {
            return response()->json(['result' => false, 'message' => $e->getMessage()]);
        }
    }

    public function productSearch(Request $request)
    {
        $noImagePath = config('app.no_image_path');
        $noImagePath = asset('public/' . $noImagePath);
        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $searchTerm = trim(strip_tags($request->search_term));
        $escapedSearchTerm = trim(strip_tags($request->escaped_search_term));
        $searchTermLower = strtolower($escapedSearchTerm);
        $requestArray = explode(' ', $searchTermLower);

        $sqlLocateSearch = Product::select([
            'product_table.product_id',
            'product_table.brand_id',
            'brand_table.brand_name',
            DB::raw("IF(product_table.type_flag = '2', REPLACE(product_table.fam_name, '\\\', ''), REPLACE(family_tbl.family_name, '\\\', '')) AS family_name"),
            DB::raw("IFNULL(IF(product_table.type_flag = '2', product_table.photo1, family_pic_tbl.aws), '$noImagePath') as family_pic"),
            DB::raw("IF(product_table.type_flag = '2', product_table.seo_url, family_tbl.seo_url) AS seo_url"),
            DB::raw("IF(product_table.type_flag = '2', product_table.product_id, product_table.linepr) AS unique_key"),
        ])
            ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as brand_table'), 'product_table.brand_id', '=', 'brand_table.brand_id')
            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sub_cat_table'), 'product_table.sub_cat_id', '=', 'sub_cat_table.sub_cat_id')
            ->join(DB::raw('(SELECT cat_id, cat_name, main_cat_id FROM cat_table) as cat_table'), 'sub_cat_table.cat_id', '=', 'cat_table.cat_id')
            ->join(DB::raw('(SELECT main_cat_id, main_cat_name FROM main_cat_table) as main_cat_table'), 'cat_table.main_cat_id', '=', 'main_cat_table.main_cat_id')
            ->leftJoin(DB::raw('(SELECT SUM(qty) AS stock, product_id FROM stock_table GROUP BY product_id) as st'), 'product_table.product_id', '=', 'st.product_id')
            ->leftJoin('family_tbl', 'product_table.linepr', '=', 'family_tbl.family_id')
            ->leftJoin('family_pic_tbl', 'family_tbl.family_id', '=', 'family_pic_tbl.family_id')
            ->where('product_table.web_status', '=', '1')
            ->where('product_table.main_price', '>', 0)
            ->whereRaw("FIND_IN_SET('$domainId', product_table.in_domain)")
            ->where(function ($query) use ($requestArray) {
                foreach ($requestArray as $v) {
                    if (ctype_alpha($v)) {
                        $query->whereRaw("LOCATE(?, LOWER(CONCAT(' ', `brand_table`.`brand_name`, ' ', IF(`product_table`.`type_flag` = '2', `product_table`.`fam_name`, `family_tbl`.`family_name`), ' ', `main_cat_table`.`main_cat_name`, ' ', `cat_table`.`cat_name`, ' ', `sub_cat_table`.`sub_cat_name`, ' ', `product_table`.`Ref_no`, ' ', `product_table`.`barcode`, ' '))) > 0", [strtolower($v)]);
                    } else {
                        $query->whereRaw("LOCATE(?, LOWER(CONCAT(' ', `product_table`.`Ref_no`, ' ', `product_table`.`barcode`, ' '))) > 0", [strtolower($v)]);
                    }
                }
            })
            ->whereNotNull('st.stock')
            ->groupBy(DB::raw("IF(product_table.type_flag = '2', product_table.product_id, product_table.linepr)"))
            ->orderBy('brand_table.brand_name', 'ASC')
            ->orderBy((DB::raw("IF(product_table.type_flag = '2', product_table.fam_name, family_tbl.family_name)")), 'ASC')
            ->take(10);

        // Print the SQL query
//        $rawSql = $sqlLocateSearch->toSql();
//        $bindings = $sqlLocateSearch->getBindings();
//        dd(vsprintf(str_replace('?', '%s', $rawSql), array_map('addslashes', $bindings)));

        $numLocateSearch = $sqlLocateSearch->count();

        if ($numLocateSearch === 0) {
            $sqlSoundexSearch = Product::select([
                'product_table.product_id',
                'product_table.brand_id',
                'brand_table.brand_name',
                DB::raw("IF(product_table.type_flag = '2', REPLACE(product_table.fam_name, '\\\', ''), REPLACE(family_tbl.family_name, '\\\', '')) AS family_name"),
                DB::raw("IFNULL(IF(product_table.type_flag = '2', product_table.photo1, family_pic_tbl.aws), '$noImagePath') as family_pic"),
                DB::raw("IF(product_table.type_flag = '2', product_table.seo_url, family_tbl.seo_url) AS seo_url"),
                DB::raw("IF(product_table.type_flag = '2', product_table.product_id, product_table.linepr) AS unique_key"),
            ])
                ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as brand_table'), 'product_table.brand_id', '=', 'brand_table.brand_id')
                ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sub_cat_table'), 'product_table.sub_cat_id', '=', 'sub_cat_table.sub_cat_id')
                ->join(DB::raw('(SELECT cat_id, cat_name, main_cat_id FROM cat_table) as cat_table'), 'sub_cat_table.cat_id', '=', 'cat_table.cat_id')
                ->join(DB::raw('(SELECT main_cat_id, main_cat_name FROM main_cat_table) as main_cat_table'), 'cat_table.main_cat_id', '=', 'main_cat_table.main_cat_id')
                ->leftJoin(DB::raw('(SELECT SUM(qty) AS stock, product_id FROM stock_table GROUP BY product_id) as st'), 'product_table.product_id', '=', 'st.product_id')
                ->leftJoin('family_tbl', 'product_table.linepr', '=', 'family_tbl.family_id')
                ->leftJoin('family_pic_tbl', 'family_tbl.family_id', '=', 'family_pic_tbl.family_id')
                ->where('product_table.web_status', '=', '1')
                ->where('product_table.main_price', '>', 0)
                ->whereRaw("FIND_IN_SET('$domainId', product_table.in_domain)")
                ->where(function ($query) use ($requestArray) {
                    foreach ($requestArray as $v) {
                        if (!is_numeric($v)) {
                            $query->orWhere(function ($query) use ($v) {
                                $query->where('brand_table.brand_name', 'SOUNDS LIKE', $v)
                                    ->orWhereRaw("SOUNDEX(IF(product_table.type_flag = '2', product_table.fam_name, family_tbl.family_name)) LIKE CONCAT(TRIM(TRAILING '0' FROM SOUNDEX('" . $v . "')),'%')");
                            });
                        }
                    }
                });

//            if (!empty($requestArray)) {
//                foreach ($requestArray as $v) {
//                    if (!is_numeric($v)) {
//                        $sqlSoundexSearch->orWhere(function ($productsQuery) use ($v) {
//                            $productsQuery->where('brand_table.brand_name', 'SOUNDS LIKE', $v)
//                                ->orWhereRaw("SOUNDEX(IF(product_table.type_flag = '2', product_table.fam_name, family_tbl.family_name)) LIKE CONCAT(TRIM(TRAILING '0' FROM SOUNDEX('" . $v . "')),'%')");
//                        });
//                    }
//                }
//            }
            $sqlSoundexSearch->whereNotNull('st.stock')
                ->groupBy(DB::raw("IF(product_table.type_flag = '2', product_table.product_id, product_table.linepr)"))
                ->orderBy('brand_table.brand_name', 'ASC')
                ->orderBy((DB::raw("IF(product_table.type_flag = '2', product_table.fam_name, family_tbl.family_name)")), 'ASC')
                ->take(10);
            $products = $sqlSoundexSearch->get();
        } else {
            $products = $sqlLocateSearch->get();
        }


        $resultArray = [];
        $brandArray = [];
        $productArray = [];

        foreach ($products as $product) {
            $product->type = "product";
            $product->name = "PRODUCT";
            $productArray[] = $product;

            if (!in_array($product->brand_id, $brandArray)) {
                $brandArray[] = $product->brand_id;
            }
        }

        $resultArray[] = [
            'term' => 'See all in ' . $searchTerm,
            'term_value' => $escapedSearchTerm,
            'type' => 'search',
            'key_type' => 'search',
            'key_word' => $escapedSearchTerm,
            'name' => $escapedSearchTerm,
        ];

        foreach ($brandArray as $brandId) {
            $brand = Brand::find($brandId);

            if ($brand) {
                $brandResult = [
                    'brand_id' => $brand->id,
                    'brand_name' => $brand->name,
                    'seo_url' => $brand->brand_slug,
                    'type' => 'brand',
                    'name' => 'BRAND',
                    'key_type' => 'brand',
                    'key_word' => $brand->id,
                ];
                $resultArray[] = $brandResult;
            }
        }

        if (!empty($productArray)) {
            foreach ($productArray as $product) {
                $resultArray[] = $product;
            }
        }

        $data = $resultArray;
        $result = count($products) > 0;
        $message = $result ? "" : "No result found";

        return response()->json(compact('result', 'message', 'data'));
    }

    public function fetchFeedProducts(Request $request)
    {
        $brandIds = [1185, 46, 52, 229, 1205, 1187, 74, 4, 1182, 15, 1206, 26, 197, 1214, 1183, 1207, 201, 202, 225, 1];
        //return Excel::download(new ProductsExport, 'products.xlsx');
        $productUrl = config('app.product_url');
        $productsQuery = DB::table('product_table as pt')
            ->select([
                'pt.product_id as id',
                'br.brand_name as brand',
            ])
//            ->select([
//                'pt.product_id as id',
//                DB::raw("IF(pt.`type_flag` = '2',
//                    SUBSTR(REPLACE(pt.`product_name`, '\\\', ''), 1, 150),
//                    SUBSTR(REPLACE(`f`.`family_name`, '\\\', ''), 1, 150)
//                ) AS `title`"),
//                DB::raw("IF(pt.`type_flag` = '2',
//                    REPLACE(pt.`long_desc`, '\\\', ''),
//                    REPLACE(`f`.`family_desc`, '\\\', '')
//                ) AS `description`"),
//                DB::raw("IF(pt.type_flag = '2',
//                    CONCAT('$productUrl', pt.seo_url),
//                    CONCAT('$productUrl', f.seo_url)
//                ) as link"),
//                DB::raw("'yes' AS identifier_exists"),
//                DB::raw("'in_stock' AS availability"),
//                DB::raw("ROUND(pt.main_price, 0) as price"),
//                DB::raw("CONCAT('https://giftscenter.s3.me-central-1.amazonaws.com/familypic/', fp.family_pic) as image_link"),
//                'br.brand_name as brand',
//            ])
            ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
            ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
            ->join(DB::raw('(SELECT main_cat_id, main_cat_name, Old_value FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
            ->leftJoin(DB::raw('(SELECT family_id, family_name, family_desc, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
            ->leftJoin(DB::raw('(SELECT family_id, family_pic, aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
            ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as br'), 'pt.brand_id', '=', 'br.brand_id')
            ->where('pt.web_status', '=', '1')
            //->whereIn('mt.Old_value', ['FR', 'CM', 'CS'])
            ->whereRaw('(FIND_IN_SET(?, pt.in_domain) OR FIND_IN_SET(?, pt.in_domain))', [1, 5])
            ->whereNotIn('pt.brand_id', $brandIds)
            ->whereIn('pt.product_id', function ($query) {
                $query->select('a.product_id')
                    ->from('account_product_table_new as a')
                    ->join('store_table as st', 'a.store_id', '=', 'st.store_id')
                    ->whereIn('a.company', [4, 9])
                    ->whereIn('st.store_id', [28, 13])
                    ->groupBy('a.product_id')
                    ->havingRaw('SUM(a.instock) - SUM(a.outstock) > 1');
            });
            //->take(20)
            //->get();
        echo $productsQuery->toSql();

// To get the bindings (actual values)
        print_r($productsQuery->getBindings());
        exit;
        dd($productsRecord);
        // Set CSV file headers
        $csvHeaders = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=products.csv",
        ];

        // Return the stream response
        return response()->stream(function () use ($productsRecord) {
            $handle = fopen('php://output', 'w');

            // Add CSV header row
            fputcsv($handle, ['ID', 'Title', 'Line in Feed', 'Description', 'Availability', 'Link', 'Image Link', 'Price', 'Identifier Exists', 'Brand']);

            $lineNumber = 1;
            // Write each product record to CSV
            foreach ($productsRecord as $product) {
                $title = ucfirst(strtolower($product->title));
                $description = ucfirst(strtolower($product->description));

                fputcsv($handle, [
                    $product->id,
                    $title,
                    $lineNumber,
                    $description,
                    $product->availability,
                    $product->link,
                    $product->image_link,
                    $product->price . ' JOD',
                    $product->identifier_exists,
                    $product->brand
                ]);
                $lineNumber++;
            }

            fclose($handle); // Properly close the output stream
        }, 200, $csvHeaders);

    }

    public function voucherList(Request $request)
    {
        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;

        $pVouchers = DB::table('product_table as pt')
            ->select(
                'pt.product_id',
                DB::raw("ROUND(MAX(pt.main_price), 0) as max_price"),
                'bt.brand_name',
                'bt.brand_id',
                DB::raw("IF(pt.type_flag = '2', pt.seo_url, f.seo_url) as seo_url"),
                DB::raw("FORMAT(pt.main_price, 0) AS main_price"),
                DB::raw("FORMAT(MAX(pt.main_price), 0) AS max_price"),
                DB::raw("FORMAT(MIN(NULLIF(pt.main_price, 0)), 0) AS min_price"),
                DB::raw("ROUND(MAX(pt.main_price), 0) as max_price"),
                DB::raw("IF(pt.type_flag = '2', SUBSTR(REPLACE(pt.product_name, '\\\\', ''), 1, 60) , SUBSTR(REPLACE(f.family_name, '\\\\', ''), 1, 60)) as family_name"),
                DB::raw("IF(pt.type_flag = '2', pt.photo1, fp.aws) as family_pic"),
                'st.stock',
                DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) AS unique_key")
            )
            ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as bt'), 'pt.brand_id', '=', 'bt.brand_id')
            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
            ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
            ->join(DB::raw('(SELECT main_cat_id, main_cat_name FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
            ->leftJoin(DB::raw('(SELECT family_id, family_name, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
            ->leftJoin(DB::raw('(SELECT family_id,aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
            ->leftJoin(DB::raw("(SELECT COUNT(*) as stock, barcode FROM voucher_table WHERE status = 'GENERATED' AND partner_id = '7' GROUP BY barcode) as st"), 'pt.barcode', '=', 'st.barcode')
            ->where('pt.web_status', '1')
            ->where('pt.is_voucher', '1')
            ->where('pt.main_price', '>', 0)
            ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
            ->where('pt.sub_cat_id', '1013')
            ->whereNotNull('st.stock')
            ->groupBy('unique_key')
            ->orderBy('bt.brand_name', 'ASC')
            ->get();
        $eVouchers = DB::table('product_table as pt')
            ->select(
                'pt.product_id',
                'bt.brand_name',
                'bt.brand_id',
                DB::raw("IF(pt.type_flag = '2', pt.seo_url, f.seo_url) as seo_url"),
                DB::raw("FORMAT(pt.main_price, 0) AS main_price"),
                DB::raw("FORMAT(MAX(pt.main_price), 0) AS max_price"),
                DB::raw("FORMAT(MIN(NULLIF(pt.main_price, 0)), 0) AS min_price"),
                DB::raw("IF(pt.type_flag = '2', SUBSTR(REPLACE(pt.product_name, '\\\\', ''), 1, 60) , SUBSTR(REPLACE(f.family_name, '\\\\', ''), 1, 60)) as family_name"),
                DB::raw("IF(pt.type_flag = '2', pt.photo1, fp.aws) as family_pic"),
                'st.stock',
                DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) AS unique_key")
            )
            ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as bt'), 'pt.brand_id', '=', 'bt.brand_id')
            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
            ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
            ->join(DB::raw('(SELECT main_cat_id, main_cat_name FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
            ->leftJoin(DB::raw('(SELECT family_id, family_name, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
            ->leftJoin(DB::raw('(SELECT family_id,aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
            ->leftJoin(DB::raw("(SELECT COUNT(*) as stock, barcode FROM voucher_table WHERE status = 'GENERATED' AND partner_id = '8' GROUP BY barcode) as st"), 'pt.barcode', '=', 'st.barcode')
            ->where('pt.web_status', '1')
            ->where('pt.is_voucher', '1')
            ->where('pt.main_price', '>', 0)
            ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
            ->where('pt.sub_cat_id', '1014')
            ->whereNotNull('st.stock')
            ->groupBy('unique_key')
            ->orderBy('bt.brand_name', 'ASC')
            ->get();


        return view('frontend.pages.voucher-lists')
            ->with('pVouchers', $pVouchers)
            ->with('eVouchers', $eVouchers);
    }

    public function voucherDetail(Request $request)
    {
        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;

        $eVouchers = DB::table('product_table as pt')
            ->select(
                'pt.product_id',
                'pt.barcode',
                'pt.linepr',
                DB::raw("FORMAT(pt.main_price, 0) AS main_price"),
                DB::raw("IF(pt.type_flag = '2', SUBSTR(REPLACE(pt.product_name, '\\\\', ''), 1, 60) , SUBSTR(REPLACE(f.family_name, '\\\\', ''), 1, 60)) as family_name"),
                DB::raw("IF(pt.type_flag = '2', pt.photo1, fp.aws) as family_pic"),
                'st.stock',
                DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) AS unique_key")
            )
            ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as bt'), 'pt.brand_id', '=', 'bt.brand_id')
            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
            ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
            ->join(DB::raw('(SELECT main_cat_id, main_cat_name FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
            ->leftJoin(DB::raw('(SELECT family_id, family_name, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
            ->leftJoin(DB::raw('(SELECT family_id,aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
            ->leftJoin(DB::raw("(SELECT COUNT(*) as stock, barcode FROM voucher_table WHERE status = 'GENERATED' AND partner_id = '8' GROUP BY barcode) as st"), 'pt.barcode', '=', 'st.barcode')
            ->where('pt.web_status', '1')
            ->where('pt.is_voucher', '1')
            ->where('pt.main_price', '>', 0)
            ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
            ->where('pt.sub_cat_id', '1014')
            ->whereNotNull('st.stock')
            ->groupBy('unique_key')
            ->orderBy('bt.brand_name', 'ASC')
            ->get()->keyBy('unique_key')->toArray();

        if (!empty($eVouchers)) {
            foreach ($eVouchers as $key => $eVoucher) {
                $products = DB::table('product_table as pt')
                    ->select(
                        'pt.type_flag as product_type',
                        'pt.product_id',
                        'pt.product_no',
                        DB::raw('ROUND(pt.main_price, 1) as main_price'),
                        DB::raw('st.stock')
                    )
                    ->leftJoin(DB::raw("(SELECT COUNT(*) AS stock, barcode FROM voucher_table WHERE status='GENERATED' AND partner_id = '8' GROUP BY barcode) as st"), 'pt.barcode', '=', 'st.barcode')
                    ->where('pt.web_status', '1')
                    ->where('pt.is_voucher', '1')
                    ->where('pt.main_price', '>', 0)
                    ->where('pt.linepr', '=', $eVoucher->linepr)
                    ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
                    ->whereNotNull('st.stock')
                    ->orderBy('pt.main_price', 'ASC')
                    ->get()->toArray();
                $eVouchers[$key]->products = $products;
            }
        }

        return view('frontend.pages.voucher-detail')
            ->with('eVouchers', $eVouchers);
    }

    public function cms($cmsslug)
    {
        $domain_id = 1; // replace with the actual domain ID

        $article = Article::where('slug', $cmsslug)
            ->where('domain', $domain_id)
            ->where('status', 1)
            ->first();

        return view('frontend.pages.cms', ['article' => $article]);
    }


    public function stores()
    {
        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;

        $stores = Store::where('web', '1')
            ->whereRaw('FIND_IN_SET(?, domain_id)', [$domainId])
            ->whereNotNull('address')
            ->whereNotNull('ph_no')
            ->select('store_id', 'store_name', DB::raw("REPLACE(address, \"'\", ' ') as address"), 'ph_no', 'latitude', 'longitude', 'extension', 'opening_hours')
            ->get()
            ->toArray();


        return view('frontend.pages.find-store', compact('stores'));
    }

    public function storeDetails($storeId)
    {

        $store = Store::select(
            'store_id',
            'store_name',
            DB::raw("REPLACE(address, \"'\", ' ') as address"),
            'ph_no',
            'latitude',
            'longitude'
        )
            ->where('store_id', $storeId)
            ->where('web', '1')
            ->whereNotNull('address')
            ->whereNotNull('ph_no')
            ->first();

        if (!$store) {
            return redirect()->route('contact-us');
        }

        return view('frontend.pages.store', compact('store'));
    }

    public function contactUs(Request $request)
    {
        $error_msg = "";
        $success_msg = "";

        if ($request->isMethod('post')) {
            // Validate the form data
            $validation = $this->validateContactForm($request->all());

            if (!$validation['success']) {
                $error_msg = $validation['message'];
            } else {
                // Process the form data or send email
                //  Send mail to admin
                Mail::send('frontend.mail.contactMail', array(
                    'fname' => $request->input('fname', ''), // Use input method to get data from the request
                    'lname' => $request->input('lname', ''),
                    'phone' => $request->input('phone', ''),
                    'email' => $request->input('email', ''),
                    'message' => $request->input('message', ''),
                ), function ($message) use ($request) {
                    //$message->from('no-reply@giftscenter.com');
                    $message->to($request->input('email', ''), 'User')->subject('Enquiry - Gift Center');
                });

//                    $mailData = [
//                        'email' => $email,
//                        'name' => 'AJ', // Change this to the recipient's name
//                        'subject' => $subject,
//                        'content' => $content,
//                    ];
//
//                    try {
//                        Mail::to($email)->send(new YourCustomMailable($mailData));
//
//                        return ['status' => true, 'message' => Message::mailSend];
//                    } catch (\Exception $e) {
//                        return ['status' => false, 'message' => Message::mailError];
//                    }
                $success_msg = "Your message has been successfully submitted!";

            }
        }

        return view('frontend.pages.contact', compact('error_msg', 'success_msg'));
    }

    private function validateContactForm($data)
    {
        if (empty($data['fname'])) {
            return ['success' => false, 'message' => 'Please enter your first name.'];
        } elseif (empty($data['lname'])) {
            return ['success' => false, 'message' => 'Please enter your last name.'];
        } elseif (empty($data['phone'])) {
            return ['success' => false, 'message' => 'Please enter your phone number.'];
        } elseif (!empty($data['phone']) && $data['country'] !== 'Jordan' && !$this->validateMobile($data['phone'])) {
            return ['success' => false, 'message' => 'Please enter a valid mobile number.'];
        } elseif (!empty($data['phone']) && $data['country'] === 'Jordan' && !$this->validatePhone($data['phone'])) {
            return ['success' => false, 'message' => 'Please enter a valid mobile number (07XXXXXXXX).'];
        } elseif (empty($data['email'])) {
            return ['success' => false, 'message' => 'Please enter your email address.'];
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        } elseif (empty($data['message'])) {
            return ['success' => false, 'message' => 'Please enter your message.'];
        }

        return ['success' => true];
    }

    private function validateMobile($txtPhone)
    {
        $filter = '/^[0-9]{8,15}$/';
        return preg_match($filter, $txtPhone) === 1;
    }

    private function validatePhone($txtPhone)
    {
        // Assuming Jordan phone number format (07XXXXXXXX)
        $filter = '/^07[0-9]{8}$/';
        return preg_match($filter, $txtPhone) === 1;
    }

    public function subscribeNewsletter(Request $request)
    {
        $email = $request->input('email');

        // Validate the email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['result' => '0', 'msg' => 'Please enter a valid email address.']);
        }

        // Check if the email already exists
        $existingEmail = GcNewsletter::where('email', $email)->first();
        if ($existingEmail) {
            return response()->json(['result' => '0', 'msg' => 'Email address is already subscribed.']);
        }

        // Insert the email into the database
        $newsletter = new GcNewsletter();
        $newsletter->email = $email;
        $newsletter->save();

        return response()->json(['result' => '1', 'msg' => 'Subscription successful.']);
    }

    /**
     * Sends birthday SMS and emails to users with birthday rewards.
     *
     * @return void
     */
    public function cronSendBirthdaySms(): void
    {
        $currentDate = now()->format('m-d');

        $users = User::select('customer_name', 'email', 'phone', 'phone_code', 'customer_id', 'tier')
            ->whereRaw("DATE_FORMAT(dob, '%m-%d') = '$currentDate'")
            ->get();

        $rewardRecords = Reward::where('slug', 'birthday-gift')->get()->keyBy('tier_id');

        $loyaltyPointsToInsert = [];

        $mailSubject = 'Happy Birthday!';
        $baseUrl = url('/');

        foreach ($users as $user) {
            $nameParts = explode(' ', $user->customer_name);
            $firstName = $nameParts[0];

            $rewardRecord = $rewardRecords->get($user->tier);

            if ($rewardRecord) {
                $gainedPoints = (int)$rewardRecord->points;
                $pointExpiry = (int)$rewardRecord->expiry;
                $rewardExpiryDate = now()->addDays($pointExpiry)->format('d/m/Y');

                // Logic to update customer points
                $preBalance = UserLoyaltyPoint::where('customer_id', $user->customer_id)
                    ->latest('id')
                    ->value('post_balance');

                $preBalance = $preBalance ?? 0;
                $postBalance = $preBalance + $gainedPoints;

                $loyaltyPointsToInsert[] = [
                    'customer_id' => $user->customer_id,
                    'point_in' => $gainedPoints,
                    'point_out' => 0,
                    'point_type' => 'birthday',
                    'invoice_no' => '',
                    'note' => '',
                    'location' => 'web',
                    'pre_balance' => $preBalance,
                    'post_balance' => $postBalance,
                    'added_date' => now(),
                    'valid_points' => $gainedPoints,
                    'valid_date' => now()->addDays($pointExpiry),
                    'valid_days' => $pointExpiry,
                ];

                if ($user->email) {
                    $mailContent = '
                    <body style="background:#f1f1f1;">
                    <div style="max-width:600px; height:auto; margin:45px auto 0;">
                    <table style="border-collapse:collapse; background:#fff; box-shadow:0 0 7px #999; text-align:center;" width="100%" border="0">
                        <tbody>
                            <tr style="background:#002855;">
                                <td colspan="2" style="padding:15px; text-align:center;"><a href="#"><img src="https://ikasco.com/images/emaillogo.png" alt=""></a></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding:50px 20px 10px 20px;">
                                    <p style="font:700 22px/28px Arial, Helvetica, sans-serif; color:#262626; margin:20px 0 10px 0;">
                                    Happy Birthday  ' . $firstName . ' from Gifts Center
                                    </p>
                                    <p style="font:normal 17px/22px Arial, Helvetica, sans-serif; color:#262626; margin:20px 0 10px 0;">
                                    You got ' . $gainedPoints . ' points on your birthday valid till ' . $rewardExpiryDate . '. Please visit giftscenter to redeem them.
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                <p style="font:italic 12px/16px Arial,Helvetica,sans-serif;color:#002855;padding:5px;text-align:left;">
                        You received this email because your date of birth registered with <a href="' . $baseUrl . '" target="_blank">giftscenter.com</a> . If you want to update your date of birth , please <a href="' . $baseUrl . 'update-your-address/' . $user->phone . '" target="_blank">click here</a> .</p>
                                </td>
                            </tr>
                            <tr style="background:#002855; padding:7px 0;">
                                <td style="float:left; padding:7px 7px 7px 30px; text-align:left;">
                                    <p style="font:normal 12px/18px Arial, Helvetica, sans-serif; color:#fff; margin:0;">Copyright &copy; Gifts Center 2017.<br> All rights reserved.</p>
                                </td>
                                <td style="width:50%; padding:7px 30px 7px 7px; text-align:left;"><p style="font:normal 12px/18px Arial, Helvetica, sans-serif; color:#fff; margin:0;">Contact</p>
                                    <p style="font:normal 12px/18px Arial, Helvetica, sans-serif; color:#fff; margin:0;"><a style="color:#fff;" href="mailto:info@gifts-center.com">info@gifts-center.com</a></p>
                                    <p style="font:normal 12px/18px Arial, Helvetica, sans-serif; color:#fff; margin:15px 0 0 0;"><a style="color:#fff; text-decoration:none;" href="tel:+962%207%2098889966">+962 7 98889966 ext 504</a></p>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                    </div>
                    </body>';

                    Helper::sendMail($user->email, $user->customer_name, $mailSubject, $mailContent);
                }

                if ($user->phone) {
                    $message = "Happy Birthday $firstName from Gifts Center, you got $gainedPoints points on your birthday valid till $rewardExpiryDate. Please visit giftscenter to redeem them.";
                    $phoneNumber = $user->phone_code . $user->phone;
                    Helper::sendSMS($phoneNumber, $message);
                }
            }
        }

        // Bulk insert loyalty points
        if (!empty($loyaltyPointsToInsert)) {
            UserLoyaltyPoint::insert($loyaltyPointsToInsert);
        }
    }

    /**
     * Cron job to send e-vouchers.
     *
     * This function fetches e-vouchers scheduled to be sent on the current date
     * and sends them either via SMS or email based on the send details.
     * After sending, it updates the voucher status and marks them as sent.
     *
     * @return void
     */
    public function cronSendEvoucher(): void
    {
        $ikascoUrl = config('app.ikasco_url');
        $baseUrl = url('/');

        // Fetch vouchers scheduled to be sent today and not yet sent
        $vouchers = EVoucher::where('send_date', date('Y-m-d'))
            ->where('is_sent', 0)
            ->get();

        foreach ($vouchers as $voucher) {
            $voucherData = Voucher::where('code', $voucher->voucher)->firstOrFail();
            $productRecord = Product::where('barcode', $voucherData->barcode)
                ->leftJoin('family_tbl as f', 'product_table.linepr', '=', 'f.family_id')
                ->leftJoin('family_pic_tbl as fp', 'f.family_id', '=', 'fp.family_id')
                ->selectRaw('IF(product_table.type_flag = 2, CONCAT("https://ikasco.com/", product_table.photo2), CONCAT("https://ikasco.com/familypic/", fp.family_pic)) AS family_pic, product_table.title AS title')
                ->first();

            // Send e-voucher based on send details
            if ($voucherData->edetails) {
                $sendDetails = json_decode($voucherData->edetails, true);

                if ($sendDetails["send_on"] == "later" && $sendDetails["send_date"] == date("Y-m-d")) {
                    if ($sendDetails["send_medium"] == "sms") {
                        $longUrl = config('app.ikasco_url') . "e-voucher/" . $voucher['code'];
                        $message = ($sendDetails["send_type"] == "recipient") ?
                            $sendDetails['send_from'] . " gifted you e-Gift Card. Click the link to view your e-Gift Card: " . $longUrl :
                            "Click the link to view your e-gift card: " . $longUrl;
                        $phone = $sendDetails["send_phone"];
                        Helper::sendSMS($phone, $message);
                    } else {
                        $voucherEmail = $sendDetails["send_email"];
                        $voucherName = $sendDetails["send_to"];

                        if ($sendDetails["send_type"] == "recipient") {
                            $recipientHtml = '<td colspan="2" style="text-align:left; padding:20px;">
                                        <p style="color: #002855;font: normal 17px/22px Arial, Helvetica, sans-serif;">Hello ' . $voucherName . ',</p>
                                        <p style="font:normal 17px/22px Arial, Helvetica, sans-serif; color:#444; margin:0 0 10px 0;">' . $sendDetails['send_from'] . ' gifted you e-Gift Card.</p>
                                    </td>';
                        } else {
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

                    // Update voucher status and validity
                    $validityDate = (int)$voucherData->valid_days > 0 ? date('Y-m-d', strtotime("+" . $voucherData->valid_days . " days", strtotime(date('Y-m-d')))) : $voucherData->VALIDITY;
                    Voucher::where('code', $voucherData->code)
                        ->update([
                            'status' => 'ACTIVATED',
                            'VALIDITY' => $validityDate,
                            'activate_date' => now()->format('Y-m-d H:i:s'),
                        ]);

                    // Mark voucher as sent
                    EVoucher::where('voucher', $voucher->voucher)->update(['is_sent' => 1]);
                }
            }
        }
    }

    /**
     * Cron job to handle points expiry.
     *
     * This function retrieves points that are expiring today and points expiring within a week.
     * It deducts the expired points and sends SMS notifications to customers about the upcoming points expiry.
     *
     * @return void
     */
    public function cronPointExpiry()
    {
        // Handle points expiring today
        $expiryDate = now()->subDay()->format('Y-m-d');
        $expiringPoints = DB::table('pos_customer as c')
            ->join('customer_points as p', 'c.customer_id', '=', 'p.customer_id')
            ->select('c.id', 'c.customer_id', 'c.phone_code', 'c.phone', 'c.customer_name', 'p.id as point_id', 'p.valid_points')
            ->whereDate('p.valid_date', $expiryDate)
            ->where('p.valid_points', '>', 0)
            ->get();

        foreach ($expiringPoints as $row) {
            // Deduct expired points and update records
            $customerId = $row->customer_id;
            $pointId = $row->point_id;
            $validPoints = (int) $row->valid_points;

            $preBalance = UserLoyaltyPoint::where('customer_id', $customerId)
                ->orderBy('id', 'desc')
                ->value('post_balance');

            $postBalance = (int) $preBalance - $validPoints;

            // Insert record for expired points and update valid points to 0
            $noteStr = "expiry-$validPoints";
            $note1Str = "$pointId-$validPoints";
            $addedDate = now()->format('Y-m-d H:i:s');

            UserLoyaltyPoint::insert([
                'customer_id' => $customerId,
                'point_in' => 0,
                'point_out' => abs($validPoints),
                'point_type' => 'expiry',
                'location' => 'web',
                'pre_balance' => $preBalance,
                'post_balance' => $postBalance,
                'note' => $noteStr,
                'note1' => $note1Str,
                'added_date' => $addedDate,
            ]);

            UserLoyaltyPoint::where('id', $pointId)
                ->update(['valid_points' => 0]);
        }

        // Send SMS notifications for points expiring within a week
        $expiryDateWeek = now()->addDays(7)->format('Y-m-d');
        $expiringPointsWeek = DB::table('pos_customer as c')
            ->join('customer_points as p', 'c.customer_id', '=', 'p.customer_id')
            ->select('c.id', 'c.customer_id', 'c.phone_code', 'c.phone', 'c.customer_name', 'p.valid_points')
            ->whereDate('p.valid_date', $expiryDateWeek)
            ->where('p.valid_points', '>', 0)
            ->get();

        foreach ($expiringPointsWeek as $rowWeek) {
            // Send SMS notification for points expiring within a week
            $customerName = $rowWeek->customer_name;
            $validPointsWeek = $rowWeek->valid_points;
            $phoneCode = $rowWeek->phone_code;
            $phone = preg_replace("/^0/", "", $rowWeek->phone);
            $phoneNumber = $phoneCode . $phone;
            $message = "Dear $customerName, your $validPointsWeek points will expire within a week. Please visit GiftsCenter to redeem them.";
            Helper::sendSMS($phoneNumber, $message);
        }
    }

    public function cronGenerateSitemap()
    {
        $s = microtime(true);
        set_time_limit(0);
        ob_start();

        // Base URL for the sitemaps
        $baseSitemapUrl = config('app.url') . '/sitemap-';

        // Sitemap index file with full path
        $indexXmlFile = public_path('sitemap-index.xml');

        // Create the sitemap index
        $sitemapIndexHeader = '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n";
        $sitemapIndexHeader .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\r\n";

        // Array of sitemaps to include in the index
        $sitemapFiles = array(
            'main.xml',
            'categories.xml',
            'brands.xml',
            'products.xml',
            'stores.xml'
        );

        foreach ($sitemapFiles as $sitemapFile) {
            $sitemapIndexHeader .= "\t<sitemap>\r\n";
            $sitemapIndexHeader .= "\t\t<loc>{$baseSitemapUrl}{$sitemapFile}</loc>\r\n";
            $sitemapIndexHeader .= "\t</sitemap>\r\n";
        }

        $sitemapIndexHeader .= '</sitemapindex>';

        file_put_contents($indexXmlFile, $sitemapIndexHeader);

        // Individual sitemap files
        foreach ($sitemapFiles as $sitemapFile) {
            $xmlfile = public_path('sitemap-' . $sitemapFile);
            $urls = array(); // Reset URLs array for each sitemap

            // Your logic to populate URLs for each section goes here
            // For example, for 'products.xml', you might call $this->getProductUrls($urls);

            // Write sitemap header
            $sitemapHeader = '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n";
            $sitemapHeader .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
                            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                            xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
                            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\r\n";

            file_put_contents($xmlfile, $sitemapHeader);

            // Write URLs to sitemap
            foreach ($urls as $url) {
                $entry = "\t<url>\r\n";
                $entry .= "\t\t<loc>{$url['loc']}</loc>\r\n";
                $entry .= "\t\t<changefreq>{$url['changefreq']}</changefreq>\r\n";
                $entry .= "\t\t<priority>{$url['priority']}</priority>\r\n";
                $entry .= "\t</url>\r\n";

                file_put_contents($xmlfile, $entry, FILE_APPEND);
            }

            // Write sitemap footer
            $xmlCloser = '</urlset>';
            file_put_contents($xmlfile, $xmlCloser, FILE_APPEND);

            // Log success message for each sitemap
            echo "Generated new sitemap at {$xmlfile} on " . date('Y-m-d') . "\n";
        }

        // Log script's results
        $logFile = storage_path('logs/sitemap.log');
        $f = fopen($logFile, 'a');
        $e = microtime(true);
        $tot = $e - $s;
        echo 'Completed sitemap generation in ' . $tot . " seconds\n";
        $out = ob_get_clean();
        fputs($f, $out);
        fclose($f);

        // Crawl the generated sitemaps
        $this->crawlSitemap();

        exit;
    }
    public function cronGenerateSitemaps()
    {
        $s = microtime(true);
        set_time_limit(0);
        ob_start();

        /** Site URL **/
        $siteUrl = config('app.url');

        /** Sitemap file with full path **/
        $xmlfile = public_path('sitemap.xml'); // Adjust path as necessary

        // URLs to add to put in the sitemap
        $urls = array($siteUrl);

        // Fill the array
        $this->getUrls($siteUrl, $urls);

        // Start writing to the sitemap
        $sitemapHeader = '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n";
        $sitemapHeader .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\r\n";

        file_put_contents($xmlfile, $sitemapHeader);

        // Pattern to use for each page
        $pagePatt = "\t<url>\r\n\t\t<loc>%s</loc>\r\n\t\t<changefreq>%s</changefreq>\r\n\t</url>\r\n";

        // Change frequency options
        $freqs = array('weekly', 'weekly', 'monthly');
        foreach ($urls as $url) {
            // Determine how often the current page "might" change
            $freq = substr_count($url, '/') - 3;
            // Add current page to the sitemap
            $entry = sprintf($pagePatt, $url, $freqs[$freq] ?? 'monthly');
            file_put_contents($xmlfile, $entry, FILE_APPEND);
        }

        // XML closer
        $xmlCloser = '</urlset>';
        file_put_contents($xmlfile, $xmlCloser, FILE_APPEND);

        // New sitemap success!
        echo 'Generated new sitemap at ' . $xmlfile . ' on ' . date('Y-m-d') . "\n";

        // Log this script's results
        $logFile = storage_path('logs/sitemap.log');
        $f = fopen($logFile, 'a');

        // Elapsed time for sitemap creation
        $e = microtime(true);
        $tot = $e - $s;
        echo 'Completed sitemap in ' . $tot . "\n";

        // Turn the output into log contents
        $out = ob_get_clean();
        fputs($f, $out);
        fclose($f);

        // Call the crawlSitemap method to crawl the generated sitemap
        $this->crawlSitemap();

        exit;
    }

    private function getUrls($url, &$urls)
    {
        // Get the page contents from the supplied $url
        $page = $this->getCurlContents($url);
        sleep(5);

        // Get all the links on the page
        if (preg_match_all('/href="([^"]+)"/i', $page, $matches)) {
            foreach ($matches[1] as $match) {
                // Convert relative URLs to absolute URLs
                if (strpos($match, 'http') !== 0) {
                    $match = rtrim($url, '/') . '/' . ltrim($match, '/');
                }

                // Normalize URL to prevent duplicate entries
                $match = preg_replace('/#.*$/', '', $match); // Remove anchors
                $match = rtrim($match, '/'); // Remove trailing slash

                // Check if the link is for this site
                if (strpos($match, parse_url($url, PHP_URL_HOST)) === false) continue;

                // Check if the link is a resource
                if (preg_match('/\.(css|js|jpg|jpeg|bmp|gif|png|zip|pdf)$/i', $match)) continue;

                // Check if the link is already in the current result array
                if (!in_array($match, $urls)) {
                    // Add link to the result array
                    $urls[] = $match;
                    // Page hasn't been parsed, get the links on that page (Recurse this function)
                    $this->getUrls($match, $urls);
                }
            }
        }
    }

    private function getCurlContents($url)
    {
        // Create curl handle
        $ch = curl_init();
        // Set curl options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:9.0.1) Gecko/20100101 Firefox/9.0.1');
        curl_setopt($ch, CURLOPT_HEADER, false);
        // Get the contents
        $contents = curl_exec($ch);
        // Close the handle
        curl_close($ch);
        // Return the contents
        return $contents;
    }

    public function crawlSitemap()
    {
        // Define the sitemap URL
        $sitemapUrl = url('sitemap.xml');  // Adjust as necessary

        // Fetch the sitemap
        $sitemapContent = $this->fetchSitemap($sitemapUrl);
        if (!$sitemapContent) {
            return response()->json(['error' => 'Error fetching the sitemap.'], 500);
        }

        // Parse the sitemap
        $sitemapXml = $this->parseSitemap($sitemapContent);
        if (!$sitemapXml) {
            return response()->json(['error' => 'Error parsing the sitemap.'], 500);
        }

        // Extract URLs from the sitemap
        $urls = $sitemapXml->url->loc;

        // Crawl the URLs
        $results = $this->crawlUrls($urls);

        // Return the results
        return response()->json(['results' => $results]);
    }

    private function fetchSitemap($sitemapUrl)
    {
        $response = Http::get($sitemapUrl);
        if ($response->successful()) {
            return $response->body();
        }
        return false;
    }

    private function parseSitemap($sitemapContent)
    {
        try {
            $sitemapXml = simplexml_load_string($sitemapContent);
            return $sitemapXml;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function crawlUrls($urls)
    {
        $results = [];
        foreach ($urls as $url) {
            $url = (string) $url;
            $result = [
                'url' => $url,
                'status' => 'success',
                'content' => ''
            ];
            try {
                $content = Http::get($url)->body();
                $result['content'] = substr($content, 0, 100);
            } catch (\Exception $e) {
                $result['status'] = 'error';
                $result['content'] = $e->getMessage();
            }
            $results[] = $result;
        }
        return $results;
    }


    private function validateEmail($sEmail)
    {
        $filter = '/^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/';
        return preg_match($filter, $sEmail) === 1;
    }
}


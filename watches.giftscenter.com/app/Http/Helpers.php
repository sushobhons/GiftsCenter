<?php

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Modules\Frontend\Entities\Domain;
use Modules\Frontend\Entities\Country;
use Modules\Frontend\Entities\SiteConfig;
use Modules\Frontend\Entities\ReferenceURL;
use Modules\Frontend\Entities\Header;
use Modules\Frontend\Entities\Footer;
use Modules\Frontend\Entities\Offer;
use Modules\Product\Entities\MainCategory;
use Modules\Product\Entities\Category;
use Modules\Product\Entities\SubCategory;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\Brand;
use Modules\Product\Entities\Segment;
use Modules\Cart\Entities\Cart;
use Modules\Cart\Entities\Wishlist;
use Modules\User\Entities\UserLoyaltyPoint;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

// use Auth;
class Helper
{

    public static function getCountries()
    {
        return Country::select([
                'sortname as country_code',
                'name as country_name',
                'phonecode as phone_code',
        ])->get();

    }

    public static function getHeaderDetails()
    {
        $currentDomain = self::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $headerRecord = Header::select('image', 'color_code')
            ->where('domain_id', $domainId)
            ->first();

        $headerBackgroundColor = $headerRecord->color_code ?? '#ffffff';
        $headerLogo = $headerRecord->image
            ? config('app.amazon_url') . $headerRecord->image
            : config('app.app_url') . 'public/img/logo.svg';

        return [
            'header_background_color' => $headerBackgroundColor,
            'header_logo' => $headerLogo,
        ];
    }

    public static function getCurrentDomain()
    {
        $currentDomain = config('app.domain_name');

        return Domain::where('domain_name', $currentDomain)->first();

    }

    public static function getFooterDetails()
    {
        $currentDomain = self::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $footerRecord = Footer::select('color_code')
            ->where('domain_id', $domainId)
            ->first();

        return $footerRecord->color_code ?? '#ffffff';
    }

    public static function getNewCategories()
    {
        $currentDomain = self::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $segment = Segment::where('slug', 'new-arrival')
            ->where('domain', $domainId)
            ->first();
        if(!$segment){
            return [];
        }
        $segmentId = $segment->id;
        $mCategoryRecords = DB::table('product_table as pt')
            ->select(
                'mt.main_cat_id',
                'mt.main_cat_name',
                'mt.main_cat_slug',
                'pt.sub_cat_id',
                'sct.sub_cat_name',
                'sct.sub_cat_slug',
                'ct.cat_id',
                'ct.cat_name',
                'ct.cat_slug',
                'st.stock'
            )
            ->join(DB::raw("(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) AS st"), 'pt.product_id', '=', 'st.product_id')
            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, sub_cat_slug, sub_cat_index, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
            ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, cat_slug, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
            ->join(DB::raw('(SELECT main_cat_id, main_cat_name, main_cat_index, main_cat_slug FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
            ->where('pt.in_segment', $segmentId)
            ->where('pt.web_status', '=', 1)
            ->where('pt.is_voucher', '0')
            ->where('pt.main_price', '>', 0)
            ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
            ->whereNotNull('st.stock')
            //->groupBy('main_cat_table.main_cat_id','cat_table.cat_id', 'sub_cat_table.sub_cat_id')
            ->orderBy('mt.main_cat_index')
            ->orderBy('ct.cat_index')
            ->orderBy('sct.sub_cat_index')
            ->get();

        $mCategoryArr = [];

        foreach ($mCategoryRecords as $record) {
            $mCategoryArr[$record->main_cat_id]['main_cat_name'] = Str::title($record->main_cat_name);
            $mCategoryArr[$record->main_cat_id]['main_cat_id'] = $record->main_cat_id;
            $mCategoryArr[$record->main_cat_id]['main_cat_slug'] = $record->main_cat_slug;
            $mCategoryArr[$record->main_cat_id]['segments'] = [];
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['cat_name'] = $record->cat_name;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['cat_id'] = $record->cat_id;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['cat_slug'] = $record->cat_slug;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['segments'] = [];
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['sub_cat_name'] = $record->sub_cat_name;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['sub_cat_id'] = $record->sub_cat_id;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['sub_cat_slug'] = $record->sub_cat_slug;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['segments'] = [];
        }

        return $mCategoryArr;
    }

    public static function getBestCategories()
    {
        $currentDomain = self::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $segment = Segment::where('slug', 'best-seller')
            ->where('domain', $domainId)
            ->first();
        if(!$segment){
            return [];
        }
        $segmentId = $segment->id;
        $mCategoryRecords = DB::table('product_table as pt')
            ->select(
                'mt.main_cat_id',
                'mt.main_cat_name',
                'mt.main_cat_slug',
                'pt.sub_cat_id',
                'sct.sub_cat_name',
                'sct.sub_cat_slug',
                'ct.cat_id',
                'ct.cat_name',
                'ct.cat_slug',
                'st.stock'
            )
            ->join(DB::raw("(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) AS st"), 'pt.product_id', '=', 'st.product_id')
            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, sub_cat_slug, sub_cat_index, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
            ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, cat_slug, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
            ->join(DB::raw('(SELECT main_cat_id, main_cat_name, main_cat_index, main_cat_slug FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
            ->where('pt.in_segment', $segmentId)
            ->where('pt.web_status', '=', 1)
            ->where('pt.is_voucher', '0')
            ->where('pt.main_price', '>', 0)
            ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
            ->whereNotNull('st.stock')
            //->groupBy('main_cat_table.main_cat_id','cat_table.cat_id', 'sub_cat_table.sub_cat_id')
            ->orderBy('mt.main_cat_index')
            ->orderBy('ct.cat_index')
            ->orderBy('sct.sub_cat_index')
            ->get();

        $mCategoryArr = [];

        foreach ($mCategoryRecords as $record) {
            $mCategoryArr[$record->main_cat_id]['main_cat_name'] = Str::title($record->main_cat_name);
            $mCategoryArr[$record->main_cat_id]['main_cat_id'] = $record->main_cat_id;
            $mCategoryArr[$record->main_cat_id]['main_cat_slug'] = $record->main_cat_slug;
            $mCategoryArr[$record->main_cat_id]['segments'] = [];
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['cat_name'] = $record->cat_name;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['cat_id'] = $record->cat_id;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['cat_slug'] = $record->cat_slug;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['segments'] = [];
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['sub_cat_name'] = $record->sub_cat_name;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['sub_cat_id'] = $record->sub_cat_id;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['sub_cat_slug'] = $record->sub_cat_slug;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['segments'] = [];
        }

        return $mCategoryArr;
    }

    public static function getBrands()
    {
        $currentDomain = self::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $mBrandRecords = Product::select(
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

        $mBrandArr = [];
        $mbBrandArr = [];

        foreach ($mBrandRecords as $rowBrandRecord) {
            $mBrandArr[$rowBrandRecord->brand_key][$rowBrandRecord->id] = [
                'brand_id' => $rowBrandRecord->id,
                'brand_name' => $rowBrandRecord->name,
                'brand_slug' => $rowBrandRecord->brand_slug,
            ];
        }
//        if (!empty($mBrandArr)) {
//            foreach ($mBrandArr as $alphabetKey => $alphabetBrands) {
//                $mBrandArr[$alphabetKey] = !empty($alphabetBrands) ? array_chunk($alphabetBrands, 6) : [];
//            }
//        }
//        foreach ($mBrandArr as $alphabetKey => $alphabetBrands) {
//            $mBrandArr[$alphabetKey] = !empty($alphabetBrands) ? array_chunk($alphabetBrands, 6) : [];
//        }
        //dd($mBrandArr);
        return $mBrandArr;
    }

    public static function getCategories()
    {
        $currentDomain = self::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;

        $mCategoryRecords = DB::table('product_table as pt')
            ->select(
                'mt.main_cat_id',
                'mt.main_cat_name',
                'mt.main_cat_slug',
                'pt.sub_cat_id',
                'sct.sub_cat_name',
                'sct.sub_cat_slug',
                'ct.cat_id',
                'ct.cat_name',
                'ct.cat_slug',
                'st.stock',
                'sg.id as in_segment',
                'sg.title as segment_title',
                'sg.slug as segment_slug',
                'sg.domain as segment_status',
            )
            ->join(DB::raw("(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) AS st"), 'pt.product_id', '=', 'st.product_id')
            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, sub_cat_slug, sub_cat_index, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
            ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, cat_slug, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
            ->join(DB::raw('(SELECT main_cat_id, main_cat_name, main_cat_index, main_cat_slug FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
            ->leftJoinSub(function ($query) use ($domainId) {
                $query->select('id','title','slug','domain')
                    ->from('gc_segments')
                    ->whereIn('slug', ['new-arrival','best-seller'])
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
            ->where('pt.sub_cat_id', '!=', 0)
            ->where('pt.web_status', '=', 1)
            ->where('pt.is_voucher', '0')
            ->where('pt.main_price', '>', 0)
            ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
            ->whereNotNull('st.stock')
            //->groupBy('mt.main_cat_id','ct.cat_id', 'sct.sub_cat_id')
            ->orderBy('mt.main_cat_index')
            ->orderBy('ct.cat_index')
            ->orderBy('sct.sub_cat_index')
            ->get();
        $mCategoryArr = [];

        foreach ($mCategoryRecords as $record) {
            $mCategoryArr[$record->main_cat_id]['main_cat_name'] = Str::title($record->main_cat_name);
            $mCategoryArr[$record->main_cat_id]['main_cat_id'] = $record->main_cat_id;
            $mCategoryArr[$record->main_cat_id]['main_cat_slug'] = $record->main_cat_slug;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['cat_name'] = $record->cat_name;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['cat_id'] = $record->cat_id;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['cat_slug'] = $record->cat_slug;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['segments'] = [];
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['sub_cat_name'] = $record->sub_cat_name;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['sub_cat_id'] = $record->sub_cat_id;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['sub_cat_slug'] = $record->sub_cat_slug;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['segments'] = [];
            if (!empty($record->in_segment)) {
                $mCategoryArr[$record->main_cat_id]['segments'][] = [
                    'title' => $record->segment_title,
                    'slug' => $record->segment_slug
                ];
            }
        }
        foreach ($mCategoryArr as $mKey => $mCategory) {
            if(!empty($mCategory['segments'])){
                $sData =  array_values(array_unique($mCategory['segments'], SORT_REGULAR));
                array_multisort(array_column($sData, 'title'), SORT_DESC, $sData);
                $mCategoryArr[$mKey]['segments'] = $sData;
            }
        }

        return $mCategoryArr;
    }

    public static function getCategoriesss()
    {
        $currentDomain = self::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;

        $mCategoryRecords = Product::with(['mainCategory', 'category', 'subCategory'])
            ->select(
                'mt.main_cat_id',
                'mt.main_cat_name',
                'mt.main_cat_slug',
                'pt.sub_cat_id',
                'sct.sub_cat_name',
                'sct.sub_cat_slug',
                'ct.cat_id',
                'ct.cat_name',
                'ct.cat_slug',
                'st.stock',
                'sg.id as in_segment',
                'sg.title as segment_title',
                'sg.slug as segment_slug',
                'sg.domain as segment_status',
            )
            ->join('stock_table', function ($join) {
                $join->on('product_table.product_id', '=', 'stock_table.product_id')
                    ->where('stock_table.qty', '>', 0);
            })
            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, sub_cat_slug, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
            ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, cat_slug, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
            ->join(DB::raw('(SELECT main_cat_id, main_cat_name, main_cat_slug FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
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
            ->where('pt.sub_cat_id', '!=', 0)
            ->where('pt.web_status', '=', 1)
            ->where('pt.main_price', '>', 0)
            ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
            ->whereNotNull('st.stock')
            //->groupBy('main_cat_table.main_cat_id','cat_table.cat_id', 'sub_cat_table.sub_cat_id')
            ->orderBy('mt.main_cat_index')
            ->orderBy('ct.cat_index')
            ->orderBy('sct.sub_cat_index')
            ->get();
        $mCategoryArr = [];

        foreach ($mCategoryRecords as $record) {
//            if (!empty($record['in_segment'])) {
//                array_push($segment_data, array(
//                    'title'  => $record->segment_title,
//                    'slug' => $record->segment_slug,
//                ));
//            }
            $mCategoryArr[$record->main_cat_id]['main_cat_name'] = Str::title($record->main_cat_name);
            $mCategoryArr[$record->main_cat_id]['main_cat_id'] = $record->main_cat_id;
            $mCategoryArr[$record->main_cat_id]['main_cat_slug'] = $record->main_cat_slug;
            $mCategoryArr[$record->main_cat_id]['segments'] = [];
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['cat_name'] = $record->cat_name;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['cat_id'] = $record->cat_id;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['cat_slug'] = $record->cat_slug;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['segments'] = [];
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['sub_cat_name'] = $record->sub_cat_name;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['sub_cat_id'] = $record->sub_cat_id;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['sub_cat_slug'] = $record->sub_cat_slug;
            $mCategoryArr[$record->main_cat_id]['category'][$record->cat_id]['subcategory'][$record->sub_cat_id]['segments'] = [];
        }

        return $mCategoryArr;
    }

    public static function getOffers()
    {
        $currentDomain = self::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $currentDate = Carbon::now()->toDateString();
        $currentDay = strtolower(now()->format('D'));
        return Offer::select('offer_slug', 'offer_name', 'offer_desc')
            ->where('status', '=', '1')
            ->where('show_status', '=', '1')
            ->whereRaw("FIND_IN_SET(?, in_domain)", [$domainId])
            ->where(function ($query) use ($currentDate, $currentDay) {
                $query->where(function ($q) use ($currentDate) {
                    $q->whereBetween(DB::raw("'" . $currentDate . "'"), [DB::raw('DATE(from_date)'), DB::raw('DATE(to_date)')])
                        ->orWhere(function ($q) use ($currentDate) {
                            $q->where('from_date', '<=', DB::raw("'" . $currentDate . "'"))
                                ->where('to_date', '=', DB::raw("'0000-00-00 00:00:00'"));
                        });
                })->orWhere(function ($q) use ($currentDay) {
                    $q->whereRaw("FIND_IN_SET(?, week_days)", ["'$currentDay'"]);
                });
            })
            ->orderBy('offer_slug', 'ASC')
            ->get();
    }

    public static function getConcerns()
    {
        $currentDomain = self::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $mainCategoryId = 23;
        $subCategoryIds = SubCategory::whereIn('cat_id', function ($query) use ($mainCategoryId) {
            $query->select('cat_id')
                ->from('cat_table')
                ->where('main_cat_id', $mainCategoryId);
        })->pluck('sub_cat_id');
        return DB::table('gc_concerns as gc')->select('gc.id', 'gc.title', 'gc.slug')
            ->joinSub(function ($query) use ($subCategoryIds) {
                $query->select('product_id', 'web_status', 'main_price', 'in_domain', 'in_concern')
                    ->from('product_table')
                    ->whereIn('sub_cat_id', $subCategoryIds)
                    ->where('in_concern', '!=', '');
            }, 'pcn', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(gc.id, pcn.in_concern)'), '>', DB::raw('0'));
            })
            ->joinSub(function ($query) {
                $query->select('qty as stock', 'product_id')
                    ->from('stock_table')
                    ->where('qty', '>', 0);
            }, 'st', 'pcn.product_id', '=', 'st.product_id')
            ->where('pcn.web_status', '1')
            ->where('pcn.main_price', '>', 0)
            ->whereRaw("FIND_IN_SET(?, pcn.in_domain)", [$domainId])
            ->whereNotNull('st.stock')
            ->groupBy('gc.id')
            ->get();
    }

    public static function getCollections()
    {
        $currentDomain = self::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        return DB::table('gc_collections as gc')->select('gc.id', 'gc.name', 'gc.slug')
            ->where('gc.status', '1')
            ->get();
    }

    public static function getHomeNewArrivals()
    {
        $domainId = 1; // Replace with the actual domain ID
        $segment = Segment::where('slug', 'new-arrival')
            ->where('domain', $domainId)
            ->first();
        $segmentId = $segment->id;
        $mCategoryRecords = Product::with(['mainCategory', 'category', 'subCategory'])
            ->select(
                'main_cat_table.main_cat_id',
                'main_cat_table.main_cat_name',
                'main_cat_table.main_cat_slug',
            )
            ->join('stock_table', function ($join) {
                $join->on('product_table.product_id', '=', 'stock_table.product_id')
                    ->where('stock_table.qty', '>', 0);
            })
            ->leftJoin('sub_cat_table', 'product_table.sub_cat_id', '=', 'sub_cat_table.sub_cat_id')
            ->leftJoin('cat_table', 'sub_cat_table.cat_id', '=', 'cat_table.cat_id')
            ->leftJoin('main_cat_table', 'cat_table.main_cat_id', '=', 'main_cat_table.main_cat_id')
            ->whereRaw("FIND_IN_SET(?, product_table.in_segment)", [$segmentId])
            ->where('product_table.web_status', '=', 1)
            ->where('product_table.main_price', '>', 0)
            ->whereRaw("FIND_IN_SET(?, product_table.in_domain)", [$domainId])
            ->whereNotNull('stock_table.qty')
            ->groupBy('main_cat_table.main_cat_id')
            ->orderBy('main_cat_table.main_cat_index')
            ->get();
        $mCategoryArr = [];

        foreach ($mCategoryRecords as $record) {
            $mainCategoryId = $record->main_cat_id;
            $subCategoryIds = SubCategory::whereIn('cat_id', function ($query) use ($mainCategoryId) {
                $query->select('cat_id')
                    ->from('cat_table')
                    ->where('main_cat_id', $mainCategoryId);
            })->pluck('sub_cat_id');
            $productsRecord = DB::table('product_table as pt')
                ->select([
                    'pt.product_id',
                    DB::raw("ROUND(pt.main_price, 0) as main_price"),
                    DB::raw("ROUND(MAX(pt.main_price), 0) as max_price"),
                    DB::raw("ROUND(MIN(NULLIF(pt.main_price, 0)), 0) as min_price"),
                    DB::raw("IF(pt.type_flag = '2', pt.seo_url, f.seo_url) as seo_url"),
                    DB::raw("IF(pt.type_flag = '2', SUBSTR(REPLACE(pt.product_name, '\\\\', ''), 1, 60) , SUBSTR(REPLACE(f.family_name, '\\\\', ''), 1, 60)) as family_name"),
                    DB::raw("IF(pt.type_flag = '2', pt.photo1, fp.aws) as family_pic"),
                    DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) as unique_key"),
                    'br.brand_name',
                ])
                ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
                ->join(DB::raw('(SELECT family_id, family_name, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
                ->join(DB::raw('(SELECT family_id,aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
                ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as br'), 'pt.brand_id', '=', 'br.brand_id')
                ->where('pt.web_status', '=', '1')
                ->where('pt.main_price', '>', 0)
                ->whereRaw("FIND_IN_SET(?, pt.in_segment)", [$segmentId])
                ->whereRaw("FIND_IN_SET(?, pt.in_domain)", [$domainId])
                ->whereIn('pt.sub_cat_id', $subCategoryIds)
                ->whereNotNull('st.stock')
                ->groupBy(DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr)"))
                ->inRandomOrder()
                ->take(12)
                ->get();
            foreach ($productsRecord as $rowProductsRecord) {
                $rowProductsRecord->dmain_price = '';
                $rowProductsRecord->dmax_price = '';
                $rowProductsRecord->dmin_price = '';
            }
            $record->products = $productsRecord;
        }
        return $mCategoryRecords;
    }

    public static function getHomeBrands()
    {
        $currentDomain = self::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        $brandIds = Brand::where('is_featured', 1)
            ->pluck('id');
        $brandRecords = Product::
        select(
            'br.brand_id',
            'br.brand_name',
            'br.brand_slug',
            'br.logo',
            'br.brand_description',
            'br.banner_image',
        )
            ->join('stock_table', function ($join) {
                $join->on('product_table.product_id', '=', 'stock_table.product_id')
                    ->where('stock_table.qty', '>', 0);
            })
            ->join(DB::raw('(SELECT id as brand_id, name as brand_name, brand_slug, logo, brand_description, banner_image FROM brand_table WHERE web_status = "1") as br'), 'product_table.brand_id', '=', 'br.brand_id')
            ->whereIn('product_table.brand_id', $brandIds)
            ->where('product_table.web_status', '=', 1)
            ->where('product_table.main_price', '>', 0)
            ->whereRaw("FIND_IN_SET(?, product_table.in_domain)", [$domainId])
            ->whereNotNull('stock_table.qty')
            ->groupBy('product_table.brand_id')
            ->orderBy('br.brand_name')
            ->get();

        foreach ($brandRecords as $record) {
            $brandId = $record->brand_id;
            $productsRecord = DB::table('product_table as pt')
                ->select([
                    'pt.product_id',
                    DB::raw("ROUND(pt.main_price, 0) as main_price"),
                    DB::raw("ROUND(MAX(pt.main_price), 0) as max_price"),
                    DB::raw("ROUND(MIN(NULLIF(pt.main_price, 0)), 0) as min_price"),
                    DB::raw("IF(pt.type_flag = '2', pt.seo_url, f.seo_url) as seo_url"),
                    DB::raw("IF(pt.type_flag = '2', SUBSTR(REPLACE(pt.product_name, '\\\\', ''), 1, 60) , SUBSTR(REPLACE(f.family_name, '\\\\', ''), 1, 60)) as family_name"),
                    DB::raw("IF(pt.type_flag = '2', pt.photo1, fp.aws) as family_pic"),
                    DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr) as unique_key"),
                    'br.brand_name',
                ])
                ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
                ->join(DB::raw('(SELECT family_id, family_name, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
                ->join(DB::raw('(SELECT family_id,aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
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
                $rowProductsRecord->dmain_price = '';
                $rowProductsRecord->dmax_price = '';
                $rowProductsRecord->dmin_price = '';
            }

            $record->products = $productsRecord;
        }
        return $brandRecords;
    }

    public static function getHeaderCategory()
    {
        $category = new Category();
        // dd($category);
        $menu = $category->getAllParentWithChild();

        if ($menu) {
            ?>

            <li>
                <a href="javascript:void(0);">Category<i class="ti-angle-down"></i></a>
                <ul class="dropdown border-0 shadow">
                    <?php
                    foreach ($menu as $cat_info) {
                        if ($cat_info->child_cat->count() > 0) {
                            ?>
                            <li>
                                <a href="<?php echo route('product-cat', $cat_info->slug); ?>"><?php echo $cat_info->title; ?></a>
                                <ul class="dropdown sub-dropdown border-0 shadow">
                                    <?php
                                    foreach ($cat_info->child_cat as $sub_menu) {
                                        ?>
                                        <li>
                                            <a href="<?php echo route('product-sub-cat', [$cat_info->slug, $sub_menu->slug]); ?>"><?php echo $sub_menu->title; ?></a>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                            </li>
                            <?php
                        } else {
                            ?>
                            <li>
                                <a href="<?php echo route('product-cat', $cat_info->slug); ?>"><?php echo $cat_info->title; ?></a>
                            </li>
                            <?php
                        }
                    }
                    ?>
                </ul>
            </li>
            <?php
        }
    }

    public static function productCategoryList($option = 'all')
    {
        if ($option = 'all') {
            return Category::orderBy('id', 'DESC')->get();
        }
        return Category::has('products')->orderBy('id', 'DESC')->get();
    }

    public static function postTagList($option = 'all')
    {
        if ($option = 'all') {
            return PostTag::orderBy('id', 'desc')->get();
        }
        return PostTag::has('posts')->orderBy('id', 'desc')->get();
    }

    public static function postCategoryList($option = "all")
    {
        if ($option = 'all') {
            return PostCategory::orderBy('id', 'DESC')->get();
        }
        return PostCategory::has('posts')->orderBy('id', 'DESC')->get();
    }

    public static function loyaltyPoints()
    {
        $user = Auth::user();
        if ($user) {
            $customerId = $user->customer_id;
            $pointRecord = UserLoyaltyPoint::where('customer_id', $customerId)
                ->orderByDesc('added_date')
                ->orderByDesc('id')
                ->first();
            if ($pointRecord) {
                $loyaltyPoint = (int)$pointRecord->post_balance;
            } else {
                $loyaltyPoint = 0; // Set a default value
            }
        } else {
            $loyaltyPoint = 0; // Set a default value
        }
        return $loyaltyPoint;
    }

    public static function wishlistCount()
    {
        $user = Auth::user();

        if ($user) {
            $customerId = $user->customer_id;
            return Wishlist::where('customer_id', $customerId)->count();
        } else {
            return 0;
        }
    }

    //Loyalty Points

    public static function cartCount()
    {
        //Session::flush('cart');
        //Session::flush('cart');
        $user = Auth::user();
        $cartCount = 0;

        if ($user) {
            // If the user is logged in, retrieve the user's cart items
            $customerId = $user->customer_id;
            $cartCount = Cart::where('customer_id', $customerId)->sum('product_qty');
        } else {
            $cart = Session::get('gc_cart', []);
            if (!empty($cart)) {
                foreach ($cart as $cartItem) {
                    $cartCount += $cartItem['product_qty'];
                }
            }
        }

        return $cartCount;
    }

    // Wishlist Count

    public static function getAllProductFromCart($user_id = '')
    {
        if (Auth::check()) {
            if ($user_id == "") $user_id = auth()->user()->id;
            return Cart::with('product')->where('user_id', $user_id)->where('order_id', null)->get();
        } else {
            return 0;
        }
    }

    // Cart Count

    public static function totalCartPrice($user_id = '')
    {
        if (Auth::check()) {
            if ($user_id == "") $user_id = auth()->user()->id;
            return Cart::where('user_id', $user_id)->where('order_id', null)->sum('amount');
        } else {
            return 0;
        }
    }

    // relationship cart with product

    public static function getAllProductFromWishlist()
    {
        $user = Auth::user();

        if ($user) {
            $customerId = $user->customer_id;
            return Wishlist::where('customer_id', $customerId)->get();
        } else {
            return 0;
        }
    }

    public static function totalWishlistPrice($user_id = '')
    {
        if (Auth::check()) {
            if ($user_id == "") $user_id = auth()->user()->id;
            return Wishlist::where('user_id', $user_id)->where('cart_id', null)->sum('amount');
        } else {
            return 0;
        }
    }

    // Total amount cart

    public static function grandPrice($id, $user_id)
    {
        $order = Order::find($id);
        dd($id);
        if ($order) {
            $shipping_price = (float)$order->shipping->price;
            $order_price = self::orderPrice($id, $user_id);
            return number_format((float)($order_price + $shipping_price), 2, '.', '');
        } else {
            return 0;
        }
    }

    public static function earningPerMonth()
    {
        $month_data = Order::where('status', 'delivered')->get();
        // return $month_data;
        $price = 0;
        foreach ($month_data as $data) {
            $price = $data->cart_info->sum('price');
        }
        return number_format((float)($price), 2, '.', '');
    }

    public static function shipping()
    {
        return Shipping::orderBy('id', 'DESC')->get();
    }

    // Total price with shipping and coupon

    /**
     * Retrieves the value of a specific option from the site configuration.
     *
     * @param string $optionName The name of the option to retrieve.
     * @return string|null The value of the option if found, or null if not found.
     */
    public static function getSiteConfig(string $optionName): ?string
    {
        $currentDomain = self::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;

        $siteConfig = SiteConfig::where('option_name', $optionName)
            ->where('in_domain', $domainId)
            ->where('status', '1')
            ->first();

        return $siteConfig ? $siteConfig->option_value : null;
    }

    /**
     * Sends an SMS using a provided SMS gateway.
     *
     * @param string $phone The recipient's phone number.
     * @param string $message The message content to send.
     *
     * @return bool True if the SMS is sent successfully, false otherwise.
     */
    public static function sendSMS(string $phone, string $message): bool {
        $smsGatewayUrl = config('services.sms_gateway.url');
        $senderId = config('services.sms_gateway.sender_id');
        $accountName = config('services.sms_gateway.account_name');
        $accountPassword = config('services.sms_gateway.account_password');
        $requestTimeout = config('services.sms_gateway.request_timeout');

        // Validate parameters
        if (empty($phone) || empty($message)) {
            return false;
        }

        // Construct the SMS gateway URL
        $url = "{$smsGatewayUrl}?numbers={$phone},&senderid={$senderId}&AccName={$accountName}&AccPass={$accountPassword}&msg={$message}&requesttimeout={$requestTimeout}";
        $url = str_replace(['+', '#', ' '], ['%2B', '%23', '%20'], $url);

        // Initialize cURL
        $curl = curl_init();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);

        // Execute cURL request
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Close cURL
        curl_close($curl);

        // Check if SMS is sent successfully
        return $httpCode === 200;
    }

    /**
     * Sends an email using Laravel's built-in Mail class.
     *
     * @param string $email The recipient's email address.
     * @param string $name The recipient's name.
     * @param string $subject The subject of the email.
     * @param string $content The HTML content of the email.
     * @param array|null $systemEmails Array of system email recipients with email and name, or null.
     *
     * @return bool True if the email is sent successfully, false otherwise.
     */
    public static function sendMail(string $email, string $name, string $subject, string $content, ?array $systemEmails = null): bool
    {
        $mail = new PHPMailer(true);
        
        try {

            //Server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP(); 
            $mail->Host = config('mail.mailers.smtp.host');
            $mail->SMTPAuth = true;
            $mail->Username = config('mail.mailers.smtp.username');
            $mail->Password = config('mail.mailers.smtp.password');
            $mail->SMTPSecure = config('mail.mailers.smtp.encryption');
            $mail->Port = config('mail.mailers.smtp.port');

            //Recipients
            $mail->addAddress($email, $name);
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));

            //Add system email recipients if provided
            if ($systemEmails !== null) {
                foreach ($systemEmails as $systemEmail) {
                    $mail->addBCC($systemEmail->email, $systemEmail->name);
                }
            }

            //Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $content;
            if( !$mail->send() ) {
                return false;
            }
            return true;
        } catch (Exception $e) {
            
            Log::error('Error sending email: ' . $e->getMessage());

            return false;
        }


    }

    function sendOTP($data)
    {
        $notificationUrl = "https://ikasco.com/Shopping-App/api/gc-send-otp.php";

        $response = Http::post($notificationUrl, $data);

        // Optionally, you can return the response if needed
        return $response->body();
    }

    function sendEmail($data)
    {
        $notificationUrl = "https://ikasco.com/Shopping-App/api/gc-send-eamil.php";

        // Prepare the data to be sent in the POST request
//        $data = [
//            'email' => $email,
//            'subject' => $subject,
//            'content' => $content
//        ];

        // Send the POST request
        $response = Http::post($notificationUrl, $data);

        // Optionally, you can return the response if needed
        return $response->body();
    }

    public static function getReferenceURLs()
    {
        $currentDomain = self::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;
        return DB::table('domain_links')
            ->select('name', 'links')
            ->where('domain', $domainId)
            ->orderBy('order_key')
            ->get();
    }

    public function product()
    {
        return $this->hasOne('App\Models\Product', 'id', 'product_id');
    }

    /**
     * Generates a random One-Time Password (OTP) consisting only of numbers.
     *
     * @param int $length (optional) The desired number of characters in the OTP.
     *                    Defaults to 4. Minimum length is 4.
     * @return string The generated OTP containing only numbers.
     *
     */
    public static function generateOTP(int $length = 4): string
    {
        // Enforce minimum length of 1
        $length = max(1, $length);

        // Define allowed numeric characters
        $allowedChars = '0123456789';

        // Initialize an empty string to store the OTP
        $otp = '';

        // Generate random numeric OTP
        for ($i = 0; $i < $length; $i++) {
            $otp .= $allowedChars[random_int(0, strlen($allowedChars) - 1)];
        }

        return $otp;
    }

    /**
     * Cleans and sanitizes a given string, making it suitable for various data processing tasks.
     *
     * @param string|null $string $string The input string to be cleaned.
     * @return string The cleaned string.
     *
     * **Cleaning Steps:**
     * 1. **Replace single quotes with backticks:** This prevents unintended side effects
     *    when using single quotes in database queries, file paths, or other situations
     *    where they might be interpreted as string delimiters.
     * 2. **Remove special characters:** All characters except alphanumeric characters
     *    (A-Z, a-z, 0-9), parentheses, plus sign (+), hyphen (-), backtick (`), at sign (@),
     *    percent sign (%), and ampersand (&) are removed. This helps ensure compatibility
     *    with different systems and prevents potential security vulnerabilities.
     * 3. **Consolidate multiple hyphens:** Replaces multiple consecutive hyphens (--) with
     *    a single hyphen (-). This improves readability and consistency.
     * 4. **Normalize whitespace:** Removes extra spaces and replaces them with a single
     *    space. This ensures consistent spacing within the string.
     * 5. **Empty string handling:** If the input string is empty after cleaning, the
     *    function returns `null` to indicate that no valid content remains.
     */
    public static function cleanString(?string $string): string
    {
        if ($string === null) {
            return ''; // or handle null value in some other way
        }
        $string = str_replace("'", '`', $string); // Replace single quotes with backticks
        $string = preg_replace('/[^A-Za-z0-9()+\-`@%&*]/', ' ', $string); // Remove special characters except allowed ones
        $string = preg_replace('/-+/', ' ', $string); // Replace multiple hyphens with a single one
        // Normalize spaces
        return preg_replace('/\s+/', ' ', trim($string));
    }

    /**
     * Removes emojis from the given text.
     *
     * @param string|null $text The input text that might contain emojis.
     * @return string The text without emojis.
     *
     * **Removal Strategy:**
     * - Uses Unicode character ranges to target specific emoji categories:
     *   - Emoticons (U+1F600 - U+1F64F)
     *   - Miscellaneous Symbols and Pictographs (U+1F300 - U+1F5FF)
     *   - Transport And Map Symbols (U+1F680 - U+1F6FF)
     *   - Miscellaneous Symbols (U+2600 - U+26FF)
     *   - Dingbats (U+2700 - U+27BF)
     * - Replaces each emoji range with an empty string, effectively removing them.
     * 4. **Normalize whitespace:** Removes extra spaces and replaces them with a single
     *    space. This ensures consistent spacing within the cleaned text.
     * 5. **Empty string handling:** If the input text is empty after removing emojis,
     *    the function returns `null` to indicate that no valid content remains.
     */
    function cleanEmojis(?string $text): string
    {
        if ($text === null) {
            return ''; // or handle null value in some other way
        }

        $clean_text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text); // Emoticons
        $clean_text = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $clean_text); // Miscellaneous Symbols and Pictographs
        $clean_text = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $clean_text); // Transport And Map Symbols
        $clean_text = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $clean_text); // Miscellaneous Symbols
        $clean_text = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $clean_text); // Dingbats
        // Normalize spaces
        return preg_replace('/\s+/', ' ', trim($clean_text));
    }

    /**
     * Creates a session with MasterCard for a new payment transaction.
     *
     * @param string $orderId The order ID associated with the payment.
     * @param string $orderAmount The amount of the payment.
     * @return string|null The session ID for the payment session, or null if unsuccessful.
     *
     * **Functionality:**
     * This function initiates a payment session with the MasterCard API for a new transaction.
     * It constructs the necessary data for the request and sends it to the MasterCard gateway URL.
     * Upon receiving the response, it parses it to extract the session ID.
     * If the request is successful and a session ID is retrieved, the function returns the session ID.
     * Otherwise, it returns null to indicate an unsuccessful operation.
     */
    public static function createMasterCardPaySession(string $orderId, string $orderAmount): ?string
    {
        // Encode the return URL
        $returnUrl = url('/order-processing');

        // Retrieve configuration values from the environment
        $apiUsername = config('services.mc_payment.api_username');
        $apiPassword = config('services.mc_payment.api_password');
        $merchantId = config('services.mc_payment.merchant_id');
        $gatewayUrl = config('services.mc_payment.gateway_url');

        // Prepare POST fields for the request
        $postFields = http_build_query([
            'apiOperation' => 'CREATE_CHECKOUT_SESSION',
            'apiPassword' => $apiPassword,
            'apiUsername' => $apiUsername,
            'merchant' => $merchantId,
            'interaction.operation' => 'PURCHASE',
            'order.id' => $orderId,
            'order.reference' => $orderId,
            'order.amount' => $orderAmount,
            'order.currency' => 'JOD',
            'order.description' => 'Gifts Center Ordered goods',
            'interaction.returnUrl' => $returnUrl
        ]);

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt_array($ch, [
            CURLOPT_URL => $gatewayUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded;charset=UTF-8']
        ]);

        // Execute the cURL request
        $response = curl_exec($ch);

        // Check for cURL errors and response
        if (curl_errno($ch) || !$response) {
            $errorMessage = curl_error($ch) ?: 'No response from server';
            curl_close($ch);
            return null;
        }

        // Close the cURL session
        curl_close($ch);

        // Parse the response to extract session ID
        parse_str($response, $responseArray);

        // Store the session data in the session
        session(['epay_session' => $responseArray]);

        // Return the session ID
        return $responseArray['session_id'] ?? null;
    }

    /**
     * Creates a session with MontyPay for a new payment transaction.
     *
     * @param string $orderNumber The order number associated with the payment.
     * @param string $orderAmount The amount of the payment.
     * @return string|null The URL to redirect the user to for payment, or null if unsuccessful.
     *
     * **Functionality:**
     * This function initiates a payment session with MontyPay API for a new transaction.
     * It sends a request to the MontyPay gateway URL with the necessary data and retrieves
     * the redirect URL to proceed with the payment.
     * If the request is successful, the function returns the redirect URL. Otherwise, it
     * returns null.
     */
    public static function createMontyPaySession(string $orderNumber, string $orderAmount): ?string
    {
        // Retrieve configuration values from the environment
        $gatewayUrl = config('services.mp_payment.gateway_url');
        $successUrl = url('/order-processing');
        $cancelUrl = url('/payment');
        $notificationUrl = url('/montypay-callback');
        $merchantKey = config('services.mp_payment.merchant_key');
        $password = config('services.mp_payment.api_password');

        // Prepare additional data for the request
        $orderCurrency = 'JOD';
        $orderDescription = 'Important gift';

        // Concatenate the values for generating hash
        $toMd5 = $orderNumber . $orderAmount . $orderCurrency . $orderDescription . $password;

        // Generate MD5 hash
        $md5Hash = md5(strtoupper($toMd5));

        // Generate SHA1 hash
        $sha1Hash = sha1($md5Hash);

        // Prepare request data
        $requestData = json_encode([
            "merchant_key" => $merchantKey,
            "operation" => "purchase",
            "methods" => ["card"],
            "order" => [
                "number" => $orderNumber,
                "amount" => $orderAmount,
                "currency" => $orderCurrency,
                "description" => $orderDescription
            ],
            "cancel_url" => $cancelUrl,
            "success_url" => $successUrl,
            "notification_url" => $notificationUrl,
            "hash" => $sha1Hash
        ]);

        // Initialize cURL session
        $curl = curl_init();

        // Set cURL options
        curl_setopt_array($curl, array(
            CURLOPT_URL => $gatewayUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $requestData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
        ));

        // Execute the cURL request
        $response = curl_exec($curl);

        // Get HTTP response code
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Close cURL session
        curl_close($curl);

        // Check if the response was successful (HTTP code 2xx)
        if ($httpCode >= 200 && $httpCode < 300) {
            session(['montypay_session' => ['order_number'=>$orderNumber,'order_amount'=>$orderAmount]]);
            // Decode the JSON response to an associative array
            $responseArray = json_decode($response, true);

            // Check if redirect_url is present in the response
            if (isset($responseArray['redirect_url'])) {
                return $responseArray['redirect_url'];
            } else {
                // Handle case where redirect_url is missing
                return null;
            }
        } else {
            // Handle case where HTTP request was not successful
            return null;
        }
    }


}

?>
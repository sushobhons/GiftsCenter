<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Facades\DB;

class ProductsExport implements FromView
{
    public function view(): View
    {
        // Retrieve product data
        $productUrl = config('app.product_url');
        $productsRecord = DB::table('product_table as pt')
            ->select([
                'pt.product_id as id',
                DB::raw("IF(pt.`type_flag` = '2',
                    SUBSTR(REPLACE(pt.`product_name`, '\\\', ''), 1, 150),
                    SUBSTR(REPLACE(`f`.`family_name`, '\\\', ''), 1, 150)
                ) AS `title`"),
                DB::raw("IF(pt.type_flag = '2', 
                    CONCAT('$productUrl', pt.seo_url), 
                    CONCAT('$productUrl', f.seo_url)
                ) as link"),
                DB::raw("'yes' AS `identifier_exists`"),
                DB::raw("ROUND(pt.main_price, 0) as price"),
                DB::raw("CONCAT('https://giftscenter.s3.me-central-1.amazonaws.com/familypic/', fp.family_pic) as image_link"),
                'br.brand_name as brand',
            ])
            ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
            ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
            ->join(DB::raw('(SELECT main_cat_id, main_cat_name, Old_value FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
            ->leftJoin(DB::raw('(SELECT family_id, family_name, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
            ->leftJoin(DB::raw('(SELECT family_id, family_pic, aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
            ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as br'), 'pt.brand_id', '=', 'br.brand_id')
            ->where('pt.web_status', '=', '1')
            ->whereIn('mt.Old_value', ['FR', 'CM', 'CS'])
            ->whereIn('pt.product_id', function ($query) {
                $query->select('a.product_id')
                    ->from('account_product_table_new as a')
                    ->join('store_table as st', 'a.store_id', '=', 'st.store_id')
                    ->whereIn('a.company', [4, 9])
                    ->whereIn('st.store_id', [28, 13])
                    ->groupBy('a.product_id')
                    ->havingRaw('SUM(a.instock) - SUM(a.outstock) > 1');
            })
            //->take(20)
            ->get();

        return view('exports.products', [
            'products' => $productsRecord
        ]);
    }
}


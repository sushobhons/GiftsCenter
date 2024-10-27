<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateProductFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:productfeed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the product feed for Facebook';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $brandIds = [1185, 46, 52, 229, 1205, 1187, 74, 4, 1182, 15, 1206, 26, 197, 1214, 1183, 1207, 201, 202, 225, 1];
        $productUrl = config('app.product_url');
        $productsRecord = DB::table('product_table as pt')
            ->select([
                'pt.product_id as id',
                DB::raw("IF(pt.`type_flag` = '2',
                    SUBSTR(REPLACE(pt.`product_name`, '\\\', ''), 1, 150),
                    SUBSTR(REPLACE(`f`.`family_name`, '\\\', ''), 1, 150)
                ) AS `title`"),
                DB::raw("IF(pt.`type_flag` = '2',
                    REPLACE(pt.`long_desc`, '\\\', ''),
                    REPLACE(`f`.`family_desc`, '\\\', '')
                ) AS `description`"),
                DB::raw("IF(pt.type_flag = '2',
                    CONCAT('$productUrl', pt.seo_url),
                    CONCAT('$productUrl', f.seo_url)
                ) as link"),
                DB::raw("'yes' AS identifier_exists"),
                DB::raw("'in_stock' AS availability"),
                DB::raw("ROUND(pt.main_price, 0) as price"),
                DB::raw("IF(pt.type_flag = '2', pt.photo1, fp.aws) as image_link"),
                'br.brand_name as brand',
                'mt.main_cat_name as product_type',
                'ct.cat_name as gender',
            ])
            ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
            ->join(DB::raw('(SELECT sub_cat_id, sub_cat_name, cat_id FROM sub_cat_table) as sct'), 'pt.sub_cat_id', '=', 'sct.sub_cat_id')
            ->join(DB::raw('(SELECT cat_id, cat_name, cat_index, main_cat_id FROM cat_table) as ct'), 'sct.cat_id', '=', 'ct.cat_id')
            ->join(DB::raw('(SELECT main_cat_id, main_cat_name, Old_value FROM main_cat_table) as mt'), 'ct.main_cat_id', '=', 'mt.main_cat_id')
            ->leftJoin(DB::raw('(SELECT family_id, family_name, family_desc, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
            ->leftJoin(DB::raw('(SELECT family_id, family_pic, aws FROM family_pic_tbl) as fp'), 'f.family_id', '=', 'fp.family_id')
            ->join(DB::raw('(SELECT id as brand_id, name as brand_name FROM brand_table WHERE web_status = "1") as br'), 'pt.brand_id', '=', 'br.brand_id')
            ->where('pt.web_status', '=', '1')
            //->whereIn('mt.Old_value', ['FR', 'CM', 'CS', 'LW'])
            ->whereRaw('(FIND_IN_SET(?, pt.in_domain) OR FIND_IN_SET(?, pt.in_domain))', [1, 5])
            ->whereNotIn('pt.brand_id', $brandIds)
            ->whereIn('pt.product_id', function ($query) {
                $query->select('a.product_id')
                    ->from('account_product_table_new as a')
                    ->join('store_table as st', 'a.store_id', '=', 'st.store_id')
                    ->whereIn('a.company', [4, 9])
                    ->whereIn('st.store_id', [28, 13])
                    ->groupBy('a.product_id')
                    ->havingRaw('SUM(a.instock) - SUM(a.outstock) > 0');
            })
            ->get();

        // Path where the CSV will be saved
        $csvFilePath = public_path('feeds/facebook_product_feed.csv');

        // Open the file for writing
        $handle = fopen($csvFilePath, 'w');

        // Add CSV header row
        fputcsv($handle, ['ID', 'Title', 'Line in Feed', 'Description', 'Availability', 'Link', 'Image Link', 'Price', 'Identifier Exists', 'Brand', 'Product Type', 'Gender']);

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
                $product->brand,
                $product->product_type,
                $product->gender
            ]);
            $lineNumber++;
        }

        // Close the file
        fclose($handle);
        Log::info('Product feed generated at ' . now());
        $this->info('Product feed has been generated.');
    }
}

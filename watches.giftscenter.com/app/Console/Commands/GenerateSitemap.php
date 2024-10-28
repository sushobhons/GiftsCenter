<?php

namespace App\Console\Commands;

use DB;
use Helper;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap for the website';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sitemap = Sitemap::create();

        // Add URLs to the sitemap
        $sitemap->add(Url::create('/')->setPriority(1.0)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)->setLastModificationDate(now()));

        $mainUrls = [
            '/find-store',
            '/loves-list',
            '/points-policy',
            '/page/about-us',
            '/page/exchange-policy',
            '/page/privacy-policy',
            '/page/delivery-policy',
            '/page/terms-and-conditions',
            '/request-product',
            '/loyalty-program',
            '/contact-us',
            '/my-account',
            '/gift-vouchers',
            '/your-orders',
            '/my-basket',
            '/checkout',
            '/payment',
            '/thanks'
        ];
        foreach ($mainUrls as $url) {
            $sitemap->add(Url::create($url)->setPriority(0.9)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
        }

        $currentDomain = Helper::getCurrentDomain();
        $domainId = $currentDomain ? $currentDomain->id : 1;

        //brands
        $sitemap->add(Url::create("/brands")->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
        $brands = DB::table('product_table')
            ->select(
                'brand_table.brand_slug as slug',
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
        foreach ($brands as $brand) {
            $sitemap->add(Url::create("/brand/{$brand->slug}")->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
        }

        //categories
        $categories = Helper::getCategories();
        foreach ($categories as $mainCategory) {
            $sitemap->add(Url::create("/category/{$mainCategory['main_cat_slug']}")->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
            foreach ($mainCategory['category'] as $category) {
                $sitemap->add(Url::create("/category/{$mainCategory['main_cat_slug']}/{$category['cat_slug']}")->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
                foreach ($category['subcategory'] as $subCategory) {
                    $sitemap->add(Url::create("/category/{$mainCategory['main_cat_slug']}/{$category['cat_slug']}/{$subCategory['sub_cat_slug']}")->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
                }
            }
        }

        //new-arrivals
        $sitemap->add(Url::create("/new-arrival")->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
        $newArrivals = Helper::getNewCategories();
        foreach ($newArrivals as $newMainCategory) {
            $sitemap->add(Url::create("/new-arrival/{$newMainCategory['main_cat_slug']}")->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
            foreach ($newMainCategory['category'] as $newCategory) {
                $sitemap->add(Url::create("/new-arrival/{$newMainCategory['main_cat_slug']}/{$newCategory['cat_slug']}")->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
            }

        }

        //best-sellers
        $sitemap->add(Url::create("/best-seller")->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
        $bestSellers = Helper::getBestCategories();
        foreach ($bestSellers as $bestMainCategory) {
            $sitemap->add(Url::create("/best-seller/{$bestMainCategory['main_cat_slug']}")->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
            foreach ($bestMainCategory['category'] as $bestCategory) {
                $sitemap->add(Url::create("/best-seller/{$bestMainCategory['main_cat_slug']}/{$bestCategory['cat_slug']}")->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
            }

        }

        //offers
        $offers = Helper::getOffers();
        $numOffers = count($offers);
        if ($numOffers > 1) {
            $sitemap->add(Url::create("/offer")->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
        }
        foreach ($offers as $offer) {
            $sitemap->add(Url::create("/offer/{$offer->offer_slug}")->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
        }


        //products
        $products = DB::table('product_table as pt')
            ->select(DB::raw("IF(pt.type_flag = '2', pt.seo_url, f.seo_url) as slug"))
            ->join(DB::raw('(SELECT qty as stock, product_id FROM stock_table WHERE qty > 0) as st'), 'pt.product_id', '=', 'st.product_id')
            ->leftJoin(DB::raw('(SELECT family_id, family_name, seo_url FROM family_tbl) as f'), 'pt.linepr', '=', 'f.family_id')
            ->where('pt.web_status', '=', '1')
            ->where('pt.main_price', '>', 0)
            ->whereRaw('FIND_IN_SET(?, pt.in_domain)', [$domainId])
            ->whereNotNull('st.stock')->groupBy(DB::raw("IF(pt.type_flag = '2', pt.product_id, pt.linepr)"))
            ->get();
        foreach ($products as $product) {
            $sitemap->add(Url::create("/product/{$product->slug}")->setPriority(0.9)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setLastModificationDate(now()));
        }


        // Write the root domain sitemap to a file
        $sitemapPath = public_path('sitemap.xml');
        $sitemap->writeToFile($sitemapPath);

        // Load the subdomain sitemap using cURL
        $subdomainSitemapUrl = 'http://watches.giftscenter.com/public/sitemap.xml';
        $subdomainSitemapXml = $this->fetchSitemapWithCurl($subdomainSitemapUrl);
        $subdomainSitemap = simplexml_load_string($subdomainSitemapXml);

        // Add subdomain URLs to the root sitemap
        foreach ($subdomainSitemap->url as $url) {
            $sitemap->add(
                Url::create((string)$url->loc)
                    ->setLastModificationDate(now())
                    ->setChangeFrequency((string)$url->changefreq)
                    ->setPriority((float)$url->priority)
            );
        }

        // Write the updated sitemap with subdomain URLs to the file
        $sitemap->writeToFile($sitemapPath);

        $this->info('Sitemap has been generated and merged successfully.');

        return 0;
    }

    /**
     * Fetch sitemap XML using cURL
     *
     * @param string $url
     * @return string
     */
    private function fetchSitemapWithCurl(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}

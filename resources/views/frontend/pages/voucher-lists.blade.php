@extends('frontend.layouts.master')
@section('title','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('description','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('keywords','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/jquery-ui-1.12.1.min.css')}}" rel="stylesheet"/>
    <link href="{{asset('public/css/product-listing.css')}}" rel="stylesheet"/>
    <link href="{{asset('public/assets/glasscase_v.3.0.2/src_prod/css/glasscase.min.css')}}" rel="stylesheet"/>
@endsection
@section('main-content')
    <div class="productListingDiv">
        @if(!empty($pVouchers))
        <h3>Physical Gift Voucher</h3>
        <div class="productLoop">
            <ul class="row justify-content-center product-listing-list" id="products-list">
                @foreach($pVouchers as $product)
                    @php
                        $range_price = (floatval($product->max_price) > floatval($product->min_price)) ? $product->min_price . ' - ' . $product->max_price : '';
                        $price = ($range_price !== "") ? $range_price . ' JD\'s' : $product->main_price . ' JD\'s';

//                        $drange_price = (floatval($product->dmax_price) > floatval($product->dmin_price)) ? $product->dmin_price . ' - ' . $product->dmax_price : '';
//                        $dprice = ($drange_price == "") ? ($product->dmain_price != "") ? $product->dmain_price . ' JD\'s' : '' : $drange_price . ' JD\'s';
//
//                        $price = ($dprice != '') ? '<span class="offer_price">' . $dprice . ' </span><span class="normal_price"> ' . $price . ' </span>' : $price;
                    @endphp
                <li class="col-md-3">
                    <div class="card">
                        <div class="imgHldr">
                            <img src="{{$product->family_pic}}" alt="" />
                        </div>
                        <div class="listing-text-content">
                            <h3>{{$product->brand_name}}</h3>
                            <h4>{{$product->family_name}}</h4>
                            <h5>{!! $price !!}</h5>
                            <a href="{{ url('/product').'/'.$product->seo_url}}" style="display: block;"><div class="img-overlay" style="background:transparent;"></div></a>
                            <div class="overlay-link">
                                <a href="javascript:void(0);"
                                   class="quick-view-link quick-look"
                                   data-product="{{$product->product_id}}"
                                   data-product-type="voucher"
                                   data-product-key="">
                                    QUICK VIEW
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
            @if(!empty($eVouchers))
        <h3>e-Gift Voucher</h3>
        <div class="productLoop">
            <ul class="row justify-content-center product-listing-list" id="products-list">
                @foreach($eVouchers as $product)
                    @php
                        $range_price = (floatval($product->max_price) > floatval($product->min_price)) ? $product->min_price . ' - ' . $product->max_price : '';
                        $price = ($range_price !== "") ? $range_price . ' JD\'s' : $product->main_price . ' JD\'s';

//                        $drange_price = (floatval($product->dmax_price) > floatval($product->dmin_price)) ? $product->dmin_price . ' - ' . $product->dmax_price : '';
//                        $dprice = ($drange_price == "") ? ($product->dmain_price != "") ? $product->dmain_price . ' JD\'s' : '' : $drange_price . ' JD\'s';
//
//                        $price = ($dprice != '') ? '<span class="offer_price">' . $dprice . ' </span><span class="normal_price"> ' . $price . ' </span>' : $price;
                    @endphp
                <li class="col-md-3">
                    <div class="card">
                        <div class="imgHldr">
                            <img src="{{$product->family_pic}}" alt="" />
                        </div>
                        <div class="listing-text-content">
                            <div class="overlay-link">
                                <a class="quick-view-link" href="{{ url('/e-gift-voucher') }}">SELECT DESIGN</a>
                            </div>
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
            @endif
    </div>
    <!-- Quick Look Modal -->
    <div id="quick_look_modal" class="modal fade cmnModal quickViewModal" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="web-hide">Quick View</h5>
                    <button type="button" class="close" data-dismiss="modal"><img
                                src="{{asset('public/img/modal-cross.png')}}"
                                alt=""/></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 quick-detail-img">

                        </div>
                        <div class="col-md-6 right-quick-view-detsils">
                            <span class="rating-span web-hide rating_review_html"></span>
                            <h3 id="brand_name_html"></h3>
                            <h4 id="product_name_html"></h4>
                            <span class="rating-span mobile-hide rating_review_html"></span>
                            <h5><span id="product_discounted_price_html" class="web-price"></span> <span
                                        id="product_price_html" class="old-price"></span> <span class="earn_point_span">Earn <span
                                            id="earn_point"></span> points</span></h5>
                            <ul class="size-list mobile-hide ql_variations_content_html ql_texts_content_html">


                            </ul>
                            <ul class="color-list ql_variations_content_html" id="ql_colours_content_html">

                            </ul>
                            <div class="clearfix"></div>
                            <h6 class="mobile-hide" id="ql_variation_name_html"></h6>
                            <div class="clearfix"></div>
                            <h6 id="product_description_html"></h6>
                            <ul class="qnty-list">
                                <li>
                                    <label>Qty</label>
                                    <div class="quantity-block" id="stock_availability">
                                        <button class="ql-quantity-arrow-minus"></button>
                                        <input class="quantity-num" type="number" value="1" min="1" max="10"
                                               id="ql_product_available_stock"/>
                                        <button class="ql-quantity-arrow-plus"></button>
                                    </div>
                                </li>
                            </ul>
                            <div class="clearfix"></div>
                            <a href="#" class="add-to-basket-btn" id="ql_add_to_cart_btn"><i
                                        class="fa fa-shopping-cart" aria-hidden="true" id="ql_add_to_cart_icon"></i>
                                ADD TO BASKET</a>
                            <a href="#" class="add-loves-btn" id="ql_save_item_btn"><i
                                        class="fa fa-heart-o" aria-hidden="true" id="ql_save_item_icon"></i> ADD TO
                                LOVES</a>
                        </div>
                    </div>
                </div>
                <!-- <div class="modal-footer">
                  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div> -->
            </div>
        </div>
    </div>

    <!-- Added To Cart Modal -->
    <div id="added_to_cart_modal" class="modal fade cmnModal addCartModal" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><img
                                src="{{asset('public/img/modal-cross.png')}}" alt=""/>
                    </button>
                    <h4 class="modal-title">ADDED TO YOUR CART</h4>
                </div>
                <div class="modal-body">
                    <div class="cart-sec">
                        <table class="table">
                            <tr>
                                <td><img src="{{asset('public/img/add-to-cart-modal-img.png')}}" alt=""
                                         id="added_to_cart_image_html"/></td>
                                <td>
                                    <h3 id="added_to_cart_brand_html"></h3>
                                    <p id="added_to_cart_product_html"></p>
                                </td>
                                <td>
                                    <span class="qty-text-span">Qty</span>
                                    <div class="quantity-block">
                                        <div class="quantity-num" id="added_to_cart_quantity_html"></div>
                                    </div>
                                </td>
                                <td><span class="price-spna" id="added_to_cart_price_html"></span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">CONTINUE SHOPPING</button>
                    <a class="btn btn-default" href="{{ route('my-basket') }}">GO TO MY CART</a>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{asset('public/js/lazysizes.min.js')}}"></script>
    <script src="{{asset('public/js/modernizr.custom.js')}}"></script>
    <script src="{{asset('public/assets/glasscase_v.3.0.2/src_dev/js/jquery.glasscase.js')}}"
            type="text/javascript"></script>
    <script src="{{asset('public/js/jquery.flexible.stars.js')}}"
            type="text/javascript"></script>
    <script src="{{asset('public/js/quick.view.js')}}"
            type="text/javascript"></script>
@endpush
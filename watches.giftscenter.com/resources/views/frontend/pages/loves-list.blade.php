@extends('frontend.layouts.master')

@section('title','Loves list | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/order-history.css')}}" rel="stylesheet"/>
    <link href="{{asset('public/assets/glasscase_v.3.0.2/src_prod/css/glasscase.min.css')}}" rel="stylesheet"/>
@endsection
@section('main-content')

    <!-- Shop Login -->
    <div class="container custom-max-container">
        <div class="order-history-white-sec">
            <h3>Wishlist</h3>
            <div class="oder-history-table-sec">
                @if (auth()->check())
                    <table class="saved_tbl table">
                        <tr>
                            <th>Product</th>
                            <th>Name</th>
                            <th>Number</th>
                            <th>Price</th>
                            <th class="text-center">Action</th>
                        </tr>
                        @forelse($products as $product)
                            <tr class="tableRow_{{ $product['wish_id'] }}">
                                <td>
                                    <a href="{{ url('/product').'/'.$product['seo_url'] }}"><img class="product-image"
                                                                                                 src="{{ $product['picture'] }}"
                                                                                                 alt="{{ $product['family_name'] }}"
                                                                                                 title="{{ $product['family_name'] }}"/></a>

                                </td>

                                <td>
                                    <a href="{{ url('/product').'/'.$product['seo_url'] }}">{{ $product['family_name'] }}</a>
                                </td>
                                <td>#{{ $product['product_no'] }}</td>
                                <td>{{ $product['main_price'] }} JD's</td>
                                <td class="text-center">
                                    <a class="add-cart-item" href="#" data-product-id="{{ $product['product_id'] }}"
                                       data-save="Yes" data-wishlist-id="{{ $product['wish_id'] }}"><i
                                                class="fa fa-shopping-cart" style="font-size: 20px;"></i></a>
                                    <a class="remove-wish-item" href="#" data-product-id="{{ $product['product_id'] }}"
                                       data-save="Yes"
                                       data-wishlist-id="{{ $product['wish_id'] }}">
                                        <i class="fa fa-trash-o" style="font-size: 20px;"></i>
                                    </a>

                                </td>
                            </tr>
                            <tr class="tableRow_{{ $product['wish_id'] }}">
                                <td colspan="5" class="blank-td"></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"><b>No items yet</b><br>Simply browse and tap on the heart icon</td>
                            </tr>
                        @endforelse
                    </table>
                @else
                    <table class="saved_tbl table">
                        <tr>
                            <td colspan="5">Sorry! No items yet,please sign in to save items in your loves list.</td>
                        </tr>
                    </table>
                @endif
            </div>
        </div>
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
                            <ul class="size-list web-hide ql_variations_content_html ql_texts_content_html">

                            </ul>
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
    <script>

        $(document).on("click", ".add-cart-item", function (e) {
            e.preventDefault();
            var currentElement = $(this);
            var currentProduct = currentElement.data('product-id');
            quickLook(currentProduct);
        });

        $(document).on("click", ".remove-wish-item", function (e) {
            e.preventDefault();
            var currentElement = $(this);
            var wishlistId = currentElement.data('wishlist-id');
            $.ajax({
                url: baseUrl + "/remove-from-wishlist",
                type: "POST",
                data: {
                    'wishlistId': wishlistId,
                },
                dataType: "json",
                beforeSend: function () {
                    currentElement.find('i').toggleClass('fa-trash-o fa-circle-o-notch fa-spin');
                },
                success: function (response) {
                    if (response.result === true) {
                        $(".tableRow_" + wishlistId).remove();
                        $(".wish_count_html").text(response.data);
                    } else {
                        showMessage(response.message);
                    }
                }
            });
        });
    </script>
@endpush
@extends('frontend.layouts.master')
@section('title','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('description','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('keywords','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/product-details.css')}}" rel="stylesheet"/>
    <link href="{{asset('public/assets/glasscase_v.3.0.2/src_prod/css/glasscase.min.css')}}" rel="stylesheet"/>
@endsection
@push ('styles')

@endpush
@section('main-content')
    <div class="product-details-top">
        <div class="container">
            <div class="row">
                <div class="col-md-6 left-top-detsils gallery-img">

                </div>
                <div class="col-md-6 right-top-detsils">
                    <span class="rating-span web-hide rating_review_html"></span>
                    <h3 id="brand_name_html">{{$brand_name}}</h3>
                    <h4 id="product_name_html">{{$family_name}}</h4>
                    @if (!empty($products_arr))
                        <span class="rating-span mobile-hide rating_review_html"></span>
                        <h5><span id="product_discounted_price_html" class="web-price"></span> <span
                                    id="product_price_html"
                                    class="old-price"></span>
                            <span class="earn_point_span">Earn <span
                                        id="earn_point"></span> points</span></h5>
                        @if (in_array($main_category, ['10', '23']))
                            <ul class="size-list" id="texts_content_html">
                                @forelse ($products_arr as $product)
                                    <li>
                                        <input type="radio" name="text_variation" class="radioInput radio-product-text"
                                               value="{{ $product->product_id }}" data-type="" data-key=""
                                               data-title="{{ $product->fam_name }}"/>
                                        <span class="quantity-text">{{ $product->fam_name }}</span>
                                    </li>
                                @empty
                                    {{-- Handle the case where $products_arr is empty --}}
                                @endforelse
                            </ul>
                        @else
                            <ul class="color-list" id="colours_content_html">
                                @forelse ($products_arr as $product)
                                    <li id="thumb_{{ $product->product_id }}">
                                        @if ($product->has_offer != '')
                                            <span class="prdct_offer_text">offer</span>
                                        @endif
                                        @if ($product->photo1 != '')
                                            <span class="colour-box">
                                                <input type="radio" name="colour_variation" class="radio-product-colour"
                                                       value="{{ $product->product_id }}"
                                                       data-type=""
                                                       data-key=""
                                                       data-title="{{ $product->fam_name }}"/>
                                                <span class="checkmark">
                                                    <img src="{{ $product->photo1 }}" alt="{{ $product->fam_name }}"
                                                         title="{{ $product->fam_name }}"/>
                                                </span>
                                            </span>
                                        @endif
                                    </li>
                                @empty
                                    {{-- Handle the case where $products_arr is empty --}}
                                @endforelse
                            </ul>
                            <div class="clearfix"></div>
                            <h6 class="variation_name_html"></h6>
                        @endif
                        <ul class="qnty-list">
                            <li>
                                <label>Qty</label>
                                <div class="quantity-block" id="stock_availability">
                                    <button class="quantity-arrow-minus"></button>
                                    <input class="quantity-num" type="number" value="1" min="1" max="10"
                                           id="product_available_stock"/>
                                    <button class="quantity-arrow-plus"></button>
                                </div>
                            </li>
                        </ul>
                        <div class="clearfix"></div>
                        <a href="javascript:void(0);" class="add-to-basket-btn" id="add_to_cart_btn"
                           onclick="addToCart();"><i
                                    class="fa fa-shopping-cart" aria-hidden="true" id="add_to_cart_icon"></i>
                            ADD TO BASKET</a>
                        <a href="javascript:void(0);" class="add-loves-btn" id="save_item_btn" onclick="saveItem();"><i
                                    class="fa fa-heart-o" aria-hidden="true" id="save_item_icon"></i> ADD TO
                            LOVES</a>
                        <a href="javascript:void(0);" class="share-btn" style="position: relative;"><i
                                    class="fa fa-share-square-o" aria-hidden="true"></i> Share
                            <div class="dropdown-content"
                                 style="position: absolute; top: 65px; left: 20px; display: none;">
                                <div class="sharethis-inline-share-buttons"></div>
                            </div>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="product-details-bottom-sec">
        <div class="container">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button
                            class="nav-link active"
                            id="product-tab"
                            data-toggle="tab"
                            data-target="#productsDes"
                            type="button"
                            role="tab"
                            aria-controls="productsDes"
                            aria-selected="true"
                    >
                        Product Description
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                            class="nav-link"
                            id="rate-tab"
                            data-toggle="tab"
                            data-target="#ratesDes"
                            type="button"
                            role="tab"
                            aria-controls="ratesDes"
                            aria-selected="false"
                    >
                        Rate & Review
                    </button>
                </li>
            </ul>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="productsDes" role="tabpanel" aria-labelledby="product-tab">
                    <!--  <h3>Product Description</h3>-->
                    <p>{!! stripslashes($family_desc) !!}</p>

                </div>
                <div
                        class="tab-pane fade"
                        id="ratesDes"
                        role="tabpanel"
                        aria-labelledby="rate-tab"
                >
                    <div class="row">
                        <div class="col-md-8 left-rate-sec">
                            {{--                            <a href="#" class="short-rate-filter web-hide"><img src="img/sort-mobile-icon.svg" alt=""/>--}}
                            {{--                                Sort</a>--}}
                            <h3>Reviews</h3>
                            {{--                            <ul class="sort-list mobile-hide">--}}
                            {{--                                <li>--}}
                            {{--                                    <label>Sort:</label>--}}
                            {{--                                    <select class="sort-select">--}}
                            {{--                                        <option>Recommended</option>--}}
                            {{--                                    </select>--}}
                            {{--                                </li>--}}
                            {{--                            </ul>--}}
                            <ul class="rate-list" id="reviews_html">

                            </ul>
                            {{--                            <p class="read-more-para"><a href="#" class="read-more-link">Read More</a></p>--}}
                        </div>
                        <div class="col-md-4 right-rate-sec">
                            <h3>Rating Overview</h3>
                            <div class="rating-box">
                                <h4 id="ratingHeader"></h4>
                                <ul class="rating-list">
                                    <li>
                                        <span class="num-span">5</span>
                                        <img src="{{asset('public/img/single-star-img.png')}}" alt=""/>
                                        <div class="prog-div">
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar" aria-valuenow=""
                                                     aria-valuemin="0" aria-valuemax="100" style="">
                                                </div>
                                            </div>
                                        </div>
                                        <span class="customer-span"></span>
                                    </li>
                                    <li>
                                        <span class="num-span">4</span>
                                        <img src="{{asset('public/img/single-star-img.png')}}" alt=""/>
                                        <div class="prog-div">
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar" aria-valuenow=""
                                                     aria-valuemin="0" aria-valuemax="100" style="">
                                                </div>
                                            </div>
                                        </div>
                                        <span class="customer-span"></span>
                                    </li>
                                    <li>
                                        <span class="num-span">3</span>
                                        <img src="{{asset('public/img/single-star-img.png')}}" alt=""/>
                                        <div class="prog-div">
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar" aria-valuenow=""
                                                     aria-valuemin="0" aria-valuemax="100" style="">
                                                </div>
                                            </div>
                                        </div>
                                        <span class="customer-span"></span>
                                    </li>
                                    <li>
                                        <span class="num-span">2</span>
                                        <img src="{{asset('public/img/single-star-img.png')}}" alt=""/>
                                        <div class="prog-div">
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar" aria-valuenow=""
                                                     aria-valuemin="0" aria-valuemax="100" style="">
                                                </div>
                                            </div>
                                        </div>
                                        <span class="customer-span"></span>
                                    </li>
                                    <li>
                                        <span class="num-span">1</span>
                                        <img src="{{asset('public/img/single-star-img.png')}}" alt=""/>
                                        <div class="prog-div">
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar" aria-valuenow=""
                                                     aria-valuemin="0" aria-valuemax="100" style="">
                                                </div>
                                            </div>
                                        </div>
                                        <span class="customer-span"></span>
                                    </li>
                                </ul>
                                <div class="clearfix"></div>
                                <a href="#" class="review-btn" id="write_review_btn">Rate and Review</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="relatedDiv">
        <h3 class="hdn">You may also like</h3>
    </div>
    <div class="relatedSlider related-products-sec">
        <div class="owl-carousel owl-theme related-carousel" id="related-carousel">

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

    <!-- Add Review Modal -->
    <div id="add_review_modal" class="modal fade cmnModal ReviewModal" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><img
                                src="{{asset('public/img/modal-cross.png')}}" alt=""/>
                    </button>
                    <h4 class="modal-title">Add Product Review?</h4>
                </div>
                <div class="modal-body">
                    <div class="redeem-small-box">
                        <ul class="promo-list">
                            <li>
                                <input class="form-control" type="text" placeholder="Title"
                                       aria-label="Title" name="review_title" id="review_title"/>
                            </li>
                            <li>
                <textarea class="form-control textarea-form" name="review_description" cols="" rows="10"
                          id="review_description" placeholder="Description"
                          aria-label="Description"></textarea>
                            </li>
                            <li>
                                <ul class="rating-list">
                                    <li><span class="text-span">Not Good</span></li>
                                    @for ($i = 1; $i <= 5; $i++)
                                        <li>
                                            <span class="num-span">{{ $i }}</span>
                                            <div class="form-group form-check form-radio-check">
                                                <label class="form-check-label">
                                                    <input class="form-radio-input" type="radio" name="review_rating"
                                                           value="{{ $i }}" {{ $i == 5 ? 'checked' : '' }}>
                                                    <span class="checkmark"></span>
                                                </label>
                                            </div>
                                        </li>
                                    @endfor
                                    <li><span class="text-span">Great</span></li>
                                </ul>
                                <ul class="excellent-list web-hide">
                                    <li>Not Good</li>
                                    <li>Great</li>
                                </ul>
                            </li>
                            <li>
                                <a class="btn btn-primary" href="javascript:void(0);" onclick="addReview()"
                                   id="submit-review-btn">Submit</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @php
        $firstKey = !empty($products_arr) ? collect($products_arr)->first()->product_id : 0;
    @endphp
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
    <script type='text/javascript'
            src='//platform-api.sharethis.com/js/sharethis.js#property=5ca5b4dafbd80b0011b66725&product=inline-share-buttons'
            async='async'></script>

    <script>
        let autoplay_owlcarousel = $(window).width() > 1199;
        var firstKey = {{ $firstKey }};
        var productArray = '@json($products_arr, JSON_HEX_APOS)';
        var productsObject = JSON.parse(productArray);
        var productType = "";
        var productKey = "";
        var product_filters = {};
        var variantId = getUrlParameter('variant');

        $(document).ready(function () {
//fetchProducts(product_filters);
            buildProductContent(variantId);

            fetchLikeProducts();

            $(".share-btn").click(function () {
                $(".dropdown-content").toggle();
                $(".st-btn").toggle();
            });

// Increase quantity
            $('.quantity-arrow-plus').click(function () {
                var inputField = $(this).siblings('.quantity-num');
                var currentValue = parseInt(inputField.val());

                if (currentValue < parseInt(inputField.attr('max'))) {
                    inputField.val(currentValue + 1);
                }
            });

// Decrease quantity
            $('.quantity-arrow-minus').click(function () {
                var inputField = $(this).siblings('.quantity-num');
                var currentValue = parseInt(inputField.val());

                if (currentValue > parseInt(inputField.attr('min'))) {
                    inputField.val(currentValue - 1);
                }
            });


        });

        const relatedItemTemplate = (value) => {
            let rangePrice = '';
            let price = '';
            let dRangePrice = '';
            let dPrice = '';
            let productUrl = '';

            productUrl = redirectUrl + '/product/' + value.seo_url;

            if (parseFloat(value.max_price) > parseFloat(value.min_price)) {
                rangePrice = `${value.min_price} - ${value.max_price}`;
            }

            if (rangePrice !== "") {
                price = `${rangePrice} JD's`;
            } else {
                price = `${value.main_price} JD's`;
            }

            if (parseFloat(value.dmax_price) > parseFloat(value.dmin_price)) {
                dRangePrice = `${value.dmin_price} - ${value.dmax_price}`;
            }

            if (dRangePrice === "") {
                if (value.dmain_price !== "") {
                    dPrice = `${value.dmain_price} JD's`;
                }
            } else {
                dPrice = `${dRangePrice} JD's`;
            }

// if (value.offer_name !== "" && dPrice === "") {
//     dPrice = value.offer_name;
// }

            if (dPrice !== '') {
                price = `<span class="offer_price">${dPrice}</span><span class="normal_price"> ${price}</span>`;
            }

            return `
    <div class="item">
        <div class="card">
            <div class="img-sec">
                <img src="${value.family_pic}" alt="">
            </div>
            <div class="text-sec">
                <h3>${value.brand_name}</h3>
                <h4>${value.family_name}</h4>
                <h5>${price}</h5>
                <h4>${value.offer_name}</h4>
            </div>
                <a href="${productUrl}" >
                    <div class="img-overlay" style="background: transparent;"></div>
                </a>
                <div class="overlay-link">
                    <a href="javascript:void(0);" class="quick-view-link quick-look" data-product="${value.product_id}" data-product-type="" data-product-key="">
                        Quick View
                    </a>
                </div>
        </div>
    </div>
`;
        };

        function fetchLikeProducts() {
            $.ajax({
                url: "{{ url('fetch-related-products') }}",
                type: "POST",
                data: {'product': firstKey},
                dataType: "json",
                beforeSend: function () {
                },
                success: function (res) {
                    let relatedHtml = "";
                    const items = res.result;
                    if (items.length > 0) {
                        items.forEach(function (item) {
                            relatedHtml += relatedItemTemplate(item);
                        });
                        //$.each(product_obj, function(i, v) {
                        // $.each(lproduct_obj, function (pi, pv) {
                        //     var range_price = parseFloat(pv.max_price) > parseFloat(pv.min_price) ? pv.min_price + ' - ' + pv.max_price : '';
                        //     var price = range_price !== "" ? range_price : pv.main_price;
                        //     phtml += '<div class="item">';
                        //     phtml += '<div class="sliderBox">';
                        //     phtml += '<div class="imgHldr"> <a href="' + redirectUrl + '/product/' + pv.seo_url + '">';
                        //     phtml += '<img class="" src="' + pv.family_pic + '" alt="' + pv.family_name + '" title="' + pv.family_name + '" />';
                        //     phtml += '</a> </div>';
                        //     phtml += '<h2>' + pv.brand_name + '</h2>';
                        //     phtml += '<h3><a href="' + redirectUrl + '/product/' + pv.seo_url + '">' + pv.family_name + '</a></h3>';
                        //     phtml += '<h4>' + price + ' JD\'s</h4>';
                        //     phtml += '</div>';
                        //     phtml += '</div>';
                        //
                        // });
                        //});
                    }
                    $("#related-carousel").html(relatedHtml);
                    //initiate star ratings
                    //$('.like-list-stars').flexibleStars();
                    // var related_items_carousel = $("#related-carousel");
                    // related_items_carousel.owlCarousel({
                    //     margin: 30,
                    //     nav: true,
                    //     loop: true,
                    //     autoplay: true,
                    //     autoplayTimeout: 1000,
                    //     autoplayHoverPause: true,
                    //     dots: false,
                    //     responsive: {
                    //         0: {
                    //             items: 1
                    //         },
                    //         600: {
                    //             items: 3
                    //         },
                    //         1000: {
                    //             items: 4
                    //         }
                    //     }
                    // });

                    $("#related-carousel").owlCarousel({
                        lazyLoad: true,
                        lazyLoadEager: 2,
                        autoplay: autoplay_owlcarousel,
                        autoplayTimeout: 5000,
                        autoplayHoverPause: true,
                        loop: false,
                        margin: 30,
                        nav: true,
                        navText: ['<img src="{{asset('public/img/prev-arrow.png')}}" alt=""/>', '<img src="{{asset('public/img/next-arrow.png')}}" alt=""/>'],
                        dots: false,
                        items: 6,
                        responsive: {
                            0: {
                                items: 2,
                                margin: 10,
                            },
                            640: {
                                items: 2,
                                margin: 10,
                            },
                            768: {
                                items: 3,
                            },
                            992: {
                                items: 3
                            },
                            1200: {
                                items: 5
                            }
                        }
                    });

                }
            });
        }

        var productsXhr;

        function fetchProducts(product_filters) {
            productsXhr && productsXhr.readyState != 4 && productsXhr.abort(); // clear previous request

            var key_type = product_filters.key_type !== null ? product_filters.key_type : '';
            var key_word = product_filters.key_word !== null ? product_filters.key_word : '0';
            var prdct_type = product_filters.prdct_type !== null ? product_filters.prdct_type : '1';
            var bfilter = product_filters.bfilter !== null ? product_filters.bfilter : '';
            var cfilter = product_filters.cfilter !== null ? product_filters.cfilter : '';
            var sgfilter = product_filters.sgfilter !== null ? product_filters.sgfilter : '';
            var spfilter = product_filters.spfilter !== null ? product_filters.spfilter : '';
            var ftfilter = product_filters.ftfilter !== null ? product_filters.ftfilter : '';
            var sfilter = product_filters.sfilter !== null ? product_filters.sfilter : '';
            var filter_type = product_filters.filter_type !== null ? product_filters.filter_type : '';
            var filter_word = product_filters.filter_word !== null ? product_filters.filter_word : '0';
            var sort = product_filters.sort !== null ? product_filters.sort : '';
            var max_price = product_filters.max_price !== null ? product_filters.max_price : '';
            var min_price = product_filters.min_price !== null ? product_filters.min_price : '';
            var mb_prdct_start = product_filters.mb_prdct_start;
            var mb_prdct_limit = product_filters.mb_prdct_limit;
            var mb_prdct_total_count = product_filters.mb_prdct_total_count;

            productsXhr = $.ajax({
                url: "{{ url('products-filter') }}",
                type: "POST",
                data: {
                    'key_type': key_type,
                    'key_word': key_word,
                    'prdct_type': prdct_type,
                    'sort': sort,
                    'filter_type': filter_type,
                    'filter_word': filter_word,
                    'max_price': max_price,
                    'min_price': min_price,
                    'bfilter': bfilter,
                    'cfilter': cfilter,
                    'sgfilter': sgfilter,
                    'spfilter': spfilter,
                    'ftfilter': ftfilter,
                    'sfilter': sfilter,
                    'start': mb_prdct_start,
                    'limit': mb_prdct_limit
                },
                dataType: "json",
                beforeSend: function () {
                    if (product_filters.mb_prdct_start == 0) {
                        $("#products-list").html('');
                    }
                    $(".loader").show();
                    $('.loadBtn').hide();
                },
                success: function (response) {
                    $("#total_products_num").html(response.total + ' Products');
                    $(".loader").hide();
                    var filtered_count = Number(response.filter);
                    product_filters.mb_prdct_total_count = Number(response.total);
                    if (product_filters.mb_prdct_total_count > 0) {
                        $("#products-list").show();
                        var phtml = '';
                        var product_detail_url;
                        var range_price;
                        var price;
                        var drange_price;
                        var dprice;
                        var prdct_type;
                        var data_prdct_type;
                        var data_prdct_key;
                        var label;

                        $.each(response.result, function (pi, pv) {
                            switch (key_type) {
                                case'sale':
                                case'collection':
                                    prdct_type = key_type;
                                    data_prdct_type = key_type;
                                    data_prdct_key = key_word;
                                    break;
                                case'segment':
                                    prdct_type = key_word;
                                    data_prdct_type = key_type;
                                    data_prdct_key = key_word;
                                    break;
                                case'offer':
                                    prdct_type = key_type + '/' + key_word;
                                    data_prdct_type = key_type;
                                    data_prdct_key = key_word;
                                    break;
                                default:
                                    prdct_type = pv.segment_slug != '' ? pv.segment_slug : '';
                                    if (pv.segment_type != '' && pv.segment_slug != '') {
                                        data_prdct_type = pv.segment_type;
                                        data_prdct_key = pv.segment_slug;
                                    } else {
                                        data_prdct_type = '';
                                        data_prdct_key = '';
                                    }
                                    break;

                            }

                            //product_detail_url = prdct_type != '' ? redirect_url + 'product/' + pv.seo_url + '/' + prdct_type : redirect_url + 'product/' + pv.seo_url;
                            product_detail_url = redirectUrl + '/product/' + pv.seo_url;
                            range_price = parseFloat(pv.max_price) > parseFloat(pv.min_price) ? pv.min_price + ' - ' + pv.max_price : '';
                            price = range_price !== "" ? range_price + ' JD\'s' : pv.main_price + ' JD\'s';
                            drange_price = parseFloat(pv.dmax_price) > parseFloat(pv.dmin_price) ? pv.dmin_price + ' - ' + pv.dmax_price : '';
                            dprice = drange_price == "" ? pv.dmain_price != "" ? pv.offer_name + ' ' + pv.dmain_price + ' JD\'s' : '' : pv.offer_name + ' ' + drange_price + ' JD\'s';
                            if (pv.offer_name != '' && dprice == '') {
                                dprice = pv.offer_name;
                            }
                            price = dprice != '' ? '<span class="normal_price">' + price + ' </span><br><span class="offer_price"> ' + dprice + ' </span>' : '<span class="offer_price">' + price + ' </span>';
                            phtml = '';
                            phtml += '<li class="col-md-3">';
                            phtml += '<div class="card">';
                            phtml += '<div class="imgHldr">';
                            phtml += '<img src="' + pv.family_pic + '" alt="' + pv.family_name + '"/>';
                            phtml += '</div>';
                            phtml += '<div class="listing-text-content">';
                            if (pv.segment != '') {
                                phtml += '<div class="newArrival">' + pv.segment + '</div>';
                            } else if (pv.has_offer != '') {
                                phtml += '';
                            } else if (pv.has_gift != '0') {
                                phtml += '<div class="newArrival">gift</div>';
                            } else {
                                phtml += '';
                            }
                            phtml += '<h3>' + pv.brand_name + '</h3>';
                            phtml += '<h4>' + pv.family_name + '</h4>';
                            phtml += '<h5>' + price + '</h5>';
                            phtml += '<a href="' + product_detail_url + '" style="display: block;">';
                            phtml += '<div class="img-overlay lazyload" style="background-image: url(' + pv.family_pic + ')"></div>';
                            phtml += '</a>';
                            phtml += '<div class="overlay-link">';
                            phtml += '<a href="javascript:void(0);" class="quick-view-link quick-look" data-product="' + pv.product_id + '" data-product-type="" data-product-key="' + data_prdct_key + '"">QUICK VIEW</a>';
                            phtml += '</div>';
                            phtml += '</div>';
                            phtml += '</div>';
                            phtml += '</li>';
                            //console.log(phtml);
                            $("#products-list").append(phtml);
                        });

                        //initiate star ratings
                        //$('.list-stars').flexibleStars();
                        product_filters.mb_prdct_start = product_filters.mb_prdct_start + filtered_count;
                        if (product_filters.mb_prdct_start == product_filters.mb_prdct_total_count) {
                            $('.loadBtn').hide();
                        } else {
                            $('.loadBtn').show();
                        }
                    }
                }
            });
            lazySizes.init();
        }

        $(document).on("click", "#loadMoreBtn", function (e) {
            e.preventDefault();
            $('.loadBtn').hide();
            fetchProducts(product_filters);

        });

        // quick look
        $(document).on("click", ".quick-look", function (e) {
            e.preventDefault();
            var current_element = $(this);
            var currentProduct = $(this).data('product');
            var current_prdct_type = $(this).data('product-type');
            var current_prdct_key = $(this).data('product-key');

            current_element.fadeOut('slow');
            setTimeout(function () {
//current_element.empty().html('Quick Look');
                current_element.fadeIn('slow');
            }, 1000);
            quickLook(currentProduct, current_prdct_type, current_prdct_key);
        });

        function quickLook(productId, productType, productKey) {

            var productType = productType !== null ? productType : '';
            var productKey = productKey !== null ? productKey : '';
            $.ajax({
                url: "{{ url('products-detail') }}",
                type: "POST",
                data: {
                    'product': productId,
                    'productType': productType,
                    'productKey': productKey
                },
                dataType: "json",
                success: function (res) {
                    $("#gifts_div").hide('slow');
                    productsObject = res.product_arr;
                    image_arr = res.image_arr;
                    // product name
                    $('#product_name_html').text(res.family_name);
                    var pdcontent = res.family_desc + '<a id="prdct_view_more" href="" target="_top">View More</a>';
                    $("#prdct_desc").html(pdcontent);
                    // brand
                    $('#brand_name_html').html(res.brand_name);
                    addedToCartProductBrandName = res.brand_name;

                    //products section
                    var colour_variation_html = "", text_variation_html = "", has_gift = "";
                    var main_cat_arr = ["10", "23"];
                    var checked_html = '';
                    $.each(productsObject, function (pidx, pobj) {
                        //if (pobj.stock > 0) {
                        has_gift = pobj.has_gift != '0' ? 'has_gift' : '';
                        text_variation_html += '<li><input type="radio" name="text_variation" class="radioInput radio-product-text" value="' + pobj.product_id + '" data-type="' + productType + '" data-key="' + productKey + '" /><span class="quantity-text">' + pobj.fam_name + '</span></li>';
                        if (main_cat_arr.indexOf(res.main_category) == -1) {
                            colour_variation_html += '<li class="thumb ' + has_gift + '" id="thumb_' + pidx + '">';
                            if (pobj.has_offer != '') {
                                //pcontent += '<span class="prdct_offer_text">offer</span>';
                            }
                            if (pobj.photo1 != "") {
                                colour_variation_html += '<span class="colour-box"><input type="radio" name="colour_variation" class="radio-product-colour" value="' + pobj.product_id + '" data-type="' + productType + '" data-key="' + productKey + '"  data-title="' + pobj.fam_name + '"/>';
                                colour_variation_html += '<span class="checkmark"><img src="' + pobj.photo1 + '" alt="" /></span>';
                                colour_variation_html += '</span>';
                            }
                            colour_variation_html += '</li>';
                        }
                        //}
                    });

                    if (main_cat_arr.includes(res.main_category)) {
                        $("#texts_content_html").html(text_variation_html);
                    } else {
                        $("#colours_content_html").html(colour_variation_html);
                    }

                    buildProductContent(Object.keys(productsObject)[0], productType, productKey);
                }
            });

//$("#messageModal .modal-body h5").html(msg);
        }

        // combo select
        $(document).on("change", ".radio-product-text", function () {
            var checkedItem = $('input[name="text_variation"]:checked');
            var pid = checkedItem.val();
            var ptype = checkedItem.data('type');
            var pkey = checkedItem.data('key');
            var ptitle = checkedItem.data('title');
            $(".variation_name_html").html(ptitle);
            if (pid === "") {
                showMessage("Please select a product.");
                buildProductContent();
            } else {
                buildProductContent(pid, ptype, pkey);
                // Modify the URL to include the selected product ID as the variant
                var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?variant=" + pid;

                // Update the URL without reloading the page
                window.history.pushState({path: newUrl}, '', newUrl);
            }

        });

        // combo select
        $(document).on("change", ".radio-product-colour", function () {
            var checkedItem = $('input[name="colour_variation"]:checked');
            var pid = checkedItem.val();
            var ptype = checkedItem.data('type');
            var pkey = checkedItem.data('key');
            var ptitle = checkedItem.data('title');
            $(".variation_name_html").html(ptitle);
            if (pid === "") {
                showMessage("Please select a product.");
                buildProductContent();
            } else {
                buildProductContent(pid, ptype, pkey);
                // Modify the URL to include the selected product ID as the variant
                var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?variant=" + pid;

                // Update the URL without reloading the page
                window.history.pushState({path: newUrl}, '', newUrl);
            }

        });

        function fetchProductImages(productId, productType) {
            $.ajax({
                url: "{{ url('product-images') }}",
                type: "POST",
                data: {
                    'product': productId,
                    'product_type': productType
                },
                dataType: "json",
                beforeSend: function () {
                    //show modal overlay loader
                    $('.quick_look_loader').css("display", "block");
                },
                success: function (res) {
//                var htm = '';
//                if (res.length > 0) {
//                    $.each(res, function (i, v) {
//                        htm += '<li>';
//                        htm += '<img class="etalage_thumb_image img-responsive" src="' + v + '" alt="" width="92" height="81" id="bigpic"/> <img class="etalage_source_image img-responsive" src="' + v + '" alt="" />';
//                        htm += '</li>';
//                    });
//                } else {
//                    htm += '<li>';
//                    htm += '<img class="etalage_thumb_image img-responsive" src="' + base_url + 'images/no-img-available.jpg" alt="" width="92" height="81" id="bigpic"/> <img class="etalage_source_image img-responsive" src="' + base_url + 'images/no-img-available.jpg" alt="" />';
//                    htm += '</li>';
//                }
//
//
//                $('#etalage').html(htm);
//                $('#etalage').etalage({
//                    thumb_image_width: 250,
//                    thumb_image_height: 300,
//                    source_image_width: 900,
//                    source_image_height: 1200,
//                    show_hint: true,
//                    autoplay: false,
//                    click_callback: function (image_anchor, instance_id) {
//                        alert('Callback example:\nYou clicked on an image with the anchor: "' + image_anchor + '"\n(in Etalage instance: "' + instance_id + '")');
//                    }
//                });
                    addedToCartProductImage = "{{ url('/') }}images/no-img-available.jpg";
                    var file_extnt;
                    var htm = '<ul class="gc-start gnr3">';
                    if (res.length > 0) {
                        $.each(res, function (i, v) {
                            switch (v.item_type) {
                                case 'youtube':
                                    htm += '<li>';
                                    htm += '<a data-gc-type="iframe" href="https://www.youtube.com/embed/' + v.product_pic + '" data-gc-width="640" data-gc-height="390" data-gc-thumbnail="https://img.youtube.com/vi/' + v.product_pic + '/0.jpg"></a>';
                                    htm += '</li>';
                                    break;
                                case 'video':
                                    htm += '<li>';
                                    htm += '<a data-gc-type="video" href="https://ikasco.com/moreproductpic/' + v.product_pic + '" data-gc-thumbnail="https://ikasco.com/moreproductpic/' + v.thumb_pic + '"></a>';
                                    htm += '</li>';
                                    break;
                                default:
                                    htm += '<li>';
                                    //  htm += '<img class="img-responsive" src="https://ikasco.com/moreproductpic/' + v.product_pic + '" alt="" />';
                                    htm += '<img class="img-responsive" src="' + v.aws + '" alt="" />';
                                    htm += '</li>';
                                    if (i === 0) {
                                        addedToCartProductImage = v.aws;
                                    }
                                    break;
                            }

                        });
                    } else {
                        htm += '<li>';
                        htm += '<img class="img-responsive" src="{{ url('/') }}images/no-img-available.jpg" alt="" />';
                        htm += '</li>';
                    }
                    htm += '</ul>';

                    $('.gallery-img').html(htm);
                    $('.gallery-img ul').glassCase({
                        'widthDisplay': 690,
                        'heightDisplay': 590,
                        'isSlowZoom': true,
                        'isSlowLens': true,
                        'isHoverShowThumbs': false,
                        'nrThumbsPerRow': 5,
                        'thumbsPosition': 'bottom',
                        'isOverlayFullImage': false,
                        'txtImgThumbVideo': ''
                    });
                    //hide modal overlay loader
                    $('.quick_look_loader').css("display", "none");
                }
            });

        }

        function buildProductContent(product) {
            var product = (typeof product !== 'undefined') ? product : 0;
// add to cart and save item buttons
            $('#add_to_cart_icon').removeClass('fa-check').addClass('fa-shopping-cart');
            $('#save_item_icon').removeClass('fa-check').addClass('fa-heart');

            var currentProduct = '';
            if (product == undefined || product == "" || product == null) {
//first key of object
                currentProduct = Object.keys(productsObject)[0];

            } else {
                currentProduct = product;
            }

//active thumb
//$(".thumb").removeClass("active");
//$("#thumb_" + currentProduct).addClass("active");

// selected combo
//$("#combo").val(currentProduct);
            console.log(currentProduct + ' selected');
            $('input.radio-product-colour[value="' + currentProduct + '"]').prop('checked', true);
            $('input.radio-product-text[value="' + currentProduct + '"]').prop('checked', true);
            $(".variation_name_html").html(productsObject[currentProduct].fam_name);

//$('#prdctNumber').text(productsObject[currentProduct].title);
            if (productsObject[currentProduct].dmain_price != '') {
                $('#product_price_html').text(productsObject[currentProduct].main_price + ' JD\'s');
                $('#product_discounted_price_html').text(productsObject[currentProduct].dmain_price + ' JD\'s');
            } else {
                $('#product_price_html').html('');
                $('#product_discounted_price_html').text(productsObject[currentProduct].main_price + ' JD\'s');
            }

            if (productsObject[currentProduct].lylty_pnts > 0) {
                $('.earn_point_span').show();
                $('#earn_point').text(productsObject[currentProduct].lylty_pnts);
            } else {
                $('.earn_point_span').hide();
            }

            if (productsObject[currentProduct].is_voucher == '1') {
                productType = 'voucher';
            }
            $('#add_to_cart_btn').attr('data-product', currentProduct);
            $('#add_to_cart_btn').attr('data-product-type', productType);
            $('#add_to_cart_btn').attr('data-product-key', productKey);
            $('#add_to_cart_btn').attr('data-title', productsObject[currentProduct].title + productsObject[currentProduct].fam_name);
            $('#add_to_cart_btn').attr('data-price', productsObject[currentProduct].main_price);
            $('#save_item_btn').attr('data-product', currentProduct);
            $('#save_item_btn').attr('data-product-type', productType);
            $('#share_item_btn').attr('data-url', redirectUrl + 'product/' + productsObject[currentProduct].seo_url);
            $('#share_item_btn').attr('data-url', redirectUrl + 'product/' + productsObject[currentProduct].seo_url);
            $('#share_item_btn').attr('data-title', productsObject[currentProduct].title);
            $('#stockStatus').text('');
            $('#write_review_btn').attr('data-product', currentProduct);
            fbq('track', 'ViewContent', {
                content_name: productsObject[currentProduct].title,
                content_ids: [currentProduct],
                content_type: 'product',
                value: productsObject[currentProduct].main_price,
                currency: 'JOD'
            });

            var rhtml = '';
            if (productsObject[currentProduct].count_rating > 0) {
                rhtml = '<div class="flexible-stars single-product-stars" data-gold="sprite-gold-star" data-silver="sprite-silver-star" data-init="' + productsObject[currentProduct].rating + '" data-isLocked="yes"></div>';
                var review = productsObject[currentProduct].count_rating > 0 ? productsObject[currentProduct].count_rating + ' reviews' : 'No reviews yet';
                rhtml += '<span class="text-span">(' + review + ')</span>';
//rhtml += ' | <span class="LoveNo"><img src="' + redirectUrl + 'images/save2.svg" id="save" data-toggle="tooltip" data-placement="bottom" title="Loves List"> <span id="quick_look_love">' + productsObject[currentProduct].love + '</span> Loves</span> </div>';
            }

            $('.rating_review_html').html(rhtml);

//initiate star ratings
            $('.single-product-stars').flexibleStars();

            if (productsObject[currentProduct].stock && productsObject[currentProduct].stock > 0) {
                $("#product_available_stock").attr('max', productsObject[currentProduct].stock);
                $("#add_to_cart_btn").show('slow');      // show add to cart button if product in stock
            } else {
                $('#stock_availability').text('Out of Stock');
                $("#add_to_cart_btn").hide('slow');      // hide add to cart button if product out of stock
            }

//reviews
            var rhtml = '';
            if (productsObject[currentProduct].reviews.length > 0) {
                $.each(productsObject[currentProduct].reviews, function (idx, obj) {
                    rhtml += '<li>';
                    rhtml += '<span class="rating-span"><div class="flexible-stars review-stars" data-gold="sprite-gold-star" data-silver="sprite-silver-star" data-init="' + obj.rating + '" data-isLocked="yes"></div> <strong>' + obj.customer + '</strong> <span class="text-span">(' + obj.added_date_ago + ')</span></span>';
                    rhtml += '<h4>' + obj.title + '</h4>';
                    rhtml += '<p>' + obj.description + '</p>';
                    rhtml += '</li>';
                });
            } else {
                rhtml += '<li>';
                rhtml += '<h4>No reviews yet</h4>';
                rhtml += '</li>';
            }
            $('.review-stars').flexibleStars();
            $("#reviews_html").html(rhtml);

// Initialize an array to store the count for each rating
            var ratingCounts = {5: 0, 4: 0, 3: 0, 2: 0, 1: 0};

// Loop through the reviews array to count ratings
            for (var i = 0; i < productsObject[currentProduct].reviews.length; i++) {
                var rating = productsObject[currentProduct].reviews[i].rating;
                ratingCounts[rating]++;
            }
// Update the header with the total ratings
            var totalRatings = ratingCounts[5] + ratingCounts[4] + ratingCounts[3] + ratingCounts[2] + ratingCounts[1];

            $('#ratingHeader').text('Customer Ratings (' + totalRatings + '/' + productsObject[currentProduct].reviews.length + ')');

// Loop through the ratings and update corresponding elements
            for (var i = 5; i >= 1; i--) {
                var numSpan = $('.num-span').eq(5 - i); // Get the corresponding num-span element
                var progressBar = $('.progress-bar').eq(5 - i); // Get the corresponding progress-bar element
                var customerSpan = $('.customer-span').eq(5 - i); // Get the corresponding customer-span element

// Update num-span with the rating value
                numSpan.text(i);

// Update progress-bar width based on the count
                var percentage = totalRatings > 0 ? (ratingCounts[i] / totalRatings) * 100 : 0;
                progressBar.css('width', percentage + '%');

// Update customer-span with the count
                customerSpan.text(ratingCounts[i]);
            }


//offers section
            var ocontent = "";
            if (productsObject[currentProduct].offers != undefined && productsObject[currentProduct].stock > 0) {
                if (productsObject[currentProduct].offers.length > 0) {
                    $.each(productsObject[currentProduct].offers, function (idx, obj) {
                        ocontent += '<div class="offer">';
                        ocontent += '<input type="radio" name="selected-offer" value="' + obj.offer_id + '" class="choose-offers" data-product= "' + currentProduct + '" data-mintype= "' + obj.minimum_type + '" data-minval= "' + obj.minimum_value + '" data-giftqty= "' + obj.gift_quantity + '"/>';
                        ocontent += '<div class="offer-content">';
                        ocontent += '<div class="offer-title">' + obj.offer_name + '</div>';
                        ocontent += '<div class="offer-desc">' + obj.offer_desc + '</div>';
                        ocontent += '</div>';
                        ocontent += '</div>';
                    });
                    $("#offers_count").html(productsObject[currentProduct].offers.length);
                    $("#offers_content").html(ocontent);
                    $("#offers_div").show('slow');
                }
            } else {
                $("#offers_count").html('');
                $("#offers_content").html('');
                $("#offers_div").hide('slow');
            }
// product description view more link
            var view_more_link = '';
            console.log(productsObject[currentProduct]);
            switch (productType) {
                case'offer':
                    view_more_link = redirectUrl + '/product/' + productsObject[currentProduct].seo_url + '/offer' + '/' + productKey;
                    break;
                case'sale':
                    view_more_link = redirectUrl + '/product/' + productsObject[currentProduct].seo_url + '/sale';
                    break;
                case'collection':
                    view_more_link = redirectUrl + '/product/' + productsObject[currentProduct].seo_url + '/collection';
                    break;
                case'segment':
                    view_more_link = redirectUrl + '/product/' + productsObject[currentProduct].seo_url + '/' + productKey;
                    break;
                case'bundle':
                    view_more_link = redirectUrl + '/product-bundle/' + productsObject[currentProduct].seo_url;
                    break;
                default:
                    view_more_link = redirectUrl + '/product/' + productsObject[currentProduct].seo_url;
                    break;
            }
            $("#prdct_view_more").attr("href", view_more_link);

//showing images
            fetchProductImages(currentProduct, productType);
        }

        //add to cart
        function addToCart() {
            var currentProduct = $('#add_to_cart_btn').attr('data-product');
            var currentProductType = $('#add_to_cart_btn').attr('data-product-type');
            var currentProductKey = $('#add_to_cart_btn').attr('data-product-key');
            addedToCartProductName = $('#add_to_cart_btn').attr('data-title');
            addedToCartProductPrice = $('#add_to_cart_btn').attr('data-price');
            addedToCartProductQuantity = $('#product_available_stock').val();
            var offer = $(".choose-offers:checked");
            var offerId = offer.val();
//var offer_id = 56;//20/04/2019 apply point offer by default
            var gift = $(".choose-gifts:checked");
            var giftId = gift.val();

            if (authUserId == '' && productsObject[currentProduct].is_voucher == '1') {
                showMessage('Sorry!Please sign in to add voucher in basket');
                return false;
            }

            if (addedToCartProductQuantity !== undefined && addedToCartProductQuantity > 0) {
                $.ajax({
                    url: '{{ url('add-to-cart') }}',
                    type: "POST",
                    data: {
                        'product': currentProduct,
                        'product_type': currentProductType,
                        'product_key': currentProductKey,
                        'quantity': addedToCartProductQuantity,
                        'offer': offerId,
                        'cart_edit_item': cartEditProductId
                    },
                    dataType: "json",
                    beforeSend: function () {
                        $('.alert').fadeOut('slow');
                        $('#add_to_cart_btn').addClass('disabled');
                        $('#add_to_cart_icon').removeClass('fa-shopping-cart');
                        $('#add_to_cart_icon').addClass('fa-circle-o-notch fa-spin');
                    },
                    success: function (response) {
                        $('#add_to_cart_btn').removeClass('disabled');
                        $('#add_to_cart_icon').removeClass('fa-circle-o-notch fa-spin');
                        $('#add_to_cart_icon').addClass('fa-shopping-cart');
                        if (response.result === true) {
                            fbq('track', 'AddToCart', {
                                content_name: addedToCartProductName,
                                content_ids: [currentProduct],
                                content_type: 'product',
                                value: addedToCartProductPrice,
                                currency: 'JOD'
                            });

                            // checking if there is any selected gift for this product
                            if (giftId !== undefined) {
                                addGiftToCart();
                            }
                            $(".cart_count_html").text(response.data);
                            showAddToCartMessage();

                        } else {
                            showMessage(response.message);
                        }

                    }
                });
            }
        }

        function saveItem() {
            var currentProduct = $('#save_item_btn').data('product');
            var currentProductType = $('#save_item_btn').data('product-type');
            $.ajax({
                url: '{{ url("add-to-wishlist") }}',
                type: "POST",
                data: {
                    'product': currentProduct,
                    'product_type': currentProductType,
                },
                dataType: "json",
                beforeSend: function () {
                    $('#save_item_btn').addClass('disabled');
                    $('#save_item_icon').removeClass('fa-heart');
                    $('#save_item_icon').addClass('fa-circle-o-notch fa-spin');
                },
                success: function (response) {
                    $('#save_item_btn').removeClass('disabled');
                    $('#save_item_icon').removeClass('fa-circle-o-notch fa-spin');
                    $('#save_item_icon').addClass('fa-heart');
                    if (response.result === true) {
                        $('#save_item_icon').css('color', '#f95040');
                        $(".wish_count_html").text(response.data);
                    } else {
                        showMessage(response.message);
                    }
                }
            });
        }

        $(document).on("click", "#write_review_btn", function (e) {
            e.preventDefault();

            if (!authUserId) {
                showMessage("Please sign in to write review.");
            } else {
                $("#add_review_modal").modal("show");
            }

        });

        function addReview() {
            var reviewTitle = $.trim($('#review_title').val());
            var reviewDescription = $.trim($('#review_description').val());
            var reviewRating = $("input[name='review_rating']:checked").val().trim();
            var msgTxt = "";

// Regular expression for title validation
            var titleRegex = /^[a-zA-Z ]*$/;

            if (!reviewTitle) {
                msgTxt = "Please enter a title.";
            } else if (!titleRegex.test(reviewTitle)) {
                msgTxt = "Title accepts characters only.";
            } else if (reviewTitle.length < 4) {
                msgTxt = "Title must be at least 4 characters long.";
            } else if (!reviewDescription) {
                msgTxt = "Please enter your review.";
            }

// Display error message and focus on the error element if validation fails
            if (msgTxt) {
                showMessage(msgTxt);
                return false;
            }

// Prepare data for AJAX request
            var currentProduct = $('#write_review_btn').data('product');
            var currentProductType = $('#save_item_btn').data('product-type');
            var requestData = {
                'product': currentProduct,
                'product_type': currentProductType,
                'title': reviewTitle,
                'description': reviewDescription,
                'rating': reviewRating
            };

// Send AJAX request
            $.ajax({
                url: '{{ url("product-review") }}',
                type: 'POST',
                data: requestData,
                dataType: "json",
                beforeSend: function () {
                    $('#review_submit_btn').prop("disabled", true); // Use prop() for setting disabled state
                },
                success: function (response) {
                    if (response.result === true) {
                        $("#add_review_modal").modal("hide");
                    }
                    $('#submit-review-btn').prop("disabled", false);
                    showMessage(response.message);

                },
                error: function () {
                    $('#submit-review-btn').prop("disabled", false); // Enable the button in case of an error
                    showMessage("An error occurred while processing your request.");
                }
            });
            return false;
        }


        function showAddToCartMessage() {
            $("#added_to_cart_image_html").attr('src', addedToCartProductImage);
            $("#added_to_cart_brand_html").html(addedToCartProductBrandName);
            $("#added_to_cart_product_html").html(addedToCartProductName);
            $("#added_to_cart_quantity_html").html(addedToCartProductQuantity);
            $("#added_to_cart_price_html").html(addedToCartProductPrice + ' JD\'s');
            $("#added_to_cart_modal").modal("show");
        }

        function getUrlParameter(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        }
    </script>

@endpush
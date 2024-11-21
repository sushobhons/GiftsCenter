@extends('frontend.layouts.master')
@section('title','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('description','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('keywords','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/home.css')}}" rel="stylesheet"/>
    <link href="{{asset('public/assets/glasscase_v.3.0.2/src_prod/css/glasscase.min.css')}}" rel="stylesheet"/>
@endsection
@section('main-content')
    <!-- Banner -->
    @if(count($banners)>0)
        <div class="home-banner mobile-hide">
            <div class="carousel slide" data-ride="carousel" id="webCarousel">
                <div class="carousel-inner">
                    @foreach($banners as $key=>$banner)
                        <div class="carousel-item {{(($key==0)? 'active' : '')}}">
                            @php
                                $ext = pathinfo($banner['banner'], PATHINFO_EXTENSION);
                            @endphp
                            @if ($ext == 'mp4')
                                <video autoplay loop muted width="100%">
                                    <source src="{{ config('app.ikasco_url') }}uploads/{{ $banner['banner'] }}"
                                            type="video/mp4"/>
                                </video>
                            @else
                                @if (!empty($banner['banner_link']))
                                    <a href="{{ $banner['banner_link'] }}">
                                        <img src="{{ $banner['banner_aws'] }}" alt="" class="lazyload"/>
                                    </a>
                                @else
                                    <img src="{{ $banner['banner_aws'] }}" alt="" class="lazyload"/>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
                <button class="carousel-control-prev" type="button" data-target="#webCarousel" data-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="sr-only">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-target="#webCarousel" data-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="sr-only">Next</span>
                </button>
            </div>
        </div>
    @endif
    @if(count($mobileBanners)>0)
        <div class="home-banner web-hide">
            <div class="carousel slide" data-ride="carousel" id="mobileCarousel">
                <div class="carousel-inner">
                    @foreach($banners as $key=>$banner)
                        <div class="carousel-item {{(($key==0)? 'active' : '')}}">
                            @php
                                $ext = pathinfo($banner['mbanner'], PATHINFO_EXTENSION);
                            @endphp
                            @if ($ext == 'mp4')
                                <video autoplay loop muted width="100%">
                                    <source src="{{ config('app.ikasco_url') }}uploads/{{ $banner['mbanner'] }}"
                                            type="video/mp4"/>
                                </video>
                            @else
                                @if (!empty($banner['banner_link']))
                                    <a href="{{ $banner['banner_link'] }}">
                                        <img src="{{ $banner['mbanner_aws'] }}" alt="" class="lazyload"/>
                                    </a>
                                @else
                                    <img src="{{ $banner['mbanner_aws'] }}" alt="" class="lazyload"/>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
                <button class="carousel-control-prev" type="button" data-target="#mobileCarousel" data-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="sr-only">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-target="#mobileCarousel" data-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="sr-only">Next</span>
                </button>
            </div>
        </div>
    @endif

    <!-- Group Segments -->
    @if(!empty($groupSegments))
        @foreach($groupSegments as $groupSegment)
            <div class="home-new-discover-sec" id="group-section-{{ $groupSegment->slug }}"
                 data-group-slug="{{ $groupSegment->slug }}">
                <h3><a href="{{ $groupSegment->url }}">{{ $groupSegment->title }}</a></h3>
                <ul class="nav nav-tabs" id="tab-{{ $groupSegment->slug }}" role="tablist">
                </ul>
                <div class="tab-content slideContent" id="tab-content-{{ $groupSegment->slug }}">
                </div>
            </div>
            @if ($groupSegment['advertisement'] != '' && in_array($groupSegment['view_by'], ['1', '2']))
                <div class="home-segments-sec">
                    <div class="dynamic-content">{!! $groupSegment['advertisement'] !!}</div>
                </div>
            @endif
        @endforeach
    @endif

    @if(!empty($homeSections))
        @php
            //dd($homeSections);
        @endphp
        @foreach($homeSections as $segment)
            <div class="home-segments-sec">

                @php
                    $products = $segment['products'];
                @endphp
                @if (in_array($segment['view_by'], ['1', '3']) && $segment['product_count'] > 0)
                    <h3>
                        <a href="{{ $segment['segment_url'] != '' ? $segment['segment_url'] : '' }}">{{ $segment['title'] != '' ? $segment['title'] : '' }}</a>
                    </h3>
                    <div id="owl-{{ $segment['id'] }}" class="owl-carousel segment-carousel">
                        @foreach ($products as $product)
                            @php
                                $range_price = (floatval($product->max_price) > floatval($product->min_price)) ? $product->min_price . ' - ' . $product->max_price : '';
                                $price = ($range_price !== "") ? $range_price . ' JD\'s' : $product->main_price . ' JD\'s';

                                $drange_price = (floatval($product->dmax_price) > floatval($product->dmin_price)) ? $product->dmin_price . ' - ' . $product->dmax_price : '';
                                $dprice = ($drange_price == "") ? ($product->dmain_price != "") ? $product->dmain_price . ' JD\'s' : '' : $drange_price . ' JD\'s';

                                $price = ($dprice != '') ? '<span class="offer_price">' . $dprice . ' </span><span class="normal_price"> ' . $price . ' </span>' : $price;

                            @endphp

                            <div class="item">
                                <div class="card">
                                    <div class="imgHldr">
                                        <img src="{{$product->family_pic}}" alt="">
                                    </div>
                                    <div class="listing-text-content">
                                        <h3>{{$product->brand_name}}</h3>
                                        <h4>{{$product->family_name}}</h4>
                                        <h5>{!! $price !!}</h5>
                                        <a href="{{ url('/product').'/'.$product->seo_url}}" style="display: block;">
                                            <div class="img-overlay"
                                                 style="background: transparent;"></div>
                                        </a>
                                        <div class="overlay-link">
                                            <a href="javascript:void(0);" class="quick-view-link quick-look"
                                               data-product="{{$product->product_id}}"
                                               data-product-type=""
                                               data-product-key="">
                                                Quick View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($segment['advertisement'] != '' && in_array($segment['view_by'], ['1', '2']))
                    <div class="dynamic-content">{!! $segment['advertisement'] !!}</div>
                @endif
            </div>

        @endforeach
    @endif

    @if(!empty($featuredBrands))
        <div class="home-second-sec">
            <div class="row">
                <div class="col-md-12">
                    <div class="slider">
                        <div class="big-carousel-sec">
                            <div id="big" class="owl-carousel owl-theme home-big-carousel">
                                @foreach($featuredBrands as $featuredBrand)
                                    <div class="item" data-brand="{{ $featuredBrand->brand_id }}">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="brand-height-section">
                                                    <div class="img-sec">
                                                        <img src="{{ config('app.ikasco_url') }}uploads/{{ $featuredBrand->banner_image }}"
                                                             alt=""/>
                                                    </div>
                                                    <div class="text-content">
                                                        <div class="text-content-inner">
                                                            <h4>Discover Our Brands</h4>
                                                            <h3>{{ $featuredBrand->brand_name }}</h3>
                                                            <p>{{ $featuredBrand->brand_description }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 brand-scroll-list-sec brandScrollBox-{{ $featuredBrand->brand_id }}"
                                                 data-brand-id="{{ $featuredBrand->brand_id }}">
                                                <ul class="row product-list brand-products-{{ $featuredBrand->brand_id }}"
                                                    id="brand-products-{{ $featuredBrand->brand_id }}">
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 thumbs-slider-section">
                                <div id="thumbs" class="owl-carousel owl-theme home-thumb-carousel">
                                    @foreach($featuredBrands as $featuredBrand)
                                        <div class="item" data-brand="{{ $featuredBrand->brand_id }}">
                                            <div class="thumb-img-sec">
                                                <img src="{{ config('app.ikasco_url') }}uploads/{{$featuredBrand->logo}}"
                                                     alt=""/>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            {{--                            <div class="owl-carousel owl-theme home-big-carousel">--}}
                            {{--                                <div class="web-hide col-md-6 brand-scroll-list-sec brandScrollBox-{{ $featuredBrand->brand_id }}"--}}
                            {{--                                     data-brand-id="{{ $featuredBrand->brand_id }}">--}}
                            {{--                                    <ul class="row product-list brand-products-{{ $featuredBrand->brand_id }}"--}}
                            {{--                                        id="mobile-brand-products-{{ $featuredBrand->brand_id }}">--}}
                            {{--                                    </ul>--}}
                            {{--                                </div>--}}
                            {{--                            </div>--}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
                            <ul class="size-list mobile-hide ql_variations_content_html" id="ql_texts_content_html">


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

@push('styles')

@endpush

@push('scripts')
    <script src="{{asset('public/js/lazysizes.min.js')}}"></script>
    <script src="{{asset('public/js/modernizr.custom.js')}}"></script>
    <script src="{{asset('public/assets/glasscase_v.3.0.2/src_dev/js/jquery.glasscase.js')}}"
            type="text/javascript"></script>
    <script src="{{asset('public/js/jquery.flexible.stars.js')}}"
            type="text/javascript"></script>
    <script src="{{asset('public/js/quick.view.js')}}"
            type="text/javascript"></script>
    <script type="text/javascript">
        let autoplay_owlcarousel = $(window).width() > 1199;

        const groupItemTemplate = (value) => {
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
                                <h4 class="red-text">${value.offer_name}</h4>
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

        function fetchGroupSegments(segmentSlug) {
            $.ajax({
                url: '{{ url("fetch-home-segments") }}',
                type: "POST",
                data: {'segmentSlug': segmentSlug},
                dataType: "json",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function (xhr) {
                    console.log('CSRF Token being sent:', xhr.getResponseHeader('X-CSRF-TOKEN'));
                },
                success: function (data) {
                    populateGroupTabs(data.slug, data.categories);
                }
            });
        }

        function populateGroupTabs(slug, data) {
            let tabs = '';
            let tabContent = '';

            data.forEach(function (category, index) {
                const activeClass = (index === 0) ? 'active' : '';
                tabs += `<li class="nav-item" role="presentation">
                                <button class="nav-link ${activeClass}" id="${slug}Tab_${index}" data-toggle="tab" data-target="#${slug}_${index}" type="button" role="tab" aria-controls="home" aria-selected="true">
                                    ${category.main_cat_name}
                                </button>
                            </li>`;

                const tabPaneClass = (index === 0) ? 'show active' : '';
                let tabItems = '';
                const items = category.products;
                items.forEach(function (item) {
                    tabItems += groupItemTemplate(item);
                });
                tabContent += `<div class="tab-pane fade ${tabPaneClass}" id="${slug}_${index}" role="tabpanel" aria-labelledby="${slug}Tab_${index}">
                                    <div class="home-segments-sec">
                                        <div id="owl-${slug}_${index}" class="owl-carousel segment-carousel">
                                            ${tabItems}
                                        </div>
                                    </div>
                               </div>
                               `;
            });

            // Append generated tabs and tab content to the DOM
            $(`#tab-${slug}`).append(tabs);
            $(`#tab-content-${slug}`).append(tabContent);
            data.forEach(function (category, index) {
                let carouselId = "#owl-" + slug + '_' + index;
                $(carouselId).owlCarousel({
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
            });

        }

        const brandItemTemplate = (value) => {
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
                price = `<span class="offer_price">${dPrice}</span> <span class="normal_price"> ${price}</span>`;
            }

            return `
                    <li class="col-md-6 col-6">
                        <div class="card">
                            <div class="img-sec">
                                <img src="${value.family_pic}" alt=""/>
                            </div>
                            <div class="text-sec">
                                <h3>${value.brand_name}</h3>
                                <h4>${value.family_name}</h4>
                                <h5>${price}</h5>
                                <h4 class="red-text">${value.offer_name}</h4>
                            </div>
                            <a href="${productUrl}">
                                <div class="img-overlay" style="background: transparent;"></div>
                            </a>
                            <div class="overlay-link">
                                <a href="javascript:void(0);" class="quick-view-link quick-look" data-product="${value.product_id}" data-product-type="" data-product-key="">
                                    QUICK VIEW
                                </a>
                            </div>
                        </div>
                    </li>
                    `;
        };

        function fetchBrandProducts(brandId) {
            $.ajax({
                url: '{{ url("fetch-brand-products") }}',
                type: "POST",
                data: {'brandId': brandId},
                dataType: "json",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function (xhr) {
                    console.log('CSRF Token being sent:', xhr.getResponseHeader('X-CSRF-TOKEN'));
                },
                success: function (data) {
                    populateBrandProducts(brandId, data);
                }
            });
        }

        function populateBrandProducts(brandId, data) {
            let itemsContent = '';

            data.forEach(function (item, index) {
                itemsContent += brandItemTemplate(item);
            });

            $(`.brand-products-${brandId}`).append(itemsContent);
            $(`.brandScrollBox-${brandId}`).scrollBar();
        }

        $(document).ready(function () {

            $('[data-group-slug]').each(function () {
                const groupSlug = $(this).data('group-slug');
                fetchGroupSegments(groupSlug);
            });

            $('[data-brand-id]').each(function () {
                const brandId = $(this).data('brand-id');
                fetchBrandProducts(brandId);
            });

            // Initialize main carousel
            const bigCarousel = $("#big").owlCarousel({
                autoplay:true,
                autoplayTimeout:5000,
                items: 1,
                nav: true,
                dots: false,
                loop: false,
                responsiveRefreshRate: 200,
                navText: [
                    '<i class="fa fa-arrow-left" aria-hidden="true"></i>',
                    '<i class="fa fa-arrow-right" aria-hidden="true"></i>'
                ]
            }).on("changed.owl.carousel", syncPosition);

// Initialize thumbnail carousel
            const thumbsCarousel = $("#thumbs").owlCarousel({
                autoplay:true,
                autoplayTimeout:5000,
                items: 5,
                nav: true,
                dots: false,
                loop: false,
                navText: [
                    '<i class="fa fa-arrow-left" aria-hidden="true"></i>',
                    '<i class="fa fa-arrow-right" aria-hidden="true"></i>'
                ],
                //slideBy: 4,
                responsiveRefreshRate: 100,
            }).on("initialized.owl.carousel", function () {
                thumbsCarousel.find(".owl-item").eq(0).addClass("current");
                $(".brandScrollBox").scrollBar();
            }).on("changed.owl.carousel", syncPosition2);

// Sync Position on Main Carousel
            function syncPosition(event) {
                const currentItemIndex = event.item.index;
                thumbsCarousel
                    .find(".owl-item")
                    .removeClass("current")
                    .eq(currentItemIndex)
                    .addClass("current");

                const visibleThumbs = thumbsCarousel.find(".owl-item.active");
                const firstVisibleThumbIndex = visibleThumbs.first().index();
                const lastVisibleThumbIndex = visibleThumbs.last().index();

                if (currentItemIndex > lastVisibleThumbIndex) {
                    thumbsCarousel.trigger("to.owl.carousel", [currentItemIndex, 100, true]);
                } else if (currentItemIndex < firstVisibleThumbIndex) {
                    thumbsCarousel.trigger("to.owl.carousel", [currentItemIndex, 100, true]);
                }
            }

// Sync Position on Thumbnail Carousel
            function syncPosition2(event) {
                const currentItemIndex = event.item.index;
                bigCarousel.trigger("to.owl.carousel", [currentItemIndex, 100, true]);
            }

// Handle Click on Thumbnails
            thumbsCarousel.on("click", ".owl-item", function (e) {
                e.preventDefault();
                const clickedIndex = $(this).index();
                bigCarousel.trigger("to.owl.carousel", [clickedIndex, 300, true]);
            });


            $('.segment-carousel').owlCarousel({
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
        });

        $(function () {
            $(".scrollBox").scrollBar();

        });

    </script>

@endpush

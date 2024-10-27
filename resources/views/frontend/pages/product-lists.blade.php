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
    <ul class="top-mobile-filter web-hide">
        <li><a href="#" class="toggle-filter"><img src="{{asset('public/img/filter-mobile-icon.svg')}}" alt=""/>
                Filter</a>
        </li>
        <li><a href="#" class="toggle-sort"><img src="{{asset('public/img/sort-mobile-icon.svg')}}" alt=""/> Sort</a>
        </li>
    </ul>
    <!-- Banner -->
    @if(count($banners)>0)
        <div class="banner inner-banner mobile-hide">
            <div class="carousel slide" data-ride="carousel">
                <div class="carousel-inner">
                    @foreach($banners as $key=>$banner)
                        <div class="carousel-item {{(($key==0)? 'active' : '')}}">
                            @php
                                $ext = pathinfo($banner['banner'], PATHINFO_EXTENSION);
                            @endphp
                            @if ($ext == 'mp4')
                                <video autoplay loop muted width="100%">
                                    <source src="{{ config('app.ikasco_url') }}uploads/{{ $banner['banner'] }}" type="video/mp4"/>
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
            </div>
        </div>
    @endif
    @if(count($mobileBanners)>0)
        <div class="banner inner-banner web-hide">
            <div class="carousel slide" data-ride="carousel">
                <div class="carousel-inner">
                    @foreach($banners as $key=>$banner)
                        <div class="carousel-item {{(($key==0)? 'active' : '')}}">
                            @php
                                $ext = pathinfo($banner['mbanner'], PATHINFO_EXTENSION);
                            @endphp
                            @if ($ext == 'mp4')
                                <video autoplay loop muted width="100%">
                                    <source src="{{ config('app.ikasco_url') }}uploads/{{ $banner['mbanner'] }}" type="video/mp4"/>
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
            </div>
        </div>
    @endif

    <div class="productListingDiv">
        @if(!empty($breadcrumbs))
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @foreach($breadcrumbs as $breadcrumb)
                        @if(!$loop->last)
                            <li class="breadcrumb-item"><a
                                        href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['title'] }}</a></li>
                        @else
                            <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['title'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif
        {{--        <h2>Discover the latest arrivals</h2>--}}
        <h3>{{$keyTerm}}</h3>
        <div class="filterLeft mobile-hide">
            <ul class="filter-list">
                <li><img src="{{asset('public/img/filter.svg')}}" alt=""/></li>
                <li><span id="total_products_num"></span></li>
                @if(count($productFilterArray['filter_segment']) > 0)
                    @foreach($productFilterArray['filter_segment'] as $segment)
                        <li>
                            <div class="checkbox-link">
                                <input class="form-check-input filter_segment" type="checkbox"
                                       value="{{ $segment['item']['in_segment'] }}"
                                       id="segment_{{ $segment['item']['in_segment'] }}"/>
                                <span>{{ $segment['title'] }}</span>
                            </div>
                        </li>
                    @endforeach
                @endif
                @if(count($productFilterArray['category']) > 1)
                    <li>
                        <a class="dropdown-link" href="#" data-toggle="dropdown" aria-expanded="false">Category</a>
                        <div class="dropdown-menu">
                            <div class="brands-list-sec">
                                <ul class="brands-list">
                                    @foreach($productFilterArray['category'] as $category)
                                        <li>
                                            <div class="form-group form-check">
                                                <label class="form-check-label" for="category_{{ $category->cat_id }}">
                                                    <input class="form-check-input filter_cat" type="checkbox"
                                                           value="{{ $category->cat_id }}"
                                                           id="category_{{ $category->cat_id }}"/>
                                                    <span class="checkmark"></span>
                                                    {{ $category->cat_name }}
                                                </label>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </li>
                @endif
                @if(count($productFilterArray['sub_category']) > 1)
                    <li>
                        <a class="dropdown-link" href="#" data-toggle="dropdown" aria-expanded="false">Category</a>
                        <div class="dropdown-menu">
                            <div class="brands-list-sec">
                                <ul class="brands-list">
                                    @foreach($productFilterArray['sub_category'] as $subCategory)
                                        <li>
                                            <div class="form-group form-check">
                                                <label class="form-check-label"
                                                       for="sub_category_{{ $subCategory->sub_cat_id }}">
                                                    <input class="form-check-input filter_sub_cat" type="checkbox"
                                                           value="{{ $subCategory->sub_cat_id }}"
                                                           id="sub_category_{{ $subCategory->sub_cat_id }}"/>
                                                    <span class="checkmark"></span>
                                                    {{ $subCategory->sub_cat_name }}
                                                </label>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </li>
                @endif
                @if(($mainCategoryId == '10' || ($filterType == 'main-category' && $filterWord == '10')) && count($productFilterArray['filter_fragtype']) > 1)
                    <li>
                        <a class="dropdown-link" href="#" data-toggle="dropdown" aria-expanded="false">Fragrance
                            Type</a>
                        <div class="dropdown-menu">
                            <div class="brands-list-sec">
                                <ul class="brands-list">
                                    @foreach($productFilterArray['filter_fragtype'] as $ftKey => $fragranceType)
                                        <li>
                                            <div class="form-group form-check">
                                                <label class="form-check-label" for="fragtype_{{ $ftKey }}">
                                                    <input class="form-check-input filter_fragtype" type="checkbox"
                                                           value="{{ $fragranceType['key'] }}"
                                                           id="fragtype_{{ $ftKey }}"/>
                                                    <span class="checkmark"></span>
                                                    {{ $fragranceType['key'] }}
                                                </label>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </li>
                @endif
                @if(($mainCategoryId == '23' || ($filterType == 'main-category' && $filterWord == '23')) && count($productFilterArray['filter_concern']) > 1)
                    <li>
                        <a class="dropdown-link" href="#" data-toggle="dropdown" aria-expanded="false">Concern</a>
                        <div class="dropdown-menu">
                            <div class="brands-list-sec">
                                <ul class="brands-list">
                                    @foreach($productFilterArray['filter_concern'] as $crKey => $concern)
                                        <li>
                                            <div class="form-group form-check">
                                                <label class="form-check-label" for="concern_{{ $crKey }}">
                                                    <input class="form-check-input filter_concern" type="checkbox"
                                                           value="{{ $concern->id }}"
                                                           id="concern_{{ $crKey }}"/>
                                                    <span class="checkmark"></span>
                                                    {{ $concern->title }}
                                                </label>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </li>
                @endif
                @if(($mainCategoryId == '24' || ($filterType == 'main-category' && $filterWord == '24')) && count($productFilterArray['filter_collection']) > 1)
                    <li>
                        <a class="dropdown-link" href="#" data-toggle="dropdown" aria-expanded="false">Collection</a>
                        <div class="dropdown-menu">
                            <div class="brands-list-sec">
                                <ul class="brands-list">
                                    @foreach($productFilterArray['filter_collection'] as $clKey => $collection)
                                        <li>
                                            <div class="form-group form-check">
                                                <label class="form-check-label" for="collection_{{ $clKey }}">
                                                    <input class="form-check-input filter_collection" type="checkbox"
                                                           value="{{ $collection->id }}"
                                                           id="collection_{{ $clKey }}"/>
                                                    <span class="checkmark"></span>
                                                    {{ $collection->name }}
                                                </label>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </li>
                @endif
                @if(count($productFilterArray['filter_brand']) > 1)
                    <li>
                        <a class="dropdown-link" href="#" data-toggle="dropdown" aria-expanded="false">Brands</a>
                        <div class="dropdown-menu">
                            <div class="brands-list-sec">
                                <ul class="brands-list">
                                    @foreach($productFilterArray['filter_brand'] as $brand)
                                        <li>
                                            <div class="form-group form-check">
                                                <label class="form-check-label" for="brand{{ $brand['brand_id'] }}">
                                                    <input class="form-check-input filter_brand" type="checkbox"
                                                           value="{{ $brand['brand_id'] }}"
                                                           id="brand{{ $brand['brand_id'] }}"/>
                                                    <span class="checkmark"></span>
                                                    {{ $brand['brand_name'] }}
                                                </label>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </li>
                @endif
                @if(isset($productFilterArray['min_price']) && $productFilterArray['min_price'] != 0 && isset($productFilterArray['max_price']) && $productFilterArray['max_price'] != 0)
                    <li>
                        <a class="dropdown-link" href="#" data-toggle="dropdown" aria-expanded="false">Price</a>
                        <div class="dropdown-menu">
                            <div class="price-filter">
                                <div id="range_slider_amount"
                                     class="price-filter-text">{{ $productFilterArray['min_price'] }} JOD
                                    - {{ $productFilterArray['max_price'] }} JOD
                                </div>
                                <div id="slider-range" class="price-filter-range" name="rangeInput"></div>
                            </div>
                        </div>
                    </li>
                @endif
            </ul>
        </div>
        <div class="filterRight mobile-hide">
            <div class="styles-short">
                <!-- Sort: -->
                <select id="filter_sort">
                    <option value="relevancy">Relevancy</option>
                    <option value="best-seller">Best Seller</option>
                    <option value="new-arrival">New Arrival</option>
                    <option value="pasc">Price: Low To High</option>
                    <option value="pdesc">Price: High To Low</option>
                    <option value="aasc" selected>Alphabetically: A-Z</option>
                    <option value="adesc">Alphabetically: Z-A</option>
                </select>
            </div>
        </div>
        <div class="productLoop">
            <ul class="row product-listing-list" id="products-list">
            </ul>
            <a href="javascript:void(0);" class="loadBtn" id="loadMoreBtn">LOAD MORE</a>
            <div class="loader"><img src="{{asset('public/img/loader.png')}}"/></div>
        </div>
    </div>
    <div class="col-md-3 left-filter-section left-filter-section1" id="mySidebar">
        <div class="filter-header web-hide">
            <h3>Filter <a href="#" class="cross-filter toggle-filter"><img
                            src="{{asset('public/img/filter-cross-icon.svg')}}" alt=""/></a></h3>
        </div>
        <div id="accordion" class="accordion">
            @if(count($productFilterArray['filter_segment']) > 0)
                <div class="card">
                    <div class="card-body">
                        <ul class="sorts-list">
                            @foreach($productFilterArray['filter_segment'] as $segment)
                                <li>
                                    <div class="form-group form-check">
                                        <label class="form-check-label" for="mobileSegment{{ $segment['item']['in_segment'] }}">
                                            <input class="form-check-input filter_segment" type="checkbox"
                                                   value="{{ $segment['item']['in_segment'] }}"
                                                   id="mobileSegment{{ $segment['item']['in_segment'] }}"/>
                                            <span class="checkmark"></span>
                                            <span class="text-span">{{ $segment['title'] }}</span>
                                        </label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            @if(count($productFilterArray['category']) > 1)
                <div class="card">
                    <div class="card-header" id="headingCategories">
                        <h5>
                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#categoriesCollapse"
                                    aria-expanded="false" aria-controls="categoriesCollapse">
                                Category
                            </button>
                        </h5>
                    </div>
                    <div id="categoriesCollapse" class="collapse" aria-labelledby="heading4" data-parent="#accordion">
                        <div class="card-body">
                            <ul class="sorts-list">
                                @foreach($productFilterArray['category'] as $category)
                                    <li>
                                        <div class="form-group form-check">
                                            <label class="form-check-label" for="mobileCategory{{ $category->cat_id }}">
                                                <input class="form-check-input filter_cat" type="checkbox"
                                                       value="{{ $category->cat_id }}"
                                                       id="mobileCategory{{ $category->cat_id }}"/>
                                                <span class="checkmark"></span>
                                                <span class="text-span">{{ $category->cat_name }}</span>
                                            </label>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
            @if(count($productFilterArray['sub_category']) > 1)
                <div class="card">
                    <div class="card-header" id="headingCategories">
                        <h5>
                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#subCategoriesCollapse"
                                    aria-expanded="false" aria-controls="subCategoriesCollapse">
                                Category
                            </button>
                        </h5>
                    </div>
                    <div id="subCategoriesCollapse" class="collapse" aria-labelledby="heading4"
                         data-parent="#accordion">
                        <div class="card-body">
                            <ul class="sorts-list">
                                @foreach($productFilterArray['sub_category'] as $subCategory)
                                    <li>
                                        <div class="form-group form-check">
                                            <label class="form-check-label"
                                                   for="mobileSubCategory{{ $subCategory->sub_cat_id }}">
                                                <input class="form-check-input filter_cat" type="checkbox"
                                                       value="{{ $subCategory->sub_cat_id }}"
                                                       id="mobileSubCategory{{ $subCategory->sub_cat_id }}"/>
                                                <span class="checkmark"></span>
                                                <span class="text-span">{{ $subCategory->sub_cat_name }}</span>
                                            </label>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
            @if(($mainCategoryId == '10' || ($filterType == 'main-category' && $filterWord == '10')) && count($productFilterArray['filter_fragtype']) > 1)
                <div class="card">
                    <div class="card-header" id="headingFragranceType">
                        <h5>
                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#fragranceTypeCollapse"
                                    aria-expanded="false" aria-controls="fragranceTypeCollapse">
                                Fragrance Type
                            </button>
                        </h5>
                    </div>
                    <div id="fragranceTypeCollapse" class="collapse" aria-labelledby="headingFragranceType"
                         data-parent="#accordion">
                        <div class="card-body">
                            <ul class="sorts-list">
                                @foreach($productFilterArray['filter_fragtype'] as $ftKey => $fragranceType)
                                    <li>
                                        <div class="form-group form-check">
                                            <label class="form-check-label" for="mobileFragranceType{{ $ftKey }}">
                                                <input class="form-check-input filter_fragtype" type="checkbox"
                                                       value="{{ $fragranceType['key'] }}"
                                                       id="mobileFragranceType{{ $ftKey }}"/>
                                                <span class="checkmark"></span>
                                                <span class="text-span">{{ $fragranceType['key'] }}</span>
                                            </label>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
            @if(($mainCategoryId == '23' || ($filterType == 'main-category' && $filterWord == '23')) && count($productFilterArray['filter_concern']) > 1)
                <div class="card">
                    <div class="card-header" id="headingConcern">
                        <h5>
                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#concernCollapse"
                                    aria-expanded="false" aria-controls="concernCollapse">
                                Concern
                            </button>
                        </h5>
                    </div>
                    <div id="concernCollapse" class="collapse" aria-labelledby="headingConcern"
                         data-parent="#accordion">
                        <div class="card-body">
                            <ul class="sorts-list">
                                @foreach($productFilterArray['filter_concern'] as $crKey => $concern)
                                    <li>
                                        <div class="form-group form-check">
                                            <label class="form-check-label" for="mobileConcern{{ $crKey }}">
                                                <input class="form-check-input filter_concern" type="checkbox"
                                                       value="{{ $concern->id }}"
                                                       id="mobileConcern{{ $crKey }}"/>
                                                <span class="checkmark"></span>
                                                <span class="text-span">{{ $concern->title }}</span>
                                            </label>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
            @if(($mainCategoryId == '24' || ($filterType == 'main-category' && $filterWord == '24')) && count($productFilterArray['filter_collection']) > 1)
                <div class="card">
                    <div class="card-header" id="headingCollection">
                        <h5>
                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collectionCollapse"
                                    aria-expanded="false" aria-controls="collectionCollapse">
                                Collection
                            </button>
                        </h5>
                    </div>
                    <div id="collectionCollapse" class="collapse" aria-labelledby="headingCollection"
                         data-parent="#accordion">
                        <div class="card-body">
                            <ul class="sorts-list">
                                @foreach($productFilterArray['filter_collection'] as $clKey => $collection)
                                    <li>
                                        <div class="form-group form-check">
                                            <label class="form-check-label" for="mobileCollection{{ $clKey }}">
                                                <input class="form-check-input filter_collection" type="checkbox"
                                                       value="{{ $collection->id }}"
                                                       id="mobileCollection{{ $clKey }}"/>
                                                <span class="checkmark"></span>
                                                <span class="text-span">{{ $collection->name }}</span>
                                            </label>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
            @if(count($productFilterArray['filter_brand']) > 1)
                <div class="card">
                    <div class="card-header" id="heading4">
                        <h5>
                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#brandsCollapse"
                                    aria-expanded="false" aria-controls="brandsCollapse">Brands
                            </button>
                        </h5>
                    </div>
                    <div id="brandsCollapse" class="collapse" aria-labelledby="heading4" data-parent="#accordion">
                        <div class="card-body">
                            <ul class="sorts-list">
                                @foreach($productFilterArray['filter_brand'] as $brand)
                                    <li>
                                        <div class="form-group form-check">
                                            <label class="form-check-label" for="mobileBrand{{ $brand['brand_id'] }}">
                                                <input class="form-check-input filter_brand" type="checkbox"
                                                       value="{{ $brand['brand_id'] }}"
                                                       id="mobileBrand{{ $brand['brand_id'] }}"/>
                                                <span class="checkmark"></span>
                                                <span class="text-span">{{ $brand['brand_name'] }}</span>
                                            </label>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
                @if(isset($productFilterArray['min_price']) && $productFilterArray['min_price'] != 0 && isset($productFilterArray['max_price']) && $productFilterArray['max_price'] != 0)
                <div class="card">
                    <div class="card-header" id="heading5">
                        <h5>
                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#priceCollapse"
                                    aria-expanded="false" aria-controls="priceCollapse">Price
                            </button>
                        </h5>
                    </div>
                    <div id="priceCollapse" class="collapse" aria-labelledby="heading5" data-parent="#accordion">
                        <div class="card-body">
                            <div class="price-filter">
                                <div id="range_slider_amount"
                                     class="price-filter-text">{{ $productFilterArray['min_price'] }} JOD
                                    - {{ $productFilterArray['max_price'] }} JOD
                                </div>
                                <div id="slider-range" class="price-filter-range" name="rangeInput"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="col-md-3 left-filter-section left-filter-section2" id="rightSidebar">
        <div class="filter-header web-hide">
            <h3 class="sort-heading">Sort <a href="#" class="cross-filter toggle-sort"><img
                            src="{{asset('public/img/filter-cross-icon.svg')}}" alt=""/></a></h3>
        </div>
        <ul class="sorts-list">
            <li>
                <div class="form-group form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="sorting" value="relevancy">
                        <span class="checkmark"></span>
                        <span class="text-span">Relevancy</span>
                    </label>
                </div>
            </li>
            <li>
                <div class="form-group form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="sorting" value="best-seller">
                        <span class="checkmark"></span>
                        <span class="text-span">Best Seller</span>
                    </label>
                </div>
            </li>
            <li>
                <div class="form-group form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="sorting" value="new-arrival">
                        <span class="checkmark"></span>
                        <span class="text-span">New Arrival</span>
                    </label>
                </div>
            </li>
            <li>
                <div class="form-group form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="sorting" value="pasc">
                        <span class="checkmark"></span>
                        <span class="text-span">Price: Low To High</span>
                    </label>
                </div>
            </li>
            <li>
                <div class="form-group form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="sorting" value="pdesc">
                        <span class="checkmark"></span>
                        <span class="text-span">Price: High To Low</span>
                    </label>
                </div>
            </li>
            <li>
                <div class="form-group form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="sorting" value="aasc">
                        <span class="checkmark"></span>
                        <span class="text-span">Alphabetically: A-Z</span>
                    </label>
                </div>
            </li>
            <li>
                <div class="form-group form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="sorting" value="adesc">
                        <span class="checkmark"></span>
                        <span class="text-span">Alphabetically: Z-A</span>
                    </label>
                </div>
            </li>
        </ul>

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

@push ('styles')
    <style>

    </style>
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
    <script src="{{asset('public/js/picker.js')}}"></script>
    <script>
        let sortPicker = $('#filter_sort');
        var productsObject;
        var product_filters = {
            key_type: '{{ $keyType }}',
            key_word: '{{ $keyWord }}',
            prdct_type: '{{ !in_array($mainCategoryId, ['34', '26']) ? '1' : '2' }}',
            bfilter: '{{ $brandFilter }}',
            crfilter: '',
            clfilter: '',
            sgfilter: '',
            spfilter: '',
            ftfilter: '',
            sfilter: '',
            filter_type: '{{ $filterType }}',
            filter_word: '{{ $filterWord }}',
            sort: '',
            min_price: parseFloat('{{ $productFilterArray['min_price'] }}'),
            max_price: parseFloat('{{ $productFilterArray['max_price'] }}'),
            mb_prdct_start: 0,
            mb_prdct_limit: 20,
            mb_prdct_total_count: 0,
        };

        $(document).ready(function () {
            fetchProducts(product_filters);
            sortPicker.picker();
        });

        //sort products web
        sortPicker.on('sp-change', function () {
            product_filters.sort = $(this).val();
            product_filters.mb_prdct_start = 0;
            fetchProducts(product_filters);
        });
        //sort products mobile
        $('input[type=radio][name=sorting]').change(function () {
            product_filters.sort = $(this).val();
            product_filters.mb_prdct_start = 0;
            fetchProducts(product_filters);
        });
        $(document).on('change', '.filter_brand', function () {
            product_filters.bfilter = $('.filter_brand:checked').map(function () {
                return $(this).val();
            }).get().join(',');
            product_filters.mb_prdct_start = 0;
            fetchProducts(product_filters);
        });
        $(document).on('change', '.filter_concern', function () {
            product_filters.crfilter = $('.filter_concern:checked').map(function () {
                return $(this).val();
            }).get().join(',');
            product_filters.mb_prdct_start = 0;
            fetchProducts(product_filters);
        });
        $(document).on('change', '.filter_concern', function () {
            product_filters.clfilter = $('.filter_collection:checked').map(function () {
                return $(this).val();
            }).get().join(',');
            product_filters.mb_prdct_start = 0;
            fetchProducts(product_filters);
        });
        $(document).on("change", ".filter_segment", function () {
            product_filters.sgfilter = $('.filter_segment:checked').map(function () {
                return $(this).val();
            }).get().join(',');
            product_filters.mb_prdct_start = 0;
            fetchProducts(product_filters);
        });
        $(document).on("change", ".filter_sunprtct", function () {
            product_filters.spfilter = $('.filter_sunprtct:checked').map(function () {
                return $(this).val();
            }).get().join(',');
            product_filters.mb_prdct_start = 0;
            fetchProducts(product_filters);
        });
        $(document).on("change", ".filter_fragtype", function () {
            product_filters.ftfilter = $('.filter_fragtype:checked').map(function () {
                return $(this).val();
            }).get().join(',');
            product_filters.mb_prdct_start = 0;
            fetchProducts(product_filters);
        });
        $(document).on("change", ".filter_size", function () {
            product_filters.sfilter = $('.filter_size:checked').map(function () {
                return $(this).val();
            }).get().join(',');
            product_filters.mb_prdct_start = 0;
            fetchProducts(product_filters);
        });
        $(document).on('click', '.filter_main_cat', function (e) {
            e.preventDefault();
            product_filters.filter_type = $('.filter_cat:checked').length ? 'main-category' : '';
            product_filters.filter_word = $(this).data('filter');
            product_filters.mb_prdct_start = 0;
            fetchProducts(product_filters);
        });
        $(document).on("change", ".filter_cat", function () {
            product_filters.filter_type = $('.filter_cat:checked').length ? 'category' : '';
            product_filters.filter_word = $('.filter_cat:checked').map(function () {
                return $(this).val();
            }).get().join(',');
            product_filters.mb_prdct_start = 0;
            fetchProducts(product_filters);
        });
        $(document).on("change", ".filter_sub_cat", function () {
            product_filters.filter_type = $('.filter_sub_cat:checked').length ? 'sub-category' : '';
            product_filters.filter_word = $('.filter_sub_cat:checked').map(function () {
                return $(this).val();
            }).get().join(',');
            product_filters.mb_prdct_start = 0;
            fetchProducts(product_filters);
        });
        var productsXhr;

        function fetchProducts(product_filters) {
            productsXhr && productsXhr.readyState != 4 && productsXhr.abort(); // clear previous request

            var key_type = product_filters.key_type !== null ? product_filters.key_type : '';
            var key_word = product_filters.key_word !== null ? product_filters.key_word : '0';
            var prdct_type = product_filters.prdct_type !== null ? product_filters.prdct_type : '1';
            var bfilter = product_filters.bfilter !== null ? product_filters.bfilter : '';
            var crfilter = product_filters.crfilter !== null ? product_filters.crfilter : '';
            var clfilter = product_filters.clfilter !== null ? product_filters.clfilter : '';
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
                    'crfilter': crfilter,
                    'clfilter': clfilter,
                    'sgfilter': sgfilter,
                    'spfilter': spfilter,
                    'ftfilter': ftfilter,
                    'sfilter': sfilter,
                    'start': mb_prdct_start,
                    'limit': mb_prdct_limit
                },
                dataType: "json",
                beforeSend: function () {
                    $(".loader").show();
                    $('.loadBtn').hide();
                    if (product_filters.mb_prdct_start == 0) {
                        $("#products-list").html('');
                    }
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
                            dprice = drange_price == "" ? pv.dmain_price != "" ? pv.dmain_price + ' JD\'s' : '' : drange_price + ' JD\'s';
                            // if (pv.offer_name != '' && dprice == '') {
                            //     dprice = pv.offer_name;
                            // }
                            price = dprice != '' ? '<span class="offer_price">' + dprice + ' </span><span class="normal_price"> ' + price + ' </span>' : price;
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
                            phtml += '<h4 class="red-text">' + pv.offer_name + '</h4>';
                            phtml += '<a href="' + product_detail_url + '" style="display: block;">';
                            phtml += '<div class="img-overlay lazyload" style="background: transparent;"></div>';
                            phtml += '</a>';
                            phtml += '<div class="overlay-link">';
                            phtml += '<a href="javascript:void(0);" class="quick-view-link quick-look" data-product="' + pv.product_id + '" data-product-type="" data-product-key="' + data_prdct_key + '">QUICK VIEW</a>';
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
                        if (product_filters.mb_prdct_start === product_filters.mb_prdct_total_count) {
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

        $(function () {
            $(".price-filter-range").slider({
                range: true,
                orientation: "horizontal",
                min: product_filters.min_price,
                max: product_filters.max_price,
                values: [product_filters.min_price, product_filters.max_price],
                step: 1,
                slide: function (event, ui) {
                    if (ui.values[0] == ui.values[1]) {
                        return false;
                    }
                    $(".price-filter-text").html(ui.values[0] + "JOD - " + ui.values[1] + "JOD");
                },
                stop: function (event, ui) {
                    product_filters.min_price = ui.values[0];
                    product_filters.max_price = ui.values[1];
                    product_filters.mb_prdct_start = 0;
                    fetchProducts(product_filters);
                }
            });
        });
        $(document).on('click', '.toggle-filter', function (e) {
            e.preventDefault();
            $("#mySidebar").toggleClass("open");
            $("#main").toggleClass("open");
        });

        $(document).on('click', '.toggle-sort', function (e) {
            e.preventDefault();
            $("#rightSidebar").toggleClass("open1");
            $("#main").toggleClass("open1");
        });

    </script>

@endpush
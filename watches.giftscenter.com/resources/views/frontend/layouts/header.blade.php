<header>
    <nav class="navbar navbar-expand-md header-navbar mobile-hide">
        @php
            $freeDelivery =  Helper::getSiteConfig('free_delivery');
            $amazonURL = config('app.amazon_url');
            $headerDetails = Helper::getHeaderDetails();
            $referenceURLs = Helper::getReferenceURLs();
            $navNewArrivals = Helper::getNewCategories();
            $navBrands = Helper::getBrands();
            $navCategories = Helper::getCategories();
            $navOffers = Helper::getOffers();
            $navConcerns = Helper::getConcerns();
            $navCollections = Helper::getCollections();

            if (auth()->check()){
                $loggedUser = auth()->user();
                $customerName = $loggedUser->customer_name;
                $customerId = $loggedUser->customer_id;
                $customerKey = $loggedUser->rand_key;
                $nameParts = explode(' ', $customerName);
                $firstName = $nameParts[0];
                $userData = compact('customerId', 'customerKey', 'firstName');
            } else {
                $userData = null;
            }

        @endphp
        @if ($freeDelivery !== null && (float) $freeDelivery > 0)
            <div class="top-header"><span>Free Delivery</span> on orders over {{ $freeDelivery . ' JD' }}</div>
        @endif
        <div class="bottom-header">
            <div class="bottom-header-top">
                <div class="top-search">
                    <form class="form-inline">
                        <input type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                               class="search_input search-query form-control" placeholder="Search here.."
                               id="search_input" data-input="desktop" id="search_input"/>
                        <ul class="typeahead dropdown-menu search-desktop"></ul>
                    </form>
                </div>
                <div class="logo">
                    <a class="navbar-brand" href="{{ url('/') }}"><img src="{{ $headerDetails['header_logo'] }}"
                                                                       height="44" width="255" alt="logo"
                                                                       title="Gifts Center"/></a>
                </div>
                <div class="top-right-nav">
                    <ul class="top-nav">
                        @if (!in_array(request()->route()->getName(), ['checkout', 'payment']))
                            @if ($userData)

                                <li class="user-link dropdown">
                                    <button class="btn btn-secondary dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <user-name>Hi, <span class="AuthorName"
                                                             id="author1">{{ $userData['firstName'] }}</span>,
                                            <points><span style="color:#f00;">{{ Helper::loyaltyPoints() }}</span>
                                                Points.
                                            </points>
                                        </user-name>
                                    </button>
                                    <div class="dropdown-menu">
                                        <ul>
                                            <li><a href="{{ url('/my-account') }}">My Account</a></li>
                                            <li><a href="{{ route('order-history') }}">Order History</a></li>
                                            <li><a href="{{ route('point-transactions') }}">Point Transactions</a></li>
                                            <li><a href="{{ route('user.logout') }}">Logout</a></li>
                                        </ul>
                                    </div>
                                </li>
                            @else
                                <li class="user-link"><a href="{{ url('/sign-in') }}">My Account </a></li>
                            @endif
                        @endif
                        <li>
                            <a href="{{ url('/loves-list') }}"><i class="fa fa-heart-o" aria-hidden="true"></i><span
                                        class="count wish_count_html">{{Helper::wishlistCount()}}</span></a>
                        </li>
                        <li>
                            <a href="{{ url('/my-basket') }}"><img src="{{asset('public/img/top-bag.png')}}"
                                                                   alt=""/><span
                                        class="count cart_count_html">{{Helper::cartCount()}}</span></a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <ul class="navbar-nav">
                    @if(count($navNewArrivals)>0)
                        <li class="nav-item dropdown">
                            <a class="nav-link" href="{{ route('products.new-arrival') }}">New</a>
                            <div class="dropdown-menu">
                                <div class="row">
                                    @foreach($navNewArrivals as $newMainCategory)
                                        <div class="col-md-4 col-border-right">
                                            <h3>
                                                <a href="{{ route('products.new-arrival.main-category', ['mainCategorySlug' => $newMainCategory['main_cat_slug']]) }}">In {{$newMainCategory['main_cat_name']}}</a>
                                            </h3>
                                            <ul>
                                                @foreach($newMainCategory['category'] as $newCategory)
                                                    <li>
                                                        <a href="{{ route('products.new-arrival.category', ['mainCategorySlug' => $newMainCategory['main_cat_slug'],'categorySlug' => $newCategory['cat_slug']]) }}">{{$newCategory['cat_name']}}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </li>
                    @endif

                    @if(count($navBrands)>0)
                        <li class="nav-item dropdown">
                            <a class="nav-link" href="{{route('brands')}}">Brands</a>
                            <div class="dropdown-menu">
                                <div class="CatFilterLetters">
                                    <ul id="menu_filter">
                                        <li><a id="All" href="javascript:void(0);">All Brands:</a></li>
                                        @foreach ($navBrands as $alphabetKey => $alphabetBrands)
                                            <li><a id="{{ $alphabetKey }}"
                                                   href="javascript:void(0);">{{ $alphabetKey }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="row">
                                    @foreach($navBrands as $alphabetKey => $alphabetBrands)
                                        <div class="col-md-2 col-border-right brand-list {{ $alphabetKey }}">
                                            <h3>{{ $alphabetKey }}</h3>
                                            <ul>
                                                @foreach($alphabetBrands as $brandKey => $brand)
                                                    <li>
                                                        <a href="{{route('products.brand',['brandSlug' => $brand['brand_slug']])}}">{{$brand['brand_name']}}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </li>
                    @endif

                    @foreach($navCategories as $mainCategory)
                        <li class="nav-item dropdown">
                            <a class="nav-link"
                               href="{{ route('products.main-category', ['mainCategorySlug' => $mainCategory['main_cat_slug']]) }}"
                            >{{$mainCategory['main_cat_name']}}</a>
                            <div class="dropdown-menu">
                                <div class="row">
                                    @if (!empty($mainCategory['segments']))
                                        <div class="col-md-2 col-border-right">
                                            @foreach($mainCategory['segments'] as $segment)
                                                <h3>
                                                    <a href="{{ route('products.'.$segment["slug"].'.main-category', ['mainCategorySlug' => $mainCategory['main_cat_slug']]) }}">{{$segment['title']}}</a>
                                                </h3>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="col-md-10 row">
                                        @foreach($mainCategory['category'] as $category)
                                            <div class="col-md-2 col-border-right">
                                                <h3>
                                                    <a href="{{ route('products.category', ['mainCategorySlug' => $mainCategory['main_cat_slug'],'categorySlug' => $category['cat_slug']]) }}"
                                                       class="">{{$category['cat_name']}}</a></h3>
                                                <ul>

                                                    @foreach($category['subcategory'] as $subCategory)
                                                        @if(isset($mainCategory['main_cat_slug'], $category['cat_slug'], $subCategory['sub_cat_slug']))
                                                            <li>
                                                                <a href="{{ route('products.sub-category', ['mainCategorySlug' => $mainCategory['main_cat_slug'],'categorySlug' => $category['cat_slug'],'subCategorySlug' => $subCategory['sub_cat_slug']]) }}">
                                                                    {{$subCategory['sub_cat_name']}}
                                                                </a>
                                                            </li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endforeach
                                        @if ($mainCategory['main_cat_id'] == 23 && count($navConcerns)>0)
                                            <div class="col-md-2 col-border-right">
                                                <h3><a href="{{ route('products.concern') }}"
                                                       class="">By concern</a></h3>
                                                <ul>
                                                    @foreach($navConcerns as $navConcern)
                                                        <li>
                                                            <a href="{{ route('products.concern.category', ['concernSlug' => $navConcern->slug]) }}">
                                                                {{$navConcern->title}}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        @if ($mainCategory['main_cat_id'] == 24 && count($navCollections)>0)
                                            <div class="col-md-2 col-border-right">
                                                <h3><a href="{{ route('products.collection') }}"
                                                       class="">Shop By Collection</a></h3>
                                                <ul>
                                                    @foreach($navCollections as $navCollection)
                                                        <li>
                                                            <a href="{{ route('products.collection.category', ['collectionSlug' => $navCollection->slug]) }}">
                                                                {{$navCollection->name}}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                    @php
                        $numNavOffers = count($navOffers);
                    @endphp
                    @if ($numNavOffers > 0)
                        @if ($numNavOffers > 1)
                            <li class="nav-item dropdown">
                                <a class="nav-link" href="{{ route('products.offer.all')}}">Offers</a>
                                <div class="dropdown-menu">
                                    <div class="row">
                                        @foreach ($navOffers as $navOffer)

                                            <div class="col-md-4 col-border-right">
                                                <h3><a class="nav-link"
                                                       href="{{ route('products.offer', ['offerSlug' => $navOffer->offer_slug]) }}">{{ $navOffer->offer_desc }}</a>
                                                </h3>

                                            </div>

                                        @endforeach
                                    </div>
                                </div>
                            </li>
                        @else
                            @php $navOffer = $navOffers->first(); @endphp
                            <li class="nav-item">
                                <a class="nav-link"
                                   href="{{ route('products.offer', ['offerSlug' => $navOffer->offer_slug]) }}">Offer</a>
                            </li>
                        @endif
                    @endif
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('gift-vouchers')}}">Gift Voucher</a>
                    </li>
                </ul>
                @if(count($referenceURLs) > 0)
                    @foreach($referenceURLs as $referenceURL)
                        <a target="_blank" href="{{ $referenceURL->links }}" class="watches-btn">
                            {{ $referenceURL->name }}
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </nav>
    <nav class="navbar navbar-expand-md header-navbar mobile-header-navbar web-hide">
        @if ($freeDelivery !== null && (float) $freeDelivery > 0)
            <div class="bottom-header"><span>Free Delivery</span> on orders over {{ $freeDelivery . ' JD' }}</div>
        @endif
        <div class="mobile-top-header">
            <div class="logo">
                <a class="navbar-brand" href="{{ url('/') }}"><img src="{{ $headerDetails['header_logo'] }}"
                                                                   alt=""/></a>
            </div>
        </div>
        <!-- <div class="mobile-top-header"> -->
        <div class="mobile-bottom-header">
            <div class="mobile-bottom-left-header">
                <button class="navbar-toggler collapsed" type="button" data-toggle="collapse"
                        data-target="#navbarCollapse"
                        aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon1"></span>
                    <span class="navbar-toggler-icon"></span>
                    <span class="navbar-toggler-icon3"></span>
                </button>
                <a class="search-link"><img src="{{asset('public/img/top-search-icon.svg')}}" alt=""/></a>
                <div class="mobile-search">
                    <input type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                           class="search_input search-query form-control" placeholder="Search here.."
                           id="search_input" data-input="desktop" id="search_input"/>
                    <ul class="typeahead dropdown-menu search-desktop"></ul>
                </div>
            </div>
            <!-- <div class="logo">
{{--                <a class="navbar-brand" href="{{route('home')}}"><img src="{{asset('public/img/mobile-logo.svg')}}"--}}
            {{--                                                                      alt=""/></a>--}}
            <a class="navbar-brand" href="{{ url('/') }}"><img src="{{ $headerDetails['header_logo'] }}"
                                                                   alt=""/></a>
            </div> -->
            <div class="top-right-nav">
                <ul class="top-nav">
                    @if (!in_array(request()->route()->getName(), ['checkout', 'payment']))
                        @if ($userData)
                            <li class="user-link dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <user-name>Hi, <span class="AuthorName"
                                                         id="author1">{{ $userData['firstName'] }}</span>,
                                        <points><span style="color:#f00;">{{ Helper::loyaltyPoints() }}</span>
                                            Points.
                                        </points>
                                    </user-name>
                                </button>
                                <div class="dropdown-menu">
                                    <ul>
                                        <li><a href="{{ url('/my-account') }}">My Account</a></li>
                                        <li><a href="{{ route('order-history') }}">Order History</a></li>
                                        <li><a href="{{ route('point-transactions') }}">Point Transactions</a></li>
                                        <li><a href="{{ route('user.logout') }}">Logout</a></li>
                                    </ul>
                                </div>
                            </li>
                        @else
                            <li class="user-link"><a href="{{ url('/sign-in') }}"><img
                                            src="{{asset('public/img/top-user-icon.svg')}}" alt=""/></a></li>
                        @endif
                    @endif
                    <li class="HeartList">
                        <a href="{{ url('/loves-list') }}"><i class="fa fa-heart-o" aria-hidden="true"></i><span
                                    class="count wish_count_html">{{Helper::wishlistCount()}}</span></a>
                    </li>
                    <li class="CartList">
                        <!-- <a href="{{ url('/my-basket') }}"><img src="{{asset('public/img/top-bag.png')}}" alt=""/><span
                                    class="count cart_count_html">{{Helper::cartCount()}}</span></a> -->
                        <a href="{{ url('/my-basket') }}"><img src="{{asset('public/img/top-basket-img.svg')}}" alt=""/><span
                                    class="count cart_count_html">{{Helper::cartCount()}}</span></a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="accordion main-accordion" id="accordionExample">
                <!-- mobile new arrival menu -->
                @if(count($navNewArrivals)>0)
                    <div class="card">
                        <div class="card-header" id="heading1">
                            <h2>
                                <a href="{{ route('products.new-arrival') }}">New</a>
                                <button class="btn btn-block collapsed" type="button" data-toggle="collapse"
                                        data-target="#collapse1" aria-expanded="true" aria-controls="collapse1">&nbsp;
                                </button>
                            </h2>
                        </div>
                        <div id="collapse1" class="collapse" aria-labelledby="heading1" data-parent="#accordionExample">
                            <div class="card-body">
                                <div class="accordion sub-accordion" id="newAccordionSub">
                                    @foreach($navNewArrivals as $newMainCategory)
                                        <div class="card">
                                            <div class="card-header"
                                                 id="newCategoryHeading{{ $newMainCategory['main_cat_id'] }}">
                                                <h2>
                                                    <a href="{{ route('products.new-arrival.main-category', ['mainCategorySlug' => $newMainCategory['main_cat_slug']]) }}">In {{$newMainCategory['main_cat_name']}}</a>
                                                    <button class="btn btn-block collapsed" type="button"
                                                            data-toggle="collapse"
                                                            data-target="#newCategory{{ $newMainCategory['main_cat_id'] }}"
                                                            aria-expanded="true"
                                                            aria-controls="newCategory{{ $newMainCategory['main_cat_id'] }}">
                                                        &nbsp;
                                                    </button>
                                                </h2>
                                            </div>
                                            <div id="newCategory{{ $newMainCategory['main_cat_id'] }}" class="collapse"
                                                 aria-labelledby="newCategoryHeading{{ $newMainCategory['main_cat_id'] }}"
                                                 data-parent="#newAccordionSub">
                                                <div class="card-body">
                                                    <ul class="sub-menu-list">
                                                        @foreach($newMainCategory['category'] as $newCategory)
                                                            <li>
                                                                <a href="{{ route('products.new-arrival.category', ['mainCategorySlug' => $newMainCategory['main_cat_slug'],'categorySlug' => $newCategory['cat_slug']]) }}">{{$newCategory['cat_name']}}</a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- mobile brand menu -->
                @if(count($navBrands)>0)
                    <div class="card">
                        <div class="card-header" id="heading2">
                            <h2>
                                <a href="{{route('brands')}}">Brands</a>
                            </h2>
                        </div>
                    </div>
                @endif

                <!-- mobile category menu -->
                @foreach($navCategories as $mainCategory)
                    <div class="card">
                        <div class="card-header" id="categoryHeading{{ $mainCategory['main_cat_id'] }}">
                            <h2>
                                <a href="{{ route('products.main-category', ['mainCategorySlug' => $mainCategory['main_cat_slug']]) }}"
                                >{{ $mainCategory['main_cat_name'] }}</a>
                                <button class="btn btn-block collapsed" type="button" data-toggle="collapse"
                                        data-target="#category{{ $mainCategory['main_cat_id'] }}" aria-expanded="true"
                                        aria-controls="category{{ $mainCategory['main_cat_id'] }}">&nbsp;
                                </button>
                            </h2>
                        </div>
                        <div id="category{{ $mainCategory['main_cat_id'] }}" class="collapse"
                             aria-labelledby="categoryHeading{{ $mainCategory['main_cat_id'] }}"
                             data-parent="#accordionExample">
                            <div class="card-body">
                                <div class="accordion sub-accordion" id="categoryAccordionSub">
                                    @if (!empty($mainCategory['segments']))
                                        @foreach($mainCategory['segments'] as $segment)
                                            <div class="card">
                                                <div class="card-header">
                                                    <h2>
                                                        <a href="{{ route('products.'.$segment["slug"].'.main-category', ['mainCategorySlug' => $mainCategory['main_cat_slug']]) }}">{{$segment['title']}}</a>
                                                    </h2>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif

                                    @foreach($mainCategory['category'] as $category)
                                        <div class="card">
                                            <div class="card-header" id="subCategoryHeading{{ $category['cat_id'] }}">
                                                <h2>
                                                    <a href="{{ route('products.category', ['mainCategorySlug' => $mainCategory['main_cat_slug'],'categorySlug' => $category['cat_slug']]) }}"
                                                       class="">{{$category['cat_name']}}</a>
                                                    <button class="btn btn-block collapsed" type="button"
                                                            data-toggle="collapse"
                                                            data-target="#subCategory{{ $category['cat_id'] }}"
                                                            aria-expanded="true"
                                                            aria-controls="subCategory{{ $category['cat_id'] }}">&nbsp;
                                                    </button>
                                                </h2>
                                            </div>
                                            <div id="subCategory{{ $category['cat_id'] }}" class="collapse"
                                                 aria-labelledby="subCategoryHeading{{ $category['cat_id'] }}"
                                                 data-parent="#categoryAccordionSub">
                                                <div class="card-body">
                                                    <ul class="sub-menu-list">
                                                        @foreach($category['subcategory'] as $subCategory)
                                                            @if(isset($mainCategory['main_cat_slug'], $category['cat_slug'], $subCategory['sub_cat_slug']))
                                                                <li>
                                                                    <a href="{{ route('products.sub-category', ['mainCategorySlug' => $mainCategory['main_cat_slug'],'categorySlug' => $category['cat_slug'],'subCategorySlug' => $subCategory['sub_cat_slug']]) }}">
                                                                        {{$subCategory['sub_cat_name']}}
                                                                    </a>
                                                                </li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    @if ($mainCategory['main_cat_id'] == 23 && count($navConcerns)>0)
                                        <div class="card">
                                            <div class="card-header" id="subCategoryHeadingConcern">
                                                <h2>
                                                    <a href="{{ route('products.concern') }}"
                                                       class="">By concern</a>
                                                    <button class="btn btn-block collapsed" type="button"
                                                            data-toggle="collapse"
                                                            data-target="#subCategoryConcern"
                                                            aria-expanded="true"
                                                            aria-controls="subCategoryConcern">&nbsp;
                                                    </button>
                                                </h2>
                                            </div>
                                            <div id="subCategoryConcern" class="collapse"
                                                 aria-labelledby="subCategoryHeadingConcern"
                                                 data-parent="#categoryAccordionSub">
                                                <div class="card-body">
                                                    <ul class="sub-menu-list">
                                                        @foreach($navConcerns as $navConcern)
                                                            <li>
                                                                <a href="{{ route('products.concern.category', ['concernSlug' => $navConcern->slug]) }}">
                                                                    {{$navConcern->title}}
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($mainCategory['main_cat_id'] == 24 && count($navCollections)>0)
                                        <div class="card">
                                            <div class="card-header" id="subCategoryHeadingCollection">
                                                <h2>
                                                    <a href="{{ route('products.collection') }}"
                                                       class="">Shop By Collection</a>
                                                    <button class="btn btn-block collapsed" type="button"
                                                            data-toggle="collapse"
                                                            data-target="#subCategoryCollection"
                                                            aria-expanded="true"
                                                            aria-controls="subCategoryCollection">&nbsp;
                                                    </button>
                                                </h2>
                                            </div>
                                            <div id="subCategoryCollection" class="collapse"
                                                 aria-labelledby="subCategoryHeadingCollection"
                                                 data-parent="#categoryAccordionSub">
                                                <div class="card-body">
                                                    <ul class="sub-menu-list">
                                                        @foreach($navCollections as $navCollection)
                                                            <li>
                                                                <a href="{{ route('products.collection.category', ['collectionSlug' => $navCollection->slug]) }}">
                                                                    {{$navCollection->name}}
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- mobile offer menu -->
                @php
                    $numNavOffers = count($navOffers);
                @endphp
                @if ($numNavOffers > 0)
                    @if ($numNavOffers > 1)
                        <div class="card">
                            <div class="card-header" id="headingOffers">
                                <h2>
                                    <a href="{{ route('products.offer.all')}}">Offers</a>
                                    <button class="btn btn-block collapsed" type="button" data-toggle="collapse"
                                            data-target="#mobileOffers" aria-expanded="true"
                                            aria-controls="mobileOffers">&nbsp;
                                    </button>
                                </h2>
                            </div>
                            <div id="mobileOffers" class="collapse"
                                 aria-labelledby="headingOffers"
                                 data-parent="#accordionExample">
                                <div class="card-body">
                                    <div class="accordion sub-accordion" id="offersAccordionSub">
                                        @foreach ($navOffers as $navOffer)
                                            <div class="card">
                                                <div class="card-header">
                                                    <h2>
                                                        <a href="{{ route('products.offer', ['offerSlug' => $navOffer->offer_slug]) }}">{{ $navOffer->offer_desc }}</a>
                                                    </h2>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        @php $navOffer = $navOffers->first(); @endphp
                        <div class="card">
                            <div class="card-header" id="headingOffer">
                                <h2>
                                    <a href="{{ route('products.offer', ['offerSlug' => $navOffer->offer_slug]) }}">Offer</a>
                                </h2>
                            </div>
                        </div>
                    @endif
                @endif

                <!-- mobile gift voucher menu -->
                <div class="card">
                    <div class="card-header" id="headingVoucher">
                        <h2>
                            <a href="{{route('gift-vouchers')}}">Gift Voucher</a>
                        </h2>
                    </div>
                </div>
                <!-- mobile find store menu -->
                <div class="card">
                    <div class="card-header" id="headingVoucher">
                        <h2>
                            <a href="{{ url('/find-store') }}">Find a Store</a>
                        </h2>
                    </div>
                </div>
                <!-- mobile request product menu -->
                <div class="card">
                    <div class="card-header" id="headingVoucher">
                        <h2>
                            <a href="{{ url('/request-product') }}">Request a Product</a>
                        </h2>
                    </div>
                </div>
            </div>

            @if(count($referenceURLs) > 0)
                @foreach($referenceURLs as $referenceURL)
                    <a target="_blank" href="{{ $referenceURL->links }}" class="watches-btn">
                        {{ $referenceURL->name }}
                    </a>
                @endforeach
            @endif
        </div>
    </nav>
</header>
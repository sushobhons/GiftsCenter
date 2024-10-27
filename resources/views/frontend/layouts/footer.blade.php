<div class="clearfix"></div>
@php
    $headerDetails = Helper::getHeaderDetails();
    $footerDetails = Helper::getFooterDetails();
@endphp
<footer>
    <div class="container">
        <div class="top-footer">
            <div class="row">
                <div class="col-md-6">
                    <a class="navbar-brand" href="{{route('home')}}"><img src="{{ $headerDetails['header_logo'] }}"
                                                                          height="44" width="279" alt="logo"
                                                                          title="Gifts Center"/></a>
                </div>
                <div class="col-md-6">
                    <ul class="footer-news-list">
                        <li>
                            <label>newsletter Sign up!</label>
                        </li>
                        <li>
                            <input class="form-control news_input" id="newsletter_email" type="text"
                                   placeholder="Email address" aria-label="Email address"/>

                            <button id="newsletter_btn" type="submit" class="news-btn btn pull-right"
                                    onclick="validateNewsletter();"></button>
                        </li>
                        <li class="showMessage" id="newsletter_alert"></li>
                    </ul>

                </div>
            </div>
        </div>
    </div>
    <div class="midFooter">
        <div class="container">
            <div class="mobile-hide">
                <div class="row">
                    <div class="col-md-3 footer-menu">
                        <h3>Our Policies</h3>
                        <ul>
                            <li><a href="{{ url('/page/privacy-policy') }}">Privacy Policy</a></li>
                            <li><a href="{{ url('/page/terms-and-conditions') }}">Terms and Conditions</a></li>
                            <li><a href="{{ url('/page/exchange-policy') }}">Exchange Policy</a></li>
                            <li><a href="{{ url('/page/delivery-policy') }}">Delivery Policy</a></li>
                        </ul>
                    </div>
                    <div class="col-md-3 footer-menu">
                        <h3>May we help you</h3>
                        <ul>
                            <li><a href="{{ url('/page/about-us') }}">About Us</a></li>
                            <li><a href="{{ url('/find-store') }}">Find a Store</a></li>
                            <li><a href="{{ url('/request-product') }}">Request a Product</a></li>
                        </ul>
                    </div>
                    <div class="col-md-3 footer-menu">
                        <h3>Customer Service</h3>
                        <ul>
                            @if (auth()->check())
                                <li><a href="{{ url('/my-account') }}">My Account</a></li>
                            @else
                                <li><a href="{{ url('/sign-in') }}">My Account </a></li>
                            @endif
                            <li><a href="{{ url('/loves-list') }}">Love List</a></li>
                            <li><a href="{{ url('/loyalty-program') }}">Loyalty Program</a></li>
                        </ul>
                    </div>
                    <div class="col-md-3 footer-menu footer-menu2">
                        <h3>Contact Info</h3>
                        <ul class="footer-contact-list">
                            @if($sitePhoneNumber = Helper::getSiteConfig('site_phone_number'))
                                @php
                                    preg_match_all('!\d+!', $sitePhoneNumber, $matches);
                                    $number = implode('', $matches[0]);
                                    $number = strlen($number) > 10 ? substr($number, 0, -3) : $number;
                                @endphp
                                <li class="telIcon">
                                    <a href="tel:{{ $number }}">{!! $sitePhoneNumber !!} </a>
                                </li>
                            @endif
                            @if($logisticsPhoneNumber = Helper::getSiteConfig('logistics_phone_number'))
                                @php
                                    preg_match_all('!\d+!', $logisticsPhoneNumber, $matches);
                                    $number = implode('', $matches[0]);
                                    $number = strlen($number) > 10 ? substr($number, 0, -3) : $number;
                                @endphp
                                <li class="telIcon">
                                    <a href="tel:{{ $number }}">{!! $logisticsPhoneNumber !!}</a>
                                </li>
                            @endif
                            @if($siteEmailAddress = Helper::getSiteConfig('site_mail'))
                                <li class="mailIcon">
                                    <a href="mailto:{{ $siteEmailAddress }}">{{ $siteEmailAddress }}</a>
                                </li>
                            @endif
                            @if($siteAddress = Helper::getSiteConfig('site_address'))
                                <li class="locIcon">
                                    {{ $siteAddress }}
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
            <div class="web-hide">
                <div class="accordion main-accordion" id="accordionFooter">
                    <div class="card">
                        <div class="card-header" id="Footerheading1">
                            <h2>
                                <button class="btn btn-block collapsed" type="button" data-toggle="collapse"
                                        data-target="#Footercollapse1" aria-expanded="true" aria-controls="Footercollapse1">Our Policies</button>
                            </h2>
                        </div>
                        <div id="Footercollapse1" class="collapse" aria-labelledby="Footerheading1" data-parent="#accordionFooter">
                            <div class="card-body">
                                <ul>
                                    <li><a href="{{ url('/page/privacy-policy') }}">Privacy Policy</a></li>
                                    <li><a href="{{ url('/page/terms-and-conditions') }}">Terms and Conditions</a></li>
                                    <li><a href="{{ url('/page/exchange-policy') }}">Exchange Policy</a></li>
                                    <li><a href="{{ url('/page/delivery-policy') }}">Delivery Policy</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" id="Footerheading2">
                            <h2>
                                <button class="btn btn-block collapsed" type="button" data-toggle="collapse"
                                        data-target="#Footercollapse2" aria-expanded="true" aria-controls="Footercollapse2">May we help you</button>
                            </h2>
                        </div>
                        <div id="Footercollapse2" class="collapse" aria-labelledby="Footerheading2" data-parent="#accordionFooter">
                            <div class="card-body">
                                <ul>
                                    <li><a href="{{ url('/page/about-us') }}">About Us</a></li>
                                    <li><a href="{{ url('/find-store') }}">Find a Store</a></li>
                                    <li><a href="{{ url('/request-product') }}">Request a Product</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" id="Footerheading3">
                            <h2>
                                <button class="btn btn-block collapsed" type="button" data-toggle="collapse"
                                        data-target="#Footercollapse3" aria-expanded="true" aria-controls="Footercollapse3">Customer Service</button>
                            </h2>
                        </div>
                        <div id="Footercollapse3" class="collapse" aria-labelledby="Footerheading3" data-parent="#accordionFooter">
                            <div class="card-body">
                                <ul>
                                    @if (auth()->check())
                                        <li><a href="{{ url('/my-account') }}">My Account</a></li>
                                    @else
                                        <li><a href="{{ url('/sign-in') }}">My Account </a></li>
                                    @endif
                                    <li><a href="{{ url('/loves-list') }}">Love List</a></li>
                                    <li><a href="{{ url('/loyalty-program') }}">Loyalty Program</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" id="Footerheading4">
                            <h2>
                                <button class="btn btn-block collapsed" type="button" data-toggle="collapse"
                                        data-target="#Footercollapse4" aria-expanded="true" aria-controls="Footercollapse4">Contact Info</button>
                            </h2>
                        </div>
                        <div id="Footercollapse4" class="collapse" aria-labelledby="Footerheading4" data-parent="#accordionFooter">
                            <div class="card-body">
                                <ul class="footer-contact-list">
                                @if($sitePhoneNumber = Helper::getSiteConfig('site_phone_number'))
                                    @php
                                        preg_match_all('!\d+!', $sitePhoneNumber, $matches);
                                        $number = implode('', $matches[0]);
                                        $number = strlen($number) > 10 ? substr($number, 0, -3) : $number;
                                    @endphp
                                    <li class="telIcon">
                                        <a href="tel:{{ $number }}">{!! $sitePhoneNumber !!} </a>
                                    </li>
                                @endif
                                @if($logisticsPhoneNumber = Helper::getSiteConfig('logistics_phone_number'))
                                    @php
                                        preg_match_all('!\d+!', $logisticsPhoneNumber, $matches);
                                        $number = implode('', $matches[0]);
                                        $number = strlen($number) > 10 ? substr($number, 0, -3) : $number;
                                    @endphp
                                    <li class="telIcon">
                                        <a href="tel:{{ $number }}">{!! $logisticsPhoneNumber !!}</a>
                                    </li>
                                @endif
                                @if($siteEmailAddress = Helper::getSiteConfig('site_mail'))
                                    <li class="mailIcon">
                                        <a href="mailto:{{ $siteEmailAddress }}">{{ $siteEmailAddress }}</a>
                                    </li>
                                @endif
                                @if($siteAddress = Helper::getSiteConfig('site_address'))
                                    <li class="locIcon">
                                        {{ $siteAddress }}
                                    </li>
                                @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="bottomFooter">
        <div class="container">
            <ul class="navbar left-bottom-footer">
                <li class="nav-item web-hide">Download Mobile Application</li>
                @if($appStoreUrl = Helper::getSiteConfig('app_store'))
                    <li class="nav-item">
                        <a href="" target="" class=""
                           onclick="window.open(this.getAttribute('data-url'), '_blank');return false;"
                           data-url="{{$appStoreUrl}}"><img
                                    src="{{asset('public/img/app-store-img.svg')}}" alt=""/></a>
                    </li>
                @endif
                @if($playStoreUrl = Helper::getSiteConfig('play_store'))
                    <li class="nav-item">
                        <a href="" target="" class=""
                           onclick="window.open(this.getAttribute('data-url'), '_blank');return false;"
                           data-url="{{$playStoreUrl}}"><img
                                    src="{{asset('public/img/google-play-img.svg')}}" alt=""/></a>
                    </li>
                @endif
                <li class="nav-item mobile-hide">Download Mobile Application</li>
            </ul>
            <ul class="navbar right-bottom-footer">
                @if($facebookUrl = Helper::getSiteConfig('facebook_url'))
                    <li class="nav-item">
                        <a href="{{$facebookUrl}}"><img src="{{asset('public/img/footer-facebook-icon.svg')}}" alt=""/></a>
                    </li>
                @endif
                @if($twitterUrl = Helper::getSiteConfig('twitter_url'))
                    <li class="nav-item">
                        <a href="{{$twitterUrl}}"><img src="{{asset('public/img/footer-twitter-icon.svg')}}"
                                                       alt=""/></a>
                    </li>
                @endif
                @if($instagramUrl = Helper::getSiteConfig('instagram_url'))
                    <li class="nav-item">
                        <a href="{{$instagramUrl}}"><img src="{{asset('public/img/footer-instagram-icon.svg')}}"
                                                         alt=""/></a>
                    </li>
                @endif
                @if($youtubeUrl = Helper::getSiteConfig('utube_url'))
                    <li class="nav-item">
                        <a href="{{$youtubeUrl}}"><img src="{{asset('public/img/footer-youtube-icon.svg')}}"
                                                       alt=""/></a>
                    </li>
                @endif
                @if($snapchatUrl = Helper::getSiteConfig('schat_url'))
                    <li class="nav-item">
                        <a href="{{$snapchatUrl}}"><img src="{{asset('public/img/footer-snapchat-icon.svg')}}" alt=""/></a>
                    </li>
                @endif
                @if($whatsappUrl = Helper::getSiteConfig('whatsapp'))
                    <li class="nav-item">
                        <a href="" target="" class="" onClick="MyWindow = window.open('{{$whatsappUrl}}', 'MyWindow', 'width=600,height=600');
                    return false;"><img src="{{asset('public/img/footer-whatsapp-icon.svg')}}" alt=""/></a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</footer>
@php
    $authUserId = auth()->check() ? auth()->user()->customer_id : null;
@endphp
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="{{asset('public/assets/dist/js/bootstrap.bundle.min.js')}}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.2/jquery.ui.touch-punch.min.js"></script>
<script src="{{asset('public/js/scrollBar.js')}}"></script>
<script src="{{asset('public/assets/owlcarousel/owl.carousel.min.js')}}"></script>
<script src="{{asset('public/js/jquery-confirm.min.js')}}"></script>
<!-- Include commonFunctions.js -->
<script src="{{ asset('public/js/commonFunctions.js') }}" defer></script>
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    const baseUrl = '{{ url('/') }}';
    const redirectUrl = '{{ url('/') }}';
    const productUrl = '{{ config('app.product_url') }}';
    const brandUrl = '{{ config('app.brand_url') }}';
    const productBundleUrl = '{{ rtrim(url('/product-bundle'), '/') }}' + '/';
    const authUserId = '{{ $authUserId }}';

    $('.whatsapp_window').click(function () {
        window.open('https://api.whatsapp.com/send?phone=962791889966&text=Welcome+to+giftscenter+whatsapp+chat&app_absent=0');
    });

    function showMessage(msg) {
        $.alert({
            title: '',
            content: msg,
            animation: 'scale',
            closeAnimation: 'scale',
            theme: 'modern',
            type: 'white',
            buttons: {
                okay: {
                    text: 'Okay',
                    btnClass: 'btn-blue'
                }
            }
        });
    }

    //newsletter
    function validateNewsletter() {
        var email = $.trim($('#newsletter_email').val());
        var msg_txt = "";
        var error_element = "";
        if (email === "") {
            msg_txt = "Please enter your email addresss.";
            error_element = $('#newsletter_email');
        } else if (email != '' && !isValidEmail(email)) {
            msg_txt = "Please enter valid email addresss.";
            error_element = $('#newsletter_email');
        }

        if (msg_txt !== "") {
            if (error_element !== '') {
                error_element.focus();
            }
            $('#newsletter_alert').addClass('error');
            $('#newsletter_alert').delay(300).fadeIn(300);
            $('#newsletter_alert').text(msg_txt);
        } else {
            $.ajax({
//   url: base_url + "ajax/subscribe-newsletter",
                url: "{{ url('subscribe-newsletter') }}",
                type: "POST",
                data: {
                    'email': email,
                },
                dataType: "json",
                beforeSend: function () {
                    $('.alert').fadeOut('slow');
                    $('#newsletter_btn').addClass('disabled');
                    $('#newsletter_icon').removeClass('fa-send-o');
                    $('#newsletter_icon').addClass('fa-circle-o-notch fa-spin');
                },
                success: function (response) {
                    $('#newsletter_btn').removeClass('disabled');
                    $('#newsletter_icon').removeClass('fa-circle-o-notch fa-spin');
                    $('#newsletter_icon').addClass('fa-send-o');
                    if (response.result == '1') {
                        $('#newsletter_email').val('');
                        $('#newsletter_alert').removeClass('error');
                        $('#newsletter_alert').addClass('success');
                        $('#newsletter_alert').delay(300).fadeIn(300);
                        $('#newsletter_alert').text(response.msg);
                    } else {
                        $('#newsletter_alert').removeClass('success');
                        $('#newsletter_alert').addClass('error');
                        $('#newsletter_alert').delay(300).fadeIn(300);
                        $('#newsletter_alert').text(response.msg);
                    }
                    setTimeout(function () {
                        $("#newsletter_alert").hide();
                    }, 3000);
                }
            });
        }
    }

    //header search
    let searchXhr;

    function escapeSpecialChars(str) {
        return str.replace(/[^a-zA-Z0-9\s]/g, ' ');
    }

    function searchResult(searchTerm, input) {
        searchXhr && searchXhr.readyState !== 4 && searchXhr.abort(); // clear previous request

        // Escape special characters except for '&'
        let escapedSearchTerm = escapeSpecialChars(searchTerm);

        searchXhr = $.ajax({
            url: "{{ url('products-search') }}",
            method: "POST",
            data: {
                search_term: searchTerm,             // Original search term
                escaped_search_term: escapedSearchTerm // Escaped search term
            },
            dataType: "json",
            success: function (response) {
                var content = '';
                var output_str = '';
                $.each(response.data, function (ik, item) {
                    console.log(item);
//                    var nitem =item.replace(new RegExp('(' + query + ')', 'ig'), function ($1, match) {
//                        return '<strong>' + match + '</strong>';
//                    });
                    if (item.term != undefined) {
                        content += '<li><a class="dropdown-item" href="' + redirectUrl + '/search/' + item.term_value + '" role="option">' + item.term + '</a></li>';
                    } else {
                        if (typeof item.product_id !== 'undefined') {
                            output_str = item.brand_name + ' ' + item.family_name;
                            output_str = output_str.replace(new RegExp('(' + searchTerm + ')', 'ig'), function ($1, match) {
                                return '<strong>' + match + '</strong>';
                            });
                            if (typeof item.family_pic != null) {
                                content += '<li><a class="dropdown-item" href="' + productUrl + item.seo_url + '" role="option"><span class="search_li_image"><img src="' + item.family_pic + '" /></span><span class="search_li_text">' + output_str + '</span></a></li>';
                            } else {
                                content += '<li><a class="dropdown-item" href="' + productUrl + item.seo_url + '" role="option">' + output_str + '</a></li>';
                            }
                        } else {
                            output_str = item.brand_name;
                            output_str = output_str.replace(new RegExp('(' + searchTerm + ')', 'ig'), function ($1, match) {
                                return '<strong>' + match + '</strong>';
                            });
                            content += '<li><a class="dropdown-item" href="' + brandUrl + item.seo_url + '" role="option">' + output_str + '</a></li>';
                        }
                    }
                });
                $(".typeahead.search-" + input).show();
                $(".typeahead.search-" + input).html(content);
            }
        })
    }

    $(document).on("keyup", ".search_input", function (e) {
        e.preventDefault();
        var query = $(this).val();
        var input = $(this).data("input");
        if (query.length >= 3) {
            searchResult(query, input);
        } else {
            searchXhr && searchXhr.readyState !== 4 && searchXhr.abort(); // clear previous request
            $(".typeahead.search-" + input).hide();
        }

        if (e.which == 13) {
            window.location.href = redirectUrl + '/search/' + query;
        }

    });

    //header brand menu
    $("#menu_filter li a").click(function () {
        var elementID = $(this).attr("id");
        var elementClass = $(".brand-list").hasClass(elementID);
        $(".brand-list").addClass("hidden");
        $(".brand-list").removeClass("full-view");

        if (elementClass == true) {
            $("." + elementID).removeClass("hidden");
            $("." + elementID).addClass("full-view");
        } else {
            $(".brand-list").removeClass("hidden");
        }

    });

    $(".search-link").click(function () {
        $(".mobile-search").slideToggle();
    });
</script>


@stack('scripts')
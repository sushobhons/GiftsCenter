@extends('frontend.layouts.master')
@section('title','My Basket | Gifts Center')
@section('description','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('keywords','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/cart.css')}}" rel="stylesheet"/>
    <link href="{{asset('public/assets/glasscase_v.3.0.2/src_prod/css/glasscase.min.css')}}" rel="stylesheet"/>
@endsection
@section('main-content')
    <div class="cart-sec">
        <div class="container">
            <h3>Shopping CART</h3>
            <h4><span class="cart_count_html">{{Helper::cartCount()}}</span> Items</h4>
            <div class="bottom-buttons-section">
                <div class="row">
                    <div class="col-md-8 left-cart-sec">
                        <ul class="cart-list not_empty_cart_section" id="cart_content_html">

                        </ul>
                        <ul class="cart-list empty_cart_section" style="display:none;">
                            <li>
                                <div class="card">
                                    <div class="cart-table-sec mobile-hide">
                                        <table class="table">
                                            <tr>
                                                <td colspan="4">
                                                    Your basket is empty
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <ul class="row btn-button-list">
                            <li class="col-md-6">
                                <a href="#" class="btn btn-primary not_empty_cart_section" id="proceed_to_checkout_btn">PROCEED TO SECURE CHECKOUT</a>
                            </li>
                            <li class="col-md-6">
                                <a href="{{ url('/')}}" class="btn btn-default">CONTINUE SHOPPING</a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-4 right-cart-sec">
                        <div class="white-inner-sec">
                            <ul class="right-cart-list">
                                <li>
                                    <table class="table heading-table">
                                        <tr>
                                            <th>
                                                <h4>Add a Gift Wrap?</h4>
                                                <p><label><input type="checkbox" name="" id="add_gift_wrap" value="1"/> gift
                                                        wrap.</label></p>
                                            </th>
                                            <td>&nbsp;</td>
                                        </tr>
                                    </table>
                                </li>
                                <li>
                                    <table class="table heading-table">
                                        <tr>
                                            <th>
                                                <h4>Add a Gift Message?</h4>
                                                <p><label><input type="checkbox" name="" id="add_gift_message" value="1"/>
                                                        gift message.</label></p>
                                            </th>
                                            <td>&nbsp;</td>
                                        </tr>
                                    </table>
                                    <input class="form-control gift_message_div" style="display:none;" type="text"
                                           placeholder="Type Message"
                                           aria-label="Type Message" id="gift_message"/>
                                </li>
                                <li>
                                    <table class="table heading-table">
                                        <tr>
                                            <th>
                                                <h4>Promo Code & Offers</h4>
                                            </th>
                                            <td>&nbsp;</td>
                                        </tr>
                                    </table>
                                    <ul class="promo-list" id="promo_code_div" style="display:none;">
                                        <li>
                                            <input class="form-control" type="text" placeholder="Enter Promo Code"
                                                   aria-label="Enter Promo Code" id="coupon_code"/>
                                            <a class="btn btn-primary" id="coupon_code_btn" href="#">Apply</a>
                                        </li>
                                    </ul>
                                    <ul class="offer-list" id="offers_div" style="display:none;">
                                        <li class="offers">
                                            <div class="offers-header"><span id="offers-count-html">1</span> Offers
                                                available
                                            </div>
                                            <div class="offers-content offers_content">
                                            </div>

                                        </li>
                                        <li class="offers-footer"><a href="javascript:void(0);"
                                                                     onclick="checkOffer()"
                                                                     class="btn-primary" id="apply_offer_btn">Apply</a></li>

                                    </ul>
                                    <ul class="gift-list" id="gifts_div" style="display:none;">

                                        <li class="gifts">
                                            <div class="gifts-header"><span id="gifts-count-html">1</span> Gifts available
                                            </div>
                                            <div class="gifts-content" id="gifts_content">
                                            </div>

                                        </li>
                                        <li class="gifts-footer">
                                            <a href="javascript:void(0);" onclick="backToOffer();" class="btn-default">back
                                                to offer</a>
                                            <a href="javascript:void(0);" onclick="selectGift();" class="btn-primary">add
                                                gift</a>
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    <div class="clearfix"></div>
                                    <h5>Order Summary</h5>
                                    <table class="table order-table">
                                        <tr>
                                            <th>Subtotal</th>
                                            <td id="sub_total_html">000.00 JDs</td>
                                        </tr>
                                        <tr>
                                            <th>Discount</th>
                                            <td id="discount_html">00.00 JDs</td>
                                        </tr>
                                        <tr>
                                            <th>Points Gained</th>
                                            <td id="points_html">00</td>
                                        </tr>
                                        <tr>
                                            <th><strong>TOTAL</strong></th>
                                            <td><strong id="total_html">000.00 JDs</strong></td>
                                        </tr>
                                    </table>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
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
        isCartPage = 1;
        let isCouponApplicable = 0;
        let cartObject = {};
        let couponDiscountPercentage = 0;
        let couponDiscountAmount = 0;
        let redeemedAmount = 0;
        let hasOffer = 0;

        $(document).ready(function () {
            //fetchOffer();
            fetchCart('refresh');
            //fetchProducts('index');


        });

        // Increase quantity
        $(document).on('click', '.quantity-arrow-plus', function () {
            var inputField = $(this).siblings('.quantity-num');
            if (inputField.prop('readonly')) {
                return false;
            }
            var currentValue = parseInt(inputField.val());
            inputField.val(currentValue + 1);
            inputField.trigger('change');
        });

        // Decrease quantity
        $(document).on('click', '.quantity-arrow-minus', function () {
            var inputField = $(this).siblings('.quantity-num');
            if (inputField.prop('readonly')) {
                return false;
            }
            var currentValue = parseInt(inputField.val());

            if (currentValue > parseInt(inputField.attr('min'))) {
                inputField.val(currentValue - 1);
                inputField.trigger('change');
            }
        });


        function fetchCart(cartAction = null) {
            var action = cartAction !== null ? cartAction : '';
            $.ajax({
                url: "{{ url('fetch-cart') }}",
                type: "POST",
                data: {'action': action},
                dataType: "json",
                beforeSend: function () {
                    //$('#loader1').css("display", "block");
                },
                success: function (response) {
                    if (response.result === true) {
                        cartObject = response.data.cart;
                        $(".cart_count_html").text(response.data.cart_count);
                        if (response.data.has_sample > 0) {
                            $("#samples_count").text(response.data.has_sample);
                            $("#samples_div").show();
                        } else {
                            $("#samples_div").hide();
                        }
                    }
                    buildCartContent();
                },
                error: function () {
                    showMessage("An error occurred while processing your request.");
                }
            });
        }

        function buildCartContent() {
            var cartContent = '';
            var subTotal = 0;
            var discount = 0;
            var cartDiscount = 0;
            var redeemedDiscount = 0;
            var loyalty = 0;
            var total = 0;

            $("#cart_content_html").html(cartContent);

            if (cartObject !== null && Object.keys(cartObject).length > 0) {
                console.log(cartObject);

                $.each(cartObject, function (key, value) {
                    var itemTotal = parseFloat(value.product_price) * Math.abs(value.product_qty);
                    var itemDiscount = parseFloat(value.product_disc_price);

                    subTotal += itemTotal;
                    discount += itemDiscount;
                    total += parseFloat(itemTotal) - parseFloat(itemDiscount);

                    console.log(total + '/' + itemDiscount);

                    loyalty += Math.round(value.product_loyalty, 1);


                    cartContent = '<li>';
                    cartContent += '<div class="card">';
                    cartContent += '<div class="cart-table-sec mobile-hide">';
                    cartContent += '<table class="table">';
                    cartContent += '<tr>';
                    cartContent += '<td><img src="' + value.product_img + '" alt="" /></td>';
                    cartContent += '<td>';
                    cartContent += '<h3>' + value.brand_name + '</h3>';
                    cartContent += '<p>' + value.family_name + '<br><span class="barcode_spn">SKU:' + value.barcode + '</span></p>';
                    cartContent += '</td>';
                    cartContent += '<td>';
                    cartContent += '<span class="qty-text-span">Qty</span>';
                    cartContent += '<div class="quantity-block">';
                    cartContent += '<button class="quantity-arrow-minus"></button>';
                    if (value.is_voucher == '1' || value.is_gift == '1') {
                        cartContent += '<input class="quantity-num" type="number" min="1" value="' + value.product_qty + '" readonly/>';
                    } else {
                        cartContent += '<input class="quantity-num update_cart_item" type="number" min="1" value="' + value.product_qty + '" data-item-no="' + key + '" data-item-id="' + value.product_id + '" data-product="' + value.product_qty + '" data-is-bundle="' + value.is_bundle + '"/>';
                    }
                    cartContent += '<button class="quantity-arrow-plus"></button>';
                    cartContent += '</div>';
                    cartContent += '</td>';
                    cartContent += '<td><span class="price-spna">' + value.product_price + ' JDs</span></td>';
                    cartContent += '</tr>';
                    cartContent += '</table>';
                    if (value.is_voucher != '1') {
                        if (value.is_gift == '1') {
                            cartContent += '<ul class="size-list">';
                            cartContent += '<li><span class="quantity-text"><a href="javascript:void(0);" class="" data-product-id="' + value.product_id + '">Edit</a></span></li>';
                            cartContent += '<li><span class="quantity-text"><a href="javascript:void(0);" class="save-cart-item" data-product-id="' + value.product_id + '">Add to love list</a></span></li>';
                            cartContent += '<li><span class="quantity-text"><a href="javascript:void(0);" class="delete-cart-item" data-item-no="' + value.product_no + '" data-item-id="' + value.product_id + '" data-is-bundle="' + value.is_bundle + '">Remove</a></span></li>';
                            cartContent += '</ul>';
                        } else {
                            cartContent += '<ul class="size-list">';
                            cartContent += '<li><span class="quantity-text"><a href="javascript:void(0);" class="edit-cart-item" data-product-id="' + value.product_id + '">Edit</a></span></li>';
                            cartContent += '<li><span class="quantity-text"><a href="javascript:void(0);" class="save-cart-item" data-product-id="' + value.product_id + '">Add to love list</a></span></li>';
                            cartContent += '<li><span class="quantity-text"><a href="javascript:void(0);" class="delete-cart-item" data-item-no="' + value.product_no + '" data-item-id="' + value.product_id + '" data-is-bundle="' + value.is_bundle + '">Remove</a></span></li>';
                            cartContent += '</ul>';
                        }
                    } else {
                        cartContent += '<ul class="size-list">';
                        cartContent += '<li><span class="quantity-text"><a href="javascript:void(0);" class="delete-cart-item" data-item-no="' + value.product_no + '" data-item-id="' + value.product_id + '" data-is-bundle="' + value.is_bundle + '">Remove</a></span></li>';
                        cartContent += '</ul>';
                    }
                    cartContent += '</div>';
                    cartContent += '<div class="mobile-cart-box web-hide" style="display:none;">';
                    cartContent += '<div class="product-img-sec"><img src="' + value.product_img + '" alt="" /></div>';
                    cartContent += '<div class="cart-dec-sec">';
                    cartContent += '<h3>' + value.brand_name + '</h3>';
                    cartContent += '<p>' + value.family_name + '<br /><span class="barcode_spn">SKU:' + value.barcode + '</span></p>';
                    cartContent += '</div>';
                    cartContent += '<div class="qnty-sec">';
                    cartContent += '<span class="qty-text-span">Qty</span>';
                    cartContent += '<div class="quantity-block">';
                    cartContent += '<button class="quantity-arrow-minus"></button>';
                    if (value.is_voucher == '1' || value.is_gift == '1') {
                        cartContent += '<input class="quantity-num" type="number" min="1" value="' + value.product_qty + '" readonly/>';
                    } else {
                        cartContent += '<input class="quantity-num update_cart_item" type="number" min="1" value="' + value.product_qty + '" data-item-no="' + key + '" data-item-id="' + value.product_id + '" data-product="' + value.product_qty + '" data-is-bundle="' + value.is_bundle + '"/>';
                    }
                    cartContent += '<button class="quantity-arrow-plus"></button>';
                    cartContent += '</div>';
                    cartContent += '<span class="price-spna">' + value.product_price + ' JDs</span>';
                    cartContent += '</div>';
                    cartContent += '<div class="cart-item-bottom-sec">';
                    if (value.is_voucher != '1') {
                        if (value.is_gift == '1') {
                            cartContent += '<ul class="size-list">';
                            cartContent += '<li><span class="quantity-text"><a href="javascript:void(0);" class="" data-product-id="' + value.product_id + '">Edit</a></span></li>';
                            cartContent += '<li><span class="quantity-text"><a href="javascript:void(0);" class="save-cart-item" data-product-id="' + value.product_id + '">Add to love list</a></span></li>';
                            cartContent += '<li><span class="quantity-text"><a href="javascript:void(0);" class="delete-cart-item" data-item-no="' + value.product_no + '" data-item-id="' + value.product_id + '" data-is-bundle="' + value.is_bundle + '">Remove</a></span></li>';
                            cartContent += '</ul>';
                        } else {
                            cartContent += '<ul class="size-list">';
                            cartContent += '<li><span class="quantity-text"><a href="javascript:void(0);" class="edit-cart-item" data-product-id="' + value.product_id + '">Edit</a></span></li>';
                            cartContent += '<li><span class="quantity-text"><a href="javascript:void(0);" class="save-cart-item" data-product-id="' + value.product_id + '">Add to love list</a></span></li>';
                            cartContent += '<li><span class="quantity-text"><a href="javascript:void(0);" class="delete-cart-item" data-item-no="' + value.product_no + '" data-item-id="' + value.product_id + '" data-is-bundle="' + value.is_bundle + '">Remove</a></span></li>';
                            cartContent += '</ul>';
                        }
                    } else {
                        cartContent += '<ul class="size-list">';
                        cartContent += '<li><span class="quantity-text"><a href="javascript:void(0);" class="delete-cart-item" data-item-no="' + value.product_no + '" data-item-id="' + value.product_id + '" data-is-bundle="' + value.is_bundle + '">Remove</a></span></li>';
                        cartContent += '</ul>';
                    }
                    cartContent += '</div>';
                    cartContent += '</div>';
                    cartContent += '</div>';
                    cartContent += '</li>';

                    $("#cart_content_html").append(cartContent);
                    if (value.has_coupon !== '') {
                        isCouponApplicable = 1;
                    }

                    if (value.applied_coupon !== '') {
                        cartDiscount += parseFloat(value.product_disc_price);
                    }
                });


                $(".not_empty_cart_section").fadeIn('slow');
            } else {
                $(".empty_cart_section").fadeIn('slow');
            }

            $("#sub_total_html").html(subTotal + ' JD\'s');
            $("#discount_html").html(discount.toFixed(3) + ' JD\'s');
            $("#points_html").html(loyalty);
            $("#total_html").html(total.toFixed(3) + ' JD\'s');

            if (isCouponApplicable === 1) {
                $("#promo_code_div").show();
            }

            fetchOffers();
        }

        //offers
        function fetchOffers() {
            var pcontent = '';
            $.ajax({
                url: "{{ url('fetch-offers') }}",
                type: "POST",
                data: {},
                dataType: "json",
                beforeSend: function () {
                    //$('#loader1').css("display", "block");
                },
                success: function (response) {
                    if (response.result === true) {
                        //offers section
                        if (Object.keys(response.data).length) {
                            $.each(response.data, function (idx, obj) {
                                pcontent += '<div class="offer">';
                                pcontent += '<div class="offer-left-section">';
                                pcontent += '<input type="radio" name="selected-offer" value="' + obj.offer_id + '" class="choose-cart-offers" data-product-id= "" data-mintype= "' + obj.minimum_type + '" data-minval= "' + obj.minimum_value + '" data-giftqty= "' + obj.gift_quantity + '" data-item= "' + obj.offer_item + '"/>';
                                pcontent += '</div>';
                                pcontent += '<div class="offer-right-section">';
                                pcontent += '<span class="offer-title">' + obj.offer_desc + '</span>';
                                pcontent += '</div>';
                                pcontent += '</div>';
                            });
                            $("#offers-count-html").html(Object.keys(response.data).length);
                            $(".offers_content").html(pcontent);
                            $("#offers_div").show('slow');
                            $("#offers_div_mb").show('slow');
                            hasOffer = Object.keys(response.data).length;
                        } else {
                            $("#offers_div").hide('slow');
                            $("#offers_div_mb").hide('slow');
                        }
                    }
                },
                error: function () {

                    showMessage("An error occurred while processing your request.");
                }
            });
        }

        function checkOffer() {
            var offer = $(".choose-cart-offers:checked");
            var offerId = offer.val();
            var offerItem = offer.data('item');
            var offer_giftqty = offer.data("giftqty") != '' ? parseInt(offer.data("giftqty")) : 0;
            var gift = $(".choose-gifts:checked");
            var giftId = gift.val();
            if (offerId === undefined) {
                showMessage("Please choose at least one offer.");
            } else {
                $.ajax({
                    url: "{{ url('check-offer') }}",
                    type: "POST",
                    data: {
                        'offer_id': offerId,
                        'offer_item': offerItem,
                    },
                    dataType: "json",
                    beforeSend: function () {
                        $('#apply_offer_btn').html('Applying ..');
                    },
                    complete: function () {
                        $('#apply_offer_btn').html('Apply');
                    },
                    success: function (response) {
                        if (response.result === false) {
                            showMessage(response.message);
                        } else {
                            if (offer_giftqty > 0) {
                                if (giftId === undefined) {
                                    fetchGifts(offerId, offerItem);
                                } else {
                                    applyOffer();
                                }
                            } else {
                                applyOffer();
                            }
                        }
                    },
                    error: function () {
                        showMessage("An error occurred while processing your request.");
                    }
                });
            }
        }

        function applyAutoOffer(offer) {
            console.log(offer);
            return;
            var offer = $(".choose-cart-offers:checked");
            var offer_id = offer.val();
            var offer_item = offer.data('items');
            var offer_giftqty = offer.data("giftqty") != '' ? parseInt(offer.data("giftqty")) : 0;
            var gift = $(".choose-gifts:checked");
            var gift_id = gift.val();
            if (offer_id === undefined) {
                showMessage("Please choose atleast one offer.");
            } else {
                $.ajax({
                    url: base_url + "ajax/apply_offer.php",
                    type: "POST",
                    data: {
                        'offer_id': offer_id,
                        'offer_item': offer_item,
                    },
                    dataType: "json",
                    beforeSend: function () {
                        //$('#loader1').css("display", "block");
                    },
                    success: function (response) {
                        if (response.result == "0") {
                            showMessage(response.msg);
                        } else {
                            hasOffer = 0;
                            // checking if there is any selected gift for this product
                            if (gift_id !== undefined) {
                                addGiftToCart(response.data.offer_prdct_qty);
                            } else {
                                fetchCart();
                            }

                        }
                    },
                    error: function () {
                        showMessage("An error occurred while processing your request.");
                    }
                });
            }
        }

        function applyOffer() {

            var offer = $(".choose-cart-offers:checked");
            var offerId = offer.val();
            var offerItem = offer.data('item');
            var offer_giftqty = offer.data("giftqty") != '' ? parseInt(offer.data("giftqty")) : 0;
            var gift = $(".choose-gifts:checked");
            var gift_id = gift.val();
            if (offerId === undefined) {
                showMessage("Please choose at least one offer.");
            } else {
                $.ajax({
                    url: "{{ url('apply-offer') }}",
                    type: "POST",
                    data: {
                        'offer_id': offerId,
                        'offer_item': offerItem,
                    },
                    dataType: "json",
                    beforeSend: function () {
                        //$('#loader1').css("display", "block");
                    },
                    success: function (response) {
                        if (response.result === false) {
                            showMessage(response.message);
                        } else {
                            hasOffer = 0;
                            // checking if there is any selected gift for this product
                            if (gift_id !== undefined) {
                                addGiftToCart(response.data);
                            } else {
                                fetchCart();
                            }

                        }
                    },
                    error: function () {
                        showMessage("An error occurred while processing your request.");
                    }
                });
            }
        }

        function fetchGifts(offerId, offerItem) {
            var gcontent = "";
            $.ajax({
                url: "{{ url('fetch-gifts') }}",
                type: "POST",
                data: {
                    'offer_id': offerId,
                    'offer_item': offerItem
                },
                dataType: "json",
                beforeSend: function () {
                    //show body overlay loader
                    $('.body-loader').css("display", "block");
                },
                complete: function () {
                    //hide body overlay loader
                    $('.body-loader').css("display", "none");
                },
                success: function (response) {
                    if (response.data.length > 0) {
                        $.each(response.data, function (idx, obj) {
                            gcontent += '<div class="gift">';
                            gcontent += '<div class="gift-left-section">';
                            gcontent += '<input type="radio" name="selected-gift" value="' + obj.product_id + '" class="choose-gifts" />';
                            gcontent += '<span class="gift-image"><img src="' + obj.product_img + '" alt="' + obj.family_name + '" title="' + obj.family_name + '" ></span>';
                            gcontent += '</div>';
                            gcontent += '<div class="gift-right-section">';
                            gcontent += '<span class="gift-title">' + obj.family_name + '</span>';
                            gcontent += '<span class="gift-desc">' + obj.family_desc + '</span>';
                            gcontent += '<span class="gift-price">' + obj.main_price + ' JD\'s</span>';
                            gcontent += '</div>';
                            gcontent += '</div>';
                        });
                        $("#offers_div").hide('slow');
                        $("#offers_div_mb").hide('slow');
                        $("#gifts-count-html").html(response.data.length);
                        $("#gifts_content").html(gcontent);
                        $("#gifts_div").show('slow');
                    }
                }
            });
        }

        function selectGift() {
            if ($('.choose-gifts:checked').length == 0) {
                showMessage("Please choose a gift.");
            } else {
                var gift = $(".choose-gifts:checked");
                var gift_id = gift.val();
                var gift_arr = {
                    image: gift.data('image'),
                    name: gift.data('name'),
                    price: gift.data('price')
                };
                $("#gifts_div").hide('slow');
                $("#gifts_div_mb").hide('slow');
//                    $("#offers_div").show('slow');
//                    $("#offers_div_mb").show('slow');
                checkOffer();
            }
        }

        function backToOffer() {
            var gift = $(".choose-gifts:checked");
            gift.prop('checked', false);
            $("#gifts_div").hide('slow');
            $("#gifts_div_mb").hide('slow');
            $("#offers_div").show('slow');
            $("#offers_div_mb").show('slow');
        }

        // add gift to cart
        function addGiftToCart(current_product_qty) {
            var offer = $(".choose-cart-offers:checked");
            var offer_id = offer.val();
            var gift = $(".choose-gifts:checked");
            var gift_id = gift.val();
            if (offer_id !== 'undefined' && gift_id !== 'undefined') {
                $.ajax({
                    url: "{{ url('add-gift-to-cart') }}",
                    type: "POST",
                    data: {
                        'product_qty': current_product_qty,
                        'offer': offer_id,
                        'product_gift': gift_id
                    },
                    dataType: "json",
                    beforeSend: function () {

                    },
                    success: function (response) {
                        if (response.result === true) {
                            fetchCart();
                        } else {
                            showMessage(response.messsage);
                        }
                        var gift = $(".choose-gifts:checked");
                        gift.prop('checked', false);
                    }
                });
            }
        }

        // apply_coupon
        function apply_coupon() {
            var apply_disc = $("#apply_disc").val();
            if (apply_disc == "") {
                showMessage("Please enter coupon code");
            } else {
                $('#coupon_code').val(apply_disc);
                $('#apply_coupon_frm').submit();
            }
        }

        function proceedToCheckout(guest = null) {
            var guest = guest !== null ? true : false;
            $.ajax({
                url: "{{ url('proceed-to-checkout') }}",
                type: "POST",
                data: {'guest': guest},
                dataType: "json",
                beforeSend: function () {
                    //$('#loader1').css("display", "block");
                },
                success: function (response) {
                    if (response.result === true) {
                        window.location.href = redirectUrl + "/checkout";
                    } else {
                        if (response.data != "") {
                            showMessage(response.message + response.data.join(', '));
                        } else {
                            window.location.href = '{{ url("/sign-in") }}';
                        }
                    }
                },
                error: function () {
                    showMessage("An error occurred while processing your request.");
                }
            });
        }

        $(document).on("change", ".update_cart_item", function (e) {
            e.preventDefault();
            var cartItem = $(this);
            var item_no = cartItem.data('item-no');
            var item_id = cartItem.data('item-id');
            var is_bundle = cartItem.data('is-bundle');
            var quantity = parseInt(cartItem.val());
            $.ajax({
                url: "{{ url('update-cart') }}",
                type: "POST",
                data: {
                    'cart_key': item_no,
                    'product_id': item_id,
                    'is_bundle': is_bundle,
                    'quantity': quantity,
                },
                dataType: "json",
                beforeSend: function () {
                    //currentElement.find('i').toggleClass('fa-shopping-cart fa-circle-o-notch fa-spin');
                },
                success: function (response) {
                    if (response.result === true) {
                        fetchCart();
                    } else {
                        showMessage(response.message);
                        cartItem.val(response.data);
                    }

                }
            });
        });
        // cart edit item
        $(document).on("click", ".edit-cart-item", function (e) {
            e.preventDefault();
            var currentElement = $(this);
            var currentProduct = currentElement.data('product-id');
            cartEditProductId = currentProduct;
            quickLook(currentProduct);
        });

        $(document).on("click", ".save-cart-item", function (e) {
            e.preventDefault();
            var currentElement = $(this);
            var currentProduct = currentElement.data('product-id');

            if (authUserId === '') {
                showMessage('Sorry! Please sign in to save this item in your loves list.');
                return false;
            }

            $.ajax({
                url: "{{ url('add-to-wishlist') }}",
                type: "POST",
                data: {
                    'product': currentProduct,
                },
                dataType: "json",
                beforeSend: function () {
                    currentElement.addClass('disabled');
                },
                success: function (response) {
                    currentElement.removeClass('disabled');
                    if (response.result === true) {
                        $(".wish_count_html").text(response.data);
                    }
                    showMessage(response.message);
                }
            });
        });


        $(document).on("click", "#add_gift_wrap", function () {
            $(".gift_wrap").text($('#add_gift_wrap').is(':checked') ? 'Yes' : 'No');
        });
        $(document).on("click", "#add_gift_box", function () {
            $(".gift_box").text($('#add_gift_box').is(':checked') ? 'Yes' : 'No');
        });
        $(document).on("click", "#add_gift_message", function () {
            if ($('#add_gift_message').is(':checked')) {
                $(".gift_message_div").show();
            } else {
                $(".gift_message_div").hide();
            }
        });
        $(document).on("click", ".delete-cart-item", function (e) {
            e.preventDefault();
            var item_no = $(this).data('item-no');
            var item_id = $(this).data('item-id');
            var is_bundle = $(this).data('is-bundle');
            $.confirm({
                title: 'Proceed?',
                content: 'Are you sure to remove item from your basket?',
                theme: 'modern',
                closeIcon: true,
                animation: 'scale',
                type: 'white',
                buttons: {
                    'confirm': {
                        text: 'Yes,Remove',
                        btnClass: 'btn-blue',
                        action: function () {
                            $.ajax({
                                url: "{{ url('delete-cart') }}",
                                type: "POST",
                                data: {
                                    'cart_key': item_no,
                                    'product_id': item_id,
                                    'is_bundle': is_bundle
                                },
                                dataType: "json",
                                beforeSend: function () {
                                },
                                success: function (response) {
                                    if (response.result === true) {
                                        fetchCart();
                                    } else {
                                        showMessage(response.message);
                                    }

                                }
                            });
                        }
                    },
                    cancel: {
                        text: 'No',
                        action: function () {

                        }
                    }
                }
            });
        });
        $(document).on('click', '#coupon_code_btn', function (e) {
            e.preventDefault();
            var couponCode = $.trim($('#coupon_code').val());
            var errorMessage = '';
            if (couponCode === '') {
                errorMessage = 'Please enter your promo code';
            }

            if (errorMessage !== '') {
                showMessage(errorMessage);
            } else {
                $.ajax({
                    url: "{{ url('apply-coupon') }}",
                    type: "POST",
                    data: {
                        'coupon_code': couponCode,
                        'cart_obj': cartObject
                    },
                    dataType: "json",
                    beforeSend: function () {
                        $('#coupon_code_btn').html('Applying ..');
                    },
                    complete: function () {
                        $('#coupon_code_btn').html('Apply');
                    },
                    success: function (response) {
                        if (response.result === true) {
                            couponDiscountAmount = response.data;
                            isCouponApplicable = 0;
                            $('#coupon_code').val('');
                            $("#promo_code_div").hide();
                            fetchCart();
                        } else {
                            showMessage(response.message);
                        }
                    }
                });
            }
        });
        $(document).on('click', '#proceed_to_checkout_btn', function (e) {
            e.preventDefault();
            if ($('#add_gift_wrap').is(':checked')) {
                sessionStorage.setItem('add_gift_wrap', 1);
            }
            if ($('#add_gift_box').is(':checked')) {
                sessionStorage.setItem('add_gift_box', 1);
            }
            if ($('#add_gift_message').is(':checked')) {
                if ($('#gift_message').val() == "") {
                    showMessage("Please add your gift message.");
                }
                sessionStorage.setItem('gift_message', $('#gift_message').val());
            }


            if (authUserId === null) {
                window.location.href = '{{ url("/sign-in") }}';
            } else {
                if (hasOffer > 0) {
                    $.confirm({
                        title: 'Proceed?',
                        content: 'Are you sure to proceed without applying offer?',
                        theme: 'modern',
                        closeIcon: true,
                        animation: 'scale',
                        type: 'white',
                        buttons: {
                            'confirm': {
                                text: 'Yes,Proceed',
                                btnClass: 'btn-blue',
                                action: function () {
                                    proceedToCheckout();
                                }
                            },
                            cancel: {
                                text: 'No',
                                action: function () {

                                }
                            }
                        }
                    });
                } else {
                    proceedToCheckout();
                }
            }
        });
    </script>
@endpush

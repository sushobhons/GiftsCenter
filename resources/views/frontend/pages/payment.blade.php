@extends('frontend.layouts.master')
@section('title','Payment | Gifts Center')
@section('description','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('keywords','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/payment.css')}}" rel="stylesheet"/>
@endsection
@section('main-content')
    <div class="payment-sec">
        <div class="container">
            <h3>Payment</h3>
            <div class="bottom-buttons-section">
                <div class="row">
                    <div class="col-md-8 left-payment-content-sec">
                        <ul class="payment-product-list" id="cart_content_html">

                        </ul>
                        <div class="payment-buttons-section">
                            <h4>payment method</h4>
                            <form class="payForm" method="post" action="{{ url('/order-processing') }}" name="form1"
                                  enctype="multipart/form-data" onsubmit="return checkPayment();">
                                @csrf
                                <ul class="payment-list">
                                    @if ($paymentTypes)
                                        @foreach ($paymentTypes as $paymentType)
                                            @php
                                                $hide = false;
                                                switch ($paymentType->pay_type) {
                                                    case 'Cash Online':
                                                        $hide = $hideCashOnDelivery == 1;
                                                        break;
                                                    case 'Card web':
                                                        $hide = isset(session('gc_shipping')['ship_state']) && session('gc_shipping')['ship_state'] != "Amman";
                                                        break;
                                                }
                                            @endphp

                                            @if (!$hide)
                                                <li>
                                                    <div class="card">
                                                        <div class="form-group form-check form-radio-check">
                                                            <label class="form-check-label">
                                                                <input class="form-radio-input" name="payment_type"
                                                                       {{ $loop->last ? 'checked' : '' }} type="radio"
                                                                       value="{{ $paymentType->pay_type }}"/>
                                                                <span class="checkmark"></span>
                                                                {{ $paymentType->payment_desc }}

                                                            </label>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endif
                                        @endforeach
                                    @endif
{{--                                    <li>--}}
{{--                                        <div class="card">--}}
{{--                                            <div class="form-group form-check form-radio-check">--}}
{{--                                                <label class="form-check-label">--}}
{{--                                                    <input class="form-radio-input" name="payment_type"--}}
{{--                                                           type="radio"--}}
{{--                                                           value="Monty Pay"/>--}}
{{--                                                    <span class="checkmark"></span>--}}
{{--                                                    Monty Pay--}}

{{--                                                </label>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </li>--}}
                                </ul>
                                <ul class="btn-list">
                                    <li><input type="submit" value="CONTINUE" class="btn btn-primary"
                                               style="display:none;"
                                               id="payment_btn"/></li>
                                    <li class="loader"><img src="{{asset('public/img/loader.png')}}"/></li>
                                </ul>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-4 right-payment-content-sec">
                        <div class="right-payment-white-sec">
                            <table class="table royal-table">
                                <tr style="display:none;" id="redeem_loyalty_tr">
                                    <td>Loyalty Point <span class="red-span">({{ Helper::loyaltyPoints() }})</span></td>
                                    <td><a href="#" class="btn btn-primary" data-toggle="modal"
                                           data-target="#loyaltyPointsModal">Redeem</a></td>
                                </tr>
                                <tr>
                                    <td>Gift Voucher</td>
                                    <td><a href="#" class="btn btn-primary" data-toggle="modal"
                                           data-target="#redeemVoucherModal">Redeem</a></td>
                                </tr>
                            </table>
                            <h5>Order Summary</h5>
                            <table class="table summary-table">
                                <tr>
                                    <td>Subtotal</td>
                                    <td id="sub_total_html">0 JDs</td>
                                </tr>
                                <tr>
                                    <td>Discount</td>
                                    <td id="discount_html">0 JDs</td>
                                </tr>
                                <tr>
                                    <td>Gift Wrap</td>
                                    <td id="gift_wrap_html">No</td>
                                </tr>
                                <tr>
                                    <td>Points Reedemed</td>
                                    <td id="redeemed_amount_html">0 JDs</td>
                                </tr>
                                <tr style="display: none;" id="voucher_tr">
                                    <td>Vouchers Redeemed</td>
                                    <td id="voucher_amount_html">0 JDs</td>
                                </tr>
                                <tr>
                                    <td>Points Gained</td>
                                    <td id="points_html">00</td>
                                </tr>
                                <tr>
                                    <td>Delivery</td>
                                    <td id="ship_charge_html">0 JDs</td>
                                </tr>
                                <tr>
                                    <td>Delivery Date</td>
                                    <td>{{ isset(session("gc_shipping")["delivery_date"]) ? \Carbon\Carbon::parse(session("gc_shipping")["delivery_date"])->format('jS M, h:i a') : "NA" }}</td>
                                </tr>
                                <tr>
                                    <td><strong>TOTAL</strong></td>
                                    <td><strong id="total_html">0 JDs</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Redeem loyalty Points Modal -->
    <div id="loyaltyPointsModal" class="modal fade cmnModal loyaltyPointsModal" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><img
                                src="{{asset('public/img/modal-cross.png')}}" alt=""/>
                    </button>
                    <h4 class="modal-title">Redeem Loyalty Points?</h4>
                </div>
                <div class="modal-body">
                    <p>Points Balance : <span class="red-span">{{ Helper::loyaltyPoints() }}</span></p>
                    <div class="redeem-small-box">
                        <ul class="promo-list">
                            <li>
                                <input class="form-control" type="text" placeholder="Enter Loyalty Point"
                                       aria-label="Enter Loyalty Point" id="redeem_points"/>
                                <a class="btn btn-primary" id="redeem-loyalty-btn">Redeem</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Redeem Voucher Modal -->
    <div id="redeemVoucherModal" class="modal fade cmnModal loyaltyPointsModal redeemVoucherModal" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><img
                                src="{{asset('public/img/modal-cross.png')}}" alt=""/>
                    </button>
                    <h4 class="modal-title">Redeem Voucher!</h4>
                </div>
                <div class="modal-body">
                    <p>Please Provide the Code on Your Voucher </p>
                    <div class="redeem-small-box">
                        <ul class="promo-list">
                            <li>
                                <input class="form-control" type="text" placeholder="Enter Voucher’s Code"
                                       aria-label="Enter Voucher’s Code" id="voucher_code"/>
                                <a class="btn btn-primary" id="verify-voucher-btn">Redeem</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @php
        $isGuest = session('is_guest', '');
        $loyaltyPoints = Helper::loyaltyPoints();
        $couponDiscountAmount = isset($_SESSION['coupon']['disc_amnt']) ? $_SESSION['coupon']['disc_amnt'] : 0;
    @endphp
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
    <script type="text/javascript">

        const stockErrorFlag = {{ $stockErrorFlag }};
        const payableAmount = {{ $payableAmount }};
        const loyaltyPoints = parseInt({{ $loyaltyPoints }});
        const isCouponApplicable = {{ $isCouponApplicable }};
        const isRedeemApplicable = {{ $isRedeemApplicable }};
        const minimumPurchase = {{ $minimumPurchase }};
        const shipCharge = {{ $shipCharge }};
        let ePaySessionId = '{{ $ePaySessionId }}';
        let ePayOrderAmount = {{ $ePayOrderAmount }};
        let ePayOrderId = '{{ $ePayOrderId }}';
        let redeemedAmount = 0;
        let vouchers = [];
        let cartObject = {};
        let paymentType = '';
        let montyPayUrl = '{{ $montyPayUrl }}';


        // Function to check payment validity
        function checkPayment() {

            $(".loader").show();
            $("#payment_btn").hide();

            if (stockErrorFlag > 0) {
                showMessage("Your cart has some out-of-stock products. Please remove them.");
                $(".loader").hide();
                $("#payment_btn").show();
                return false;
            }
            if (!$('input:radio[name=payment_type]').is(':checked')) {
                showMessage("Please select any payment method to proceed for checkout.");
                $(".loader").hide();
                $("#payment_btn").show();
                return false;
            }
            if (parseFloat(payableAmount) < 0) {
                showMessage("Your payment amount cannot be negative.");
                $(".loader").hide();
                $("#payment_btn").show();
                return false;
            }
            paymentType = $('input:radio[name=payment_type]:checked').val();

            sessionStorage.setItem('payment_type', paymentType);

            if (paymentType === 'Credit Online') {
                Checkout.showPaymentPage();
                return false;
            }

            if (paymentType === 'Pay Online') {
                window.location.href = montyPayUrl;
                return false;
            }


            return true;
        }

        function fetchCart() {
            $.ajax({
                url: "{{ url('fetch-cart') }}",
                type: "POST",
                data: {},
                dataType: "json",
                beforeSend: function () {
                },
                success: function (response) {
                    if (response.result === true) {
                        cartObject = response.data.cart;
                    }
                    buildCartContent();
                },
                error: function () {
                    showMessage("An error occurred while processing your request.");
                }
            });
        }

        function buildCartContent() {
            let cartContent = '';
            let subTotal = 0;
            let discount = 0;
            let cartDiscount = 0;
            let voucherDiscount = 0;
            let loyalty = 0;
            let total = 0;

            initializeECheckout();

            // Build cart content HTML string
            if (vouchers.length) {
                vouchers.forEach(function (voucher) {
                    voucherDiscount += parseFloat(voucher.amount);
                    console.log(voucherDiscount);
                });
            }

            if (cartObject !== null && Object.keys(cartObject).length > 0) {
                Object.values(cartObject).forEach(function (value) {
                    const itemTotal = parseFloat(value.product_price) * Math.abs(value.product_qty);
                    const itemDiscount = parseFloat(value.product_disc_price);

                    subTotal += itemTotal;
                    discount += itemDiscount;
                    total += itemTotal - itemDiscount;

                    loyalty += Math.round(value.product_loyalty, 1);

                    cartContent += `
                <li>
                    <div class="card">
                        <table class="table">
                            <tr>
                                <td><img src="${value.product_img}" alt="" class="product-img" /></td>
                                <td>
                                    <h5>${value.brand_name}</h5>
                                    <p>${value.family_name}</p>
                                    <span class="size-span">SKU: ${value.barcode}</span>
                                </td>
                                <td>
                                    <span class="qty-text-span">Qty</span>
                                    <div class="quantity-block"><div class="quantity-num">${value.product_qty}</div></div>
                                </td>
                                <td><span class="price-span">${value.product_price} JDs</span></td>
                            </tr>
                        </table>
                    </div>
                </li>`;

                    if (value.applied_coupon !== '') {
                        cartDiscount += parseFloat(value.product_disc_price);
                    }
                });

                $("#cart_content_html").html(cartContent);
                $(".not_empty_cart_section").fadeIn('slow');
            } else {
                $(".empty_cart_section").fadeIn('slow');
            }

            total += shipCharge;
            total -= redeemedAmount;
            total -= voucherDiscount;

            $("#sub_total_html").html(`${subTotal} JD's`);
            $("#discount_html").html(`${discount} JD's`);
            $("#points_html").html(loyalty);
            $("#ship_charge_html").html(`${shipCharge} JD's`);
            $("#redeemed_amount_html").html(`${redeemedAmount} JD's`);

            if (voucherDiscount > 0) {
                $("#voucher_amount_html").html(`${voucherDiscount} JD's`);
                $("#voucher_tr").show();
            }

            $("#total_html").html(`${total.toFixed(3)} JD's`);
            $(".vpc_Amount").val(total * 1000); // Payment amount for card pay
            $("#payment_btn").show();

            if (isCouponApplicable === 1) {
                $("#promo_code_div").show();
            }

            if (isRedeemApplicable === 1) {
                $("#redeem_loyalty_tr").show();
            }
        }


        // redeeming loyalty points
        const redeemLoyaltyPoints = () => {
            var redeemPoints = $.trim($('#redeem_points').val());
            var errorMessage = '';
            if (redeemPoints == '') {
                errorMessage = 'Please enter loyalty points you want to redeem';
            } else if (redeemPoints != '' && isNaN(redeemPoints)) {
                errorMessage = 'Please enter valid loyalty points you want to redeem';
            } else if (redeemPoints != '' && parseFloat(redeemPoints) <= 0) {
                errorMessage = 'Please enter valid loyalty points you want to redeem';
            } else if (redeemPoints != '' && parseInt(redeemPoints) > parseInt(loyaltyPoints)) {
                errorMessage = 'You cannot redeem more than your point balance';
            } else if (redeemPoints != '' && parseInt(redeemPoints) > parseInt(payableAmount)) {
                errorMessage = 'You cannot redeem more than your payable amount';
            }
            if (errorMessage != '') {
                showMessage(errorMessage);
            } else {
                var redeemPoints = $.trim($('#redeem_points').val());
                $.ajax({
                    url: '{{ url("redeem-loyalty-points") }}',
                    type: "POST",
                    data: {
                        'redeem_points': redeemPoints,
                        'cart_obj': cartObject,
                        'ePayOrderAmount': ePayOrderAmount
                    },
                    dataType: "json",
                    beforeSend: function () {
                        // Add any pre-processing logic
                    },
                    success: function (response) {
                        if (response.result === true) {
                            redeemedAmount = response.data.redeemedAmount;
                            ePaySessionId = response.data.ePaySessionId;
                            ePayOrderAmount = response.data.ePayOrderAmount;
                            ePayOrderId = response.data.ePayOrderId;
                            montyPayUrl = response.data.montyPayUrl;
                            //$("#redeem_loyalty_tr").remove();
                            $("#loyaltyPointsModal").modal('hide');
                            buildCartContent();
                        } else {
                            showMessage(response.message);
                        }
                    }
                });
            }
        }

        $(document).on('click', '#redeem-loyalty-btn', e => {
            e.preventDefault();
            redeemLoyaltyPoints();
        });

        // redeeming voucher
        const redeemVoucher = () => {
            var voucherCode = $('#voucher_code').val();

            if (!voucherCode) {
                showMessage('please provide voucher code');
                return false;
            }

            $.ajax({
                url: "{{ url('/verify-voucher-code') }}",
                type: "POST",
                data: {
                    'code': voucherCode,
                    'cart_obj': cartObject,
                    'ePayOrderAmount': ePayOrderAmount
                },
                dataType: "json",
                beforeSend: function () {
                    // You can add loader or any other UI update here
                },
                success: function (response) {
                    if (response.result === true) {
                        $('#voucher_code').val('');
                        vouchers.push(response.data.voucher);
                        ePaySessionId = response.data.ePaySessionId;
                        ePayOrderAmount = response.data.ePayOrderAmount;
                        ePayOrderId = response.data.ePayOrderId;
                        montyPayUrl = response.data.montyPayUrl;
                        $("#redeemVoucherModal").modal('hide');
                        buildCartContent();
                    } else {
                        showMessage(response.message);
                    }
                }
            });
        };

        $(document).on('click', '#verify-voucher-btn', e => {
            e.preventDefault();
            redeemVoucher();
        });


        // Event listener for coupon code submission
        $(document).on('click', '#coupon_code_btn', e => {
            e.preventDefault();
            // Implement coupon code submission logic
        });
        $(document).ready(() => {
            const giftWrap = sessionStorage.getItem('add_gift_wrap') !== undefined ? sessionStorage.getItem('add_gift_wrap') : 0;
            const giftBox = sessionStorage.getItem('add_gift_box') !== undefined ? sessionStorage.getItem('add_gift_box') : 0;

            $("#gift_wrap_html").text(giftWrap == 1 ? 'Yes' : 'No');
            $("#gift_box_html").text(giftBox == 1 ? 'Yes' : 'No');

            // Call fetchCart function
            fetchCart();

            // Set default payment type
            $("input:radio[name=payment_type]:last").attr('checked', true);

        });


    </script>
    <script type="text/javascript" src="{{ $ePayJSUrl }}"
            data-error="errorCallback" data-cancel="{{ url('payment') }}"
            data-complete="{{ url('order-processing') }}"></script>
    <script>
        function errorCallback(error) {
            showMessage("Something went wrong during payment. Please try again.");

            setTimeout(function () {
            }, 1000);
        }

        function cancelCallback() {
            console.log('Payment cancelled');
        }

        function initializeECheckout() {
            Checkout.configure({
                merchant: '9500002036EP',
                order: {
                    amount: ePayOrderAmount,
                    currency: 'JOD',
                    description: 'Gifts Center',
                    id: ePayOrderId
                },
                interaction: {
                    operation: 'PURCHASE',
                    merchant: {
                        name: 'Gifts Center',
                        address: {
                            line1: 'Amman',
                            line2: 'Amman'
                        }
                    }
                },
                session: {
                    id: ePaySessionId
                }
            });
        }
    </script>
    {{--    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.0.0/crypto-js.min.js"></script>--}}
    {{--    <script>--}}
    {{--        var order_number = ePayOrderId;--}}
    {{--        var order_amount = ePayOrderAmount;--}}
    {{--        var order_currency = "JOD";--}}
    {{--        var order_description = "Important gift";--}}
    {{--        var merchant_pass = "00fec44536b5f7c7e2725bf82c5c1ca5";--}}

    {{--        // Concatenate the values--}}
    {{--        var to_md5 = order_number + order_amount + order_currency + order_description + merchant_pass;--}}

    {{--        // Compute the MD5 hash--}}
    {{--        var md5Hash = CryptoJS.MD5(to_md5.toUpperCase()).toString();--}}

    {{--        // Compute the SHA1 hash of the MD5 hash--}}
    {{--        var sha1Hash = CryptoJS.SHA1(md5Hash).toString();--}}

    {{--        // Set the new environment variable--}}
    {{--        //pm.environment.set('session_hash', sha1Hash);--}}
    {{--        var settings = {--}}
    {{--            "url": "https://checkout.montypay.com/api/v1/session",--}}
    {{--            "method": "POST",--}}
    {{--            "timeout": 0,--}}
    {{--            "headers": {--}}
    {{--                "Content-Type": "application/json",--}}
    {{--                "Cookie": "PHPSESSID=oubi7jo0nbnda2s1cckiqqb1mq"--}}
    {{--            },--}}
    {{--            "data": JSON.stringify({--}}
    {{--                "merchant_key": "08f9354c-af91-11ee-bfd6-7aa05a16e0e1",--}}
    {{--                "operation": "purchase",--}}
    {{--                "methods": [--}}
    {{--                    "card"--}}
    {{--                ],--}}
    {{--                "order": {--}}
    {{--                    "number": "order-1234",--}}
    {{--                    "amount": "5.000",--}}
    {{--                    "currency": "JOD",--}}
    {{--                    "description": "Important gift"--}}
    {{--                },--}}
    {{--                "cancel_url": "https://www.giftscenter.com/new/payment",--}}
    {{--                "success_url": "https://www.giftscenter.com/new/order-processing",--}}
    {{--                "hash": sha1Hash--}}
    {{--            }),--}}
    {{--        };--}}

    {{--        $.ajax(settings).done(function (response) {--}}
    {{--            console.log(response);--}}
    {{--        });--}}
    {{--    </script>--}}

@endpush
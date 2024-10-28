@extends('frontend.layouts.master')
@section('title','E | Gifts Center')
@section('description','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('keywords','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/egiftvoucher.css')}}" rel="stylesheet"/>
@endsection
@section('main-content')
    <div class="container custom-max-container egift-voucher-container">
        <div class="container-fluid egift-voucher-section">
            <div class="row text-center">
                <div class="gftDv">
                    <form method="post">
                        <h3>1. Choose a design</h3>
                        <div id="owl-egiftcards" class="owl-carousel egift-card-carousel">
                            @foreach ($eVouchers as $eVoucher)
                                <div class="item">
                                    <div class="image-wrapper{{ $loop->first ? ' selected' : '' }}"
                                         data-card-id="{{ $eVoucher->unique_key }}">
                                        <img src="{{ $eVoucher->family_pic ?? '' }}"
                                             alt="{{ $eVoucher->family_name ?? '' }}"
                                             title="{{ $eVoucher->family_name ?? '' }}">
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="egift-card-large" id="egift-card-preview">
                            <img src=""/>
                        </div>
                        <h3>2. Choose the amount</h3>
                        <div class="crn-container">
                            <div class="crnbudget">
                                <div class="crnbudget--centre">
                                    <div class="crnbudget--conseil" id="amount-options">
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="section-title">3. Add your message</div>

                        <div class="areaSec">
                            <textarea placeholder="Write a personal message" id="send_message"></textarea>
                        </div>

                        <div class="crn-container">
                            <div class="crnbudget">
                                <div class="crnbudget--centre">
                                    <div class="crnbudget--conseil">
                                        <div class="crnbudget--idees">
                                            <div class="crndelivery--el active" data-product="" data-price=""
                                                 style="height: 46px;">
                                                <div class="crnbudget--price" style="position: relative;">Email <input
                                                            id="radio_email" class="delivery_radio_btn" type="radio"
                                                            name="send_medium" value="email" checked="checked"/></div>

                                            </div>
                                        </div>
                                        <div class="crnbudget--idees">
                                            <div class="crndelivery--el" data-product="" data-price=""
                                                 style="height: 46px;">
                                                <div class="crnbudget--price" style="position: relative;">SMS <input
                                                            id="radio_sms" class="delivery_radio_btn" type="radio"
                                                            name="send_medium" value="sms"/></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rdo"><input type="radio" name="send_to" value="recipient" checked> SEND TO RECIPIENT
                        </div>
                        <div class="recipient recnt">
                            <ul>
                                <li><input type="text" value="" placeholder="*To" id="recipient_to"></li>
                                <li><input type="text" value="" placeholder="*From" id="recipient_from"></li>
                                <li class="del_med_sms" style="display:none;"><input type="text" value=""
                                                                                     placeholder="*Recipient's phone number(9627xxxxxxxx)"
                                                                                     id="recipient_phone"></li>
                                <li class="del_med_email"><input type="email" value=""
                                                                 placeholder="*Recipient's email address"
                                                                 id="recipient_email"></li>
                            </ul>
                        </div>

                        <div class="rdo"><input type="radio" name="send_to" value="yourself"> SEND TO YOURSELF</div>
                        <div class="yourself recnt" style="display:none;">
                            <ul class="send_details">
                                <li style="display:none;"><input type="text"
                                                                 value="{{ auth()->check() ? auth()->user()->customer_name : '' }}"
                                                                 placeholder="*To" id="yourself_to"></li>
                                <li style="display:none;"><input type="text"
                                                                 value="{{ auth()->check() ? auth()->user()->customer_name : '' }}"
                                                                 placeholder="*From" id="yourself_from"></li>
                                <li class="del_med_sms" style="display:none;"><input type="text"
                                                                                     value="{{ auth()->check() ? auth()->user()->phone_code.auth()->user()->phone : '' }}"
                                                                                     placeholder="*Recipient's phone number(9627xxxxxxxx)"
                                                                                     id="yourself_phone" {{ auth()->check() ? 'readonly' : '' }}>
                                </li>
                                <li class="del_med_email"><input type="email"
                                                                 value="{{ auth()->check() ? auth()->user()->email : '' }}"
                                                                 placeholder="*Recipient's email address"
                                                                 id="yourself_email" {{ auth()->check() ? 'readonly' : '' }}>
                                </li>
                            </ul>
                        </div>
                        <div class="form-row full-width gift-email required">
                            <div class="svs-select-send-time-wrapper flex-center col-9-desktop col-10-tablet col-6-mobile">
                                <div class="svs-send-time-button">
                                    <div class="button-wrapper-now selected">
                                        <div class="svs-send-time-button-inner-block">
                                            <img src="{{asset('public/img/svs-send-now.png')}}" alt="">
                                            <div class="cta-text">
                                                Send ASAP
                                            </div>
                                            <input id="radioNow" type="radio" name="send_on" value="now"
                                                   aria-label="SvsDateRadioSendNow" checked="checked"
                                                   class="required valid" aria-required="true" aria-invalid="false"/>
                                            <label for="radioNow"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="svs-send-time-button--later">
                                    <div class="button-wrapper-later">
                                        <div class="flex-center date-block">
                                    <span class="datepicker-toggle">
                                        <div class="form-group">
                                            <input id="native-date-picker" type="date" name="native-date-picker"
                                                   value="{{ now()->addDay()->format('Y-m-d') }}"
                                                   class="svs-native-date input-text native-date-picker-js filled valid ignore"
                                                   min="{{ now()->addDay()->format('Y-m-d') }}"
                                                   max="{{ now()->addYear()->format('Y-m-d') }}"
                                                   aria-required="true"
                                                   aria-invalid="false" novalidate="novalidate">
                                        </div>
                                    </span>
                                        </div>
                                        <div class="svs-send-time-button-inner-block">
                                            <img src="{{asset('public/img/svs-send-later.png')}}" alt="">
                                            <div class="cta-text"> Send on <span id="date-text-empty-js" class="">future date</span>
                                            </div>
                                            <input id="radioLater" type="radio" name="send_on" value="later"
                                                   class="valid ignore" aria-label="SvsDateRadioSendLater"/>
                                            <label for="radioLater"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="asset-block flex-center col-9-desktop col-10-tablet col-6-mobile">
                                <div class="send-now-content">
                                    Choose to send it as soon as possible, or on a future date of your choice.
                                </div>
                            </div>
                        </div>

                        <div class="amount-banner col-9-desktop col-10-tablet col-6-mobile text-center">
                            <div class="icon gift-card-svs-js svg-gift_card_svs svg-gift_card_svs-dims"></div>
                            <div class="message text-left">
                                <span>Amount of</span><br>
                                <span>your e-gift voucher</span>
                            </div>
                            <div class="amount">
                                <span class="total-egc-js">0</span>&nbsp;<span>JD's</span>
                            </div>
                        </div>
                        <a href="" class="addBtn" id="add_to_cart_btn" data-product="">Add to bag</a>
                    </form>
                </div>
            </div>
        </div>

    </div>
    @php
        $eVouchers = json_encode($eVouchers, JSON_HEX_APOS);
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
    <script>
        const evouchers = '{!! addslashes($eVouchers) !!}';
        const evoucherObject = JSON.parse(evouchers);
        console.log(evoucherObject);
        $(document).ready(function () {
            // Initialize the Owl Carousel for eGift card selection
            $('#owl-egiftcards').owlCarousel({
                lazyLoad: true,
                loop: false,
                margin: 30,
                nav: true,
                navText: ['<img src="{{asset('public/img/prev-arrow.png')}}" alt=""/>', '<img src="{{asset('public/img/next-arrow.png')}}" alt=""/>'],
                dots: false,
                items: 4,
                responsive: {
                    0: {items: 1, margin: 10},
                    640: {items: 4, margin: 10},
                    768: {items: 4},
                    992: {items: 4},
                    1200: {items: 4}
                }
            });
            $('.image-wrapper:first-child').click();
        });
        // Initially select the first eGift card design
        $('.item:first-child .image-wrapper').addClass('selected');

        // Handle click on eGift card design
        $('.image-wrapper').on('click', function (event) {
            $('.image-wrapper').removeClass('selected');
            $(this).addClass('selected');

            // Update preview image and amount options based on selected card
            updatePreviewAndAmount($(this).data('card-id'));
        });

        $('input[type="radio"][name="send_to"]').change(function () {
            var inputValue = $(this).attr("value");
            var targetBox = $("." + inputValue);
            $(".recnt").not(targetBox).hide();
            $(targetBox).show();
        });

        $(".svs-send-time-button").click(function () {
            $("#radioNow").prop("checked", true);
        });
        $(".svs-send-time-button--later").click(function () {
            $("#radioLater").prop("checked", true);
        });

        $('input[type="radio"][name="send_medium"]').on('change', function () {
            // Remove active class from all elements
            $(".crndelivery--el").removeClass("active");

            // Add active class to the closest element if checked
            if ($(this).is(':checked')) {
                $(this).closest(".crndelivery--el").addClass("active");
            }

            // Get the value of the selected medium
            var medium = $(this).val();

            // Show/hide delivery options based on the selected medium
            if (medium === "sms") {
                $(".del_med_sms").show();
                $(".del_med_email").hide();
            } else {
                $(".del_med_sms").hide();
                $(".del_med_email").show();
            }
        });

        // Handle click on amount option
        $(document).on('click', '.amount-option', function () {
            $('.amount-option').removeClass('active');
            $(this).addClass('active');
            $('#add_to_cart_btn').attr('data-product', $(this).data('product-id'));
            $('.total-egc-js').text($(this).data('price'));
        });

        // Handle click on "Add to Cart" button
        $('#add_to_cart_btn').click(function (e) {
            e.preventDefault();
            addToCart();
        });

        // Function to update eGift card preview image and amount options
        function updatePreviewAndAmount(cardId) {
            var card = evoucherObject[cardId];
            console.log(card);
            var imageSrc = card.family_pic ? card.family_pic : '';
            $('#egift-card-preview img').attr('src', imageSrc);

            var amountOptionsHtml = '';
            $.each(card.products, function (idx, product) {
                amountOptionsHtml += '<div class="crnbudget--idees">';
                amountOptionsHtml += '<div class="crnbudget--el amount-option" data-product-id="' + product.product_id + '" data-price="' + product.main_price + '">';
                amountOptionsHtml += '<div class="crnbudget--price price">' + product.main_price + ' JD\'s</div>';
                amountOptionsHtml += '</div>';
                amountOptionsHtml += '</div>';
            });
            $('#amount-options').html(amountOptionsHtml);
        }

        const addToCart = () => {
            const currentProduct = $('#add_to_cart_btn').data('product');
            const sendType = $('input[name="send_to"]:checked').val();
            const sendMessage = $('#send_message').val();
            const sendTo = sendType === 'recipient' ? $('#recipient_to').val() : $('#yourself_to').val();
            const sendFrom = sendType === 'recipient' ? $('#recipient_from').val() : $('#yourself_from').val();
            const sendPhone = sendType === 'recipient' ? $('#recipient_phone').val() : $('#yourself_phone').val();
            const sendEmail = sendType === 'recipient' ? $('#recipient_email').val() : $('#yourself_email').val();
            const sendOn = $('input[name="send_on"]:checked').val();
            const sendDate = sendOn === 'later' ? $('#native-date-picker').val() : '';
            const sendMedium = $('input[name="send_medium"]:checked').val();

            if (authUserId === '') {
                showMessage('Sorry! Please sign in to add voucher in basket');
                return false;
            } else if (currentProduct === '') {
                showMessage('Please select an amount');
                return false;
            } else if (sendMedium === 'email' && sendEmail === '') {
                showMessage('Please enter email address');
                return false;
            } else if (sendMedium === 'email' && sendEmail !== '' && !isValidEmail(sendEmail)) {
                showMessage('Please enter a valid email address');
                return false;
            } else if (sendMedium === 'sms' && sendPhone === '') {
                showMessage('Please enter phone number with country code');
                return false;
            } else if (sendMedium === 'sms' && sendPhone !== '' && !isValidJordanianRFPhone(sendPhone)) {
                showMessage('Please enter a valid phone number (9627xxxxxxxx)');
                return false;
            }

            $.ajax({
                url: `{{ url('add-to-cart') }}`,
                type: 'POST',
                data: {
                    product: currentProduct,
                    product_type: 'voucher',
                    quantity: 1,
                    offer: '',
                    send_message: sendMessage,
                    send_type: sendType,
                    send_to: sendTo,
                    send_from: sendFrom,
                    send_email: sendEmail,
                    send_phone: sendPhone,
                    send_medium: sendMedium,
                    send_on: sendOn,
                    send_date: sendDate,
                },
                dataType: 'json',
                beforeSend: () => {
                    $('#add_to_cart_btn').addClass('disabled');
                    $('#add_to_cart_icon').addClass('fa-circle-o-notch fa-spin');
                },
                success: (response) => {
                    $('#add_to_cart_btn').removeClass('disabled');
                    $('#add_to_cart_icon').removeClass('fa-circle-o-notch fa-spin');
                    if (response.result === true) {
                        location.href = '{{ url("/my-basket") }}';
                    } else {
                        showMessage(response.message);
                    }
                }
            });
        };

    </script>
@endpush
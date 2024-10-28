@extends('frontend.layouts.master')
@section('title','Sign In | Gifts Center')
@section('description','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('keywords','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/sign-in.css')}}" rel="stylesheet"/>
    <link href="{{asset('public/css/bootstrap-select@1.13.14.min.css')}}" rel="stylesheet">
    <link href="{{asset('public/css/flag-icon.min.css')}}" rel="stylesheet">
@endsection
@section('main-content')
    <div class="login-sec">
        <div class="login-white-box">
            <h3>MEMBER SIGN IN</h3>
            <h4>Welcome back! Sign in for faster checkout.</h4>
            <div class="small-popup" id="send_otp_div">
                <form id="send_otp_frm" action="" method="post">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="product-tab" data-toggle="tab"
                                    data-target="#mobileTab" type="button" role="tab" aria-controls="mobileTab"
                                    aria-selected="true">Phone
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rate-tab" data-toggle="tab" data-target="#emailTab"
                                    type="button" role="tab" aria-controls="emailTab" aria-selected="false">Email
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="mobileTab" role="tabpanel"
                             aria-labelledby="product-tab">
                            <ul class="row sign-in-list">
                                <!-- Phone Input -->
                                <li class="col-md-5">
                                    <div class="country">
                                        <div class="topLang mobileLang">
                                            <select class="selectpicker" name="country"
                                                    id="send_otp_country" data-live-search="true">
                                                @foreach(Helper::getCountries() as $value)
                                                    <option
                                                            data-content='<span class="flag-icon flag-icon-{{Str::lower($value->country_code)}}"></span> {{ $value->country_name . " (+" . $value->phone_code.")" }}'
                                                            value="{{ $value->country_name }}"
                                                            data-phonecode="{{ $value->phone_code }}" {{ $value->country_code == 'JO' ? 'selected' : '' }}>{{ " +" . $value->phone_code }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </li>
                                <li class="col-md-7">
                                    <label for="send_otp_phone_code" class="sr-only">Country Code</label>
                                    <input type="hidden" id="send_otp_phone_code" value="962" style="width:16%;"
                                           readonly/>
                                    <label for="send_otp_phone" class="sr-only">Phone Number</label>
                                    <input class="form-control send-otp-input" name="" type="tel"
                                           placeholder="Phone Number"
                                           id="send_otp_phone" aria-label="Phone Number"/>
                                </li>
                                <!-- Send Code Button -->
                                <!-- <li class="col-md-12">
                                <a id="send_otp_btn_phone" href="" class="SignBtn send_otp_btn">Send Code</a>
                                </li> -->
                            </ul>
                        </div>
                        <div class="tab-pane fade" id="emailTab" role="tabpanel" aria-labelledby="rate-tab">
                            <ul class="row sign-in-list">
                                <!-- Email Input -->
                                <li class="col-md-12">
                                    <label for="send_otp_email" class="sr-only">Email ID</label>
                                    <input class="form-control send-otp-input" type="email" placeholder="Email ID"
                                           aria-label="Email ID" id="send_otp_email"/>
                                </li>

                            </ul>
                        </div>
                    </div>
                    <ul class="row sign-in-list">
                        <li class="col-md-12">
                            <a id="send_otp_btn" href="" class="btn btn-primary SignBtn send_otp_btn">Send Code</a>
                        </li>
                    </ul>
                </form>
                <p id="send_otp_msg" class="alert-msg small-popup"></p>
            </div>
            <div class="registrPnl small-popup" id="verify_otp_div" style="display:none;">
                <p><i class="icon fa fa-info-circle"></i> Please enter the 6-digit confirmation code you received.
                </p>
                <form id="verify_otp_frm" action="" method="post">
                    <ul class="row sign-in-list">
                        <li class="col-md-12">
                            <input class="form-control" name="" type="text" placeholder="Confirmation Code"
                                   id="verify_otp_code"/>
                        </li>
                    </ul>
                    <ul class="row sign-in-list">

                        <li class="col-md-12" id="verify_otp_li" style="display:none;">
                            <a id="verify_otp_btn" href="" class="btn btn-primary SignBtn">Verify Code</a>
                        </li>
                        <li class="col-md-12" id="resend_otp_li" style="display:none;">
                            <a id="resend_otp_btn" href="" class="btn btn-primary SignBtn">Resend Code</a>
                        </li>
                        <li id="resend_code_timer_li">
                            <p id="resend_code_timer">Resend code <span id="timer"></span></p>
                        </li>

                    </ul>
                    <p id="verify_otp_msg" class="alert-msg small-popup"></p>
                </form>
            </div>
            <div class="registrPnl small-popup" id="sign_up_div" style="display:none;">
                <form id="sign_up_frm" action="" method="post">
                    <ul class="row sign-in-list">
                        <li class="col-md-12">
                            <input class="form-control" name="customer_name" id="sign_up_customer_name" type="text"
                                   placeholder="Your Name" value="" autocomplete="off" class="only-english"/>
                        </li>
                        <li class="col-md-12">
                            <select class="form-control" name="gender" id="sign_up_gender">
                                <option selected="selected" value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                            <input name="company" id="sign_up_company" type="hidden" placeholder="Company" value="">
                        </li>
                        <!--                                        <li>
                        <input name="company" id="sign_up_company" type="text" placeholder="Company" value="">
                        </li>-->
                        <li class="col-md-12">
                            <input class="form-control" name="email" id="sign_up_email" type="email"
                                   placeholder="Email Address"
                                   value="">
                        </li>
                        <li class="col-md-5">
                            <div class="country">
                                <div class="topLang mobileLang">
                                    <select class="selectpicker" name="country"
                                            id="sign_up_country" data-live-search="true">
                                        @foreach(Helper::getCountries() as $value)
                                            <option
                                                    data-content='<span class="flag-icon flag-icon-{{Str::lower($value->country_code)}}"></span> {{ $value->country_name . " (+" . $value->phone_code.")" }}'
                                                    value="{{ $value->country_name }}"
                                                    data-phonecode="{{ $value->phone_code }}" {{ $value->country_code == 'JO' ? 'selected' : '' }}>{{ " +" . $value->phone_code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li class="col-md-7">
                            <input class="form-control" type="hidden" id="sign_up_phone_code" value="962" readonly/>
                            <input class="form-control" name="phone" type="tel" placeholder="Phone Number"
                                   id="sign_up_phone"/>
                        </li>
                        <li class="col-md-4">
                            <select class="form-control" name="dob" id="sign_up_day" class="dob">
                                <option value="00">Day</option>
                                <?php
                                for ($i = 1;
                                     $i <= 31;
                                     $i++) {
                                    $i = str_pad($i, 2, 0, STR_PAD_LEFT);
                                    ?>
                                <option value='<?php echo $i; ?>'><?php echo $i; ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </li>
                        <li class="col-md-4">
                            <select class="form-control" name="dob" id="sign_up_month" class="dob">
                                <option value="00">Month</option>
                                <?php
                                for ($i = 1;
                                     $i <= 12;
                                     $i++) {
                                    $i = str_pad($i, 2, 0, STR_PAD_LEFT);
                                    ?>
                                <option value='<?php echo $i; ?>'><?php echo $i; ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </li>
                        <li class="col-md-4">
                            <select class="form-control" name="dob" id="sign_up_year" class="dob">
                                <option value="0000">Year</option>
                                <?php
                                for ($i = date('Y', strtotime('-100 years'));
                                     $i <= date('Y', strtotime('-18 years'));
                                     $i++) {
                                    ?>
                                <option value='<?php echo $i; ?>'><?php echo $i; ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </li>
                        <li class="col-md-12">
                            <input type="hidden" id="sign_up_customer" name="sign_up_customer" value=""/>
                            <input type="hidden" id="sign_up_regid" name="sign_up_regid" value=""/>
                            <input type="hidden" id="sign_up_type" name="sign_up_type" value=""/>
                            <input type="hidden" id="sign_up_country_name" name="sign_up_country_name" value=""/>
                            <div class="form-group form-check">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="checkbox" name="agree" value="agree_terms"
                                           id="sign_up_agree_terms"/>
                                    <span class="checkmark"></span>
                                    I agree to the <a href="{{ url('/page/terms-and-conditions') }}" target="_blank">Terms
                                        & Conditions</a> and <a href="{{ url('/page/privacy-policy') }}"
                                                                target="_blank">Privacy Policy</a>
                                </label>
                            </div>

                        </li>
                    </ul>
                    <ul class="row sign-in-list">
                        <!-- Email Input -->
                        <li class="col-md-12">
                            <a id="sign_up_btn" href="" class="btn btn-primary SignBtn">Sign Up</a>
                        </li>
                    </ul>
                    <p id="sign_up_msg" class="alert-msg small-popup"></p>
                </form>
            </div>
            @php
                $previousPage = url()->previous();
                $cartCount = Helper::cartCount()
            @endphp
            @if(Str::contains($previousPage, 'my-basket') && $cartCount > 0)
                <div class="bottom-sign-in-sec">
                    <span class="or-span-text">Or</span>
                    <p>Would you like to order as guest?</p>
                    <a href="#" class="guest-btn" id="guest_chckout_btn">CHECKOUT AS GUEST</a>
                </div>
            @endif
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{asset('public/js/bootstrap-select@1.13.14.min.js')}}"></script>
    <script>

        $(function () {
            $(".selectpicker").selectpicker();
        });

        // function countryDropdown(seletor) {
        //     var Selected = $(seletor);
        //     var Drop = $(seletor + '-drop');
        //     var DropItem = Drop.find('li');
        //     Selected.click(function () {
        //         Selected.toggleClass('open');
        //         Drop.toggle();
        //     });
        //     Drop.find('li').click(function () {
        //         Selected.removeClass('open');
        //         Drop.hide();
        //         var item = $(this);
        //         Selected.html(item.html());
        //     });
        //     DropItem.each(function () {
        //         var code = $(this).attr('data-code');
        //         if (code != undefined) {
        //             var countryCode = code.toLowerCase();
        //             $(this).find('i').addClass('flagstrap-' + countryCode);
        //         }
        //     });
        // }
        //
        // countryDropdown('#country');

        // Customer send-otp pop-up
        function showSendOTP() {
            $(".small-popup").hide();
            $("#send_otp_frm")[0].reset();
            $('#send_otp_div').show();
        }

        $(document).on("click", "#send_otp_btn", function (e) {
            e.preventDefault();
            validateSendOTP('send');
        });
        $(document).on("click", "#resend_otp_btn", function (e) {
            e.preventDefault();
            validateSendOTP('resend');
            $('#resend_otp_li').hide();
        });

        $('#send_otp_email').on('input', function () {
            // When typing in the email input, clear the phone input
            $('#send_otp_phone').val('');
        });

        // Add input event listener to the phone input
        $('#send_otp_phone').on('input', function () {
            // When typing in the phone input, clear the email input
            $('#send_otp_email').val('');
        });

        function validateSendOTP(type = 'send') {
            var email = $.trim($('#send_otp_email').val());
            var phone_code = $.trim($('#send_otp_phone_code').val());
            var phone = $.trim($('#send_otp_phone').val());
            var country = $.trim($('#send_otp_country').val());
            var msg_txt = "";
            var error_element = "";
            if (!email && !phone) {
                msg_txt = "Please enter your email address or phone number.";
                error_element = $('#send_otp_email');
            } else if (email && !isValidEmail(email)) {
                msg_txt = "Please enter a valid email address.";
                error_element = $('#send_otp_email');
            } else if (phone && country !== 'Jordan' && !isValidMobile(phone)) {
                msg_txt = "Please enter a valid mobile number.";
                error_element = $('#send_otp_phone');
            } else if (phone && country === 'Jordan' && !isValidJordanianMobile(phone)) {
                msg_txt = "Please enter a valid Jordanian mobile number (79, 78, or 77 without 0).";
                error_element = $('#send_otp_phone');
            }
            if (msg_txt) {
                if (error_element) {
                    error_element.focus();
                }
                if (type === 'resend') {
                    $('#verify_otp_msg').html(msg_txt).show();
                } else {
                    $('#send_otp_msg').html(msg_txt).show();
                }

            } else {
                $.ajax({
                    url: "{{ url('user/send-otp') }}",
                    type: "POST",
                    data: {
                        'email': email,
                        'phone_code': phone_code,
                        'phone': phone,
                        'country': country,
                    },
                    dataType: "json",
                    beforeSend: function () {
                        $('#send_otp_msg').html('').show();
                        $('#send_otp_btn').hide();
                    },
                    complete: function () {
                        $('#send_otp_btn').show();
                    },
                    success: function (response) {
                        if (response.res === true) {
                            if ('customer' in response.data) {
                                sessionStorage.setItem('gc_curr_cust', response.data.customer);
                            }
                            if ('reg_type' in response.data) {
                                sessionStorage.setItem('gc_reg_type', response.data.reg_type);
                            }
                            if ('action' in response.data) {
                                sessionStorage.setItem('gc_action', response.data.action);
                            }
                            // if (type === 'resend') {
                            //     $('#verify_otp_msg').html(response.msg).show('slow');
                            // } else {
                            //     $('#send_otp_msg').html(response.msg).show('slow');
                            // }
                            // setTimeout(showVerifyOTP, 2000);
                            showVerifyOTP();
                        } else {
                            if (type === 'resend') {
                                $('#verify_otp_msg').html(response.msg).show('slow');
                            } else {
                                $('#send_otp_msg').html(response.msg).show('slow');
                            }
                        }
                    },
                    error: function (xhr, status, error) {
                        // Handle the error (optional)
                    }
                });
            }
        }

        $('#send_otp_country').change(function () {
            var phone_code = $(this).find(':selected').attr('data-phonecode');
            $('#send_otp_phone_code').val(phone_code);
        });

        var timer;

        function startTimer() {
            $('#resend_otp_li').hide();
            $('#timer').text('01:00');
            var seconds = 59;
            timer = setInterval(function () {
                var minutes = Math.floor(seconds / 60);
                var remainingSeconds = seconds % 60;

                // Format the timer as mm:ss
                $('#timer').text(
                    (minutes < 10 ? '0' : '') + minutes + ':' + (remainingSeconds < 10 ? '0' : '') + remainingSeconds
                );

                seconds--;

                if (seconds < 0) {
                    clearInterval(timer);
                    $(".small-popup").hide();
                    $('#resend_code_timer_li').hide();
                    $('#verify_otp_div').show();
                    $('#resend_otp_li').show();
                    $('#verify_otp_li').hide();
                    $('#timer').text('');
                }
            }, 1000);
            $('#resend_code_timer_li').show();
        }

        // Customer verify-otp pop-up
        function showVerifyOTP() {
            $(".small-popup").hide();
            $("#verify_otp_frm")[0].reset();
            $('#verify_otp_div').show();
            $('#verify_otp_code').focus();
            startTimer();
        }

        $(document).on('keyup keydown change paste input', "#verify_otp_code", function (e) {
            // Show the button if there is any input in the field
            $('#verify_otp_li').toggle($(this).val().trim() !== '');

            // Check if the pressed key is Enter (key code 13)
            if (e.keyCode === 13) {
                // Prevent the default action of the Enter key (form submission)
                e.preventDefault();
                // Call the validateVerifyOTP() function
                validateVerifyOTP();
            }
        });
        $(document).on("click", "#verify_otp_btn", function (e) {
            e.preventDefault();
            validateVerifyOTP();
        });

        function validateVerifyOTP() {
            // alert("trst");
            // sessionStorage.setItem('curr_cust', 'test_value');
            var otp = $.trim($('#verify_otp_code').val());// alert(otp);
            var customer = sessionStorage.getItem('gc_curr_cust'); //alert(customer);
            var action = sessionStorage.getItem('gc_action'); //alert(customer);
            var msg_txt = "";
            var error_element = "";
            if (!otp) {
                msg_txt = "Please enter your confirmation code.";
                error_element = $('#verify_otp_code');
            } else if (isNaN(otp) || otp < 1) {
                msg_txt = "Please enter a valid confirmation code.";
                error_element = $('#verify_otp_code');
            }
            if (msg_txt) {
                if (error_element) {
                    error_element.focus();
                }
                $('#verify_otp_msg').html(msg_txt).show();
            } else {
                $.ajax({
                    url: "{{ url('user/verify-otp') }}",
                    type: "POST",
                    data: {
                        'otp': otp,
                        'customer': customer,
                        'action': action,
                    },
                    dataType: "json",
                    beforeSend: function () {
                        $('#verify_otp_msg').html('').show();
                        $('#verify_otp_btn').hide();
                    },
                    complete: function () {

                    },
                    success: function (response) {
                        if (response.res == true) {
                            $('#verify_otp_msg').html(response.msg).show();
                            sessionStorage.setItem('gc_curr_cust', JSON.stringify(response.data));
                            if (action == 'login') {
                                var previousUrl = "{{ url()->previous() }}";
                                window.location.href = previousUrl;
                            } else {
                                clearInterval(timer);
                                setTimeout(showSignUp, 2000);
                            }
                        } else {
                            $('#verify_otp_msg').html(response.msg).show();
                            $('#verify_otp_btn').show();
                        }
                    }
                });
            }
        }

        //customer sign-up pop-up
        function showSignUp() {
            $(".small-popup").hide();
            $("#sign_up_frm")[0].reset();
            $('#sign_up_frm :input:first').focus();
            $('#sign_up_div').show();
            var reg_type = sessionStorage.getItem('gc_reg_type');
            var customer = JSON.parse(sessionStorage.getItem('gc_curr_cust'));
            var gender = customer.gender != '' ? customer.gender : 'male';
            var country = customer.country != '' ? customer.country : 'Jordan';
            var phone_code = customer.phone_code != '' ? customer.phone_code : '962';
            $('#sign_up_regid').val(customer.reg_id);
            $('#sign_up_customer').val(customer.customer_id);
            $('#sign_up_customer_name').val(customer.customer_name);
            $('#sign_up_email').val(customer.email);
            $('#sign_up_phone_code').val(phone_code);
            $('#sign_up_phone').val(customer.phone);
            $('#sign_up_gender').val(gender);
            console.log(country);
            $('#sign_up_country').val(country);
            $('#sign_up_country_name').val(country);
            $('#sign_up_type').val(reg_type);
//            if (reg_type == 'email') {
//                $('#sign_up_email').attr("readonly", "readonly");
//            }
//            if (reg_type == 'phone') {
//                $('#sign_up_country').attr("disabled", "disabled");
//                $('#sign_up_phone_code').attr("readonly", "readonly");
//                $('#sign_up_phone').attr("readonly", "readonly");
//            }
            sessionStorage.removeItem('gc_reg_type');
            sessionStorage.removeItem('gc_curr_cust');
        }

        $(document).on("click", "#sign_up_btn", function (e) {
            e.preventDefault();
            validateSignUp();
        });

        function validateSignUp() {
            var regid = $.trim($('#sign_up_regid').val());
            var type = $.trim($('#sign_up_type').val());
            var customer = $.trim($('#sign_up_customer').val());
            var customer_name = $.trim($('#sign_up_customer_name').val());
            var email = $.trim($('#sign_up_email').val());
            var gender = $.trim($('#sign_up_gender').val());
            var country = $.trim($('#sign_up_country').val());
            var company = $.trim($('#sign_up_company').val());
            var phone_code = $.trim($('#sign_up_phone_code').val());
            var phone = $.trim($('#sign_up_phone').val());
            var day = $.trim($('#sign_up_day').val());
            var month = $.trim($('#sign_up_month').val());
            var year = $.trim($('#sign_up_year').val());
            var dob = year + '-' + month + '-' + day;
            var accept_terms = $('#sign_up_agree_terms');
            var msg_txt = "";
            var error_element = "";
            if (customer_name === "") {
                msg_txt = "Please enter your name.";
                error_element = $('#sign_up_customer_name');
            } else if (email === "") {
                msg_txt = "Please enter your email addresss.";
                error_element = $('#sign_up_email');
            } else if (email && !isValidEmail(email)) {
                msg_txt = "Please enter a valid email address.";
                error_element = $('#sign_up_email');
            } else if (country === "") {
                msg_txt = "Please select country.";
                error_element = $('#sign_up_country');
            } else if (phone === "") {
                msg_txt = "Please enter your mobile number.";
                error_element = $('#sign_up_phone');
            } else if (phone && country !== 'Jordan' && !isValidMobile(phone)) {
                msg_txt = "Please enter a valid mobile number.";
                error_element = $('#sign_up_phone');
            } else if (phone && country === 'Jordan' && !isValidJordanianMobile(phone)) {
                msg_txt = "Please enter a valid Jordanian mobile number (79, 78, or 77 without 0).";
                error_element = $('#sign_up_phone');
            } else if (!accept_terms.is(':checked')) {
                msg_txt = "Please agree our terms and conditions,privacy policy.";
                error_element = $('#sign_up_agree_terms');
            }

            if (msg_txt !== "") {
                if (error_element !== '') {
                    error_element.focus();
                }
                $('#sign_up_msg').html(msg_txt).show();
            } else {
                $.ajax({
                    url: "{{ url('user/sign-up') }}",
                    type: "POST",
                    data: {
                        'regid': regid,
                        'type': type,
                        'customer': customer,
                        'customer_name': customer_name,
                        'company': company,
                        'gender': gender,
                        'email': email,
                        'country': country,
                        'phone_code': phone_code,
                        'phone': phone,
                        'dob': dob,
                    },
                    dataType: "json",
                    beforeSend: function () {
                        $('#sign_up_msg').html('').show();
                        $('#sign_up_btn').hide();
                    },
                    complete: function () {
                        $('#sign_up_btn').show();
                    },
                    success: function (response) {

                        if (response.result == '1') {
                            $('#sign_up_msg').html(response.msg).show();
                            setTimeout(function () {
                                var previousUrl = "{{ url()->previous() }}";
                                window.location.href = previousUrl;
                            }, 2000);
                        } else {
                            $('#sign_up_msg').html(response.msg).show();
                        }
                    }
                });
            }
        }

        $('#sign_up_country').change(function () {
            var phone_code = $(this).find(':selected').attr('data-phonecode');
            var country = $(this).val();
            $('#sign_up_phone_code').val(phone_code);
            $('#sign_up_country_name').val(country);
        });

        function showSignIn() {
            $(".small-popup").hide();
            $("#sign_in_frm")[0].reset();
            var container = $('#sign_in_div');
            container.show();
            $(document).bind('mouseup', function (e) {
                if (!container.is(e.target) && container.has(e.target).length === 0) {
                    container.hide();
                }
            });
        }

        $(document).on("click", "#guest_chckout_btn", function (e) {
            e.preventDefault();
            proceedToCheckout('1');
        });

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

        //validating jordanion phone number starting with '07' or '7'
        function validatePhone(txtPhone) {
            var filter = /^((07)|(7))[0-9]{8,9}$/;
            if (filter.test(txtPhone)) {
                return true;
            } else {
                return false;
            }
        }

        //validating jordanion phone number starting with '9627'
        function validateRFPhone(txtPhone) {
            var filter = /^(9627)[0-9]{8}$/;
            if (filter.test(txtPhone)) {
                return true;
            } else {
                return false;
            }
        }

        //validating jordanion phone number starting with '07'
        function validateDeliveryPhone(txtPhone) {
            var filter = /^((06)|(07)|(00)|(\+9))[0-9]{8,12}$/;
            if (filter.test(txtPhone)) {
                return true;
            } else {
                return false;
            }
        }

        //validating mobile number
        function validateMobile(txtPhone) {
            var filter = /^[0-9]{8,15}$/;
            if (filter.test(txtPhone)) {
                return true;
            } else {
                return false;
            }
        }

        function validateEmail(sEmail) {
            var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
            if (filter.test(sEmail)) {
                return true;
            } else {
                return false;
            }
        }

        // $(document).on("click", ".nav-link", function () {
        //     const tabId = $(this).attr("data-target");
        //     console.log('clicked on :'+tabId)
        //     tabId === "#emailTab" && $('#send_otp_phone').val('');
        //     tabId === "#mobileTab" && $('#send_otp_email').val('');
        // });

        $(document).on('input', '.send-otp-input', function () {
            $('#send_otp_msg').html('').hide('slow');
        });
    </script>
@endpush
@extends('frontend.layouts.master')
@section('title','My Account | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/my-account.css')}}" rel="stylesheet"/>
    <link href="{{asset('public/css/sign-in.css')}}" rel="stylesheet"/>
    <link href="{{asset('public/css/bootstrap-select@1.13.14.min.css')}}" rel="stylesheet">
    <link href="{{asset('public/css/flag-icon.min.css')}}" rel="stylesheet">
@endsection

@section('main-content')
    <div class="container custom-max-container">
        <div class="after-login-white-sec">
            <h3>My Account</h3>
            <div class="my-account-box">
                <h4>My Account</h4>

                <form action="" method="post" class="chk_form" id="save_profile_frm">

                    <ul class="my-account-form-list row">

                        <li class="col-md-4">
                            <input name="customer_id" class="form-control" type="text"
                                   value="{{ isset($user['customer_id']) ? $user['customer_id'] : '' }}"
                                   readonly="readonly" aria-label="crm-6">
                        </li>

                        <li class="col-md-4">
                            <input class="form-control" name="customer_name" id="my_acc_customer_name" type="text"
                                   value="{{ isset($user['customer_name']) ? $user['customer_name'] : '' }}"
                                   class="only-english" placeholder="*" aria-label="Taha Abu dabbour"/>
                        </li>

                        <li class="col-md-4">
                            <input class="form-control" name="email" id="my_acc_email" type="email"
                                   value="{{ isset($user['email']) ? $user['email'] : '' }}"
                                   placeholder="*" aria-label="taha.db@gmail.com"/>
                        </li>
                        <li class="col-md-3">
                            <div class="country">
                                <div class="topLang mobileLang">
                                    <select class="selectpicker" name="country"
                                            id="my_acc_country" data-live-search="true">
                                        @foreach(Helper::getCountries() as $value)
                                            <option
                                                    data-content='<span class="flag-icon flag-icon-{{Str::lower($value->country_code)}}"></span> {{ $value->country_name . " (+" . $value->phone_code.")" }}'
                                                    value="{{ $value->country_name }}"
                                                    data-phonecode="{{ $value->phone_code }}" {{ $value->country_name == $user['country'] ? 'selected' : '' }}>{{ " +" . $value->phone_code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li class="col-md-5">

                            <input class="form-control" name="phone" id="my_acc_phone" type="tel"
                                   value="{{ isset($user['phone']) ? $user['phone'] : '' }}"
                                   placeholder="*" aria-label="788266268"/>
                            <input type="hidden" id="my_acc_phone_code" name="phone_code"
                                   value="{{ isset($user['phone_code']) ? $user['phone_code'] : '' }}"/>
                        </li>
                        <!-- <li class="col-md-4">
                <input class="form-control" placeholder="City" aria-label="City"  name="city" type="text" id="city" value="{{ isset($user['city']) ? $user['city'] : '' }}">
              </li> -->
                        <li class="col-md-4">
                            <select id="my_acc_gender" name="gender" class="form-control select-control">
                                <option value="">Select</option>
                                <option value="male" {{ $user['gender'] == 'male' ? 'selected' : '' }}>Male
                                </option>
                                <option value="female" {{ $user['gender'] == 'female' ? 'selected' : '' }}>
                                    Female
                                </option>
                            </select>
                        </li>
                        <li class="col-md-4">
                            <select class="dob form-control select-control" name="day" id="my_acc_day">
                                <option value="00">Day</option>
                                @for ($i = 1; $i <= 31; $i++)
                                    <option value='{{ str_pad($i, 2, 0, STR_PAD_LEFT) }}' {{ $dd == $i ? 'selected' : '' }}>{{ str_pad($i, 2, 0, STR_PAD_LEFT) }}</option>
                                @endfor
                            </select>
                        </li>
                        <li class="col-md-4">
                            <select class="form-control select-control dob" name="month" id="my_acc_month">
                                <option value="00">Month</option>
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value='{{ str_pad($i, 2, 0, STR_PAD_LEFT) }}' {{ $mm == $i ? 'selected' : '' }}>{{ str_pad($i, 2, 0, STR_PAD_LEFT) }}</option>
                                @endfor
                            </select>
                        </li>
                        <li class="col-md-4">
                            <select class="form-control select-control dob" name="year" id="my_acc_year">
                                <option value="0000">Year</option>
                                @for ($i = date('Y', strtotime('-100 years')); $i <= date('Y', strtotime('-18 years')); $i++)
                                    <option value='{{ $i }}' {{ $yyyy == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </li>
                        <li class="col-md-12">
                            <!-- <input type="button" name="" value="submit" class="btn btn-primary" /> -->

                            <a href="javascript:void(0);" id="save_profile_btn" class="btn btn-primary">Submit</a>

                        </li>
                    </ul>

                </form>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{asset('public/js/bootstrap-select@1.13.14.min.js')}}"></script>
    <script type="text/javascript">
        $(function () {
            $(".selectpicker").selectpicker();
        });

        $(document).ready(function () {
            $('#save_profile_btn').on('click', function () {

                validateMyAccount();
            });
        });

        //Customer checkout
        function validateMyAccount() {

            var customer_name = $.trim($('#my_acc_customer_name').val());
            var email = $.trim($('#my_acc_email').val());
            var gender = $.trim($('#my_acc_gender').val());
            var country = $.trim($('#my_acc_country').val());
            var phone_code = $.trim($('#my_acc_phone_code').val());
            var phone = $.trim($('#my_acc_phone').val());
            var day = $.trim($('#my_acc_day').val());
            var month = $.trim($('#my_acc_month').val());
            var year = $.trim($('#my_acc_year').val());
            var dob = year + '-' + month + '-' + day;
            // var opassword = $.trim($('#my_acc_opassword').val());
            // var npassword = $.trim($('#my_acc_npassword').val());
            // var cpassword = $.trim($('#my_acc_cpassword').val());
            var msg_txt = "";
            var error_element = "";

            if (customer_name === "") {
                msg_txt = "Please enter your name.";
                error_element = $('#my_acc_customer_name');
            } else if (email === "") {
                msg_txt = "Please enter your email addresss.";
                error_element = $('#my_acc_email');
            } else if (email != '' && !(validateEmail(email))) {
                msg_txt = "Please enter valid email addresss.";
                error_element = $('#my_acc_email');
            } else if (country === "") {
                msg_txt = "Please select country.";
                error_element = $('#my_acc_country');
            } else if (phone === "") {
                msg_txt = "Please enter your mobile number.";
                error_element = $('#my_acc_phone');
            } else if (phone != '' && country != 'Jordan' && !(validateMobile(phone))) {
                msg_txt = "Please enter valid mobile number.";
                error_element = $('#my_acc_phone');
            } else if (phone != '' && country == 'Jordan' && !(validatePhone(phone))) {
                msg_txt = "Please enter valid mobile number (07XXXXXXXX).";
                error_element = $('#my_acc_phone');
            }
            //else if (npassword !== "" && opassword === "") {
            //     msg_txt = "Please enter old password.";
            //     error_element = $('#my_acc_opassword');
            // } else if (npassword !== "" && cpassword === "") {
            //     msg_txt = "Please enter confirm password.";
            //     error_element = $('#my_acc_cpassword');
            // } else if (npassword !== "" && cpassword !== "" && npassword !== cpassword) {
            //     msg_txt = "Confirm password does not match password.";
            //     error_element = $('#my_acc_cpassword');
            // }

            if (msg_txt !== "") {
                if (error_element !== '') {
                    $(error_element).focus();
                }
                // showMessage(msg_txt);
            } else {
                $.ajax({

                    url: "{{ url('save-profile') }}",
                    type: "POST",
                    data: new FormData($('#save_profile_frm')[0]), // The form with the file    inputs.
                    processData: false, // Using FormData, no need to process data.
                    contentType: false,
                    dataType: 'json',
                    beforeSend: function () {
                        $('#save_profile_btn').hide('slow');
                    },
                    complete: function () {
                        $('#save_profile_btn').show('slow');
                    },
                    success: function (response) {
                        if (response.result == '1') {
                            console.log(response.msg);
                            showMessage(response.msg);
                            console.log('ok');
                            setTimeout(function () {
                                location.reload();
                            }, 2000);
                        } else {
                            console.error('Warning: data is missing in the response');
                        }
                    },
                    error: function () {

                        console.error('An error occurred while processing your request.');
                    }
                });
            }
        }

        jQuery(document).on("change", '#my_acc_country', function () {
            var phone_code = jQuery(this).find(':selected').attr('data-phonecode');
            jQuery('#my_acc_phone_code').val(phone_code);
        });

        //validating jordanion phone number starting with '07' or '7'
        function validatePhone(txtPhone) {
            var filter = /^((07)|(7))[0-9]{8,9}$/;
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


    </script>
@endpush

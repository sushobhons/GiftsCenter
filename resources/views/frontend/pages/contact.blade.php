@extends('frontend.layouts.master')

@section('title','Contact Us | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/brand.css')}}" rel="stylesheet"/>
@endsection
        @section('main-content')
        <div class="container">
            <div class="brands-white-sec">
                <div class="row">
                    <div class="col-md-6 col-sm-6 col-xs-12">
                        <form action="{{ url('/contact-us') }}" method="post" class="chk_form3" onSubmit="return validateContactForm();">
                        @csrf 
                            <!--<h2>Contact Us</h2>-->
                            <div class="alert alert-danger alert-dismissable" @if ($error_msg == "") style="display:none;" @endif>
                            {{ $error_msg }}
                            </div>
                            <div class="alert alert-success alert-dismissable" @if ($success_msg == "") style="display:none;" @endif>
                            {{ $success_msg }}
                            </div>
                            <ul class="row contForm">
                                <li class="col-md-12"> 
                                    <!--<label>First Name<span>*</span></label>-->
                                    <input name="fname" id="fname" type="text" placeholder="First Name" value="{{ old('fname') }}" class="form-control" />
                                </li>
                                <li class="col-md-12"> 
                                    <!--<label>Last Name<span>*</span></label>-->
                                    <input name="lname" id="lname" type="text" placeholder="Last Name" value="{{ old('lname') }}" class="form-control" />
                                </li>
                                <li class="col-md-12"> 
                                    <select name="country" id="contactus_country" class="form-select">
                                        <option value="">Select Country</option>
                                        <option value="Jordan">Jordan</option>
                                    </select>
                                </li>
                                <li class="col-md-4 col-lg-3"> 
                                    <input name="phone_code" type="text" id="contactus_phone_code" value="962" readonly class="form-control"/>
                                </li>
                                <li class="col-md-8 col-lg-9">
                                    <input name="phone" id="phone" type="text" placeholder="Phone No" value="{{ old('phone') }}" class="form-control" />
                                </li>
                                <li class="col-md-12"> 
                                    <!--<label>Email Address<span>*</span></label>-->
                                    <input name="email" id="email" type="mail" placeholder="Email Address" value="{{ old('email') }}" class="form-control" />
                                </li>
                                <li class="col-md-12"> 
                                    <!--<label>Message<span>*</span></label>-->
                                    <textarea name="message" id="message" cols="" rows="3" placeholder="Type your message here.." value="{{ old('message') }}" class="form-control"></textarea>
                                </li>
                                <li class="col-md-12">
                                    <input name="contact" id="send-btn"  type="submit" value="Submit" class="btn btn-primary" />
                                </li>
                            </ul>
                        </form>
                    </div>
                    <div class="col-md-6 col-sm-6 col-xs-12">
                        <!--<ul class="conAdrs">
                            <li><i class="fa fa-phone"></i> 079 - 888 99 77</li>
                            <li><i class="fa fa-envelope"></i> <a href="mailto:info@gifts-center.com">info@gifts-center.com</a></li>
                            <li><i class="fa fa-map-signs"></i> Amman, Jordan </li>
                        </ul>-->
                        <ul class="conAdrs">
                            <li>
                                <h2>Contact</h2>
                                <p>For questions and suggestions related to online purchase</p>
                            </li>
                            <li>
                                <p><i class="fa fa-phone"></i> <strong>Phone</strong> : 079-1 88 99 66 | Beauty Advisor</p>
                                <p>Saturday to Thursday 10am - 9pm</p>
                                <p>Friday 2pm - 7pm</p>
                            </li>
                            <li>
                                    <p><i class="fa fa-phone"></i> <strong>Phone</strong> : 079-888 99 77 Ext 504 | Support & Logistics</p>
                                <p>Saturday to Thursday 10am - 9pm</p>
                            </li>
                            <li>
                                <p><i class="fa fa-envelope"></i> <strong>Email</strong> : <a href="mailto:info@gifts-center.com">info@gifts-center.com</a></p>
                                <p>Saturday to Thursday 8am - 9pm</p>
                                <p>Friday 2pm - 7pm</p>
                            </li>
                            <!--<li>
                                <p><i class="fa fa-comments"></i> <strong>Chat</strong></p>
                                <p>Saturday to Thursday 10am - 9pm</p>
                                <p>Friday 2pm - 7pm</p>
                            </li>-->
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endsection
        <script>
        //customer contact form
        function validateContactForm() {
            var fname = $.trim($('#fname').val());
            var lname = $.trim($('#lname').val());
            var email = $.trim($('#email').val());
            var phone_code = $.trim($('#contactus_phone_code').val());
            var country = $.trim($('#contactus_country').val());
            var phone = $.trim($('#phone').val());
            var message = $.trim($('#message').val());
            var msg_txt = "";
            var error_element = "";

            if (fname === "") {
                msg_txt = "Please enter your first name.";
                error_element = $('#fname');
            } else if (lname === "") {
                msg_txt = "Please enter your last name.";
                error_element = $('#lname');
            } else if (phone === "") {
                msg_txt = "Please enter your phone number.";
                error_element = $('#phone');
            } else if (phone != '' && country != 'Jordan' && !(validateMobile(phone))) {
                msg_txt = "Please enter valid mobile number.";
                error_element = $('#phone');
            } else if (phone != '' && country == 'Jordan' && !(validatePhone(phone))) {
                msg_txt = "Please enter valid mobile number (07XXXXXXXX).";
                error_element = $('#phone');
            } else if (email === "") {
                msg_txt = "Please enter your email addresss.";
                error_element = $('#email');
            } else if (email != '' && !(validateEmail(email))) {
                msg_txt = "Please enter valid email addresss.";
                error_element = $('#email');
            } else if (message === "") {
                msg_txt = "Please enter your message.";
                error_element = $('#password');
            }

            if (msg_txt !== "") {
                if (error_element !== '') {
                    error_element.focus();
                }
                showMessage(msg_txt);
                return false;
            } else {
                $('#send-btn').hide();
                return true;
            }
        }

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

        //validating jordanion phone number starting with '07' or '7'
        function validatePhone(txtPhone) {
        var filter = /^((07)|(7))[0-9]{8,9}$/;
        if (filter.test(txtPhone)) {
        return true;
        } else {
        return false;
        }
        }
        </script>
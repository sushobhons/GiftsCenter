<meta charset="UTF-8">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="@yield('description')">
<meta name="keywords" content="@yield('keywords')">
<title>@yield('title')</title>

<!--<meta name="facebook-domain-verification" content="an0sbufeqpp634zocdhhuibtwe4uvp" />-->
<meta name="facebook-domain-verification" content="u04u9uu9ff7zo6evwlamp3bafoh4wx" />
<meta name="google-site-verification" content="hBEzV9KTsoHkCSTEr6IVKWj31z_6TU5WVxeIWB-xTXI" />

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Mulish:wght@200;300;400;500;600;700;800;900;1000&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Tenor+Sans&display=swap" rel="stylesheet">

<!-- Bootstrap core CSS -->
<link href="{{ asset('public/assets/dist/css/bootstrap.min.css') }}" rel="stylesheet">

<!-- Owl Carousel CSS -->
<link href="{{ asset('public/assets/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">
<link href="{{ asset('public/assets/owlcarousel/assets/owl.theme.default.min.css') }}" rel="stylesheet">

<!-- Additional CSS -->
<link href="{{ asset('public/css/scrollBar.css') }}" rel="stylesheet">
<link href="{{ asset('public/css/jquery-confirm.min.css') }}" rel="stylesheet">
<link href="{{ asset('public/css/font-awesome.min.css') }}" rel="stylesheet">

<!-- Custom styles -->
<link href="{{ asset('public/css/style.css') }}" rel="stylesheet">
<link href="{{ asset('public/css/responsive.css') }}" rel="stylesheet">

@stack('styles')
<!-- Google tag (gtag.js) -->

<script async src="https://www.googletagmanager.com/gtag/js?id=G-PQ97858GBH"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){ dataLayer.push(arguments);}
    gtag('js', new Date());

  //  gtag('config', 'G-3NC6MZ8024');G-PQ97858GBH
// gtag('config', 'G-PQ97858GBH');
  gtag('config', 'G-PQ97858GBH');
  gtag('config', 'AW-866317736');
  
</script>

<!-- Facebook Pixel Code -->
<!--<script>
    !function (f, b, e, v, n, t, s)
    {
        if (f.fbq)
            return;
        n = f.fbq = function () {
            n.callMethod ?
                n.callMethod.apply(n, arguments) : n.queue.push(arguments)
        };
        if (!f._fbq)
            f._fbq = n;
        n.push = n;
        n.loaded = !0;
        n.version = '2.0';
        n.queue = [];
        t = b.createElement(e);
        t.async = !0;
        t.src = v;
        s = b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t, s)
    }(window, document, 'script',
        'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '253952848549556');
    fbq('track', 'PageView');
</script>
<noscript>
    <img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=253952848549556&ev=PageView&noscript=1"/>
</noscript>-->
<!-- End Facebook Pixel Code -->


<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window,document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '1223118322163435'); 
fbq('track', 'PageView');
</script>
<noscript>
<img height="1" width="1" 
src="https://www.facebook.com/tr?id=1223118322163435&ev=PageView
&noscript=1"/>
</noscript>
<!-- End Facebook Pixel Code -->

<!DOCTYPE html>
<html lang="en">
<head>
	@include('frontend.layouts.head')
	@yield('custom-styles')
</head>
<body id="navbarCollapse" class="body-class">
	
	<!-- Preloader -->
{{--	<div class="preloader">--}}
{{--		<div class="preloader-inner">--}}
{{--			<div class="preloader-icon">--}}
{{--				<span></span>--}}
{{--				<span></span>--}}
{{--			</div>--}}
{{--		</div>--}}
{{--	</div>--}}
	<!-- End Preloader -->
	
{{--	@include('frontend.layouts.notification')--}}
	<main role="main" id="main">
		<!-- Header -->
		@include('frontend.layouts.header')
		<!--/ End Header -->
		@yield('main-content')
	
		@include('frontend.layouts.footer')
	</main>
</body>
</html>
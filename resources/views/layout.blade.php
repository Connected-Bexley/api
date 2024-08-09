<!DOCTYPE html>
<html class="govuk-template" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- CSRF Token -->
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>@yield('title', config('app.name', 'Laravel'))</title>

	<!-- Favicon -->
	@include('partials.favicons')

	<!-- Styles -->
	<link href="{{ asset('css/app.css') }}" rel="stylesheet">
	@yield('css')
</head>

<body class="govuk-template__body js-enabled govuk-frontend-supported">
	<header class="govuk-header" role="banner" data-module="header">
		<div class="govuk-header__container govuk-width-container">

			<div class="govuk-header__logo">
				<a href="{{ route('home') }}" class="govuk-header__link govuk-header__link--homepage">
					<span class="govuk-header__logotype">
						<img src="{{ asset('/img/logo.png') . '?' . filemtime(public_path('img/logo.png')) }}"
							class="govuk-header__logotype-crown" alt="{{ config('app.name', 'Laravel') }} logo"
							title="{{ config('app.name', 'Laravel') }}" />
					</span>
				</a>
			</div>

			<div class="govuk-header__content">
				<a href="{{ route('home') }}" class="govuk-header__link govuk-header__link--service-name">
					{{ config('app.name') }}
				</a>
			</div>

		</div>
		@if (config('app.env') === 'staging')
		@include('partials.environment-warning')
		@endif
	</header>

	<div class="govuk-width-container">
		<main class="govuk-main-wrapper " id="main-content" role="main">
			@yield('content')
		</main>
	</div>

	<footer class="govuk-footer" role="contentinfo">
		<div class="govuk-width-container">
			<div class="govuk-footer__meta">
				<div class="govuk-footer__meta-item">
					Powered by
					<a class="govuk-footer__link" href="https://ayup.agency/">Ayup Connect</a>
				</div>
			</div>
		</div>
	</footer>

	@yield('js')
</body>
</html>

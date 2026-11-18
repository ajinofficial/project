<link rel="stylesheet" href="{{ asset('css/localization.css') }}?v={{ rawurlencode(config('app.asset_version')) }}">
<script src="{{ asset('js/locales/'.app()->getLocale().'.js') }}?v={{ rawurlencode(config('app.asset_version')) }}-{{ filemtime(public_path('js/locales/'.app()->getLocale().'.js')) }}"></script>
<script src="{{ asset('js/localization.js') }}?v={{ rawurlencode(config('app.asset_version')) }}"></script>

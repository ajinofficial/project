<details class="language-menu">
    <summary class="language-menu__trigger" aria-label="{{ __('common.language') }}" title="{{ __('common.language') }}">
        <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 5h12M10 3v2M6 5c1 5 4 8 8 10M14 5c-1 5-4 8-9 11M13 21l4-10 4 10M14.5 17h5" />
        </svg>
    </summary>
    <form class="language-selector language-menu__panel" method="POST" action="{{ route('language.update') }}">
    @csrf
    <input type="hidden" name="return_to" value="{{ request()->getPathInfo().(request()->getQueryString() ? '?'.request()->getQueryString() : '') }}">
    <p class="language-menu__heading">{{ __('common.language') }}</p>
        @foreach (config('localization.supported') as $code => $label)
            <button type="submit" name="locale" value="{{ $code }}" lang="{{ $code }}" @class(['language-menu__option', 'is-current' => app()->getLocale() === $code]) @if(app()->getLocale() === $code) aria-current="true" @endif>
                <span>{{ $label }}</span>
                @if(app()->getLocale() === $code)
                    <svg viewBox="0 0 20 20" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m4 10 4 4 8-8" /></svg>
                @endif
            </button>
        @endforeach
    </form>
</details>

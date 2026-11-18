<details class="product-translations">
    <summary>{{ __('products.translations') }}</summary>
    <p>{{ __('products.translation_help') }}</p>
    @foreach (config('localization.supported') as $locale => $language)
        <fieldset class="product-form-grid" lang="{{ $locale }}">
            <legend>{{ $language }}</legend>
            @foreach (['name', 'category', 'description'] as $field)
                <label>
                    <span>{{ __('products.'.$field) }}</span>
                    @if ($field === 'description')
                        <textarea name="translations[{{ $locale }}][{{ $field }}]" maxlength="5000">{{ old('translations.'.$locale.'.'.$field, $product->translations[$locale][$field] ?? '') }}</textarea>
                    @else
                        <input name="translations[{{ $locale }}][{{ $field }}]" maxlength="{{ $field === 'name' ? 255 : 120 }}" value="{{ old('translations.'.$locale.'.'.$field, $product->translations[$locale][$field] ?? '') }}">
                    @endif
                    @error('translations.'.$locale.'.'.$field) <small>{{ $message }}</small> @enderror
                </label>
            @endforeach
        </fieldset>
    @endforeach
</details>

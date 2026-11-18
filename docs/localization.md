# Application localization

This application uses Laravel 10.50.2's built-in translator. English (`en`) is the default and fallback; Tamil (`ta`), Hindi (`hi`), and Malayalam (`ml`) are enabled. Malayalam covers every English PHP and JSON catalog entry, including validation, emails, notifications, and browser messages. Brand names and technical identifiers remain recognizable. No translation package or external translation service is required. The existing tenant permissions, authentication routes, canonical product fields, identifiers, and financial calculations remain in use.

The complete source inventory is in [localization-files.md](localization-files.md).

**Architecture**

| Location | Responsibility |
| --- | --- |
| `config/localization.php` | Supported language whitelist, native language names, encrypted preference cookie name and lifetime |
| `config/app.php` | Existing `locale` and `fallback_locale` both remain `en` |
| `app/Http/Middleware/SetLocale.php` | Resolve and validate locale, set Laravel and Carbon locales, persist session locale, set the response language header |
| `app/Support/LocalePreference.php` | Remember selections and reconcile an explicit guest selection with the saved preference on login |
| `app/Http/Controllers/LanguageController.php` | Validate and save a selection; redirect safely within the application |
| `app/Http/Kernel.php` | Laravel 10 registration immediately after `StartSession`, before controllers and view rendering |
| `app/Exceptions/Handler.php` | Start the cookie/session/locale pipeline for unmatched HTML requests without adding a wildcard route or changing 404/405 routing semantics |
| `lang/{en,ta,hi,ml}/*.php` | Module catalogs: common, auth, dashboard, products, orders, settings, validation, notifications |
| `lang/{en,ta,hi,ml}.json` | Laravel string-key translations for existing enum labels, legacy notification titles, and compatible dynamic labels |
| `resources/views/partials/language-selector.blade.php` | Accessible translation-icon dropdown with native-language choices, current-language checkmark, CSRF token, and current application-relative path and query |
| `resources/views/partials/localization.blade.php` | Load the selected browser dictionary and shared translation functions before inline page scripts |
| `public/js/localization.js` | `window.t(key, replacements)` and localized browser form-validation messages |
| `app/Console/Commands/ExportTranslations.php` | Export PHP and JSON catalogs, with English fallbacks, to cacheable browser assets |
| `public/css/localization.css` | Selector styling and responsive multilingual typography |

Static Blade text uses escaped `{{ __('module.key') }}` expressions. PHP messages use the same `__()` helper. A key can contain named placeholders, for example `__('common.available_only', ['quantity' => 5])`. Existing migrated keys include a readable English slug and a short stable suffix; new keys can use descriptive names directly. Keep keys stable when editing wording.

Frontend messages use `window.t('module.key', {quantity: 5})`. Browser dictionaries are exported from Laravel's catalogs and include English fallback entries. They are ordinary static assets and can use normal web-server/CDN compression and caching. No language catalog queries run during rendering. The middleware reads the authenticated user already resolved by Laravel's guard rather than issuing a second preference query. Product JSON translations require no translation-table joins.

**Language-switch lifecycle**

1. A page request enters the `web` middleware group. Laravel decrypts cookies and starts the session.
2. `SetLocale` chooses a valid authenticated user's `preferred_locale`, then the session's `locale`, then the encrypted language cookie, then English. Unsupported stored values resolve to English. The middleware sets Laravel and Carbon before controller validation or rendering.
3. The header translation icon opens the language choices. Selecting a language submits `POST /language` with `_token`, `locale`, and `return_to`. The native dropdown and submit buttons also work without JavaScript; JavaScript adds dismissal on outside click and Escape.
4. Laravel checks CSRF and throttling. The controller validates `locale` against the configured whitelist, saves the session selection, and queues an encrypted, HTTP-only, SameSite=Lax preference cookie for one year. For authenticated requests it updates only the current user's preference, and only when changed.
5. The controller returns a 303 redirect to a validated application-relative path, retaining its query string. Absolute URLs, protocol-relative URLs, encoded leading slashes, backslashes, and control characters are rejected. The selector excludes the installation base path, so XAMPP/subdirectory installations avoid duplicated base URLs. Unsafe destinations fall back to the application home.
6. On login, an explicit selection made by the guest takes precedence and is saved to that account. Otherwise, the account's saved preference is restored. Registration also saves the current selection. Session IDs are regenerated as part of authentication.
7. Logout invalidates the authentication session and regenerates the CSRF token, then restores only the language selection into the new session and preference cookie. A later anonymous visit can recover the cookie preference; a subsequent authenticated visit can recover the database preference.

The cookie is encrypted by Laravel's existing middleware, remains unavailable to browser JavaScript, and follows the application's cookie path/domain. It is marked secure on HTTPS requests or when `SESSION_SECURE_COOKIE` is enabled. Existing session security settings remain intact. Deploy HTTPS environments with `SESSION_SECURE_COOKIE=true` and keep the application's trusted-proxy configuration accurate.

**Database and dynamic content**

The additive migration `2026_11_17_200000_add_localization_fields.php` adds three nullable fields:

- `users.preferred_locale`: the account's language preference. It is written explicitly after validated selection rather than being added to general user mass assignment.
- `products.translations`: JSON keyed by supported locale, containing only `name`, `category`, and `description`.
- `notifications.translation_data`: a translation key plus interpolation parameters for newly generated activity messages.

The migration has been applied to the connected development database. It does not rewrite or delete existing records. For another environment, run:

```sh
php artisan migrate
php artisan localization:export-js
php artisan view:cache
```

Use the application's existing deployment procedure for `php artisan config:cache`. Configuration caching was checked; the development configuration cache was cleared afterward. Regenerate browser dictionaries after translation changes and increase the existing `APP_ASSET_VERSION` when deploying changed assets.

An example product translation document is:

```json
{
  "en": {"name": "Rice", "category": "Groceries", "description": "White rice"},
  "ta": {"name": "அரிசி", "category": "மளிகை", "description": "வெள்ளை அரிசி"},
  "hi": {"name": "चावल", "category": "किराना", "description": "सफेद चावल"}
}
```

Call `$product->localized('name')`, `localized('category')`, or `localized('description')` for display. Resolution is the requested locale's nonempty text, then English's nonempty text, then the existing canonical column. Blank or missing translations therefore work for every pre-existing product. Unsupported fields throw an exception; SKU, barcode, email, passwords, prices, taxes, quantities, and identifiers are never translated through this API.

The product create/edit form includes an optional translations section. Requests whitelist locale keys and the three text fields and enforce the original field-length limits. Existing product authorization still enforces tenant ownership. Updates that omit `translations`, including existing archive/activate actions, retain saved translations. Canonical form fields continue editing canonical data, which avoids overwriting English/original values while viewing another language.

Product listings and selectors display localized names. Product search, stock selection, billing selection, and product-related history searches can match the selected language's stored product name; the product catalog also matches translated categories. Existing canonical names/SKUs remain searchable. Aggregate report grouping and sorting retain canonical database values, maintaining established financial/report behavior.

JSON was chosen because this application stores categories directly on products and supports a small, configured language set. This preserves records, avoids joins and per-product queries, and works with the existing MySQL database and SQLite test database. A separately indexed translation table can be introduced later if large multilingual catalogs require translated sorting or advanced full-text search; that is not required by the current architecture.

**Notifications, validation, and safe rendering**

Activity records retain their English/original snapshot text and store optional structured translation metadata. Notification accessors render messages for the viewing user's locale. Known historical English activity templates can be interpreted without rewriting stored audit records; unmatched historical/custom messages retain their original text. Automatic stock-movement notes have a separate display accessor so their original notes and numeric values remain unchanged. Owner activity emails use each recipient's saved locale; verification and password-reset emails also resolve the recipient's language.

Laravel validation catalogs include the installed framework's rules and translated attributes, alongside the application's custom validation messages. JavaScript form feedback uses the selected application's language rather than relying on the browser's UI language. Tables, navigation, pagination, confirmation dialogs, authentication screens, error pages, AJAX messages, and existing admin/vendor/customer-facing workspace views use these catalogs. Enum option labels are translated while submitted values remain canonical.

Blade escapes translated product text and interpolation output. Browser UI messages use text nodes/textContent; user data is not translated or executed as HTML. The exporter uses JSON hex escaping, and JavaScript interpolation runs in one pass so placeholder-like text inside a product name is not interpolated again. Do not insert an untrusted translation or interpolated user value with `innerHTML` or `{!! !!}`.

**Adding a language**

1. Add its code and native display name to `config/localization.php`, for example `'ml' => 'മലയാളം'`. Locale codes must match the exporter's safe language-code format; request input is always checked against this whitelist.
2. Copy the module files in `lang/en/` to `lang/ml/`, keeping their keys and named placeholders. Translate the values. Copy and translate `lang/en.json` as `lang/ml.json` for dynamic labels and legacy-message compatibility. English remains the fallback for missing entries.
3. Run `php artisan localization:export-js` to create `public/js/locales/ml.js`, then refresh deployment caches and the asset version as above. No database schema change is needed for another language; the selector, product fields, and request validation read the whitelist.
4. Extend the locale lists in `tests/Feature/LocalizationTest.php` and `tests/js/localization.test.cjs`. Check wording, typography, and long labels in the target language. The enabled languages currently use left-to-right layouts; an RTL language would additionally need appropriate direction/layout rules.

**Verification**

```sh
php artisan test
node tests/js/localization.test.cjs
node tests/js/blade-syntax.test.cjs
php artisan view:cache
```

The localization feature tests cover the default language, switching, page/query retention, session and cookie persistence, database preferences, login/logout, invalid input, missing-key fallback, translated validation, product persistence/search/fallback, output escaping, canonical enum values, historical/per-viewer notifications, recipient email locale, CSRF, and unsafe redirects. They render the main authenticated pages in both Tamil and Hindi, plus guest/vendor screens. Catalog checks verify matching keys, interpolation placeholders, and text encoding. Existing regression tests remain part of the suite.

Browser dictionaries are checked in Node for fallback, interpolation, and numeric preservation. The inline-JavaScript check validates syntax with Blade values masked; HTTP tests separately compile/render the templates. Configuration caching and view caching also succeed.

The complete PHP regression run passed **152 tests with 9,434 assertions**. The final validation-label/catalog pass was followed by the 24 localization feature tests and both Node checks. All 28 inline JavaScript blocks passed syntax checks, and 65 changed/new PHP files passed syntax checks.

Browser visual QA and native-speaker review have not been performed. Product translations are entered by users; missing translations intentionally display English or original content. Existing arbitrary customer-entered text and unrecognized historical messages retain their original content.

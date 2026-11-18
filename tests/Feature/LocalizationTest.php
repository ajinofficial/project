<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\{Notification, Plan, Product, Tenant, User};
use App\Support\{ActivityNotifier, RolePermission};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_language_and_guest_selector(): void
    {
        $this->get(route('login'))->assertOk()->assertHeader('Content-Language', 'en')
            ->assertSee('lang="en"', false)->assertSee('English')->assertSee('தமிழ்')->assertSee('हिन्दी')
            ->assertSee('name="_token"', false);
    }

    public function test_guest_can_switch_language_and_keep_current_query(): void
    {
        $this->post(route('language.update'), ['locale' => 'ta', 'return_to' => '/login?source=header'])
            ->assertStatus(303)->assertRedirect(url('/login?source=header'))->assertSessionHas('locale', 'ta')
            ->assertCookie(config('localization.cookie'), 'ta');
        $this->get(route('login'))->assertOk()->assertHeader('Content-Language', 'ta')->assertSee('வணிகப் பெயர்');
        $this->get(route('login'))->assertHeader('Content-Language', 'ta');
    }

    public function test_guest_can_switch_to_malayalam(): void
    {
        $this->post(route('language.update'), ['locale' => 'ml', 'return_to' => '/login'])
            ->assertStatus(303)->assertSessionHas('locale', 'ml')
            ->assertCookie(config('localization.cookie'), 'ml');
        $this->get(route('login'))->assertOk()->assertHeader('Content-Language', 'ml')
            ->assertSee('lang="ml"', false)->assertSee('മലയാളം')->assertSee('സ്ഥാപനത്തിന്റെ പേര്')
            ->assertSee('നിങ്ങളുടെ ബിസിനസ്.')->assertDontSee('Log in to manage your business.');
        $this->postJson(route('login.store'), [])->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'ഇമെയിൽ ഫീൽഡ് നിർബന്ധമാണ്.');
    }

    public function test_invalid_language_codes_are_rejected_without_changing_preferences(): void
    {
        foreach (['fr', '../ta', '%2e%2e/en', '<script>', ['ta'], 'TA', 'en.php'] as $code) {
            $this->withSession(['locale' => 'en'])->postJson(route('language.update'), ['locale' => $code])
                ->assertUnprocessable()->assertJsonValidationErrors('locale');
            $this->assertSame('en', session('locale'));
        }
        $this->get('/language')->assertMethodNotAllowed();
    }

    public function test_locale_cookie_restores_anonymous_language(): void
    {
        $this->withCookie(config('localization.cookie'), 'hi')->get(route('login'))
            ->assertHeader('Content-Language', 'hi')->assertSee('व्यवसाय का नाम');
    }

    public function test_invalid_stored_locale_cannot_load_arbitrary_files(): void
    {
        $this->withSession(['locale' => '../../.env'])->get(route('login'))->assertHeader('Content-Language', 'en');
    }

    public function test_switch_updates_only_current_users_database_preference(): void
    {
        $user = $this->owner();
        $other = User::factory()->create();
        $this->actingAs($user)->post(route('language.update'), ['locale' => 'hi', 'user_id' => $other->id, 'return_to' => '/products?page=2'])
            ->assertRedirect(url('/products?page=2'))->assertSessionHas('locale', 'hi');
        $this->assertSame('hi', $user->fresh()->preferred_locale);
        $this->assertNull($other->fresh()->preferred_locale);
    }

    public function test_database_preference_wins_on_a_new_authenticated_session(): void
    {
        $user = $this->owner();
        $user->forceFill(['preferred_locale' => 'ta'])->save();
        $this->actingAs($user)->withSession(['locale' => 'hi'])->get(route('products.index'))
            ->assertOk()->assertHeader('Content-Language', 'ta')->assertSee('பொருட்கள்');
    }

    public function test_saved_preference_is_restored_after_login(): void
    {
        $user = $this->owner();
        $user->forceFill(['preferred_locale' => 'hi'])->save();
        $this->post(route('login.store'), ['tenant_id' => $user->tenant_id, 'email' => $user->email, 'password' => 'Password123'])
            ->assertRedirect()->assertSessionHas('locale', 'hi');
    }

    public function test_explicit_guest_selection_is_saved_on_login_and_survives_logout(): void
    {
        $user = $this->owner();
        $user->forceFill(['preferred_locale' => 'hi'])->save();
        $this->post(route('language.update'), ['locale' => 'ta', 'return_to' => '/login']);
        $this->post(route('login.store'), ['tenant_id' => $user->tenant_id, 'email' => $user->email, 'password' => 'Password123'])
            ->assertRedirect()->assertSessionHas('locale', 'ta');
        $this->assertSame('ta', $user->fresh()->preferred_locale);
        $this->post(route('logout'))->assertRedirect(route('login'))->assertSessionHas('locale', 'ta');
        $this->assertGuest();
        $this->get(route('login'))->assertHeader('Content-Language', 'ta');
    }

    public function test_missing_translation_uses_english_fallback(): void
    {
        app('translator')->addLines(['common.fallback_probe' => 'English fallback'], 'en');
        app()->setLocale('ta');
        $this->assertSame('English fallback', __('common.fallback_probe'));
    }

    public function test_standard_and_custom_validation_are_translated(): void
    {
        $this->withSession(['locale' => 'ta'])->postJson(route('login.store'), [])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', 'மின்னஞ்சல் புலம் தேவை.');
        $this->withSession(['locale' => 'hi'])->postJson(route('login.store'), ['tenant_id' => 0, 'email' => 'invalid', 'password' => 'password'])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', 'मान्य ईमेल पता दर्ज करें।');
    }

    public function test_dynamic_product_text_falls_back_without_mutating_identifiers_or_financials(): void
    {
        $product = new Product(['name' => 'Original', 'category' => 'General', 'description' => 'Original description', 'sku' => 'SKU-001', 'price' => 100,
            'translations' => ['ta' => ['name' => 'தமிழ் பொருள்'], 'en' => ['category' => 'English category']]]);
        $this->assertSame('தமிழ் பொருள்', $product->localized('name', 'ta'));
        $this->assertSame('English category', $product->localized('category', 'hi'));
        $this->assertSame('Original description', $product->localized('description', 'ta'));
        $this->assertSame('Original', $product->name);
        $this->assertSame('SKU-001', $product->sku);
        $this->assertSame('100.00', $product->price);
        $this->expectException(\InvalidArgumentException::class);
        $product->localized('sku');
    }

    public function test_product_translations_can_be_saved_searched_and_displayed_safely(): void
    {
        Mail::fake();
        $user = $this->owner();
        $payload = ['name' => 'Canonical', 'sku' => 'SAFE-001', 'minimum_stock_level' => 1, 'tax_percentage' => 0, 'status' => 'draft',
            'translations' => ['ta' => ['name' => 'தமிழ் பொருள்', 'category' => '<script>alert(1)</script>', 'description' => 'விவரம்']]];
        $this->actingAs($user)->post(route('products.store'), $payload)->assertRedirect(route('products.index'));
        $product = Product::where('sku', 'SAFE-001')->firstOrFail();
        $this->assertSame('தமிழ் பொருள்', $product->translations['ta']['name']);
        $this->withSession(['locale' => 'ta'])->get(route('products.index', ['search' => 'தமிழ்']))
            ->assertOk()->assertSee('தமிழ் பொருள்')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        unset($payload['translations']);
        $this->put(route('products.update', $product), $payload)->assertRedirect();
        $this->assertSame('தமிழ் பொருள்', $product->fresh()->translations['ta']['name']);
        $this->getJson(route('stock.products', ['search' => 'தமிழ்']))->assertOk()->assertJsonCount(1, 'data');
        $product->update(['status' => 'active', 'inventory' => 5]);
        $this->getJson(route('sales.products', ['search' => 'தமிழ்']))->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'தமிழ் பொருள்');
        \App\Models\StockMovement::create(['tenant_id' => $user->tenant_id, 'user_id' => $user->id,
            'product_id' => $product->id, 'type' => 'sales_return', 'quantity' => 1, 'stock_after' => 5]);
        $this->get(route('returns.index', ['search' => 'தமிழ்']))->assertOk()
            ->assertViewHas('movements', fn ($movements) => $movements->total() === 1);
    }

    public function test_product_translation_keys_and_fields_are_whitelisted(): void
    {
        $user = $this->owner();
        $this->actingAs($user)->postJson(route('products.store'), ['translations' => ['../ta' => ['name' => 'unsafe']]])
            ->assertUnprocessable()->assertJsonValidationErrors('translations');
        $this->postJson(route('products.store'), ['translations' => ['ta' => ['price' => 1]]])
            ->assertUnprocessable()->assertJsonValidationErrors('translations.ta');
    }

    public function test_unsafe_redirects_are_replaced_with_local_home(): void
    {
        foreach (['https://evil.example/path', '//evil.example', '/%2fevil.example', '/\\evil.example', '/%5cevil.example', '/%0d%0aLocation:evil', 'javascript:alert(1)'] as $target) {
            $this->post(route('language.update'), ['locale' => 'hi', 'return_to' => $target])
                ->assertStatus(303)->assertRedirect(url('/'));
        }
    }

    public function test_language_updates_enforce_csrf_protection(): void
    {
        $this->app->bind(VerifyCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends VerifyCsrfToken {
            protected function runningUnitTests() { return false; }
        });
        $this->post(route('language.update'), ['locale' => 'ta'])->assertStatus(419);
        $this->withSession(['_token' => 'test-token'])->post(route('language.update'), ['_token' => 'test-token', 'locale' => 'ta'])
            ->assertStatus(303);
    }

    public function test_notifications_render_in_each_viewers_language(): void
    {
        Mail::fake();
        $user = $this->owner();
        ActivityNotifier::notify($user->tenant_id, 'product_created', 'Product created', 'Owner created product Example.', false,
            ['actor' => 'Owner', 'product' => 'Example']);
        $notification = Notification::firstOrFail();
        app()->setLocale('ta');
        $this->assertSame('பொருள் உருவாக்கப்பட்டது', $notification->title);
        $this->assertStringContainsString('Example', $notification->message);
        $this->assertStringContainsString('உருவாக்கினார்', $notification->message);
        app()->setLocale('hi');
        $this->assertStringContainsString('बनाया', $notification->message);
        app()->setLocale('ml');
        $this->assertSame('ഉൽപ്പന്നം സൃഷ്ടിച്ചു', $notification->title);
        $this->assertStringContainsString('Example', $notification->message);
        $this->assertStringContainsString('സൃഷ്ടിച്ചു', $notification->message);
        $this->assertSame('Owner created product Example.', $notification->getRawOriginal('message'));
    }

    public function test_all_catalogs_have_matching_keys_and_interpolation_placeholders(): void
    {
        foreach (glob(lang_path('en/*.php')) as $file) {
            $group = basename($file);
            $english = \Illuminate\Support\Arr::dot(require $file);
            foreach (['ta', 'hi', 'ml'] as $locale) {
                $translated = \Illuminate\Support\Arr::dot(require lang_path($locale.'/'.$group));
                foreach ($english as $key => $value) {
                    if (! is_string($value)) { continue; }
                    $this->assertArrayHasKey($key, $translated, $locale.'/'.$group.':'.$key);
                    $this->assertDoesNotMatchRegularExpression('/\?{2,}|\x{FFFD}/u', $translated[$key], $locale.'/'.$group.':'.$key);
                    preg_match_all('/:[a-z_]+/i', $value, $expected);
                    preg_match_all('/:[a-z_]+/i', $translated[$key], $actual);
                    $expectedKeys = array_values(array_unique($expected[0]));
                    $actualKeys = array_values(array_unique($actual[0]));
                    sort($expectedKeys); sort($actualKeys);
                    $this->assertSame($expectedKeys, $actualKeys, $locale.'/'.$group.':'.$key);
                }
            }
        }
    }

    public function test_authenticated_pages_render_in_each_translated_language(): void
    {
        $user = $this->owner();
        foreach (['ta', 'hi', 'ml'] as $locale) {
            $user->forceFill(['preferred_locale' => $locale])->save();
            foreach (['dashboard', 'products.index', 'products.create', 'stock.index', 'stock.create', 'purchases.index', 'returns.index', 'customers.index', 'suppliers.index', 'sales.index', 'services.index', 'expenses.index', 'reports.index', 'setup.index', 'users.index', 'role-permissions.index', 'notifications.index'] as $route) {
                $this->actingAs($user)->get(route($route))->assertOk()->assertHeader('Content-Language', $locale);
            }
        }
    }

    public function test_an_unknown_page_keeps_locale_without_changing_removed_endpoint_status(): void
    {
        $this->withCookie(config('localization.cookie'), 'hi')->get('/missing-localized-page')
            ->assertNotFound()->assertSee('पृष्ठ नहीं मिला')->assertSee('name="_token"', false);
        $this->post('/products/99999/stock')->assertNotFound();
    }

    public function test_translated_options_keep_canonical_values(): void
    {
        $user = $this->owner();
        $user->forceFill(['preferred_locale' => 'ta'])->save();
        $this->actingAs($user)->get(route('expenses.index'))->assertOk()
            ->assertSee('value="Rent"', false)->assertSee('வாடகை');
        $this->get(route('services.index'))->assertOk()
            ->assertSee('value="Computer"', false)->assertSee('கணினி');
        $this->post(route('expenses.store'), ['title' => 'Rent bill', 'category' => 'Rent', 'amount' => 200,
            'expense_date' => now()->toDateString(), 'payment_method' => 'cash'])->assertRedirect();
        $this->assertDatabaseHas('expenses', ['tenant_id' => $user->tenant_id, 'category' => 'Rent', 'amount' => 200]);
    }

    public function test_historical_notification_snapshots_are_translated_without_rewriting_data(): void
    {
        $user = $this->owner();
        $notification = Notification::create(['tenant_id' => $user->tenant_id, 'type' => 'stock_low', 'channel' => 'in_app',
            'title' => 'Low stock alert', 'message' => 'Example is down to 2 units.']);
        app()->setLocale('ta');
        $this->assertSame('Example இருப்பு 2 அலகுகளாகக் குறைந்துள்ளது.', $notification->message);
        $this->assertSame('Example is down to 2 units.', $notification->fresh()->getRawOriginal('message'));
    }

    public function test_activity_mail_uses_the_recipients_saved_language(): void
    {
        Mail::fake();
        $user = $this->owner();
        $user->forceFill(['preferred_locale' => 'hi'])->save();
        ActivityNotifier::notify($user->tenant_id, 'product_created', 'Product created', 'Owner created product Example.', true,
            ['actor' => 'Owner', 'product' => 'Example']);
        Mail::assertSent(\App\Mail\ImportantEventMail::class, fn ($mail) => $mail->hasTo($user->email)
            && $mail->eventTitle === 'उत्पाद बनाया गया' && str_contains($mail->eventMessage, 'Example'));
    }

    public function test_vendor_and_guest_panels_render_in_hindi(): void
    {
        $this->withSession(['locale' => 'hi'])->get(route('register'))->assertOk()->assertSee('व्यवसाय का नाम');
        $this->get(route('password.request'))->assertOk()->assertSee('ईमेल');
        $user = $this->owner();
        $user->tenant->update(['tenant_type' => Tenant::TYPE_VENDOR]);
        $user->forceFill(['preferred_locale' => 'hi'])->save();
        foreach (['vendor.dashboard', 'clients.index', 'email.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk()->assertHeader('Content-Language', 'hi');
        }
    }

    private function owner(): User
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();
        $tenant = Tenant::create(['plan_id' => $plan->id, 'tenant_type' => Tenant::TYPE_CLIENT, 'business_name' => 'Demo Store',
            'owner_name' => 'Owner', 'mobile' => '+91 9876543210', 'email' => 'owner@example.com', 'business_category' => Tenant::CATEGORY_RETAIL,
            'store_address' => 'Demo road', 'role_permissions' => RolePermission::defaults()]);
        return User::create(['tenant_id' => $tenant->id, 'name' => 'Owner', 'email' => 'owner@example.com', 'company_name' => 'Demo Store',
            'plan' => $plan->id, 'role' => User::ROLE_OWNER, 'password' => 'Password123']);
    }
}

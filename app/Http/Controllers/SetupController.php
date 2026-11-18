<?php

namespace App\Http\Controllers;

use App\Support\ActivityNotifier;
use App\Support\RolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function index(Request $request): View
    {
        return view('setup.index', ['tenant' => $request->user()->tenant]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        $data = $request->validate(
            [
                'currency' => ['required', 'in:INR'],
                'default_tax_percentage' => ['required', 'numeric', 'min:0', 'max:99.99'],
                'low_stock_threshold' => ['required', 'integer', 'min:1', 'max:9999'],
                'invoice_prefix' => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9-]+$/'],
            ],
            [
                'currency.required' => __('validation.currency_is_required_6ac1e5'),
                'default_tax_percentage.required' => __('validation.default_tax_percentage_is_required_be51b7'),
                'low_stock_threshold.required' => __('validation.low_stock_alert_threshold_is_required_5a1e48'),
                'invoice_prefix.required' => __('validation.invoice_prefix_is_required_484f9b'),
                'invoice_prefix.regex' => __('orders.invoice_prefix_may_only_contain_letters_numbers_and_hyphens_bc7cac'),
            ]
        );

        $data['invoice_prefix'] = strtoupper($data['invoice_prefix']);

        $tenant->update($data);

        ActivityNotifier::notify(
            $tenant->id,
            'setup_updated',
            "Store setup updated",
            $request->user()->name.' updated billing, tax, stock alert, or invoice defaults.',
            parameters: ['actor' => $request->user()->name]
        );

        return redirect()->route(RolePermission::firstAccessibleRoute($request->user()))->with('status', __('settings.store_setup_saved_fd8d08'));
    }
}

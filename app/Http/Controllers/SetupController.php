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
                'invoice_prefix.regex' => 'Invoice prefix may only contain letters, numbers, and hyphens.',
            ]
        );

        $data['invoice_prefix'] = strtoupper($data['invoice_prefix']);

        $tenant->update($data);

        ActivityNotifier::notify(
            $tenant->id,
            'setup_updated',
            'Store setup updated',
            $request->user()->name.' updated billing, tax, stock alert, or invoice defaults.'
        );

        return redirect()->route(RolePermission::firstAccessibleRoute($request->user()))->with('status', 'Store setup saved.');
    }
}

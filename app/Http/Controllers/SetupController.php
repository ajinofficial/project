<?php

namespace App\Http\Controllers;

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

        $data = $request->validate([
            'currency' => ['required', 'in:INR'],
            'default_tax_percentage' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'low_stock_threshold' => ['required', 'integer', 'min:1', 'max:9999'],
            'invoice_prefix' => ['required', 'string', 'max:12'],
        ]);

        $tenant->update($data);

        return redirect()->route('dashboard')->with('status', 'Store setup saved.');
    }
}

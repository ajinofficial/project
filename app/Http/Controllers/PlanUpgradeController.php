<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlanUpgradeController extends Controller
{
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless((int) $request->user()->role === User::ROLE_OWNER, 403);

        $data = $request->validate([
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(fn ($query) => $query->where('monthly_price', '>', 0)),
            ],
        ], [
            'plan_id.required' => __('settings.choose_a_plan_to_continue_7f241f'),
            'plan_id.exists' => __('settings.choose_a_valid_paid_plan_c3a163'),
        ]);

        $plan = Plan::whereKey($data['plan_id'])->where('monthly_price', '>', 0)->firstOrFail();

        DB::transaction(function () use ($request, $plan) {
            $tenant = Tenant::query()
                ->whereKey($request->user()->tenant_id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($tenant->plan?->name === 'free_trial' && $tenant->domain_expired_date?->endOfDay()->isPast(), 422, __('common.this_account_does_not_have_an_expired_free_trial_baa795'));

            $renewsAt = now()->addMonth()->toDateString();

            $tenant->update([
                'plan_id' => $plan->id,
                'domain_expired_date' => $renewsAt,
            ]);

            User::where('tenant_id', $tenant->id)->update(['plan' => $plan->id]);

            DB::table('subscriptions')->updateOrInsert(
                ['tenant_id' => $tenant->id],
                [
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'renews_at' => $renewsAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        });

        $message = __('settings.plan_upgraded', ['plan' => __((string) str($plan->name)->headline())]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect' => url()->previous(),
            ]);
        }

        return back()->with('status', $message);
    }
}

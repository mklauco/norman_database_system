<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\ServerPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServerPaymentController extends Controller
{
    public function index()
    {
        $payments = ServerPayment::orderByDesc('period_end_date')->get();

        return view('backend.server-payments.index', compact('payments'));
    }

    public function create()
    {
        $lastPayment = ServerPayment::orderByDesc('period_end_date')->first();
        $defaultStartDate = $lastPayment?->period_end_date?->copy()->addDay()->format('Y-m-d');
        $defaultEndDate = $defaultStartDate
            ? Carbon::parse($defaultStartDate)->addMonth()->subDay()->format('Y-m-d')
            : null;

        return view('backend.server-payments.create', compact('defaultStartDate', 'defaultEndDate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_start_date' => ['required', 'date'],
            'period_end_date' => ['required', 'date', 'after_or_equal:period_start_date'],
            'status' => ['required', Rule::in(['not_paid', 'pending', 'paid'])],
            'amount_without_vat' => ['required', 'numeric', 'min:0'],
            'variable_symbol' => ['nullable', 'string', 'max:64'],
        ]);

        ServerPayment::create($validated);

        return redirect()->route('backend.server-payments.index')->with('status', 'Payment saved');
    }

    public function edit(ServerPayment $serverPayment)
    {
        return view('backend.server-payments.edit', compact('serverPayment'));
    }

    public function update(Request $request, ServerPayment $serverPayment)
    {
        $validated = $request->validate([
            'period_start_date' => ['required', 'date'],
            'period_end_date' => ['required', 'date', 'after_or_equal:period_start_date'],
            'status' => ['required', Rule::in(['not_paid', 'pending', 'paid'])],
            'amount_without_vat' => ['required', 'numeric', 'min:0'],
            'variable_symbol' => ['nullable', 'string', 'max:64'],
        ]);

        $serverPayment->update($validated);

        return redirect()->route('backend.server-payments.index')->with('status', 'Payment updated');
    }

    public function destroy(ServerPayment $serverPayment)
    {
        $serverPayment->delete();

        return redirect()->route('backend.server-payments.index')->with('status', 'Payment deleted');
    }
}

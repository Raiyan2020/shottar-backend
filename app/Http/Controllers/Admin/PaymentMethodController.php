<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\PaymentMethodDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Models\LessonSection;
use App\Models\PaymentMethod;
use App\Services\PaymentMethodService;
use App\Traits\HasStatusToggle;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    use HasStatusToggle;
    protected $paymentMethodService;

    public function __construct(PaymentMethodService $paymentMethodService)
    {
        $this->paymentMethodService = $paymentMethodService;
    }

    public function index(PaymentMethodDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.payment_methods.index');
    }

    public function create()
    {
        return view('dashboard.admin.payment_methods.create');
    }

    public function store(StorePaymentMethodRequest $request)
    {
        $data = $request->validated();

        $this->paymentMethodService->createPaymentMethod($data, $request->image);

        return redirect()->route('admin.payment-methods.index')->with('success', 'created successfully');
    }
    public function edit($id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);
        return view('dashboard.admin.payment_methods.edit', compact('paymentMethod'));
    }

    public function update(UpdatePaymentMethodRequest $request, $id)
    {
        $data = $request->validated();
        $paymentMethod = PaymentMethod::findOrFail($id);

        $this->paymentMethodService->updatePaymentMethod($paymentMethod, $data, $request->image);

        return redirect()->route('admin.payment-methods.index')->with('success', 'updated successfully');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $this->paymentMethodService->deletePaymentMethod($paymentMethod);

        return response()->json('success');
    }

    public function toggleStatus($paymentMethod)
    {
        return $this->toggleStatu(PaymentMethod::class, $paymentMethod);
    }
}

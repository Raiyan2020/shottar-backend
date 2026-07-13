<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\CouponsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\Models\Coupon;
use App\Traits\HasStatusToggle;

class CouponController extends Controller
{
    use HasStatusToggle;

    public function index(CouponsDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.coupons.index');
    }

    public function create()
    {
        return view('dashboard.admin.coupons.create');
    }

    public function store(StoreCouponRequest $request)
    {
        Coupon::create($request->validated());

        return redirect()->route('admin.coupons.index')
            ->with('success', 'created successfully');
    }

    public function edit(Coupon $coupon)
    {
        return view('dashboard.admin.coupons.edit', compact('coupon'));
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon)
    {
        $coupon->update($request->validated());

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Coupon updated successfully');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json('success');
    }

    public function toggleStatus($id)
    {
        return $this->toggleStatu(Coupon::class, $id);
    }
}

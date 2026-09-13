<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // معرّف MyFatoorah الحقيقي للفاتورة (فريد ومضمون من عندهم) — بنستخدمه
            // في GetPaymentStatus بدل ما نعتمد على order_id لوحده كـ
            // CustomerReference، لأن CustomerReference نص حر ممكن يتكرر مع
            // فواتير تجار تانيين في sandbox المشترك.
            $table->string('myfatoorah_invoice_id')->nullable()->after('payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('myfatoorah_invoice_id');
        });
    }
};

<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === UserRole::ADMIN;
    }

    public function rules(): array
    {
        return [
            'payment_type'   => ['required', Rule::enum(PaymentType::class)],
            'amount'         => ['required', 'numeric', 'gt:0'],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_type.required'   => 'Tipe pembayaran wajib dipilih.',
            'payment_type.Illuminate\Validation\Rules\Enum' => 'Tipe pembayaran yang dipilih tidak valid.',
            'amount.required'         => 'Nominal pembayaran wajib diisi.',
            'amount.numeric'          => 'Nominal pembayaran harus berupa angka.',
            'amount.gt'               => 'Nominal pembayaran harus lebih besar dari 0.',
            'payment_date.required'   => 'Tanggal pembayaran wajib diisi.',
            'payment_date.date'       => 'Format tanggal pembayaran tidak valid.',
            'payment_method.required' => 'Metode pembayaran wajib diisi.',
            'payment_method.Illuminate\Validation\Rules\Enum' => 'Metode pembayaran yang dipilih tidak valid.',
        ];
    }
}

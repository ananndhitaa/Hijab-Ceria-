<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === UserRole::ADMIN;
    }

    public function rules(): array
    {
        $customer = $this->route('customer');
        $customerId = is_object($customer) ? $customer->id : $customer;

        return [
            'customer_name'      => ['required', 'string', 'max:255'],
            'first_contact_date' => ['nullable', 'date'],
            'phone'              => ['nullable', 'string', 'max:50'],
            'notes'              => ['nullable', 'string'],
            'address'            => ['nullable', 'string'],
            'user_id'            => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', UserRole::CUSTOMER->value);
                }),
                Rule::unique('customers', 'user_id')->ignore($customerId),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $phone = $this->input('phone');
            $customer = $this->route('customer');
            $customerId = is_object($customer) ? $customer->id : (int) $customer;

            if (! empty($phone)) {
                $existing = \App\Models\Master\Customer::findByNormalizedPhone($phone, $customerId);
                if ($existing) {
                    $validator->errors()->add(
                        'phone',
                        "Nomor WhatsApp ini sudah terdaftar pada customer {$existing->customer_name}."
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Nama customer wajib diisi.',
            'customer_name.max'      => 'Nama customer maksimal 255 karakter.',
            'first_contact_date.date'=> 'Format tanggal masuk tidak valid.',
            'phone.max'              => 'Nomor telepon maksimal 50 karakter.',
            'user_id.exists'         => 'User yang dipilih tidak ditemukan atau bukan ber-role Customer.',
            'user_id.unique'         => 'User ini sudah terhubung dengan profil customer lain.',
        ];
    }
}

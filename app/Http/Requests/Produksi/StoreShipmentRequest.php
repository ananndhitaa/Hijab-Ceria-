<?php

namespace App\Http\Requests\Produksi;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isShipAction = $this->input('action') === 'ship';

        return [
            'order_id'          => ['required', 'integer', 'exists:orders,id'],
            'courier'           => [$isShipAction ? 'required' : 'nullable', 'string', 'max:100'],
            'courier_service'   => ['nullable', 'string', 'max:100'],
            'tracking_no'       => [$isShipAction ? 'required' : 'nullable', 'string', 'max:100'],
            'shipping_cost'     => ['nullable', 'numeric', 'min:0', 'max:99999999999999.99'],
            'recipient_address' => [$isShipAction ? 'required' : 'nullable', 'string', 'max:1000'],
            'notes'             => ['nullable', 'string', 'max:1000'],
            'action'            => ['nullable', 'string', 'in:draft,ship'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required'          => 'Pesanan (Sales Order) wajib dipilih.',
            'order_id.exists'            => 'Pesanan yang dipilih tidak ditemukan dalam sistem.',
            'courier.required'           => 'Nama kurir / ekspedisi wajib diisi sebelum pengiriman ditandai Dikirim.',
            'courier.max'                => 'Nama kurir / ekspedisi maksimal 100 karakter.',
            'courier_service.max'        => 'Layanan kurir maksimal 100 karakter.',
            'tracking_no.required'       => 'Nomor resi wajib diisi sebelum pengiriman ditandai Dikirim.',
            'tracking_no.max'            => 'Nomor resi maksimal 100 karakter.',
            'shipping_cost.numeric'      => 'Ongkos kirim harus berupa angka yang valid.',
            'shipping_cost.min'          => 'Ongkos kirim tidak boleh bernilai negatif.',
            'shipping_cost.max'          => 'Ongkos kirim melebihi batas maksimum sistem.',
            'recipient_address.required' => 'Alamat tujuan pengiriman wajib diisi sebelum pengiriman ditandai Dikirim.',
            'recipient_address.max'      => 'Alamat penerima maksimal 1000 karakter.',
            'notes.max'                  => 'Catatan pengiriman maksimal 1000 karakter.',
        ];
    }
}

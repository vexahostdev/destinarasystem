<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'org_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:25', 'regex:/^(08|628|\+628)[0-9]{7,13}$/'],
            'password' => ['required', 'string', app()->isProduction() ? Password::min(8)->mixedCase()->numbers() : Password::min(8), 'confirmed'],
            'account_type' => ['required', 'in:buyer,partner'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap PIC wajib diisi.',
            'org_name.required' => 'Nama organisasi / sekolah / lembaga wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar di sistem Destinara.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'phone.regex' => 'Format nomor WhatsApp tidak valid (contoh: 08123456789 atau 628123456789).',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'account_type.required' => 'Tipe akun wajib dipilih.',
            'account_type.in' => 'Tipe akun harus berupa Institusi (buyer) atau Mitra (partner).',
            'terms.accepted' => 'Anda wajib menyetujui Syarat & Ketentuan serta Kebijakan Privasi.',
        ];
    }
}

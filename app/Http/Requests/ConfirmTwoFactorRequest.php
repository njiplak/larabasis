<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
        ];
    }

    /**
     * Keep failures in the bag the setup modal reads.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw (new \Illuminate\Validation\ValidationException($validator))
            ->errorBag('confirmTwoFactorAuthentication');
    }
}

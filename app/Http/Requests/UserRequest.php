<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->route('id'))],
            // Same strength rule as the user's own password change: this is
            // the path that mints every new account's first credential.
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'string', Password::defaults()],
            'role' => ['nullable', 'integer', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => $this->trashedUserHoldsEmail()
                ? 'That email belongs to a deleted user. Restore that user, or use a different email.'
                : 'That email address is already in use.',
        ];
    }

    /**
     * A soft-deleted user keeps their email reserved so two records can never
     * claim the same identity; the message points at the restore path.
     */
    private function trashedUserHoldsEmail(): bool
    {
        return User::onlyTrashed()->where('email', $this->input('email'))->exists();
    }
}

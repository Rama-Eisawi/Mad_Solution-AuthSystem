<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class signupRequest extends FormRequest
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
            'username' => 'required|string',
            'phone_number' => 'required|regex:/(09)[0-9]{8}/|unique:users', 
            'email' => 'required|email|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'profile_photo'=>'nullable|image|mimes:jpeg,png,jpg,gif,svg|unique:users',
            'certificate'=> 'nullable|mimes:pdf|unique:users'
        ];
    }
}

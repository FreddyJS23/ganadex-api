<?php

namespace App\Http\Requests;

use App\Rules\ComprobarVeterianario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreUsuarioVeterinarioRequest extends FormRequest
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
            'personal_id' => [
                'required', 'numeric', Rule::exists('personals', 'id')
                    ->where(
                        fn($query) => $query->where('user_id', Auth::id())
                    ),
                new ComprobarVeterianario(),
            ],
        ];
    }
}

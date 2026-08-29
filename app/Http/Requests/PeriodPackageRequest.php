<?php

namespace App\Http\Requests;

use App\Models\PeriodPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PeriodPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $package = $this->route('periodPackage');

        return $package instanceof PeriodPackage
            ? (bool) $this->user()?->can('update', $package)
            : (bool) $this->user()?->can('create', PeriodPackage::class);
    }

    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return [
                'zip'         => ['required', 'file', 'mimes:zip', 'max:20480'],
                'name'        => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
                'is_shared'   => ['boolean'],
                // Whitelist stricte : jamais une URL arbitraire (open redirect).
                'redirect_to' => ['nullable', 'string', Rule::in(['library', 'create'])],
            ];
        }

        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_shared'   => ['boolean'],
        ];
    }
}

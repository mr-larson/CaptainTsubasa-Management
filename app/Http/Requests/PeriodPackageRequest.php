<?php

namespace App\Http\Requests;

use App\Models\PeriodPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Requête de création (import d'un zip) ou de modification d'un package de période. */
class PeriodPackageRequest extends FormRequest
{
    /** Modification ou création autorisée par PeriodPackagePolicy (auteur ou admin). */
    public function authorize(): bool
    {
        $package = $this->route('periodPackage');

        return $package instanceof PeriodPackage
            ? (bool) $this->user()?->can('update', $package)
            : (bool) $this->user()?->can('create', PeriodPackage::class);
    }

    /**
     * À la création : fichier zip, nom, description, partage et redirection. À la mise à jour : nom, description, partage.
     */
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

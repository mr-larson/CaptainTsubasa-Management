<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Inertia\Inertia;
use App\Http\Requests\ContractRequest;

class ContractController extends Controller
{
    public function index()
    {
        return Inertia::render('Contracts/Index', [
            'contracts' => Contract::orderBy('name')->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Contracts/Create');
    }

    public function store(ContractRequest $request)
    {
        Contract::create($request->all());

        return redirect()->route('contracts')->with('success', "Le contrat a été créé avec succès");
    }

    public function edit()
    {
        return Inertia::render('Contracts/Edit', [
            'contracts' => Contract::orderBy('name')->get()
        ]);
    }

    public function update(ContractRequest $request, Contract $contract)
    {
        $contract->update($request->all());

        return redirect()->route('contracts')->with('success', "Le contrat a été modifié avec succès");
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();

        return redirect()->route('contracts')->with('message', 'Contract successfully deleted.');
    }
}

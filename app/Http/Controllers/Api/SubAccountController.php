<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubAccount;
use Illuminate\Http\Request;

class SubAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $subAccounts = SubAccount::where('parent_user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $subAccounts
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'credits_remaining' => 'nullable|integer|min:0',
            'delivery_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // Generate default email and password for sub-account
        $email = strtolower(str_replace(' ', '.', $validated['name'])) . '@subaccount.local';
        $password = bcrypt(uniqid());

        $subAccount = SubAccount::create([
            'parent_user_id' => $request->user()->id,
            'name' => $validated['name'],
            'email' => $email,
            'password' => $password,
            'status' => $validated['status'],
            'credits_remaining' => $validated['credits_remaining'] ?? 0,
            'credits_used_this_month' => 0,
            'delivery_rate' => $validated['delivery_rate'] ?? 0,
        ]);

        return response()->json([
            'data' => $subAccount,
            'message' => 'Compte créé avec succès'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $subAccount = SubAccount::where('parent_user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'data' => $subAccount
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $subAccount = SubAccount::where('parent_user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,inactive',
            'credits_remaining' => 'sometimes|integer|min:0',
            'delivery_rate' => 'sometimes|numeric|min:0|max:100',
        ]);

        $subAccount->update($validated);

        return response()->json([
            'data' => $subAccount,
            'message' => 'Compte modifié avec succès'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $subAccount = SubAccount::where('parent_user_id', $request->user()->id)
            ->findOrFail($id);

        $subAccount->delete();

        return response()->json([
            'message' => 'Compte supprimé avec succès'
        ]);
    }
}

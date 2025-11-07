<?php

namespace App\Http\Controllers;

use App\Models\ContactGroup;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactGroupController extends Controller
{
    /**
     * Liste tous les groupes de l'utilisateur
     */
    public function index(Request $request)
    {
        $groups = ContactGroup::byUser($request->user()->id)
            ->withCount('contacts')
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Liste des groupes',
            'data' => $groups
        ]);
    }

    /**
     * Créer un nouveau groupe
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id'
        ]);

        try {
            $group = ContactGroup::create([
                'user_id' => $request->user()->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'contacts_count' => 0
            ]);

            // Ajouter les contacts si fournis
            if (!empty($validated['contact_ids'])) {
                $group->addContacts($validated['contact_ids']);
            }

            Log::info('Contact group created', [
                'group_id' => $group->id,
                'name' => $group->name,
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'message' => 'Groupe créé avec succès',
                'data' => $group->load('contacts')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create contact group', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création du groupe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un groupe spécifique
     */
    public function show(Request $request, int $id)
    {
        $group = ContactGroup::byUser($request->user()->id)
            ->with('contacts')
            ->findOrFail($id);

        return response()->json([
            'message' => 'Détails du groupe',
            'data' => $group
        ]);
    }

    /**
     * Mettre à jour un groupe
     */
    public function update(Request $request, int $id)
    {
        $group = ContactGroup::byUser($request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            $group->update($validated);

            Log::info('Contact group updated', [
                'group_id' => $group->id,
                'updated_fields' => array_keys($validated)
            ]);

            return response()->json([
                'message' => 'Groupe mis à jour avec succès',
                'data' => $group
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update contact group', [
                'group_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un groupe
     */
    public function destroy(Request $request, int $id)
    {
        $group = ContactGroup::byUser($request->user()->id)
            ->findOrFail($id);

        try {
            $group->delete();

            Log::info('Contact group deleted', [
                'group_id' => $id,
                'name' => $group->name
            ]);

            return response()->json([
                'message' => 'Groupe supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete contact group', [
                'group_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajouter des contacts à un groupe
     */
    public function addContacts(Request $request, int $id)
    {
        $group = ContactGroup::byUser($request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'contact_ids' => 'required|array|min:1',
            'contact_ids.*' => 'exists:contacts,id'
        ]);

        try {
            $group->addContacts($validated['contact_ids']);

            Log::info('Contacts added to group', [
                'group_id' => $id,
                'contacts_added' => count($validated['contact_ids'])
            ]);

            return response()->json([
                'message' => 'Contacts ajoutés avec succès',
                'data' => [
                    'contacts_count' => $group->contacts_count
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to add contacts to group', [
                'group_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'ajout des contacts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retirer des contacts d'un groupe
     */
    public function removeContacts(Request $request, int $id)
    {
        $group = ContactGroup::byUser($request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'contact_ids' => 'required|array|min:1',
            'contact_ids.*' => 'exists:contacts,id'
        ]);

        try {
            $group->removeContacts($validated['contact_ids']);

            Log::info('Contacts removed from group', [
                'group_id' => $id,
                'contacts_removed' => count($validated['contact_ids'])
            ]);

            return response()->json([
                'message' => 'Contacts retirés avec succès',
                'data' => [
                    'contacts_count' => $group->contacts_count
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to remove contacts from group', [
                'group_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors du retrait des contacts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les contacts d'un groupe
     */
    public function getContacts(Request $request, int $id)
    {
        $group = ContactGroup::byUser($request->user()->id)
            ->findOrFail($id);

        $contacts = $group->contacts()
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Contacts du groupe',
            'data' => $contacts
        ]);
    }
}

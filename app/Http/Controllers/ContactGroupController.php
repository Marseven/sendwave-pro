<?php

namespace App\Http\Controllers;

use App\Models\ContactGroup;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactGroupController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/contact-groups",
     *     tags={"Contact Groups"},
     *     summary="List all contact groups",
     *     description="Retrieve all contact groups for the authenticated user with contact counts",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of contact groups",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Liste des groupes"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="VIP Clients"),
     *                 @OA\Property(property="description", type="string", example="High-value clients"),
     *                 @OA\Property(property="contacts_count", type="integer", example=25)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
     * @OA\Post(
     *     path="/api/contact-groups",
     *     tags={"Contact Groups"},
     *     summary="Create a new contact group",
     *     description="Create a new contact group, optionally with initial contacts",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="VIP Clients"),
     *             @OA\Property(property="description", type="string", example="High-value clients"),
     *             @OA\Property(property="contact_ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Group created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Groupe cree avec succes"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
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
     * @OA\Get(
     *     path="/api/contact-groups/{id}",
     *     tags={"Contact Groups"},
     *     summary="Get a specific contact group",
     *     description="Retrieve a contact group by ID with its contacts",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact group ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group details",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Details du groupe"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Group not found")
     * )
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
     * @OA\Put(
     *     path="/api/contact-groups/{id}",
     *     tags={"Contact Groups"},
     *     summary="Update a contact group",
     *     description="Update an existing contact group by ID",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact group ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="VIP Clients Updated"),
     *             @OA\Property(property="description", type="string", example="Updated description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Groupe mis a jour avec succes"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Group not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
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
     * @OA\Delete(
     *     path="/api/contact-groups/{id}",
     *     tags={"Contact Groups"},
     *     summary="Delete a contact group",
     *     description="Remove a contact group by ID",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact group ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Groupe supprime avec succes")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Group not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
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
     * @OA\Post(
     *     path="/api/contact-groups/{id}/contacts/add",
     *     tags={"Contact Groups"},
     *     summary="Add contacts to a group",
     *     description="Add one or more contacts to a contact group",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact group ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"contact_ids"},
     *             @OA\Property(property="contact_ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contacts added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Contacts ajoutes avec succes"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="contacts_count", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Group not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
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
     * @OA\Post(
     *     path="/api/contact-groups/{id}/contacts/remove",
     *     tags={"Contact Groups"},
     *     summary="Remove contacts from a group",
     *     description="Remove one or more contacts from a contact group",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact group ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"contact_ids"},
     *             @OA\Property(property="contact_ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contacts removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Contacts retires avec succes"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="contacts_count", type="integer", example=7)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Group not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
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
     * @OA\Get(
     *     path="/api/contact-groups/{id}/contacts",
     *     tags={"Contact Groups"},
     *     summary="Get contacts of a group",
     *     description="Retrieve all contacts belonging to a specific group",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact group ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of contacts in the group",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Contacts du groupe"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Group not found")
     * )
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

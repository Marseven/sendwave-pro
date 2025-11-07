<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContactGroup extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'contacts_count',
    ];

    protected $casts = [
        'contacts_count' => 'integer',
    ];

    /**
     * Relations
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_group_members', 'group_id', 'contact_id')
            ->withPivot('added_at')
            ->withTimestamps();
    }

    /**
     * Add contacts to group
     */
    public function addContacts(array $contactIds): void
    {
        $this->contacts()->syncWithoutDetaching($contactIds);
        $this->updateContactsCount();
    }

    /**
     * Remove contacts from group
     */
    public function removeContacts(array $contactIds): void
    {
        $this->contacts()->detach($contactIds);
        $this->updateContactsCount();
    }

    /**
     * Update contacts count
     */
    public function updateContactsCount(): void
    {
        $this->update(['contacts_count' => $this->contacts()->count()]);
    }

    /**
     * Scopes
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithContactsCount($query)
    {
        return $query->withCount('contacts');
    }
}

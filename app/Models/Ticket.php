<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'tickets';

    protected $fillable = [
        'user_id',
        'title',
        'status',
    ];

    public static array $validationRules = [
        'user_id' => 'required|numeric|exists:users,id',
        'title' => 'required|string|max:191',
        'status' => 'required|string|in:open,closed',
    ];

    /**
     * Get the user who opened the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages for the ticket.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }
}

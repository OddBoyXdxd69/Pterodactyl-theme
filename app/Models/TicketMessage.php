<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'ticket_messages';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'is_admin',
    ];

    public static array $validationRules = [
        'ticket_id' => 'required|numeric|exists:tickets,id',
        'user_id' => 'required|numeric|exists:users,id',
        'message' => 'required|string',
        'is_admin' => 'required|boolean',
    ];

    /**
     * Get the ticket this message belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who sent this message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

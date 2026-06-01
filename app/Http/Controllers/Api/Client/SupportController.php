<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\TicketMessage;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class SupportController extends ClientApiController
{
    /**
     * Helper check to ensure support ticket system is enabled.
     */
    protected function checkEnabled()
    {
        if (!config('pterodactyl.tickets.enabled', true)) {
            abort(403, 'The support ticket system is currently disabled by administrator.');
        }

        // Clean up tickets based on inactivity and closed settings
        $inactiveDays = (int) config('pterodactyl.tickets.clear_inactive_days', 2);
        $closedDays = (int) config('pterodactyl.tickets.clear_closed_days', 1);

        Ticket::whereIn('status', ['open', 'review'])
            ->where('updated_at', '<', now()->subDays($inactiveDays))
            ->delete();

        Ticket::where('status', 'closed')
            ->where('updated_at', '<', now()->subDays($closedDays))
            ->delete();
    }

    /**
     * Get all tickets for the user.
     */
    public function index(ClientApiRequest $request): JsonResponse
    {
        $this->checkEnabled();

        $tickets = Ticket::where('user_id', $request->user()->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($tickets);
    }

    /**
     * Create a new ticket.
     */
    public function store(Request $request): JsonResponse
    {
        $this->checkEnabled();

        // Check active open tickets limit
        $openTicketsCount = Ticket::where('user_id', $request->user()->id)
            ->whereIn('status', ['open', 'review'])
            ->count();

        $limit = (int) config('pterodactyl.tickets.limit', 3);
        if ($openTicketsCount >= $limit) {
            abort(400, "You have reached the maximum limit of open support tickets ($limit). Please close one before opening another.");
        }

        $request->validate([
            'title' => 'required|string|max:191',
            'message' => 'required|string',
        ]);

        $ticket = Ticket::create([
            'user_id' => $request->user()->id,
            'title' => $request->input('title'),
            'status' => 'open',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->input('message'),
            'is_admin' => false,
        ]);

        return response()->json($ticket, JsonResponse::HTTP_CREATED);
    }

    /**
     * View a ticket with messages.
     */
    public function view(ClientApiRequest $request, Ticket $ticket): JsonResponse
    {
        $this->checkEnabled();

        if ($ticket->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to support ticket.');
        }

        $ticket->load(['messages.user' => function ($query) {
            $query->select('id', 'username', 'email');
        }]);

        return response()->json($ticket);
    }

    /**
     * Post a message to a ticket.
     */
    public function storeMessage(Request $request, Ticket $ticket): JsonResponse
    {
        $this->checkEnabled();

        if ($ticket->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to support ticket.');
        }

        if ($ticket->status === 'closed') {
            abort(400, 'Cannot post a message to a closed ticket.');
        }

        // Message limit check (only active if status is 'open', bypass if status is 'review')
        if ($ticket->status === 'open') {
            $messageCount = $ticket->messages()->where('is_admin', false)->count();
            $messageLimit = (int) config('pterodactyl.tickets.message_limit', 10);
            if ($messageCount >= $messageLimit) {
                abort(400, "You have reached the maximum message limit ($messageLimit) for this ticket. Please wait for an administrator to review it.");
            }
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->input('message'),
            'is_admin' => false,
        ]);

        $ticket->touch();

        return response()->json($message->load('user:id,username,email'), JsonResponse::HTTP_CREATED);
    }

    /**
     * Close the ticket.
     */
    public function close(ClientApiRequest $request, Ticket $ticket): JsonResponse
    {
        $this->checkEnabled();

        if ($ticket->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to support ticket.');
        }

        $ticket->update(['status' => 'closed']);

        return response()->json($ticket);
    }
}

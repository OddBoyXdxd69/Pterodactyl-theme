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
     * Get all tickets for the user.
     */
    public function index(ClientApiRequest $request): JsonResponse
    {
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
        if ($ticket->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to support ticket.');
        }

        if ($ticket->status === 'closed') {
            abort(400, 'Cannot post a message to a closed ticket.');
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
        if ($ticket->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to support ticket.');
        }

        $ticket->update(['status' => 'closed']);

        return response()->json($ticket);
    }
}

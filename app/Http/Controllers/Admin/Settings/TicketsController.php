<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Models\TicketMessage;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;

class TicketsController extends Controller
{
    /**
     * TicketsController constructor.
     */
    public function __construct(private AlertsMessageBag $alert)
    {
    }

    /**
     * Render the admin tickets overview page.
     */
    public function index(): View
    {
        Ticket::where('updated_at', '<', now()->subDays(2))->delete();

        $tickets = Ticket::with(['user'])->orderBy('status', 'asc')->orderBy('updated_at', 'desc')->paginate(20);

        return view('admin.settings.tickets.index', [
            'tickets' => $tickets,
        ]);
    }

    /**
     * Show a specific ticket.
     */
    public function view(Ticket $ticket): View
    {
        $ticket->load(['user', 'messages.user']);

        return view('admin.settings.tickets.view', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Post a reply to a ticket.
     */
    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        if ($ticket->status === 'closed') {
            $this->alert->danger('Cannot post a message to a closed ticket.')->flash();
            return redirect()->route('admin.tickets.view', $ticket->id);
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->input('message'),
            'is_admin' => true,
        ]);

        $ticket->touch();

        $this->alert->success('Reply has been posted successfully.')->flash();

        return redirect()->route('admin.tickets.view', $ticket->id);
    }

    /**
     * Close (end) a ticket.
     */
    public function close(Ticket $ticket): RedirectResponse
    {
        $ticket->update(['status' => 'closed']);

        $this->alert->success('Ticket has been closed successfully.')->flash();

        return redirect()->route('admin.tickets.view', $ticket->id);
    }

    /**
     * Mark a ticket under review.
     */
    public function review(Ticket $ticket): RedirectResponse
    {
        $ticket->update(['status' => 'review']);

        $this->alert->success('Ticket has been marked under review successfully.')->flash();

        return redirect()->route('admin.tickets.view', $ticket->id);
    }

    /**
     * Delete a ticket.
     */
    public function delete(Ticket $ticket): RedirectResponse
    {
        $ticket->delete();

        $this->alert->success('Ticket has been deleted successfully.')->flash();

        return redirect()->route('admin.tickets');
    }
}

import React, { useEffect, useState, useRef } from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import http from '@/api/http';
import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faHeadset,
    faPlus,
    faPaperPlane,
    faLock,
    faInfoCircle,
    faUser,
    faUserShield,
    faClock,
    faInbox,
    faTimes
} from '@fortawesome/free-solid-svg-icons';
import Button from '@/components/elements/Button';
import Input from '@/components/elements/Input';
import Spinner from '@/components/elements/Spinner';
import GreyRowBox from '@/components/elements/GreyRowBox';

interface TicketMessage {
    id: number;
    ticket_id: number;
    user_id: number;
    message: string;
    is_admin: boolean;
    created_at: string;
    user?: {
        id: number;
        username: string;
        email: string;
    };
}

interface Ticket {
    id: number;
    user_id: number;
    title: string;
    status: 'open' | 'closed';
    created_at: string;
    updated_at: string;
    messages?: TicketMessage[];
}

export default () => {
    const { clearAndAddHttpError, clearFlashes, addFlash } = useFlash();

    const [tickets, setTickets] = useState<Ticket[]>([]);
    const [selectedTicket, setSelectedTicket] = useState<Ticket | null>(null);
    const [loadingTickets, setLoadingTickets] = useState(true);
    const [loadingMessages, setLoadingMessages] = useState(false);
    
    // Create ticket states
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [ticketTitle, setTicketTitle] = useState('');
    const [ticketMessage, setTicketMessage] = useState('');
    const [isCreating, setIsCreating] = useState(false);

    // Send reply states
    const [replyText, setReplyText] = useState('');
    const [isReplying, setIsReplying] = useState(false);
    const [isClosing, setIsClosing] = useState(false);

    const chatEndRef = useRef<HTMLDivElement>(null);

    const loadTickets = () => {
        setLoadingTickets(true);
        http.get('/api/client/tickets')
            .then((res) => setTickets(res.data))
            .catch((err) => {
                console.error(err);
                addFlash({ key: 'support', type: 'danger', message: 'Failed to load tickets. Please refresh.' });
            })
            .finally(() => setLoadingTickets(false));
    };

    useEffect(() => {
        loadTickets();
    }, []);

    useEffect(() => {
        if (chatEndRef.current) {
            chatEndRef.current.scrollIntoView({ behavior: 'smooth' });
        }
    }, [selectedTicket?.messages]);

    const handleSelectTicket = (ticket: Ticket) => {
        setSelectedTicket(ticket);
        setLoadingMessages(true);
        clearFlashes('support-chat');

        http.get(`/api/client/tickets/${ticket.id}`)
            .then((res) => {
                setSelectedTicket(res.data);
            })
            .catch((err) => {
                console.error(err);
                addFlash({ key: 'support-chat', type: 'danger', message: 'Failed to retrieve conversation messages.' });
            })
            .finally(() => setLoadingMessages(false));
    };

    const handleCreateTicket = (e: React.FormEvent) => {
        e.preventDefault();
        if (!ticketTitle.trim() || !ticketMessage.trim()) return;

        setIsCreating(true);
        clearFlashes('support');

        http.post('/api/client/tickets', {
            title: ticketTitle,
            message: ticketMessage,
        })
            .then((res) => {
                addFlash({ key: 'support', type: 'success', message: 'Support ticket opened successfully!' });
                setShowCreateModal(false);
                setTicketTitle('');
                setTicketMessage('');
                loadTickets();
                handleSelectTicket(res.data);
            })
            .catch((err) => {
                console.error(err);
                clearAndAddHttpError({ key: 'support', error: err });
            })
            .finally(() => setIsCreating(false));
    };

    const handleSendReply = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedTicket || !replyText.trim()) return;

        setIsReplying(true);
        clearFlashes('support-chat');

        http.post(`/api/client/tickets/${selectedTicket.id}/messages`, {
            message: replyText,
        })
            .then((res) => {
                const updatedMessages = [...(selectedTicket.messages || []), res.data];
                setSelectedTicket({ ...selectedTicket, messages: updatedMessages });
                setReplyText('');
                // update tickets list to reflect last activity time
                setTickets(prev => prev.map(t => t.id === selectedTicket.id ? { ...t, updated_at: new Date().toISOString() } : t));
            })
            .catch((err) => {
                console.error(err);
                clearAndAddHttpError({ key: 'support-chat', error: err });
            })
            .finally(() => setIsReplying(false));
    };

    const handleCloseTicket = () => {
        if (!selectedTicket) return;
        if (!confirm('Are you sure you want to close this support ticket? This will mark it as resolved.')) return;

        setIsClosing(true);
        clearFlashes('support-chat');

        http.post(`/api/client/tickets/${selectedTicket.id}/close`)
            .then((res) => {
                setSelectedTicket({ ...selectedTicket, status: 'closed' });
                addFlash({ key: 'support-chat', type: 'success', message: 'Ticket closed successfully.' });
                loadTickets();
            })
            .catch((err) => {
                console.error(err);
                clearAndAddHttpError({ key: 'support-chat', error: err });
            })
            .finally(() => setIsClosing(false));
    };

    return (
        <PageContentBlock title={'Support Tickets'} showFlashKey={'support'}>
            <FlashMessageRender byKey={'support'} css={tw`mb-4`} />

            <div css={tw`flex flex-col lg:flex-row gap-6 min-h-[75vh]`}>
                {/* Left Side: Tickets List */}
                <div css={tw`w-full lg:w-96 flex flex-col gap-4 bg-neutral-900 border border-neutral-800 p-4 rounded-lg flex-shrink-0`}>
                    <div css={tw`flex justify-between items-center pb-3 border-b border-neutral-800`}>
                        <h3 css={tw`text-base font-bold text-gray-100 flex items-center gap-2`}>
                            <FontAwesomeIcon icon={faHeadset} css={tw`text-purple-400`} />
                            <span>My Support Tickets</span>
                        </h3>
                        <Button size={'xsmall'} onClick={() => setShowCreateModal(true)} css={tw`flex items-center gap-1.5`}>
                            <FontAwesomeIcon icon={faPlus} />
                            <span>Open Ticket</span>
                        </Button>
                    </div>

                    <div css={tw`flex-1 overflow-y-auto max-h-[60vh] lg:max-h-[65vh] space-y-2 pr-1`}>
                        {loadingTickets ? (
                            <div css={tw`flex justify-center items-center py-12`}>
                                <Spinner size={'large'} />
                            </div>
                        ) : tickets.length === 0 ? (
                            <div css={tw`text-center py-12 text-neutral-400`}>
                                <FontAwesomeIcon icon={faInbox} size={'2x'} css={tw`text-neutral-700 mb-2`} />
                                <p css={tw`text-sm`}>No tickets found.</p>
                                <p css={tw`text-xs text-neutral-500 mt-1`}>Open a ticket to get support.</p>
                            </div>
                        ) : (
                            tickets.map((t) => (
                                <GreyRowBox
                                    key={t.id}
                                    onClick={() => handleSelectTicket(t)}
                                    css={[
                                        tw`flex flex-col items-start p-3 bg-neutral-800/40 border border-neutral-800 hover:border-purple-500 hover:bg-neutral-800/80 transition-all rounded-lg cursor-pointer gap-2 text-left`,
                                        selectedTicket?.id === t.id && tw`border-purple-600 bg-neutral-800`
                                    ]}
                                >
                                    <div css={tw`flex justify-between items-center w-full`}>
                                        <span css={tw`text-[10px] text-neutral-500 font-mono`}>#{t.id}</span>
                                        <span css={[
                                            tw`text-[9px] px-2 py-0.5 rounded font-semibold uppercase`,
                                            t.status === 'open' ? tw`bg-green-500/10 text-green-400` : tw`bg-neutral-700 text-neutral-400`
                                        ]}>
                                            {t.status}
                                        </span>
                                    </div>
                                    <div css={tw`font-semibold text-sm text-gray-200 truncate w-full`}>
                                        {t.title}
                                    </div>
                                    <div css={tw`text-[10px] text-neutral-400 flex items-center gap-1 mt-1`}>
                                        <FontAwesomeIcon icon={faClock} />
                                        <span>Updated {new Date(t.updated_at).toLocaleDateString()}</span>
                                    </div>
                                </GreyRowBox>
                            ))
                        )}
                    </div>
                </div>

                {/* Right Side: Chat Window */}
                <div css={tw`flex-1 flex flex-col bg-neutral-900 border border-neutral-800 p-4 rounded-lg min-w-0`}>
                    <FlashMessageRender byKey={'support-chat'} css={tw`mb-4`} />

                    {!selectedTicket ? (
                        <div css={tw`flex-1 flex flex-col justify-center items-center text-center text-neutral-400 p-8`}>
                            <FontAwesomeIcon icon={faHeadset} size={'3x'} css={tw`text-neutral-700 mb-3`} />
                            <h3 css={tw`text-lg font-bold text-gray-200`}>Direct Support Ticket System</h3>
                            <p css={tw`text-sm text-neutral-500 mt-2 max-w-sm`}>
                                Select a ticket from the left sidebar to view messages, send replies, or open a new support ticket using the button.
                            </p>
                        </div>
                    ) : (
                        <div css={tw`flex-1 flex flex-col h-full min-h-[50vh]`}>
                            {/* Chat Header */}
                            <div css={tw`flex justify-between items-center pb-3 border-b border-neutral-800 flex-shrink-0`}>
                                <div css={tw`min-w-0`}>
                                    <div css={tw`flex items-center gap-2`}>
                                        <span css={tw`text-xs text-neutral-500 font-mono`}>#{selectedTicket.id}</span>
                                        <h3 css={tw`text-base font-bold text-gray-100 truncate`}>{selectedTicket.title}</h3>
                                    </div>
                                    <p css={tw`text-[10px] text-neutral-400 mt-0.5`}>
                                        Opened on {new Date(selectedTicket.created_at).toLocaleString()}
                                    </p>
                                </div>
                                <div css={tw`flex items-center gap-2 flex-shrink-0`}>
                                    <span css={[
                                        tw`text-xs px-2.5 py-0.5 rounded font-semibold uppercase`,
                                        selectedTicket.status === 'open' ? tw`bg-green-500/10 text-green-400` : tw`bg-neutral-700 text-neutral-400`
                                    ]}>
                                        {selectedTicket.status}
                                    </span>
                                    {selectedTicket.status === 'open' && (
                                        <Button
                                            size={'xsmall'}
                                            color={'red'}
                                            onClick={handleCloseTicket}
                                            disabled={isClosing}
                                            css={tw`flex items-center gap-1.5`}
                                        >
                                            <FontAwesomeIcon icon={faLock} />
                                            <span>Close Ticket</span>
                                        </Button>
                                    )}
                                </div>
                            </div>

                            {/* Messages Stream */}
                            <div css={tw`flex-1 overflow-y-auto py-4 space-y-4 my-2 max-h-[45vh] lg:max-h-[50vh] pr-1`}>
                                {loadingMessages ? (
                                    <div css={tw`flex justify-center items-center py-12`}>
                                        <Spinner size={'large'} />
                                    </div>
                                ) : (
                                    selectedTicket.messages?.map((m) => (
                                        <div
                                            key={m.id}
                                            css={[
                                                tw`flex flex-col max-w-[85%] rounded-lg p-3 gap-1`,
                                                m.is_admin
                                                    ? tw`bg-purple-900/20 border border-purple-500/30 self-start mr-auto`
                                                    : tw`bg-neutral-800 border border-neutral-700/60 self-end ml-auto`
                                            ]}
                                        >
                                            <div css={tw`flex justify-between items-center gap-6`}>
                                                <span css={tw`text-[10px] font-bold flex items-center gap-1.5`}>
                                                    <FontAwesomeIcon
                                                        icon={m.is_admin ? faUserShield : faUser}
                                                        css={m.is_admin ? tw`text-purple-400` : tw`text-blue-400`}
                                                    />
                                                    <span css={m.is_admin ? tw`text-purple-300` : tw`text-blue-300`}>
                                                        {m.is_admin ? 'Staff Reply' : m.user?.username || 'You'}
                                                    </span>
                                                </span>
                                                <span css={tw`text-[9px] text-neutral-500 font-mono`}>
                                                    {new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                </span>
                                            </div>
                                            <div css={tw`text-sm text-gray-200 mt-1 whitespace-pre-wrap`}>
                                                {m.message}
                                            </div>
                                        </div>
                                    ))
                                )}
                                <div ref={chatEndRef} />
                            </div>

                            {/* Reply Input Bar */}
                            <div css={tw`pt-3 border-t border-neutral-800 flex-shrink-0`}>
                                {selectedTicket.status === 'open' ? (
                                    <form onSubmit={handleSendReply} css={tw`flex gap-2`}>
                                        <Input
                                            placeholder={'Type your message here...'}
                                            value={replyText}
                                            onChange={(e) => setReplyText(e.target.value)}
                                            disabled={isReplying}
                                            css={tw`flex-1`}
                                        />
                                        <Button
                                            type={'submit'}
                                            disabled={isReplying || !replyText.trim()}
                                            css={tw`flex justify-center items-center px-5`}
                                        >
                                            {isReplying ? (
                                                <Spinner size={'small'} />
                                            ) : (
                                                <>
                                                    <FontAwesomeIcon icon={faPaperPlane} css={tw`mr-2`} />
                                                    <span>Reply</span>
                                                </>
                                            )}
                                        </Button>
                                    </form>
                                ) : (
                                    <div css={tw`bg-neutral-800/40 border border-neutral-800 p-3 rounded text-center text-xs text-neutral-400 flex items-center justify-center gap-2`}>
                                        <FontAwesomeIcon icon={faInfoCircle} />
                                        <span>This ticket has been marked as resolved and closed. Open a new ticket if you need further help.</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Create Ticket Modal Dialog */}
            {showCreateModal && (
                <div css={tw`fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm`}>
                    <div css={tw`bg-neutral-900 border border-neutral-800 rounded-lg max-w-lg w-full p-6 relative flex flex-col gap-4`}>
                        {/* Header */}
                        <div css={tw`flex justify-between items-center pb-3 border-b border-neutral-800`}>
                            <h3 css={tw`text-lg font-bold text-gray-100 flex items-center gap-2`}>
                                <FontAwesomeIcon icon={faHeadset} css={tw`text-purple-400`} />
                                <span>Open Support Ticket</span>
                            </h3>
                            <button onClick={() => setShowCreateModal(false)} css={tw`text-neutral-400 hover:text-white focus:outline-none`} disabled={isCreating}>
                                <FontAwesomeIcon icon={faTimes} size={'lg'} />
                            </button>
                        </div>

                        {/* Form */}
                        <form onSubmit={handleCreateTicket} css={tw`flex flex-col gap-4`}>
                            <div css={tw`flex flex-col gap-2`}>
                                <label css={tw`text-xs font-bold text-neutral-400 uppercase`}>Subject Title</label>
                                <Input
                                    type="text"
                                    value={ticketTitle}
                                    onChange={(e) => setTicketTitle(e.target.value)}
                                    placeholder="Brief summary of your issue (e.g. MySQL connection error)"
                                    required
                                    disabled={isCreating}
                                />
                            </div>

                            <div css={tw`flex flex-col gap-2`}>
                                <label css={tw`text-xs font-bold text-neutral-400 uppercase`}>Details of request</label>
                                <textarea
                                    value={ticketMessage}
                                    onChange={(e) => setTicketMessage(e.target.value)}
                                    placeholder="Please describe your issue in detail. What happened? How can we reproduce it?..."
                                    required
                                    disabled={isCreating}
                                    rows={5}
                                    css={tw`w-full bg-neutral-800 border border-neutral-800 rounded p-3 text-sm text-gray-200 focus:outline-none focus:border-purple-600 transition-colors resize-none`}
                                />
                            </div>

                            {/* Footer Controls */}
                            <div css={tw`flex justify-end gap-3 pt-3 border-t border-neutral-800`}>
                                <Button color={'grey'} type="button" onClick={() => setShowCreateModal(false)} disabled={isCreating}>
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={isCreating || !ticketTitle.trim() || !ticketMessage.trim()} css={tw`flex items-center space-x-2`}>
                                    {isCreating ? (
                                        <>
                                            <Spinner size={'small'} />
                                            <span>Opening Ticket...</span>
                                        </>
                                    ) : (
                                        <>
                                            <FontAwesomeIcon icon={faPaperPlane} />
                                            <span>Submit Ticket</span>
                                        </>
                                    )}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </PageContentBlock>
    );
};

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { LuPlus, LuTrash2, LuDownload, LuSettings, LuCircleHelp, LuX, LuEllipsisVertical, LuMaximize2, LuMinimize2 } from 'react-icons/lu';
import MessageList from './MessageList';
import MessageInput from './MessageInput';
import ConversationList from './ConversationList';
import SettingsPanel from './SettingsPanel';
import PanelHeader from './PanelHeader';

const DEFAULT_WIDTH = 420;
const EXPANDED_WIDTH = 600;
const DEFAULT_HEIGHT = 620;
const POPUP_MARGIN = 16;

const ChatSidebar = () => {
    const [isOpen, setIsOpen] = useState(false);
    const [isExpanded, setIsExpanded] = useState(false);
    const [view, setView] = useState('chat');
    const [messages, setMessages] = useState([]);
    const [loading, setLoading] = useState(false);
    const [conversationId, setConversationId] = useState(null);
    const [showMenu, setShowMenu] = useState(false);
    const [inputText, setInputText] = useState('');
    const abortRef = useRef(null);
    const userStoppedRef = useRef(false);

    // Drag state.
    const [position, setPosition] = useState(null);
    const draggingRef = useRef(false);
    const dragStartRef = useRef({ x: 0, y: 0, posX: 0, posY: 0 });

    const panelWidth = isExpanded ? EXPANDED_WIDTH : DEFAULT_WIDTH;

    // Initialize position to bottom-right on first open.
    useEffect(() => {
        if (isOpen && !position) {
            setPosition({
                x: window.innerWidth - panelWidth - POPUP_MARGIN,
                y: window.innerHeight - DEFAULT_HEIGHT - POPUP_MARGIN,
            });
        }
    }, [isOpen]);

    useEffect(() => {
        if (!showMenu) return;
        const handleKeyDown = (e) => { if (e.key === 'Escape') setShowMenu(false); };
        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [showMenu]);

    useEffect(() => {
        if (!isOpen) return;
        const handleKeyDown = (e) => { if (e.key === 'Escape' && !showMenu) setIsOpen(false); };
        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [isOpen, showMenu]);

    useEffect(() => {
        const toggle = () => setIsOpen(prev => !prev);
        document.addEventListener('wpaia-toggle', toggle);
        return () => document.removeEventListener('wpaia-toggle', toggle);
    }, []);

    // Drag handler on header.
    const handleDragStart = useCallback((e) => {
        if (e.button !== 0) return;
        e.preventDefault();
        draggingRef.current = true;
        dragStartRef.current = {
            x: e.clientX,
            y: e.clientY,
            posX: position?.x ?? 0,
            posY: position?.y ?? 0,
        };

        const onMouseMove = (moveEvent) => {
            if (!draggingRef.current) return;
            const dx = moveEvent.clientX - dragStartRef.current.x;
            const dy = moveEvent.clientY - dragStartRef.current.y;
            setPosition({
                x: Math.max(0, Math.min(window.innerWidth - 100, dragStartRef.current.posX + dx)),
                y: Math.max(0, Math.min(window.innerHeight - 50, dragStartRef.current.posY + dy)),
            });
        };

        const onMouseUp = () => {
            draggingRef.current = false;
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        };

        document.body.style.cursor = 'grabbing';
        document.body.style.userSelect = 'none';
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    }, [position]);

    const loadConversation = useCallback(async (id) => {
        setLoading(true);
        try {
            const conv = await apiFetch({ path: `wally/v1/conversations/${id}` });
            setConversationId(id);
            setMessages(
                (conv.messages || []).map(m => ({
                    role: m.role,
                    content: m.content,
                    createdAt: m.created_at || null,
                }))
            );
        } catch {
            setMessages([{ role: 'assistant', content: 'Could not load conversation.' }]);
        } finally {
            setLoading(false);
        }
    }, []);

    const startNewConversation = useCallback(() => {
        setConversationId(null);
        setMessages([]);
        setInputText('');
    }, []);

    const handleStop = useCallback(() => {
        if (abortRef.current) {
            userStoppedRef.current = true;
            abortRef.current.abort();
        }
    }, []);

    const toggleExpanded = useCallback(() => {
        setIsExpanded(prev => {
            const nextExpanded = !prev;
            const nextWidth = nextExpanded ? EXPANDED_WIDTH : DEFAULT_WIDTH;
            setPosition(pos => {
                if (!pos) return pos;
                const deltaW = nextWidth - (prev ? EXPANDED_WIDTH : DEFAULT_WIDTH);
                return { ...pos, x: Math.max(0, pos.x - deltaW) };
            });
            return nextExpanded;
        });
    }, []);

    const exportChat = useCallback(() => {
        if (messages.length === 0) return;
        const lines = messages.map(m => `[${m.role.toUpperCase()}]\n${m.content}`);
        const blob = new Blob([lines.join('\n\n---\n\n')], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'conversation.txt';
        a.click();
        URL.revokeObjectURL(url);
    }, [messages]);

    const parseErrorResponse = async (response) => {
        try {
            const body = await response.json();
            return {
                message: body.message || body.data?.message || `Request failed (${response.status})`,
                status: response.status,
            };
        } catch {
            if (response.status === 429) return { message: 'Rate limit exceeded', status: 429 };
            if (response.status === 403) return { message: 'Permission denied', status: 403 };
            if (response.status >= 500) return { message: 'Internal server error', status: response.status };
            return { message: `Request failed (${response.status})`, status: response.status };
        }
    };

    const buildErrorFields = (message, status) => {
        let errorTitle = 'Request Failed';
        let errorDetail = null;
        let errorMessage = message;
        let errorExplanation = null;

        if (status === 429) {
            errorTitle = 'Rate Limit Reached';
            errorMessage = 'Too many requests sent in a short period.';
            errorExplanation = 'You have reached your message limit. Please wait a moment before trying again.';
        } else if (status === 403) {
            errorTitle = 'Permission Denied';
            errorMessage = 'Your account does not have access to this feature.';
            errorExplanation = 'You do not have permission to use the AI assistant. Contact your administrator.';
        } else if (status >= 500) {
            errorTitle = 'Server Error';
            errorDetail = `HTTP ${status}`;
            errorMessage = message;
            errorExplanation = 'The server encountered an error. Please try again in a moment.';
        } else if (message.includes('Failed to connect') || message.includes('connect to')) {
            errorTitle = 'Connection Failed';
            const match = message.match(/(localhost[^\s:]*(?::\d+)?|[\d.]+:\d+)/);
            errorDetail = match ? match[1] : null;
            errorMessage = message;
            errorExplanation = 'Could not connect to the AI service. Make sure the backend server is running.';
        } else if (message.includes('timed out') || message.includes('timeout')) {
            errorTitle = 'Request Timed Out';
            errorMessage = 'The request took too long to complete.';
            errorExplanation = 'The AI service may be busy. Please try again.';
        } else {
            errorExplanation = message;
            errorMessage = 'An unexpected error occurred.';
        }

        return { errorTitle, errorDetail, errorMessage, errorExplanation };
    };

    const sendMessage = async (text) => {
        const isNewConversation = conversationId === null;
        let receivedConvId = null;

        const userMessage = { role: 'user', content: text, createdAt: new Date().toISOString() };
        setMessages(prev => [...prev, userMessage]);
        setLoading(true);
        setInputText('');
        setMessages(prev => [...prev, { role: 'assistant', content: '', streaming: true, createdAt: new Date().toISOString() }]);

        const controller = new AbortController();
        abortRef.current = controller;
        const timeoutId = setTimeout(() => controller.abort(), 90000);

        try {
            const response = await fetch(`${wpaiaData.restUrl}chat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': wpaiaData.nonce },
                body: JSON.stringify({
                    message: text,
                    ...(conversationId !== null && { conversation_id: conversationId }),
                    stream: true,
                }),
                signal: controller.signal,
            });

            clearTimeout(timeoutId);
            if (!response.ok) {
                const parsed = await parseErrorResponse(response);
                const err = new Error(parsed.message);
                err.status = parsed.status;
                throw err;
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let sseBuffer = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                sseBuffer += decoder.decode(value, { stream: true });
                const lines = sseBuffer.split('\n');
                sseBuffer = lines.pop();

                for (const line of lines) {
                    const trimmed = line.trim();
                    if (!trimmed.startsWith('data: ')) continue;
                    let data;
                    try { data = JSON.parse(trimmed.slice(6)); } catch { continue; }

                    switch (data.type) {
                        case 'thinking_start':
                            setMessages(prev => { const u = [...prev]; const l = u[u.length-1]; if(l?.streaming) u[u.length-1] = {...l, thinking:'', thinkingDone:false}; return u; });
                            break;
                        case 'thinking':
                            setMessages(prev => { const u = [...prev]; const l = u[u.length-1]; if(l?.streaming && l.thinking !== undefined) u[u.length-1] = {...l, thinking: l.thinking+(data.content||'')}; return u; });
                            break;
                        case 'thinking_end':
                            setMessages(prev => { const u = [...prev]; const l = u[u.length-1]; if(l?.streaming) u[u.length-1] = {...l, thinkingDone:true}; return u; });
                            break;
                        case 'token':
                            setMessages(prev => { const u = [...prev]; const l = u[u.length-1]; if(l?.streaming) u[u.length-1] = {...l, content: l.content+(data.content||'')}; return u; });
                            break;
                        case 'conversation_id':
                            setConversationId(data.id); receivedConvId = data.id;
                            break;
                        case 'confirmation':
                            setMessages(prev => { const u = [...prev]; const l = u[u.length-1]; if(l?.streaming) u[u.length-1] = {...l, confirmation:{action_id:data.action_id,tool_name:data.tool_name,preview:data.preview,status:'pending'}}; return u; });
                            break;
                        case 'tool_start':
                            setMessages(prev => { const u = [...prev]; const l = u[u.length-1]; if(l?.streaming) u[u.length-1] = {...l, toolsRunning: data.tools||[]}; return u; });
                            break;
                        case 'error': {
                            const fields = buildErrorFields(data.message || 'An error occurred', null);
                            setMessages(prev => { const u = [...prev]; const l = u[u.length-1]; if(l?.streaming) u[u.length-1] = {...l, content: data.message, streaming:false, isError:true, retryText:text, ...fields}; return u; });
                            break;
                        }
                        case 'done':
                            setMessages(prev => { const u = [...prev]; const l = u[u.length-1]; if(l?.streaming) u[u.length-1] = {...l, streaming:false, toolsRunning:null}; return u; });
                            if (isNewConversation && receivedConvId) {
                                apiFetch({ path: `wally/v1/conversations/${receivedConvId}/title`, method: 'POST' }).catch(() => {});
                            }
                            break;
                    }
                }
            }

            setMessages(prev => { const u = [...prev]; const l = u[u.length-1]; if(l?.streaming) u[u.length-1] = {...l, streaming:false, toolsRunning:null}; return u; });
        } catch (err) {
            clearTimeout(timeoutId);
            if (err.name === 'AbortError') {
                const wasUserStop = userStoppedRef.current;
                userStoppedRef.current = false;
                if (wasUserStop) {
                    setMessages(prev => { const u = [...prev]; const l = u[u.length-1]; if(l?.streaming) u[u.length-1] = {...l, streaming:false}; return u; });
                    return;
                }
                const fields = buildErrorFields('The request timed out', null);
                setMessages(prev => { const u = [...prev]; const l = u[u.length-1]; if(l?.streaming) u[u.length-1] = {...l, content:'The request timed out.', streaming:false, isError:true, retryText:text, ...fields}; return u; });
            } else {
                const errorContent = err.message || 'Something went wrong. Please try again.';
                const fields = buildErrorFields(errorContent, err.status || null);
                setMessages(prev => { const u = [...prev]; const l = u[u.length-1]; if(l?.streaming) { u[u.length-1] = {...l, content: errorContent, streaming:false, isError:true, retryText:text, ...fields}; } else { u.push({role:'assistant', content:errorContent, isError:true, retryText:text, ...fields}); } return u; });
            }
        } finally {
            setLoading(false);
            abortRef.current = null;
        }
    };

    const updateConfirmationStatus = useCallback((actionId, newStatus, resultMessage) => {
        setMessages(prev => prev.map(msg => {
            if (msg.confirmation?.action_id === actionId) return { ...msg, confirmation: { ...msg.confirmation, status: newStatus } };
            return msg;
        }));
        if (resultMessage) setMessages(prev => [...prev, { role: 'assistant', content: resultMessage }]);
    }, []);

    const handleConfirm = useCallback(async (actionId) => {
        try {
            const response = await apiFetch({ path: `wally/v1/confirm/${actionId}`, method: 'POST', data: { approved: true } });
            updateConfirmationStatus(actionId, 'confirmed', response.result?.message || response.result?.error || 'Action completed.');
        } catch (err) { updateConfirmationStatus(actionId, 'pending', `Error: ${err.message}`); }
    }, [updateConfirmationStatus]);

    const handleReject = useCallback(async (actionId) => {
        try {
            await apiFetch({ path: `wally/v1/confirm/${actionId}`, method: 'POST', data: { approved: false } });
            updateConfirmationStatus(actionId, 'rejected', 'Action cancelled.');
        } catch (err) { updateConfirmationStatus(actionId, 'pending', `Error: ${err.message}`); }
    }, [updateConfirmationStatus]);

    const handleRetry = useCallback((retryText) => {
        setMessages(prev => {
            const updated = [...prev];
            if (updated.length && updated[updated.length - 1].isError) updated.pop();
            if (updated.length && updated[updated.length - 1].role === 'user') updated.pop();
            return updated;
        });
        sendMessage(retryText);
    }, [conversationId]);

    const handleDismissError = useCallback((index) => {
        setMessages(prev => prev.filter((_, i) => i !== index));
    }, []);

    if (!isOpen) return null;

    const conversationTitle = conversationId
        ? (messages[0]?.content?.slice(0, 40) || 'Conversation')
        : 'New Conversation';

    const menuItemCls = 'flex items-center gap-3 w-full px-4 py-2.5 border-0 bg-transparent text-sm text-wpaia-text hover:bg-wpaia-bg cursor-pointer text-left font-sans';

    return (
        <div
            className="fixed flex flex-col bg-wpaia-panel rounded-wpaia-panel font-sans overflow-hidden"
            role="complementary"
            aria-label="AI Assistant"
            style={{
                width: panelWidth, height: DEFAULT_HEIGHT,
                left: position?.x ?? 0, top: position?.y ?? 0,
                zIndex: 99999,
                boxShadow: '0 25px 60px -12px rgba(0,0,0,0.25), 0 0 0 1px rgba(0,0,0,0.06)',
                transition: 'width 0.2s ease',
            }}
        >
            {/* ── Header / drag handle (hidden in history view — ConversationList has its own) ── */}
            {view !== 'history' && (
                <PanelHeader
                    title={view === 'settings' ? 'Settings' : conversationTitle}
                    onBack={view === 'settings' ? () => setView('chat') : () => setView('history')}
                    onDragStart={handleDragStart}
                    borderBottom
                >
                    {view === 'chat' && (
                        <>
                            <button className={PanelHeader.circleBtn} onClick={toggleExpanded} aria-label={isExpanded ? 'Collapse panel' : 'Expand panel'}>
                                {isExpanded ? <LuMinimize2 size={18} /> : <LuMaximize2 size={18} />}
                            </button>
                            <button className={PanelHeader.circleBtn} onClick={() => setShowMenu(prev => !prev)} aria-label="Open menu" aria-expanded={showMenu} aria-haspopup="true">
                                <LuEllipsisVertical size={18} />
                            </button>
                        </>
                    )}
                    {view === 'settings' && (
                        <button className={PanelHeader.circleBtn} onClick={() => setIsOpen(false)} aria-label="Close AI Assistant"><LuX size={16} /></button>
                    )}
                    {showMenu && (
                        <>
                            <div className="fixed inset-0" style={{ zIndex: 1 }} onClick={() => setShowMenu(false)} aria-hidden="true" />
                            <div className="absolute right-5 top-16 bg-wpaia-panel rounded-wpaia-card border border-solid border-wpaia-border py-1.5 min-w-[200px]" style={{ zIndex: 2, boxShadow: '0 10px 25px -5px rgba(0,0,0,0.1)' }} role="menu">
                                <button className={menuItemCls} role="menuitem" onClick={() => { startNewConversation(); setShowMenu(false); }}><span className="text-wpaia-muted"><LuPlus size={15} /></span>New conversation</button>
                                <button className={menuItemCls} role="menuitem" onClick={() => { setMessages([]); setShowMenu(false); }}><span className="text-wpaia-muted"><LuTrash2 size={15} /></span>Clear conversation</button>
                                <button className={menuItemCls} role="menuitem" onClick={() => { exportChat(); setShowMenu(false); }}><span className="text-wpaia-muted"><LuDownload size={15} /></span>Export chat</button>
                                <div className="my-1.5 border-0 border-t border-solid border-wpaia-border" />
                                <button className={menuItemCls} role="menuitem" onClick={() => { setView('settings'); setShowMenu(false); }}><span className="text-wpaia-muted"><LuSettings size={15} /></span>Settings</button>
                                <button className={menuItemCls} role="menuitem" onClick={() => setShowMenu(false)}><span className="text-wpaia-muted"><LuCircleHelp size={15} /></span>Help &amp; shortcuts</button>
                                <button className={menuItemCls} role="menuitem" onClick={() => { setIsOpen(false); setShowMenu(false); }}><span className="text-wpaia-muted"><LuX size={15} /></span>Close</button>
                            </div>
                        </>
                    )}
                </PanelHeader>
            )}

            {view === 'history' && (
                <ConversationList currentId={conversationId} onSelect={(id) => { loadConversation(id); setView('chat'); }} onNew={() => { startNewConversation(); setView('chat'); }} onClose={() => setIsOpen(false)} onDragStart={handleDragStart} fullView />
            )}
            {view === 'settings' && <SettingsPanel onBack={() => setView('chat')} />}
            {view === 'chat' && (
                <>
                    <MessageList messages={messages} loading={loading} onConfirm={handleConfirm} onReject={handleReject} onRetry={handleRetry} onDismissError={handleDismissError} onChipSelect={(chipText) => setInputText(chipText)} />
                    <MessageInput onSend={sendMessage} disabled={loading || messages.some(m => m.confirmation?.status === 'pending')} value={inputText} onChange={setInputText} isStreaming={loading} onStop={handleStop} placeholder={messages.some(m => m.confirmation?.status === 'pending') ? 'Waiting for confirmation…' : undefined} />
                </>
            )}
        </div>
    );
};

export default ChatSidebar;

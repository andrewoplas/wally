import { useState, useEffect, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { LuPlus, LuTrash2, LuSearch, LuPlug, LuFileText, LuShield, LuMessageSquare, LuX } from 'react-icons/lu';
import PanelHeader from './PanelHeader';

const getAvatarConfig = (title = '') => {
    const t = title.toLowerCase();
    if (/plugin|update|install|activat/i.test(t)) return { color: '#8B5CF6', iconColor: '#FFFFFF', Icon: LuPlug };
    if (/post|page|content|creat|publish|draft/i.test(t)) return { color: '#14B8A6', iconColor: '#FFFFFF', Icon: LuFileText };
    if (/search|replac|find/i.test(t)) return { color: '#F472B6', iconColor: '#FFFFFF', Icon: LuSearch };
    if (/health|securit|error|warn|site|option/i.test(t)) return { color: '#E4E4E7', iconColor: '#71717A', Icon: LuShield };
    return { color: '#E4E4E7', iconColor: '#71717A', Icon: LuMessageSquare };
};

const getDateGroup = (dateStr) => {
    if (!dateStr) return 'Older';
    const date = new Date(dateStr);
    const now = new Date();
    const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterdayStart = new Date(todayStart);
    yesterdayStart.setDate(yesterdayStart.getDate() - 1);
    if (date >= todayStart) return 'Recent';
    if (date >= yesterdayStart) return 'Yesterday';
    return 'Older';
};

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now - date;
    const mins = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins}m`;
    if (hours < 24) return `${hours}h`;
    if (days < 7) return `${days}d`;
    return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
};

const ConversationList = ({ currentId, onSelect, onNew, onClose, onBack, onDragStart, fullView = false }) => {
    const [conversations, setConversations] = useState([]);
    const [deleting, setDeleting] = useState(null);
    const [search, setSearch] = useState('');

    const fetchConversations = () => {
        apiFetch({ path: 'wally/v1/conversations' })
            .then(setConversations)
            .catch(() => {});
    };

    useEffect(() => { fetchConversations(); }, [currentId]);

    const handleDelete = async (e, id) => {
        e.stopPropagation();
        setDeleting(id);
        try {
            await apiFetch({ path: `wally/v1/conversations/${id}`, method: 'DELETE' });
            setConversations(prev => prev.filter(c => c.id !== id));
            if (currentId === id) onNew();
        } catch {} finally { setDeleting(null); }
    };

    const grouped = useMemo(() => {
        const query = search.trim().toLowerCase();
        const filtered = query
            ? conversations.filter(c => (c.title || 'Untitled conversation').toLowerCase().includes(query))
            : conversations;
        const groups = { Recent: [], Yesterday: [], Older: [] };
        filtered.forEach(conv => { groups[getDateGroup(conv.updated_at)].push(conv); });
        return groups;
    }, [conversations, search]);

    const totalFiltered = Object.values(grouped).flat().length;

    return (
        <div className={`flex flex-col${fullView ? ' flex-1 overflow-hidden' : ''}`}>
            {/* Header / drag handle */}
            <PanelHeader title="History" onBack={onBack} onDragStart={onDragStart}>
                <button
                    className={PanelHeader.circleBtn}
                    onClick={onNew}
                    aria-label="New conversation"
                    title="New conversation"
                >
                    <LuPlus size={18} />
                </button>
                {onClose && (
                    <button className={PanelHeader.circleBtn} onClick={onClose} aria-label="Close">
                        <LuX size={16} />
                    </button>
                )}
            </PanelHeader>

            {/* Search */}
            <div className="px-6 pb-3">
                <div className="relative">
                    <span className="absolute left-4 top-1/2 -translate-y-1/2 text-wpaia-hint" aria-hidden="true">
                        <LuSearch size={16} />
                    </span>
                    <input
                        type="search"
                        className="w-full pl-11 pr-4 py-3 text-sm bg-wpaia-bg border-0 rounded-full font-sans text-wpaia-text placeholder:text-wpaia-hint focus:outline-none focus:ring-2 focus:ring-wpaia-primary/20"
                        placeholder="Search conversations..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        aria-label="Search conversations"
                    />
                </div>
            </div>

            {/* Divider */}
            <div className="h-px bg-wpaia-border" />

            {/* List */}
            <div className="flex-1 overflow-y-auto px-3 pb-2 wpaia-scrollbar" role="list" aria-label="Conversation history">
                {totalFiltered === 0 && (
                    <div className="py-8 text-center text-xs text-wpaia-muted">
                        {search ? 'No results found.' : 'No conversations yet.'}
                    </div>
                )}

                {['Recent', 'Yesterday', 'Older'].map(groupLabel => {
                    const items = grouped[groupLabel];
                    if (items.length === 0) return null;
                    return (
                        <div key={groupLabel}>
                            <div className="px-3 pt-3 pb-2 text-[11px] font-semibold text-wpaia-hint uppercase tracking-wider">{groupLabel}</div>
                            <div className="flex flex-col gap-0.5">
                                {items.map(conv => {
                                    const { color, iconColor, Icon } = getAvatarConfig(conv.title);
                                    const isActive = conv.id === currentId;
                                    return (
                                        <div
                                            key={conv.id}
                                            className={`flex items-center gap-3.5 px-4 py-3.5 rounded-2xl cursor-pointer group transition-colors ${isActive ? 'bg-wpaia-primary/[0.08]' : 'hover:bg-wpaia-bg'}`}
                                            onClick={() => onSelect(conv.id)}
                                            role="listitem"
                                            tabIndex={0}
                                            onKeyDown={(e) => e.key === 'Enter' && onSelect(conv.id)}
                                            aria-current={isActive ? 'true' : undefined}
                                        >
                                            <div className="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full" style={{ background: color }} aria-hidden="true">
                                                <Icon size={18} color={iconColor} />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="text-[15px] text-wpaia-text truncate font-semibold leading-tight">{conv.title || 'Untitled conversation'}</div>
                                            </div>
                                            <div className="flex items-center gap-1.5 flex-shrink-0">
                                                <span className="text-xs text-wpaia-hint font-medium">{formatTime(conv.updated_at)}</span>
                                                <button
                                                    className="hidden group-hover:flex items-center justify-center w-7 h-7 border-0 bg-transparent rounded-lg text-wpaia-hint hover:text-wpaia-error-text cursor-pointer transition-colors"
                                                    onClick={(e) => handleDelete(e, conv.id)}
                                                    disabled={deleting === conv.id}
                                                    aria-label="Delete conversation"
                                                    title="Delete"
                                                >
                                                    {deleting === conv.id ? '…' : <LuTrash2 size={14} />}
                                                </button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

export default ConversationList;

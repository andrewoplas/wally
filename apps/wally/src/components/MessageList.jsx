import { useEffect, useRef } from '@wordpress/element';
import { LuPlug, LuFileText, LuSearch, LuShield, LuSparkles, LuCheck, LuRefreshCw, LuX, LuCircleAlert } from 'react-icons/lu';
import ConfirmAction from './ConfirmAction';
import MarkdownContent from './MarkdownContent';
import ThinkingBlock from './ThinkingBlock';

const formatTimestamp = (dateStr) => {
    if (!dateStr) return null;
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now - date;
    const mins = Math.floor(diff / 60000);

    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins}m ago`;

    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    if (diff < 7 * 86400000) {
        return date.toLocaleDateString([], { weekday: 'short' }) + ' ' +
            date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    return date.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' ' +
        date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
};

const SUGGESTION_CHIPS = [
    { icon: LuPlug, label: 'Update plugins', iconColor: '#8B5CF6' },
    { icon: LuFileText, label: 'Create a post', iconColor: '#14B8A6' },
    { icon: LuSearch, label: 'Search & replace', iconColor: '#F472B6' },
    { icon: LuShield, label: 'Site health', iconColor: '#71717A' },
];

const AssistantAvatar = () => (
    <div className="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-wpaia-primary text-white" aria-hidden="true">
        <LuSparkles size={16} />
    </div>
);

const ToolProgressCard = ({ toolsRunning }) => {
    const title = toolsRunning.length === 1
        ? toolsRunning[0].replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) + '…'
        : `Running ${toolsRunning.length} tools…`;

    return (
        <div className="mt-2 rounded-2xl bg-wpaia-bg p-4">
            <div className="flex items-center gap-2.5">
                <span className="wpaia-spinner flex-shrink-0" aria-hidden="true" />
                <div className="flex flex-col gap-0.5">
                    <span className="text-[13px] font-semibold text-wpaia-text">{title}</span>
                </div>
            </div>
            {toolsRunning.length > 1 && (
                <div className="mt-3 flex flex-col gap-2 pl-[38px]">
                    {toolsRunning.map((tool, i) => (
                        <div key={i} className="flex items-center gap-2 text-xs text-wpaia-muted">
                            <span className="wpaia-spinner-sm flex-shrink-0" aria-hidden="true" />
                            <span>{tool.replace(/_/g, ' ')}</span>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

const ToolResultCard = ({ status, toolName, detail }) => (
    <div className="mt-2 rounded-2xl bg-wpaia-bg p-4">
        <div className="flex items-center gap-2.5">
            <span className={`flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-lg text-white ${status === 'success' ? 'bg-[#14B8A6]' : status === 'error' ? 'bg-wpaia-error-text' : 'bg-wpaia-muted'}`}>
                {status === 'success' ? <LuCheck size={14} /> : status === 'running' ? <span className="wpaia-spinner-sm" /> : <LuX size={14} />}
            </span>
            <div className="flex flex-col gap-0.5">
                <span className="text-[13px] font-semibold text-wpaia-text">{toolName}</span>
                {detail && <span className="text-xs text-wpaia-muted">{detail}</span>}
            </div>
        </div>
    </div>
);

const ErrorCard = ({ errorTitle, errorDetail, errorMessage }) => (
    <div className="rounded-2xl bg-wpaia-error-bg border border-solid border-[#FECACA] p-4 flex flex-col gap-2.5">
        {/* Error Header */}
        <div className="flex items-center gap-2.5">
            <span className="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-lg bg-[#FECACA]">
                <LuX size={14} className="text-wpaia-error-text" />
            </span>
            <div className="flex flex-col gap-px">
                <span className="text-[13px] font-semibold text-[#991B1B]">{errorTitle}</span>
                {errorDetail && <span className="text-xs text-wpaia-error-text">{errorDetail}</span>}
            </div>
        </div>
        {/* Divider */}
        <div className="h-px bg-[#FECACA] opacity-60" />
        {/* Error Message */}
        <div className="flex items-center gap-2 rounded-wpaia-card bg-white p-2.5 px-3.5">
            <LuCircleAlert size={14} className="flex-shrink-0 text-wpaia-error-text" />
            <span className="text-[13px] text-[#991B1B] leading-snug">{errorMessage}</span>
        </div>
    </div>
);

const MessageList = ({ messages, loading, onConfirm, onReject, onRetry, onDismissError, onChipSelect }) => {
    const endRef = useRef(null);

    useEffect(() => {
        endRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, loading]);

    const renderContent = (msg) => {
        if (msg.role === 'assistant' && msg.content) {
            return <MarkdownContent content={msg.content} />;
        }
        return msg.content;
    };

    return (
        <div className="flex-1 overflow-y-auto px-5 py-5 wpaia-scrollbar" role="log" aria-live="polite">
            {messages.length === 0 && !loading && (
                <div className="flex flex-col items-center justify-center text-center px-6 h-full min-h-[300px] gap-8" style={{ paddingBottom: 16 }}>
                    <div className="flex flex-col items-center gap-4">
                        <div className="wpaia-empty-icon-gradient flex items-center justify-center w-[72px] h-[72px] rounded-full" aria-hidden="true">
                            <LuSparkles size={32} color="#FFFFFF" />
                        </div>
                        <h2 className="text-[28px] font-bold text-[#18181B] font-heading m-0 leading-tight">
                            How can I help?
                        </h2>
                        <p className="text-sm text-wpaia-muted max-w-[300px] leading-normal m-0">
                            Ask me anything about your WordPress site. I can manage plugins, create content, and more.
                        </p>
                    </div>
                    <div className="flex flex-col gap-2.5 w-full" role="group" aria-label="Suggestion prompts">
                        <div className="flex gap-2.5 w-full">
                            {SUGGESTION_CHIPS.slice(0, 2).map(({ icon: Icon, label, iconColor }) => (
                                <button
                                    key={label}
                                    className="flex items-center gap-2.5 flex-1 py-3.5 px-4 bg-wpaia-bg border-0 rounded-2xl cursor-pointer text-[13px] font-medium text-[#18181B] text-left font-sans transition-colors hover:bg-wpaia-bg/80"
                                    onClick={() => onChipSelect?.(label)}
                                    type="button"
                                >
                                    <span className="flex-shrink-0 flex items-center" aria-hidden="true"><Icon size={16} color={iconColor} /></span>
                                    <span>{label}</span>
                                </button>
                            ))}
                        </div>
                        <div className="flex gap-2.5 w-full">
                            {SUGGESTION_CHIPS.slice(2, 4).map(({ icon: Icon, label, iconColor }) => (
                                <button
                                    key={label}
                                    className="flex items-center gap-2.5 flex-1 py-3.5 px-4 bg-wpaia-bg border-0 rounded-2xl cursor-pointer text-[13px] font-medium text-[#18181B] text-left font-sans transition-colors hover:bg-wpaia-bg/80"
                                    onClick={() => onChipSelect?.(label)}
                                    type="button"
                                >
                                    <span className="flex-shrink-0 flex items-center" aria-hidden="true"><Icon size={16} color={iconColor} /></span>
                                    <span>{label}</span>
                                </button>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            {messages.map((msg, i) => (
                <div
                    key={i}
                    className={`flex mb-4 ${msg.role === 'user' ? 'flex-col items-end gap-1' : 'flex-row items-start gap-2.5'}`}
                >
                    {msg.role === 'user' ? (
                        <>
                            <div className="wpaia-user-bubble">
                                {msg.isError ? (
                                    <div className="flex flex-col gap-3">
                                        <ErrorCard
                                            errorTitle={msg.errorTitle || 'Connection Failed'}
                                            errorDetail={msg.errorDetail || null}
                                            errorMessage={msg.errorMessage || msg.content || 'Something went wrong'}
                                        />
                                    </div>
                                ) : (
                                    <div className="text-[15px] leading-normal">{renderContent(msg)}</div>
                                )}
                            </div>
                            {msg.createdAt && !msg.streaming && (
                                <span className="text-[10px] text-wpaia-hint">{formatTimestamp(msg.createdAt)}</span>
                            )}
                        </>
                    ) : (
                        <>
                            <AssistantAvatar />
                            <div className="flex-1 min-w-0">
                                {msg.thinking !== undefined && (
                                    <ThinkingBlock content={msg.thinking} isStreaming={!msg.thinkingDone} />
                                )}
                                {msg.streaming && !msg.content && !msg.thinking && !msg.toolsRunning && (
                                    <div className="pt-1.5">
                                        <div className="flex items-center gap-2 mb-3">
                                            <span className="text-sm font-medium text-wpaia-primary">Thinking</span>
                                            <span className="flex items-center gap-1 pt-0.5">
                                                <span className="wpaia-dot" />
                                                <span className="wpaia-dot" />
                                                <span className="wpaia-dot" />
                                            </span>
                                        </div>
                                        <div className="flex flex-col gap-2.5" aria-hidden="true">
                                            <div className="h-3 rounded-md wpaia-shimmer" style={{ width: '72%' }} />
                                            <div className="h-3 rounded-md wpaia-shimmer" style={{ width: '55%' }} />
                                            <div className="h-3 rounded-md wpaia-shimmer" style={{ width: '39%' }} />
                                        </div>
                                    </div>
                                )}
                                {msg.isError ? (
                                    <div className="flex flex-col gap-3">
                                        <ErrorCard
                                            errorTitle={msg.errorTitle || 'Connection Failed'}
                                            errorDetail={msg.errorDetail || null}
                                            errorMessage={msg.errorMessage || msg.content || 'Something went wrong'}
                                        />
                                        {msg.errorExplanation && (
                                            <p className="text-[15px] leading-relaxed text-wpaia-text m-0">{msg.errorExplanation}</p>
                                        )}
                                        {msg.retryText && onRetry && (
                                            <div className="flex gap-2.5">
                                                <button
                                                    className="flex items-center gap-2 h-10 px-[18px] text-[13px] font-semibold text-white bg-wpaia-primary border-0 rounded-full cursor-pointer hover:bg-wpaia-primary-hover transition-colors"
                                                    onClick={() => onRetry(msg.retryText)}
                                                >
                                                    <LuRefreshCw size={14} />
                                                    Retry
                                                </button>
                                                <button
                                                    className="flex items-center gap-2 h-10 px-[18px] text-[13px] font-semibold text-[#52525B] bg-wpaia-bg border-0 rounded-full cursor-pointer hover:bg-wpaia-border transition-colors"
                                                    onClick={() => onDismissError?.(i)}
                                                >
                                                    Dismiss
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <>
                                        <div className="text-[15px] leading-normal text-wpaia-text">{renderContent(msg)}</div>
                                        {msg.streaming && msg.content && <span className="wpaia-cursor" />}
                                    </>
                                )}
                                {msg.toolsRunning?.length > 0 && <ToolProgressCard toolsRunning={msg.toolsRunning} />}
                                {msg.toolResults?.length > 0 && msg.toolResults.map((result, j) => <ToolResultCard key={j} {...result} />)}
                                {msg.confirmation && <ConfirmAction confirmation={msg.confirmation} onConfirm={onConfirm} onReject={onReject} />}
                                {msg.createdAt && !msg.streaming && (
                                    <span className="text-[10px] text-wpaia-hint mt-1 block">{formatTimestamp(msg.createdAt)}</span>
                                )}
                            </div>
                        </>
                    )}
                </div>
            ))}

            {loading && (messages.length === 0 || !messages[messages.length - 1]?.streaming) && (
                <div className="flex items-start gap-2.5 mb-4" aria-label="Thinking">
                    <AssistantAvatar />
                    <div className="pt-1.5">
                        <div className="flex items-center gap-2 mb-3">
                            <span className="text-sm font-medium text-wpaia-primary">Thinking</span>
                            <span className="flex items-center gap-1 pt-0.5">
                                <span className="wpaia-dot" />
                                <span className="wpaia-dot" />
                                <span className="wpaia-dot" />
                            </span>
                        </div>
                        <div className="flex flex-col gap-2.5" aria-hidden="true">
                            <div className="h-3 rounded-md wpaia-shimmer" style={{ width: 260 }} />
                            <div className="h-3 rounded-md wpaia-shimmer" style={{ width: 200 }} />
                            <div className="h-3 rounded-md wpaia-shimmer" style={{ width: 140 }} />
                        </div>
                    </div>
                </div>
            )}
            <div ref={endRef} />
        </div>
    );
};

export default MessageList;

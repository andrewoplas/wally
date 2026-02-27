import { useState } from '@wordpress/element';

const ThinkingBlock = ({ content, isStreaming }) => {
    const [expanded, setExpanded] = useState(false);

    return (
        <div className="mb-2">
            <button
                className="flex items-center gap-1.5 px-0 py-1 border-0 bg-transparent text-xs text-wpaia-muted hover:text-wpaia-text cursor-pointer font-sans"
                onClick={() => setExpanded(prev => !prev)}
                aria-expanded={expanded}
            >
                <span className="text-[10px]">{expanded ? '\u25BC' : '\u25B6'}</span>
                {isStreaming ? '\u23F3 Thinking\u2026' : '\uD83D\uDCAD View thinking process'}
            </button>
            {expanded && (
                <div className="mt-1 px-3 py-2 text-xs text-wpaia-muted bg-wpaia-bg rounded-lg border border-wpaia-border whitespace-pre-wrap font-mono leading-relaxed">
                    {content || '\u2026'}
                </div>
            )}
        </div>
    );
};

export default ThinkingBlock;

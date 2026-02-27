import { useState, useRef, useEffect } from '@wordpress/element';
import { LuArrowUp, LuSquare } from 'react-icons/lu';

const MessageInput = ({ onSend, disabled, value: externalValue, onChange: externalOnChange, isStreaming, onStop, placeholder }) => {
    const [internalText, setInternalText] = useState('');
    const textareaRef = useRef(null);

    const isControlled = externalValue !== undefined;
    const text = isControlled ? externalValue : internalText;
    const setText = isControlled ? externalOnChange : setInternalText;

    useEffect(() => {
        if (isControlled && externalValue && textareaRef.current) {
            textareaRef.current.focus();
        }
    }, [isControlled, externalValue]);

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!text.trim() || disabled) return;
        onSend(text.trim());
        setText('');
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSubmit(e);
        }
    };

    const isDisabledInput = disabled && !isStreaming;

    return (
        <div className="flex-shrink-0 border-0 border-t border-solid border-wpaia-border bg-wpaia-panel px-5 pt-3 pb-5">
            <form className="flex items-end gap-2.5" onSubmit={handleSubmit}>
                <div className={`flex-1 flex items-center rounded-full bg-wpaia-bg px-[18px]${isDisabledInput ? ' opacity-50' : ''}`} style={{ minHeight: 48 }}>
                    <textarea
                        ref={textareaRef}
                        value={text}
                        onChange={(e) => setText(e.target.value)}
                        onKeyDown={handleKeyDown}
                        placeholder={placeholder || 'Ask anything about your site...'}
                        disabled={disabled}
                        rows={1}
                        aria-label="Message input"
                        className="w-full resize-none border-0 bg-transparent py-3 text-[15px] text-wpaia-text font-sans placeholder:text-wpaia-hint focus:outline-none leading-normal focus:shadow-none"
                    />
                </div>
                {isStreaming ? (
                    <button
                        type="button"
                        className="flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-full border-0 cursor-pointer hover:opacity-90 transition-opacity"
                        style={{ background: '#18181B' }}
                        onClick={onStop}
                        aria-label="Stop generating"
                    >
                        <LuSquare size={16} color="#FFFFFF" />
                    </button>
                ) : (
                    <button
                        type="submit"
                        className="flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-full border-0 cursor-pointer disabled:cursor-not-allowed transition-colors"
                        style={{ background: disabled || !text.trim() ? '#D4D4D8' : '#8B5CF6' }}
                        disabled={disabled || !text.trim()}
                        aria-label="Send message"
                    >
                        <LuArrowUp size={20} color="#FFFFFF" />
                    </button>
                )}
            </form>
        </div>
    );
};

export default MessageInput;

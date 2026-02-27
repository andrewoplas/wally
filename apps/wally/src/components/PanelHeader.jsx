import { LuChevronLeft, LuGripVertical } from 'react-icons/lu';

const circleBtn = 'flex items-center justify-center w-9 h-9 border-0 bg-wpaia-bg rounded-full text-wpaia-muted hover:bg-wpaia-border hover:text-wpaia-text cursor-pointer transition-colors';

const PanelHeader = ({ title, onBack, onDragStart, borderBottom = false, children }) => (
    <div
        className={`relative flex items-center justify-between px-5 py-4 flex-shrink-0 cursor-grab active:cursor-grabbing select-none${borderBottom ? ' border-0 border-b border-solid border-wpaia-border' : ''}`}
        onMouseDown={onDragStart}
    >
        <div className="flex items-center gap-3">
            {onBack && (
                <button className={circleBtn} onClick={onBack} aria-label="Back">
                    <LuChevronLeft size={18} />
                </button>
            )}
            <h3 className="m-0 text-[17px] font-bold text-wpaia-text truncate max-w-[220px] font-heading">
                {title}
            </h3>
        </div>
        <div className="flex items-center gap-2">
            <LuGripVertical size={12} className="text-wpaia-hint" />
            {children}
        </div>
    </div>
);

PanelHeader.circleBtn = circleBtn;

export default PanelHeader;

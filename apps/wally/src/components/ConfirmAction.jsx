import { useState } from '@wordpress/element';
import { LuTriangleAlert, LuPackage, LuInfo, LuX, LuCheck } from 'react-icons/lu';

const ConfirmAction = ({ confirmation, onConfirm, onReject }) => {
    const [resolving, setResolving] = useState(false);
    const { action_id, tool_name, preview, status } = confirmation;

    const handleConfirm = async () => { setResolving(true); await onConfirm(action_id); };
    const handleReject = async () => { setResolving(true); await onReject(action_id); };

    if (status === 'confirmed') {
        return (
            <div className="mt-3 rounded-2xl bg-wpaia-success-bg p-4 flex items-center gap-2.5" style={{ border: '1px solid #86EFAC' }}>
                <span className="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-lg bg-wpaia-success-text text-white"><LuCheck size={14} /></span>
                <div className="flex flex-col gap-0.5">
                    <span className="text-[13px] font-semibold text-wpaia-success-text">Approved</span>
                    <span className="text-xs text-wpaia-muted">{formatToolName(tool_name)}</span>
                </div>
            </div>
        );
    }

    if (status === 'rejected') {
        return (
            <div className="mt-3 rounded-2xl bg-wpaia-error-bg p-4 flex items-center gap-2.5" style={{ border: '1px solid #FCA5A5' }}>
                <span className="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-lg bg-wpaia-error-text text-white"><LuX size={14} /></span>
                <div className="flex flex-col gap-0.5">
                    <span className="text-[13px] font-semibold text-wpaia-error-text">Rejected</span>
                    <span className="text-xs text-wpaia-muted">{formatToolName(tool_name)}</span>
                </div>
            </div>
        );
    }

    return (
        <div className="mt-3 flex flex-col gap-3.5">
            <div className="rounded-[20px] p-[18px] flex flex-col gap-3.5" style={{ background: '#FFF7ED', border: '1px solid #FDBA74' }}>
                {/* Action Header */}
                <div className="flex items-center gap-3">
                    <div className="flex-shrink-0 flex items-center justify-center w-9 h-9 rounded-wpaia-card" style={{ background: '#FFEDD5' }}>
                        <LuTriangleAlert size={18} color="#EA580C" />
                    </div>
                    <div className="flex flex-col gap-0.5">
                        <span className="text-[15px] font-semibold" style={{ color: '#9A3412' }}>{formatToolName(tool_name)}</span>
                        {preview?.plugin && <span className="text-[13px]" style={{ color: '#C2410C' }}>{preview.plugin}</span>}
                    </div>
                </div>

                {/* Divider */}
                <div className="h-px w-full" style={{ background: '#FDBA74', opacity: 0.4 }} />

                {/* Detail rows */}
                {preview && Object.keys(preview).length > 0 && (
                    <div className="flex flex-col gap-1.5">
                        {Object.entries(preview).map(([key, value], idx) => (
                            <div key={key} className="flex items-start gap-2">
                                <span className="flex-shrink-0 mt-0.5" style={{ color: '#EA580C' }}>
                                    {idx === 0 ? <LuPackage size={14} /> : <LuInfo size={14} />}
                                </span>
                                <span className="text-[13px]" style={{ color: '#9A3412' }}>
                                    {formatKey(key)}: {formatValue(value)}
                                </span>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Button Row */}
            <div className="flex gap-2.5">
                <button
                    className="flex-1 flex items-center justify-center gap-2 h-12 text-[15px] font-semibold rounded-full bg-wpaia-bg border-0 cursor-pointer hover:bg-wpaia-border transition-colors"
                    style={{ color: '#52525B' }}
                    onClick={handleReject}
                    disabled={resolving}
                >
                    <LuX size={16} color="#71717A" />
                    Cancel
                </button>
                <button
                    className="flex-1 flex items-center justify-center gap-2 h-12 text-[15px] font-semibold text-white rounded-full bg-wpaia-primary border-0 cursor-pointer hover:bg-wpaia-primary-hover transition-colors"
                    onClick={handleConfirm}
                    disabled={resolving}
                >
                    <LuCheck size={16} />
                    {resolving ? 'Processing…' : 'Confirm'}
                </button>
            </div>
        </div>
    );
};

function formatToolName(name) {
    return name.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatKey(key) {
    return key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatValue(value) {
    if (value === null || value === undefined) return '—';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
}

export default ConfirmAction;

const ToolOutputTable = ({ columns = [], rows = [], actions = [] }) => {
    if (!columns.length || !rows.length) return null;

    return (
        <div className="mt-3 rounded-wpaia-card border border-wpaia-border overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full text-xs border-collapse">
                    <thead>
                        <tr className="bg-wpaia-bg">
                            {columns.map(col => (
                                <th key={col.key} className="px-3 py-2 text-left font-semibold text-wpaia-muted border-b border-wpaia-border" style={col.width ? { width: col.width } : {}}>
                                    {col.label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, i) => (
                            <tr key={i} className="border-b border-wpaia-border last:border-b-0 hover:bg-wpaia-bg/50">
                                {columns.map(col => (
                                    <td key={col.key} className="px-3 py-2 text-wpaia-text">
                                        {col.key === 'type' ? (
                                            <span className="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-wpaia-primary-light text-wpaia-primary">
                                                {row[col.key]}
                                            </span>
                                        ) : (
                                            row[col.key] ?? '—'
                                        )}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {actions.length > 0 && (
                <div className="flex gap-2 px-3 py-2.5 border-t border-wpaia-border bg-wpaia-bg">
                    {actions.map((action, i) => (
                        <button
                            key={i}
                            className={`px-3 py-1.5 text-xs font-medium rounded-lg border cursor-pointer transition-colors ${
                                action.variant === 'secondary'
                                    ? 'border-wpaia-border bg-white text-wpaia-text hover:bg-wpaia-bg'
                                    : 'border-0 bg-wpaia-primary text-white hover:bg-wpaia-primary-hover'
                            }`}
                            onClick={action.onClick}
                            type="button"
                        >
                            {action.label}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
};

export default ToolOutputTable;

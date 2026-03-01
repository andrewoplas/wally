import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { LuFilePen, LuSearch, LuPlug, LuInfo, LuCode } from 'react-icons/lu';

const PERMISSIONS = [
    { icon: LuFilePen, label: 'Content CRUD', actions: ['read', 'create', 'update', 'delete'] },
    { icon: LuSearch, label: 'Search & Replace', actions: ['read', 'update'] },
    { icon: LuPlug, label: 'Plugin Management', actions: ['plugins'] },
    { icon: LuInfo, label: 'Site Info & Options', actions: ['site'] },
    { icon: LuCode, label: 'Elementor Integration', actions: ['read', 'update'] },
];

const ToggleRow = ({ label, description, checked, onChange, disabled }) => (
    <div className={`flex items-center justify-between py-3 border-b border-wpaia-border last:border-b-0 ${disabled ? 'opacity-50' : ''}`}>
        <div className="flex-1 min-w-0 pr-3">
            <div className="text-sm font-medium text-wpaia-text">{label}</div>
            {description && <div className="text-xs text-wpaia-muted mt-0.5">{description}</div>}
        </div>
        <label className={`relative inline-flex items-center flex-shrink-0 ${disabled ? 'cursor-not-allowed' : 'cursor-pointer'}`}>
            <input type="checkbox" checked={checked} onChange={disabled ? undefined : (e) => onChange(e.target.checked)} disabled={disabled} className="sr-only peer" />
            <div className="w-9 h-5 bg-wpaia-border rounded-full peer-checked:bg-wpaia-primary transition-colors" />
            <div className="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4" />
        </label>
    </div>
);

const SettingsPanel = () => {
    const [confirmDestructive, setConfirmDestructive] = useState(true);
    const [streamResponses, setStreamResponses] = useState(true);
    const [notificationSounds, setNotificationSounds] = useState(false);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        apiFetch({ path: 'wally/v1/settings' })
            .then((data) => {
                if (data.confirm_destructive !== undefined) setConfirmDestructive(data.confirm_destructive);
                if (data.stream_responses !== undefined) setStreamResponses(data.stream_responses);
                if (data.notification_sounds !== undefined) setNotificationSounds(data.notification_sounds);
            })
            .catch(() => {});
    }, []);

    const isAdmin = typeof wpaiaData !== 'undefined' && wpaiaData.isAdmin;

    const saveSettings = (patch) => {
        if (!isAdmin) return;
        setSaving(true);
        apiFetch({ path: 'wally/v1/settings', method: 'PATCH', data: patch })
            .catch(() => {})
            .finally(() => setSaving(false));
    };

    const handleConfirmDestructive = (val) => { setConfirmDestructive(val); saveSettings({ confirm_destructive: val }); };
    const handleStreamResponses = (val) => { setStreamResponses(val); saveSettings({ stream_responses: val }); };
    const handleNotificationSounds = (val) => { setNotificationSounds(val); saveSettings({ notification_sounds: val }); };

    const userRole = typeof wpaiaData !== 'undefined' && wpaiaData.userRole ? wpaiaData.userRole : 'Subscriber';
    const userPermissions = typeof wpaiaData !== 'undefined' && Array.isArray(wpaiaData.userPermissions) ? wpaiaData.userPermissions : [];

    const hasPermission = (actions) => actions.some((a) => userPermissions.includes(a));

    return (
        <div className="flex flex-col flex-1 overflow-hidden">
            <div className="flex-1 overflow-y-auto px-4 py-4 wpaia-scrollbar">

                {/* Behavior Section */}
                <div className="mb-6">
                    <div className="flex items-center justify-between mb-2">
                        <div className="text-xs font-semibold text-wpaia-muted uppercase tracking-wider">Behavior</div>
                        {!isAdmin && <span className="text-[10px] text-wpaia-muted italic">View only</span>}
                    </div>
                    <ToggleRow label="Confirm destructive actions" description="Ask before deleting posts or modifying options." checked={confirmDestructive} onChange={handleConfirmDestructive} disabled={!isAdmin} />
                    <ToggleRow label="Stream responses" description="Show the assistant's reply as it's being generated." checked={streamResponses} onChange={handleStreamResponses} disabled={!isAdmin} />
                    <ToggleRow label="Notification sounds" description="Play a sound when a response is ready." checked={notificationSounds} onChange={handleNotificationSounds} disabled={!isAdmin} />
                </div>

                {/* Permissions Section */}
                <div className="mb-6">
                    <div className="flex items-center justify-between mb-2">
                        <div className="text-xs font-semibold text-wpaia-muted uppercase tracking-wider">Your Permissions</div>
                        <span className="text-[10px] font-semibold text-wpaia-primary bg-wpaia-primary-light px-2 py-0.5 rounded-full">{userRole}</span>
                    </div>
                    <div className="flex flex-col gap-1">
                        {PERMISSIONS.map(({ icon: Icon, label, actions }) => {
                            const allowed = hasPermission(actions);
                            return (
                                <div key={label} className={`flex items-center gap-3 py-2 ${!allowed ? 'opacity-50' : ''}`}>
                                    <span className={allowed ? 'text-wpaia-success-text' : 'text-wpaia-muted'} aria-hidden="true"><Icon size={16} /></span>
                                    <span className="flex-1 text-sm text-wpaia-text">{label}</span>
                                    {allowed
                                        ? <span className="text-wpaia-success-text text-sm font-bold" aria-label="Allowed">✓</span>
                                        : <span className="text-wpaia-muted text-sm font-bold" aria-label="Not allowed">✗</span>
                                    }
                                </div>
                            );
                        })}
                    </div>
                </div>

            </div>

            <div className="flex-shrink-0 px-4 py-3 border-t border-wpaia-border text-[11px] text-wpaia-hint text-center">
                Wally — AI Assistant v1.0.0
                {saving && ' · Saving…'}
            </div>
        </div>
    );
};

export default SettingsPanel;

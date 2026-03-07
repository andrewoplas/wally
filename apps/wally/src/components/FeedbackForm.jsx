import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { LuBug, LuLightbulb, LuMessageCircle, LuCheck } from 'react-icons/lu';

const CATEGORIES = [
    { value: 'bug', label: 'Bug Report', icon: LuBug },
    { value: 'feature', label: 'Feature Request', icon: LuLightbulb },
    { value: 'general', label: 'General Feedback', icon: LuMessageCircle },
];

const FeedbackForm = ({ conversationId, onBack }) => {
    const [category, setCategory] = useState('general');
    const [message, setMessage] = useState('');
    const [email, setEmail] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [submitted, setSubmitted] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async () => {
        if (!message.trim()) return;

        setSubmitting(true);
        setError('');

        try {
            await apiFetch({
                path: 'wally/v1/feedback/general',
                method: 'POST',
                data: {
                    message: message.trim(),
                    category,
                    ...(conversationId && { conversation_id: String(conversationId) }),
                },
            });
            setSubmitted(true);
        } catch (err) {
            setError(err?.message || 'Failed to submit feedback. Please try again.');
        } finally {
            setSubmitting(false);
        }
    };

    if (submitted) {
        return (
            <div className="flex flex-col flex-1 items-center justify-center px-8 text-center gap-4">
                <div className="flex items-center justify-center w-14 h-14 rounded-full bg-[#D1FAE5]">
                    <LuCheck size={28} className="text-[#059669]" />
                </div>
                <h3 className="text-lg font-bold text-wpaia-text m-0 font-heading">Thank you!</h3>
                <p className="text-sm text-wpaia-muted m-0">Your feedback has been submitted. We appreciate you helping us improve Wally.</p>
                <button
                    className="mt-2 px-5 py-2.5 text-sm font-semibold text-wpaia-primary bg-wpaia-primary-light border-0 rounded-full cursor-pointer hover:bg-wpaia-primary hover:text-white transition-colors"
                    onClick={onBack}
                    type="button"
                >
                    Back to chat
                </button>
            </div>
        );
    }

    return (
        <div className="flex flex-col flex-1 overflow-hidden">
            <div className="flex-1 overflow-y-auto px-4 py-4 wpaia-scrollbar">
                {/* Category */}
                <div className="mb-5">
                    <label className="block text-xs font-semibold text-wpaia-muted uppercase tracking-wider mb-2">Category</label>
                    <div className="flex flex-col gap-1.5">
                        {CATEGORIES.map(({ value, label, icon: Icon }) => (
                            <button
                                key={value}
                                className={`flex items-center gap-3 w-full px-3.5 py-3 rounded-xl border border-solid cursor-pointer text-left font-sans transition-colors text-sm ${
                                    category === value
                                        ? 'border-wpaia-primary bg-wpaia-primary-light text-wpaia-primary font-semibold'
                                        : 'border-wpaia-border bg-transparent text-wpaia-text hover:bg-wpaia-bg'
                                }`}
                                onClick={() => setCategory(value)}
                                type="button"
                            >
                                <Icon size={16} />
                                {label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Message */}
                <div className="mb-5">
                    <label className="block text-xs font-semibold text-wpaia-muted uppercase tracking-wider mb-2">
                        Message <span className="text-[#EF4444]">*</span>
                    </label>
                    <textarea
                        className="w-full p-3 text-sm text-wpaia-text bg-wpaia-bg border border-solid border-wpaia-border rounded-xl resize-none font-sans outline-none focus:border-wpaia-primary transition-colors"
                        rows={5}
                        placeholder="Tell us what's on your mind..."
                        value={message}
                        onChange={(e) => setMessage(e.target.value)}
                    />
                </div>

                {/* Email */}
                <div className="mb-5">
                    <label className="block text-xs font-semibold text-wpaia-muted uppercase tracking-wider mb-2">Email (optional)</label>
                    <input
                        type="email"
                        className="w-full h-10 px-3 text-sm text-wpaia-text bg-wpaia-bg border border-solid border-wpaia-border rounded-xl font-sans outline-none focus:border-wpaia-primary transition-colors"
                        placeholder="your@email.com"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                    />
                    <p className="text-[11px] text-wpaia-hint mt-1 m-0">If you'd like us to follow up with you.</p>
                </div>

                {error && (
                    <p className="text-sm text-[#EF4444] m-0 mb-3">{error}</p>
                )}
            </div>

            {/* Submit */}
            <div className="flex-shrink-0 px-4 py-3 border-t border-wpaia-border">
                <button
                    className="w-full h-10 text-sm font-semibold text-white bg-wpaia-primary border-0 rounded-full cursor-pointer hover:bg-wpaia-primary-hover transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    onClick={handleSubmit}
                    disabled={!message.trim() || submitting}
                    type="button"
                >
                    {submitting ? 'Submitting...' : 'Submit Feedback'}
                </button>
            </div>
        </div>
    );
};

export default FeedbackForm;

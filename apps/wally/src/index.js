import { createRoot } from '@wordpress/element';
import ChatSidebar from './components/ChatSidebar';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('wpaia-chat-root');
    if (container) {
        const root = createRoot(container);
        root.render(<ChatSidebar />);
    }
});

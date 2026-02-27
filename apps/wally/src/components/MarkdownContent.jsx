import { useMemo, useRef, useEffect } from '@wordpress/element';
import { marked } from 'marked';

marked.setOptions({ breaks: true, gfm: true, headerIds: false, mangle: false });

const ALLOWED_TAGS = new Set([
    'p', 'br', 'strong', 'b', 'em', 'i', 'code', 'pre',
    'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'a', 'blockquote', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
]);

function sanitizeHtml(html) {
    return html.replace(/<\/?([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/g, (match, tag) => {
        const lower = tag.toLowerCase();
        if (ALLOWED_TAGS.has(lower)) {
            if (lower === 'a') {
                const href = match.match(/href="([^"]*)"/);
                if (href) return match.startsWith('</') ? '</a>' : `<a href="${href[1]}" target="_blank" rel="noopener noreferrer">`;
                return match.startsWith('</') ? '</a>' : '<a>';
            }
            return match;
        }
        return '';
    });
}

const MarkdownContent = ({ content }) => {
    const ref = useRef(null);

    const html = useMemo(() => {
        if (!content) return '';
        return sanitizeHtml(marked.parse(content));
    }, [content]);

    useEffect(() => {
        if (!ref.current) return;
        const pres = ref.current.querySelectorAll('pre');
        pres.forEach((pre) => {
            if (pre.querySelector('.wpaia-copy-btn')) return;
            const wrapper = document.createElement('div');
            wrapper.className = 'wpaia-code-wrapper';
            pre.parentNode.insertBefore(wrapper, pre);
            wrapper.appendChild(pre);

            const btn = document.createElement('button');
            btn.className = 'wpaia-copy-btn';
            btn.textContent = 'Copy';
            btn.setAttribute('aria-label', 'Copy code to clipboard');
            btn.addEventListener('click', () => {
                const code = pre.querySelector('code')?.textContent || pre.textContent;
                navigator.clipboard.writeText(code).then(() => {
                    btn.textContent = 'Copied!';
                    btn.classList.add('wpaia-copy-btn-success');
                    setTimeout(() => { btn.textContent = 'Copy'; btn.classList.remove('wpaia-copy-btn-success'); }, 2000);
                });
            });
            wrapper.appendChild(btn);
        });
    }, [html]);

    if (!content) return null;

    return (
        <div
            ref={ref}
            className="wpaia-markdown"
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
};

export default MarkdownContent;

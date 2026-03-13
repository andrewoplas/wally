# Page Templates

## When to Use
- User asks to create a new page (landing page, about page, contact page, blog page, etc.)
- User wants to build a full page layout quickly
- Use these structural blueprints with `create_post` or `update_post`

## Available Tools
- `create_post` — create a new page with block markup in the `content` field
- `update_post` — replace an existing page's content with a new layout

## Workflows

### Build a Page from a Template
1. Ask the user for their business name, tagline, and key content (if not provided)
2. Choose the matching template structure below
3. Replace ALL CAPS placeholders with the user's actual content
4. Call `create_post` with `post_type: 'page'`, `title`, `status: 'publish'`, and `content`
5. Call `update_post` if replacing an existing page's layout

For block syntax details, see `gutenberg-blocks.md`.

## Template Structures

### Business Landing Page
Sections (top → bottom):
1. **Hero** — `cover` block, dark overlay, h1 headline + tagline + CTA button
2. **Features** — 3-column `columns` in a `group`; each column: icon/heading + paragraph
3. **About** — `media-text` block; image on one side, heading + 2 paragraphs on other
4. **Testimonials** — `group` with background color + 3-column `columns`; `quote` block per column
5. **CTA** — `group` with accent background + centered h2 + paragraph + button
6. **Contact** — 2-column `columns`; address/phone/email in one, `shortcode` form embed in other

### Services / Portfolio Page
Sections:
1. **Hero** — `cover` block with service/portfolio headline
2. **Services Grid** — 3-column `columns` in a `group`; heading + paragraph + button per service
3. **Process / How it Works** — numbered `list` or 3 steps in `columns`
4. **Testimonials** — 2-column `columns` with `quote` blocks
5. **CTA** — `group` with accent background + heading + button

### About Page
Sections:
1. **Hero** — `cover` with "About [Company Name]" heading
2. **Our Story** — `media-text` (image right) + heading + 2-3 paragraphs
3. **Our Mission / Values** — 3-column `columns` + icon-style heading + paragraph per value
4. **Team** — 3-column `columns`; each: image + h3 name + paragraph role/bio
5. **CTA** — `group` with background + "Work with us" heading + button

### Contact Page
Sections:
1. **Hero** — simple `group` with h1 "Contact Us" + tagline paragraph
2. **Contact Info + Form** — 2-column `columns`:
   - Left: h3 "Get in Touch" + paragraphs for address, phone, email
   - Right: `shortcode` block with contact form embed
3. **Map** (optional) — `shortcode` block with Google Maps embed or HTML block

### Blog / Archive Page
Sections:
1. **Hero** — `group` with h1 "Blog" or category name
2. **Posts** — tell the user this is managed by WordPress automatically via the blog page setting; suggest calling `get_option` with key `page_for_posts` to check the current blog page assignment

## Important Notes
- Always ask for the user's business name, tagline, and key content before building — never use placeholder text in the final output
- For Elementor sites, use `elementor_create_page` instead — see `elementor.md`
- If the user's site uses a page builder other than Gutenberg, check which builder is active first via `list_plugins`
- After creating, confirm the page was created by showing the title and ID to the user
- Set `status: 'draft'` first if the user wants to review before publishing

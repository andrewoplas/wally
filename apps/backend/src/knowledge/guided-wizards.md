# Guided Wizards

## When to Use
- User says "set up my site", "help me get started", "new WordPress site", or "start from scratch"
- User says "moving from Squarespace/Wix", "migrate my site", or "switch to WordPress"
- User says "is my site ready to launch", "pre-launch check", or "launch checklist"

## Available Tools
- `create_post` — create pages (Home, About, Contact)
- `update_option` — configure site settings (requires confirmation)
- `get_option` — read settings for checks
- `get_site_info` — get site overview
- `list_plugins` — check installed plugins
- `install_plugin` — install recommended plugins (requires confirmation)
- `activate_plugin` — activate plugins (requires confirmation)
- `list_posts` — check existing content
- `search_content` — find specific content

## Workflows

### New Site Setup Wizard
Ask ONE question at a time, wait for the answer, then proceed.

1. **Identify site type** — Ask: "What kind of site are you building? (business, blog, portfolio, online store, or something else?)"
2. **Collect identity** — Ask: "What's your site name and a short tagline?"
3. **Set site title** — Call `update_option` with `key: 'blogname'` and user's site name (requires confirmation), then `key: 'blogdescription'` with tagline
4. **Create essential pages** — Call `create_post` (post_type: `page`, status: `publish`) for Home, About, and Contact pages
5. **Set static front page** — Call `update_option` with `key: 'show_on_front', value: 'page'` and `key: 'page_on_front', value: <Home page ID>` (requires confirmation)
6. **Set up menus** — Wally cannot create navigation menus; guide user to **Appearance > Menus** to add the new pages
7. **Recommend plugins** — Suggest SEO (Yoast or Rank Math), caching, security, and forms plugins. Ask: "Would you like me to install any of these?"
8. **Install chosen plugins** — Call `install_plugin` + `activate_plugin` for each confirmed plugin
9. **Wrap up** — Summarize what was done, suggest next steps (upload logo, customize theme, add content)

### Migration Helper
1. **Identify source** — Ask: "Are you migrating from Squarespace, Wix, another WordPress site, or something else?"
2. **Export instructions** — Provide platform-specific guidance:
   - Squarespace: Settings > Advanced > Import/Export > Export as WordPress XML
   - Wix: Use a third-party migration tool (CMS2CMS) or manually export blog posts
   - WordPress: Tools > Export > All content > download XML
3. **Import guidance** — Guide user to **Tools > Import > WordPress** to upload the XML file (Wally cannot import files)
4. **Post-import check** — Call `search_content` to look for leftover platform shortcodes (`[squarespace`, `{wix:`)
5. **Check content** — Call `list_posts` to verify content was imported
6. **Recommend redirects** — If old URLs differ, suggest installing the Redirection plugin for 301 redirects
7. **Warn about media** — External images may not import; suggest re-uploading or using an image import plugin

### Launch Checklist
Run all checks and report results together.

1. **SSL** — Call `get_option` with key `siteurl`; check if it starts with `https://`
2. **SEO plugin** — Call `list_plugins`; check for Yoast, Rank Math, AIOSEO, or SEOPress
3. **Essential pages** — Call `list_posts` with `post_type: 'page'` and `search: 'privacy'` to check for Privacy Policy
4. **Contact form** — Call `list_plugins`; check for CF7, WPForms, Gravity Forms, or Ninja Forms
5. **Caching plugin** — Call `list_plugins`; check for WP Super Cache, W3 Total Cache, WP Rocket, or LiteSpeed Cache
6. **Image optimization** — Call `list_plugins`; check for Smush, ShortPixel, Imagify, or EWWW
7. **Favicon** — Call `get_option` with key `site_icon`; if `0` or empty, flag as missing
8. **Search visibility** — Call `get_option` with key `blog_public`; if `"0"`, warn that search engines are discouraged
9. **Report** — Present results as a checklist with pass/warning/blocker status; offer to fix issues Wally can resolve

## Important Notes
- Always ask ONE question at a time in wizard mode — do not dump all questions at once
- Wally cannot create navigation menus — always guide user to Appearance > Menus for menu setup
- Wally cannot import content files (XML, CSV) — guide user to Tools > Import in WordPress admin
- For the launch checklist, run all checks before presenting results so the user gets a complete picture

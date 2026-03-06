# Phase 4: Advanced Features

> Higher-effort features that make Wally feel truly intelligent. These require changes across plugin, backend, and possibly frontend.

## 1. Rollback / Undo System

**Problem:** Multi-step operations (building a page, bulk updates) can go wrong. Users need a safety net.

**Approach: Snapshot-based rollback**

Before any multi-step operation, Wally takes a lightweight snapshot of what it's about to change. If something goes wrong (or the user says "undo that"), Wally can restore the previous state.

**What to snapshot:**
- Post content + meta before update
- Plugin active/inactive state before toggle
- Option values before update
- Menu structure before modification

**Implementation:**

1. New DB table: `wp_wally_snapshots`
```sql
CREATE TABLE wp_wally_snapshots (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT NOT NULL,
    snapshot_type VARCHAR(50) NOT NULL,    -- 'post', 'option', 'menu', 'plugin'
    object_id BIGINT,                      -- post_id, menu_id, etc.
    object_key VARCHAR(255),               -- option name, meta key, etc.
    previous_value LONGTEXT,               -- serialized previous state
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (conversation_id),
    INDEX (created_at)
);
```

2. New tool: `class-undo-tools.php`

| Tool | Action | Description | Confirmation |
|------|--------|-------------|------------|
| `undo_last_action` | update | Revert the last change made in this conversation | yes |
| `list_recent_changes` | read | Show what Wally changed in this conversation | no |

3. Snapshot creation happens inside existing tool `execute()` methods:
```php
// In update_post execute(), before making changes:
$existing = get_post($post_id);
Snapshot::save($conversation_id, 'post', $post_id, null, serialize($existing));

// Then proceed with the update
wp_update_post($args);
```

4. Undo reads the snapshot and restores:
```php
$snapshot = Snapshot::get_latest($conversation_id);
$previous = unserialize($snapshot->previous_value);
wp_update_post($previous); // restore
Snapshot::delete($snapshot->id);
```

**Cleanup:** Auto-delete snapshots older than 24 hours via the daily cron job.

**Effort:** Medium — new table, snapshot helper class, modifications to existing tool execute methods, new undo tool file.

---

## 2. Template Library (Page Recipes)

**Problem:** LLM-generated pages are good but inconsistent. Pre-built templates ensure quality and speed.

**Approach: Curated block markup templates stored as knowledge**

Not a new tool — a new knowledge file (`page-templates.md`) with complete, tested Gutenberg block markup for common page types.

**Templates to create:**

| Template | Sections |
|----------|----------|
| Business Landing Page | Hero (cover), Features (3-col), About (media-text), Testimonials (3-col quotes), CTA, Contact |
| Restaurant | Hero with image, Menu/Pricing (columns + tables), About, Hours & Location, Reservation CTA |
| Portfolio | Hero, Project Grid (columns), About, Skills, Contact |
| Blog Homepage | Featured post (cover), Recent posts grid (columns), Categories sidebar |
| About Page | Hero, Story (media-text), Team (columns with images), Values, CTA |
| Contact Page | Intro, Contact Form (shortcode) + Contact Info (columns), Map embed, FAQ |
| Service Page | Hero, Service list (columns), Process/How it works, Testimonials, CTA |
| Coming Soon | Full-screen cover with heading, countdown text, email signup CTA |

**How it works:**
- LLM receives the template when the `gutenberg-blocks` intent fires
- LLM customizes the template with user's business details (name, colors, content)
- User iterates: "change the hero text", "add a pricing section"

**Intent trigger:** Add to `gutenberg-blocks` pattern — already covered.

**Effort:** Low — purely content creation (writing block markup templates), no code changes.

---

## 3. Smart Content Style Matching

**Problem:** When Wally writes a blog post, it should match the site's existing voice and style.

**Approach:** Before writing content, pull 2-3 recent published posts and include them as style examples in the prompt.

**Implementation:**

1. New method in `PromptBuilderService`:
```typescript
private buildContentStyleContext(siteProfile: SiteProfile): string {
    // This info would come from a new field in site_profile
    // that includes excerpts of recent posts
    if (!siteProfile.recent_posts_sample) return '';
    return `\n--- Content Style Reference ---\n` +
        `These are excerpts from the site's existing content. Match this tone and style:\n` +
        siteProfile.recent_posts_sample.map(p =>
            `Title: ${p.title}\nExcerpt: ${p.excerpt}\n`
        ).join('\n');
}
```

2. Extend `SiteScanner::scan()` to include 3 recent published post excerpts (first 200 chars each):
```php
'recent_posts_sample' => array_map(function($post) {
    return [
        'title'   => $post->post_title,
        'excerpt' => wp_trim_words(wp_strip_all_tags($post->post_content), 40),
    ];
}, get_posts(['numberposts' => 3, 'post_status' => 'publish', 'post_type' => 'post'])),
```

3. Only inject this context when content creation intent is detected.

**Effort:** Low — small additions to SiteScanner and PromptBuilder.

---

## 4. Guided Multi-Step Wizards

**Problem:** Complex tasks like "set up my new site" benefit from structured, guided conversation flow rather than one-shot execution.

**Approach:** Not a code feature — a prompt engineering pattern. Add wizard-mode instructions to the system prompt.

**Wizard scenarios to support:**

### New Site Setup Wizard
Triggered by: "I just installed WordPress", "set up my site", "help me get started"

Steps:
1. Ask: What kind of site? (business, blog, portfolio, store)
2. Ask: Business name, tagline
3. Create essential pages (Home, About, Contact)
4. Set up navigation menu
5. Configure reading settings (static front page)
6. Recommend essential plugins (SEO, caching, security, forms)
7. Offer to install and configure them

### Migration Helper
Triggered by: "moving from Squarespace/Wix", "migrate my site"

Steps:
1. Ask about source platform
2. Guide through WordPress importer
3. Check for broken links/images
4. Set up redirects

### Launch Checklist
Triggered by: "is my site ready to launch?", "pre-launch check"

Steps:
1. Check SSL is active
2. Check SEO plugin installed + configured
3. Check essential pages exist (Privacy Policy, Terms)
4. Check contact form works
5. Check site speed basics (caching, image optimization)
6. Check favicon is set
7. Report findings with action items

**Implementation:** Add wizard trigger patterns to intent classifier + add wizard flow instructions to the system prompt or a dedicated knowledge file.

**Effort:** Low-medium — primarily prompt engineering + a new knowledge file.

---

## 5. Image Generation / Sourcing

**Problem:** Pages need images. Users don't always have them ready.

**Approach:** Two options, could do both:

### Option A: Unsplash/Pexels Integration (simpler)
New tool: `search_stock_photos`
- Calls Unsplash/Pexels API with a search query
- Returns image URLs + attribution
- LLM can then use `upload_media_from_url` (Phase 2) to add to media library

**Requires:** API key for Unsplash/Pexels stored in plugin settings.

### Option B: AI Image Generation (more impressive)
New tool: `generate_image`
- Calls an image generation API (DALL-E, Stability AI)
- Returns generated image
- Upload to media library

**Requires:** Additional API costs, image generation API key.

**Recommendation:** Start with Option A — it's simpler, free tier available, and stock photos are more appropriate for business sites than AI-generated images.

**Effort:** Medium — new tool + API integration + settings UI for API key.

---

## 6. Scheduled Actions / Automation

**Problem:** Users want recurring tasks: "publish this post next Monday", "remind me to update plugins every week"

**Approach:** Leverage WordPress cron for scheduling.

New tools:
| Tool | Description |
|------|-------------|
| `schedule_post` | Already possible via `create_post` with `post_date` + `post_status: future` |
| `schedule_bulk_posts` | Create multiple posts with staggered publish dates |

For content calendars, the LLM can already do this by calling `create_post` multiple times with different `post_date` values. No new tool needed — just ensure the system prompt guides this behavior.

**Effort:** Minimal — mostly prompt guidance.

---

## Implementation Priority

| Feature | Effort | Impact | Priority |
|---------|--------|--------|----------|
| Template Library | Low | High | Do first |
| Content Style Matching | Low | Medium | Do second |
| Guided Wizards | Low-Med | High | Do third |
| Rollback/Undo | Medium | High | Do fourth |
| Image Sourcing | Medium | Medium | Do fifth |
| Scheduled Actions | Minimal | Low | Already possible, just document |

## Dependencies

- Template Library depends on Phase 1 (gutenberg-blocks knowledge) — done
- Content Style Matching depends on Phase 2 (SiteScanner extension)
- Rollback depends on Phase 2 tools existing (so there's something to undo)
- Image Sourcing depends on Phase 2 (`upload_media_from_url` tool)

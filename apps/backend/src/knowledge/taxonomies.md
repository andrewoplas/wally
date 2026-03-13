# Taxonomies

## When to Use
- User wants to list, create, or manage categories or tags
- User asks about post organization or filtering content by category/tag
- User wants to assign categories or tags to a post
- User asks to find posts in a specific category or with a specific tag

## Available Tools
- `list_categories` — list all categories (id, name, slug, count, parent)
- `list_tags` — list all tags (id, name, slug, count)
- `create_category` — create a new category (name, slug, parent, description)
- `create_tag` — create a tag (name, slug, description)
- `update_post` — assign categories/tags to a post
- `list_posts` — filter posts by category or tag

## Workflows

### List All Categories
1. Call `list_categories`
2. Returns all categories with id, name, slug, post count, and parent ID

### List All Tags
1. Call `list_tags`
2. Returns all tags with id, name, slug, and post count

### Create a Category
1. Call `create_category` with `name: '<Category Name>'`
2. Optionally add `slug`, `description`, or `parent` (parent category ID for a subcategory)

### Create a Subcategory
1. Call `list_categories` to find the parent category ID
2. Call `create_category` with `name: '<Sub Name>'` and `parent: <parent_id>`

### Create a Tag
1. Call `create_tag` with `name: '<Tag Name>'`
2. Optionally add `slug` or `description`

### Assign Category or Tag to a Post
1. Get the category ID from `list_categories` or confirm the tag slug from `list_tags`
2. Call `update_post` with `id: <post_id>` and `categories: [<cat_id>]` or `tags: ['<tag-slug>']`

### Find Posts in a Category
1. Call `list_posts` with `category: <category_id>` to filter by category

### Find Posts with a Tag
1. Call `list_posts` with `tag: '<tag-slug>'` to filter by tag

## Important Notes
- Wally cannot rename, update, or delete categories/tags — guide user to Posts > Categories or Posts > Tags in WordPress admin
- Custom taxonomies (beyond category and post_tag) are not manageable via Wally tools
- Categories are hierarchical (support parent/child); tags are flat (no hierarchy)
- When creating a subcategory, use the numeric parent ID from `list_categories`, not the name

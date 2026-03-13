# Menus

## When to Use
- User asks to view, create, or manage navigation menus
- User wants to know which menu is assigned to which location (header, footer, sidebar)
- User asks about menu items or menu structure

## Available Tools
- `get_option` with key `nav_menu_locations` — see which menus are assigned to theme locations
- `get_site_info` — includes active theme info (helps identify menu location context)

## Workflows

### Check Which Menus Are Assigned
1. Call `get_option` with key `nav_menu_locations`
2. Returns a map of location slugs to menu IDs
3. Tell the user which menu is assigned to each location (header, footer, etc.)

### Identify Active Theme Context
1. Call `get_site_info` to get the active theme name
2. Explain that available menu locations depend on the active theme
3. Guide the user to Appearance > Menus to see and manage all registered locations

## Important Notes
- Wally cannot create, edit, or delete navigation menus — guide user to Appearance > Menus
- Wally cannot add, remove, or reorder menu items — guide user to Appearance > Menus
- In block themes (FSE), menus use the wp:navigation block — edit via Appearance > Editor
- For all menu management, direct the user to the WordPress admin Appearance menu

# Implementation Plan: App / License Screen (uu1YB)

## Scope
Implement the **App / License (Paid)** screen at `/app/license`, including:
- Shared app shell with sidebar (used across all future app screens)
- License card (license key, copy, expiry, download)
- Activated sites card (table with status chips)

URL structure: `/app/license`, `/app/subscriptions`, `/app/account`

---

## Files to Create

### New UI Primitives
None needed — the design uses raw divs/spans with Tailwind, not shared primitives. The sidebar, cards, and table will be implemented as self-contained components using inline Tailwind matching the exact design values.

### New Components & Pages

```
apps/frontend/src/
  app/
    (app)/
      layout.tsx                            ← App shell: sidebar + scrollable main
      app/
        license/
          page.tsx                          ← /app/license page
  components/
    app/
      app-sidebar.tsx                       ← 240px sidebar (needs 'use client' for usePathname)
      license-card.tsx                      ← License key card (needs 'use client' for copy state)
      activated-sites-card.tsx              ← Sites table card (static, server component)
```

---

## Exact Design Values (from uu1YB)

### App shell layout
- Root frame: 1280×900, `bg-[#FAFAFA]`, `flex flex-row`
- Sidebar: `w-[240px] h-screen flex flex-col justify-between py-7 px-5 bg-white border-r border-[#E4E4E7]`
- Page content: `flex-1 flex flex-col overflow-y-auto`
- Content inner container: `w-[760px] flex flex-col gap-8 py-12 px-16`

### Sidebar
**Logo row**: `flex items-center gap-[10px]`
- Circle: `w-8 h-8 bg-[#8B5CF6] rounded-[10px] flex items-center justify-center`
- Icon: `MessageCircle` size 16 `text-white`
- Text: `Plus Jakarta Sans` 16px bold `#18181B`

**Nav items** (`flex flex-col gap-0.5`):
- Active item: `flex items-center gap-[10px] px-3 py-[10px] rounded-lg bg-[#F4F4F5]`
  - Icon: `#8B5CF6`, text: `Inter` 14px `font-semibold text-[#18181B]`
- Inactive item: same padding/gap/radius, no fill
  - Icon: `#A1A1AA`, text: `Inter` 14px `font-normal text-[#71717A]`
- Icons: `KeyRound` (License), `CreditCard` (Subscription), `CircleUser` (Account) — all size 16

**Bottom section** (`flex flex-col gap-3`):
- Divider: `h-px bg-[#E4E4E7] w-full`
- User row: `flex items-center gap-[10px]`
  - Avatar: `w-8 h-8 rounded-full bg-[#8B5CF6] flex items-center justify-center`
  - Avatar text: `Inter` 12px `font-semibold text-white` content `JD`
  - Name: `Inter` 13px `font-semibold text-[#18181B]`
  - Email: `Inter` 12px `font-normal text-[#A1A1AA]`
- Logout btn: `flex items-center gap-2 px-3 py-2 rounded-lg` (hover state)
  - Icon: `LogOut` size 15 `#A1A1AA`, text: `Inter` 13px `text-[#71717A]`

### Page header
- Container: `flex flex-col gap-1`
- Title: `Plus Jakarta Sans` 28px bold `text-[#18181B]` — "License"
- Subtitle: `Inter` 14px `text-[#71717A]` leading-[1.5] — "Manage your Wally license key and download the plugin."

### License Card (`rounded-xl border border-[#E4E4E7] bg-white`)

**Top section** (`flex flex-col gap-5 px-7 py-6`):

StatusRow (`flex items-center justify-between`):
- Left TitleGroup: `flex items-center gap-[10px]`
  - Plan name: `Plus Jakarta Sans` 18px bold `#18181B` — "Wally"
  - PRO badge: `rounded-full bg-[#8B5CF6] px-[10px] py-[3px]`
    - Text: `Inter` 11px bold `#FFFFFF` — "PRO"
- Right StatusChip: `flex items-center gap-[6px] px-3 py-[5px] rounded-full bg-[#F0FDF4] border border-[#86EFAC]`
  - Dot: `w-1.5 h-1.5 rounded-full bg-[#22C55E]`
  - Text: `Inter` 12px `font-medium text-[#15803D]` — "Active"

KeyRow (`flex items-center gap-[10px]`):
- Label: `Inter` 13px `font-semibold text-[#18181B]` — "License Key"
- KeyInput (`flex-1 h-11 flex items-center px-[14px] rounded-lg bg-[#F9F9F9] border border-[#E4E4E7]`):
  - Text: `Inter` 13px `text-[#18181B]` — license key value
- CopyBtn (`flex items-center gap-[6px] h-11 px-[18px] rounded-lg bg-[#18181B]`):
  - Icon: `Copy` size 14 `text-white`
  - Text: `Inter` 13px `font-semibold text-white` — "Copy key"

MetaRow (`flex gap-8`):
- Expires: label `Inter` 12px `text-[#A1A1AA]`, value `Inter` 13px `font-medium text-[#18181B]` — "Dec 31, 2026"
- Activations: label same, value "3 of 5 sites"

**Divider**: `h-px bg-[#F0F0F0]`

**Download section** (`flex items-center justify-between px-7 py-5`):
- Left: `flex flex-col gap-0.5`
  - Title: `Inter` 13px `font-semibold text-[#18181B]` — "Download Plugin"
  - Sub: `Inter` 12px `text-[#A1A1AA]` — "Latest version · v1.2.0 · Released Feb 20, 2026"
- DlBtn (`flex items-center gap-[7px] h-10 px-[18px] rounded-lg bg-[#8B5CF6]`):
  - Icon: `Download` size 14 `text-white`
  - Text: `Inter` 13px `font-semibold text-white` — "Download .zip"

### Activated Sites Card (`rounded-xl border border-[#E4E4E7] bg-white`)

**Header** (`flex items-center justify-between px-6 py-[18px] pb-4`):
- Left: `flex flex-col gap-0.5`
  - Title: `Plus Jakarta Sans` 15px `font-semibold text-[#18181B]` — "Activated Sites"
  - Sub: `Inter` 12px `text-[#A1A1AA]` — "Sites where this license key is active."
- Count badge: `px-[10px] py-[3px] rounded-full bg-[#F4F4F5]`
  - Text: `Inter` 12px `font-medium text-[#71717A]` — "3 / 5"

**Table header** (`flex items-center px-6 py-[10px] bg-[#F9F9F9] border-b border-[#F0F0F0]`):
- "Site URL": `flex-1 Inter 12px font-semibold text-[#71717A]`
- "Activated": `w-[140px] Inter 12px font-semibold text-[#71717A]`
- "Status": `w-[90px] Inter 12px font-semibold text-[#71717A]`
- Action col: `w-[90px]` (empty header)

**Site rows** (`flex items-center px-6 py-[14px] border-t border-[#F0F0F0]`):
- SiteGroup (`flex-1 flex items-center gap-[10px]`):
  - Icon: `Globe` size 14 `text-[#A1A1AA]`
  - URL text: `Inter` 13px `font-medium text-[#18181B]`
- Date: `w-[140px] Inter 13px text-[#71717A]`
- StatusChip:
  - Active: `flex items-center gap-[5px] px-[10px] py-1 rounded-full bg-[#F0FDF4]`
    - Dot: `w-1.5 h-1.5 rounded-full bg-[#22C55E]`, text: `Inter 12px font-medium text-[#15803D]`
  - Expiring: `bg-[#FEF9EC]`
    - Dot: `bg-[#F59E0B]`, text: `text-[#92400E]`
- DeactivateBtn (`flex items-center gap-[5px] w-[90px]`):
  - Icon: `Power` size 13 `text-[#A1A1AA]`
  - Text: `Inter` 12px `text-[#A1A1AA]` — "Deactivate"

**Static data** (3 rows):
1. `goodtime.io` · Jan 12, 2026 · Active
2. `staging.goodtime.io` · Jan 15, 2026 · Active
3. `myclientsite.com` · Feb 3, 2026 · Expiring

---

## Implementation Steps

1. **`(app)/layout.tsx`** — app shell with sidebar + scrollable main area
2. **`components/app/app-sidebar.tsx`** — sidebar with `usePathname` active state (`'use client'`)
3. **`components/app/license-card.tsx`** — license card with copy state (`'use client'`)
4. **`components/app/activated-sites-card.tsx`** — static sites table (server component)
5. **`(app)/app/license/page.tsx`** — assembles page header + two cards

---

## Key Notes

- `'use client'` required on: `app-sidebar.tsx` (usePathname), `license-card.tsx` (copy state)
- Button component (`@/components/ui/button`) uses `rounded-pill` by default — override with `rounded-lg` via `className` prop (twMerge handles it)
- Colors are hardcoded hex values from the design, not CSS vars — the design uses literal hex, not the token system
- The `(app)` route group means URLs are `/app/license` (not `/app/app/license`) because we nest inside an `app/` folder within the `(app)` group
- Active nav detection: `pathname === '/app/license'` etc.
- Copy-to-clipboard: `navigator.clipboard.writeText(key)` + 1s "Copied!" feedback state

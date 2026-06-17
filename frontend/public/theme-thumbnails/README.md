# Theme thumbnails (public-site theme picker)

WordPress-style preview images for the admin theme picker
(`features/manage-appearance` → `PublicThemeView`).

## Convention

- Drop a file here named `<theme-id>.webp` (e.g. `aurora.webp`).
- It is served at `/theme-thumbnails/<theme-id>.webp` (Vite `public/`).
- Wire it by setting `thumbnail: '/theme-thumbnails/<id>.webp'` on the theme in
  `src/shared/lib/public-themes.ts`.
- When `thumbnail` is omitted, the picker falls back to the 3-colour swatch
  (surface · raised · accent), so a missing image never breaks the card.

## Image spec

- **Aspect**: 16:7 (the card crops with `object-cover`).
- **Size**: ~640×280px is plenty; keep files small (WebP, < ~60 KB).
- **Content**: a representative screenshot of the theme's home/feed in light
  mode. (Per-mode light/dark thumbnails are a future extension; the registry
  field is a single path today.)

## Delivered bundles

ClaudeDesign bundles ship screenshots under `docs/theming/themes/<id>/screenshots/`
and may declare `assets.preview` in the manifest. At import time, copy the chosen
screenshot here as `<id>.webp` and set the registry `thumbnail`.

# Build theme tokens: Mac OS 7

The OS7 theme (formerly Bold) remains registered as `theme4` so the existing theme switcher and saved preference continue to work. Its presentation layer lives in `mac-os-7.css`, imported from `assets/design-system/components.css`.

- Reference: System 7.5 Finder. Teal/gray 4px dithered desktop, white windows with striped title bars, close and zoom boxes, 2px hard drop shadow.
- Colors: `#000`, `#FFF`, `#55AAAA` desktop teal, `#CCCCFF` lavender (folders, scroll thumb, progress track, selection), `#DFDDF7` pale lavender (hover), `#526FC7` startup blue (accent), `#FFFFAA` note-pad yellow (recommendations).
- Fonts (bundled in `fonts/`, from system.css, MIT): ChicagoFLF for h1/h2, ChiKareGo2 (Chicago 12) for chrome, h3, eyebrows, buttons and menus, FindersKeepers (Geneva 9) for small labels. Body copy uses Geneva with Verdana fallback for readability.
- Windows: any section in the window selector list gets a title bar via `::before` and a title via `::after`. Titles are set per section near the "Title text" block; unknown windows show "Untitled".
- Buttons: 7px rounded push buttons, invert on press; the primary action is the default button with the heavy outer ring.
- Lists: `details` in practice steps, FAQ and AI Design Lab use Finder disclosure triangles.
- Journey animation: shadow DOM reads `--journey-font`, `--journey-mono`, `--journey-radius`, `--journey-button-radius`; defaults are unchanged for other themes.
- Motion: none in this theme; fully disabled under `prefers-reduced-motion`.
- Scope: Build only. Root, Livesite, content, routes and other themes are not changed by this layer.

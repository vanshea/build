# Codex prompt: apply the Van Shea Creative design system to Build (all themes except OS7)

Paste everything below the line into Codex, run from `/Library/WebServer/Documents/build`.

---

You are working in the `vanshea/build` static site (plain HTML/CSS/vanilla JS, no build step, deployed to build.vanshea.com). Bring the **Van Shea Creative design system** into the site's Clear and Contrast themes, align Wild's components with it while keeping Wild's current colours, using current web-platform standards, meeting WCAG 2.2 AA. **Leave the OS7 theme (`data-theme="theme4"`, `assets/themes/mac-os-7.css`) exactly as it looks and behaves today.**

## 0. Ground rules
- Read `README.md` first. Do **not** run `npm run variants:sync`. Do **not** commit, push or deploy. When you're done, list the changed files so I can review and stage them myself with `.github/scripts/GITHUB-FILES.txt`.
- Out of scope, don't touch: `blog/` (WordPress), the nested `build/` preview copy, `assets/aidesign/prototypes/**`, `assets/home/demos/**`, `assets/embeds/**`, `.github/`, the markdown reports.
- In scope: `index.html`, `studio.html`, `work.html`, `experience.html`, `400–503.html`, `case-studies/**/index.html`, `aidesign/index.html`, `aidesign/experiments/**`, the site chrome (header, footer, theme control) of `aidesign/*.html` and `aidesign/share/*.html`, `portfolio/**`, and the stylesheets/scripts they load: `styles.css`, `assets/home/agency-home.css`, `assets/home/agency-site.css`, `assets/case-studies.css`, `assets/aidesign.css`, `assets/design-system/tokens.css`, `assets/design-system/components.css`, `portfolio/shared/portfolio.css`, `assets/home/agency-home.js`, `script.js`.
- Don't change copy, images, alt text, URLs, routes or analytics. Content and structure stay; presentation and accessibility improve.

## 1. Protect OS7 before anything else
1. Before editing, take baseline screenshots of `index.html`, `work.html`, one case study and `aidesign/index.html` in **theme4** at 375, 768, 1280 and 1600px (Playwright, local server `python3 -m http.server 8766 --bind 127.0.0.1`). Save to `qa/os7-baseline/`. Capture the same set in **theme5** to `qa/wild-baseline/` so Wild can be compared afterwards.
2. Never edit `assets/themes/mac-os-7.css` or its fonts.
3. Every new or changed rule must be scoped so it cannot reach theme4: use the selectors `:root[data-theme="theme1"]`, `[data-theme="theme3"]`, `[data-theme="theme5"]`, or a shared `:root:not([data-theme="theme4"])`. Unscoped `:root` changes are only allowed if you prove theme4 still renders identically.
4. Keep `theme4` in the switcher, labelled "OS7", in its current position, with its existing storage key, cookie and favicon behaviour.
5. At the end, re-shoot the same screenshots and pixel-diff them against the baseline. Any OS7 difference is a bug; fix it before finishing and report the diff result.

## 2. Theme mapping (live id → design-system theme)
| Switcher label | id | Becomes |
|---|---|---|
| Clear | `theme1` | DS **Clear** (light; primary theme) |
| Contrast | `theme3` | DS **High Contrast** (new Figma palette, light) |
| OS7 | `theme4` | **unchanged** |
| Wild | `theme5` | **Keep current colours** (the design system's Wild now matches the live palette); apply DS type, components and accessibility fixes only. **Default theme.** |
| (legacy, not in switcher) | `theme2` | DS **Tinted**; keep it working for old saved preferences, don't add it to the switcher |

Also:
- **Keep Wild (`theme5`) as the default** for visitors with no saved theme, as it is today in `agency-home.js` and `script.js`, and keep `data-theme="theme5"` on each page's `<html>`. Saved preferences and the shared `vsc_site_theme_v2` cookie still win.
- Contrast is now light, so update the `isDark` logic and the light/dark favicon switching in both scripts: only treat a theme as dark if its `color-scheme` is dark.
- Remove the `prefers-color-scheme: dark` override that turns theme1 dark; the DS Clear theme is light-only. Keep `color-scheme` declared per theme.
- `<meta name="theme-color">` must follow `--paper` for the active theme.
- Consolidate: `agency-home.js` and `script.js` both implement theme switching. Make one source of truth (keep `agency-home.js`'s storage key, cookie and radio-group markup), and make `script.js` defer to it, without breaking pages that load only one of them.
- Prevent a flash of the wrong theme: add a tiny inline script in each in-scope `<head>` that applies the saved theme before first paint.

## 3. Tokens (exact values)
Put these in `assets/design-system/tokens.css` inside `@layer tokens`. Keep the existing semantic aliases the site's CSS uses (`--bg`, `--muted`, `--button`, `--button-edge`, `--button-ink`, `--focus`, `--footer-bg`, `--footer-ink`, `--panel`, `--line`, `--accent`), and point them at the DS tokens per theme so legacy selectors resolve correctly.

```css
@layer tokens {
  :root{--font-display:"Lexend Deca",system-ui,sans-serif;--font-text:"Open Sans",system-ui,sans-serif;--font-label:"Space Grotesk",system-ui,sans-serif;--serif:Georgia,"Times New Roman",serif}

  /* Clear (theme1) — primary */
  :root[data-theme="theme1"]{color-scheme:light;
    --paper:#fffcf3;--panel:#f6f1e6;--ink:#2f2427;--ink-soft:#5e5357;--line:#e2d9c9;
    --accent:#2d7a58;--accent-ink:#fffcf3;--accent-2:#ff8a4c;--focus-ring:#2d7a58;--radius-soft:10px}

  /* Tinted (legacy theme2) */
  :root[data-theme="theme2"]{color-scheme:light;
    --paper:#f5efe2;--panel:#ebe1cd;--ink:#2f2427;--ink-soft:#594b47;--line:#d5c6ab;
    --accent:#94472f;--accent-ink:#fff8eb;--accent-2:#ff8a4c;--focus-ring:#94472f;--radius-soft:10px}

  /* Contrast (theme3) — from Figma */
  :root[data-theme="theme3"]{color-scheme:light;
    --paper:#fffcf3;--ink:#1c1914;--ink-soft:#6b6158;--panel:#f5f0e8;--line:#ddd6c8;
    --accent:#1a5ccc;--accent-ink:#ffffff;--accent-hover:#0e44a8;
    --brand:#f16070;--brand-dark:#d94a5b;--success:#1a7a52;--warning:#b86a08;--danger:#c0312a;
    --text-display:3rem;--text-h1:2.25rem;--text-h2:1.625rem;--text-h3:1.25rem;--text-lead:1.125rem;
    --text-body:1rem;--text-detail:.875rem;--text-label:.75rem;--text-footer:.6875rem;
    --border-width:1px;--radius-soft:4px;--radius-round:8px;
    --accent-2:var(--accent-hover);--focus-ring:var(--accent)}

  /* Wild (theme5) — the CURRENT live palette, unchanged. Values already in agency-home.css; move them here, don't alter them. */
  :root[data-theme="theme5"]{color-scheme:light;
    --paper:#fff7eb;--panel:#d96b96;--ink:#171222;--ink-soft:#493b50;--line:#2b2332;
    --accent:#d2ff00;--accent-ink:#171222;--accent-2:#39b8e8;--accent-3:#fbe0eb;
    --button:#39b8e8;--button-edge:#d96b96;--button-ink:#171222;--btn-bg:#171222;--btn-text:#fff7eb;
    --footer-bg:#d2ff00;--footer-ink:#171222;--rec-bg:#b9e4df;--rec-ink:#123b38;--rec-line:#427b75;
    --input-bg:#fffaf1;--grid-line:rgba(23,18,34,.07);--grid-size:42px;--media-shadow:9px 9px 0 #d96b96;
    --filter-active-bg:linear-gradient(135deg,#d96b96,#39b8e8);--filter-active-text:#171222;
    --focus:#144dff;--focus-ring:#144dff;--radius-soft:12px}

  :root:not([data-theme="theme4"]){--headline:#f16070}
  :root[data-theme="theme3"]{--headline:var(--brand)}
  :root[data-theme="theme5"]{--headline:#d94a5b}
}
```

**Spacing name collision:** the Figma file defines `--space-4:0.25rem`, `--space-8`, `--space-12`, `--space-16`, `--space-22`, `--space-28`, `--space-32`, `--space-48`, `--space-64`, but the site already uses `--space-1…7` and `--space-4:24px`. Don't overwrite the existing `--space-*` names. Add the Figma scale as `--fs-4:.25rem; --fs-8:.5rem; --fs-12:.75rem; --fs-16:1rem; --fs-22:1.375rem; --fs-28:1.75rem; --fs-32:2rem; --fs-48:3rem; --fs-64:4rem` (in rem, so it scales with user font size) and use it for new component spacing in all three themes.

**Wild stays visually as it is today.** Keep its signature treatments: the 42px ink grid on the body, pink `.practice` panel, 9px hard pink offset shadow on case visuals, sky-blue conversation button with pink edge, teal recommendation and experience-impact cards, chartreuse footer, and the pink→blue gradient on active filters (the one allowed gradient). Change Wild only where section 6 needs an accessibility fix, and list each such change.

**Type:** keep the existing fluid `--type-*` clamp scale for Clear and Wild; in Contrast, use the Figma `--text-*` sizes as the minimums of those clamps so text never renders smaller than Figma specifies. Headings: Lexend Deca 600 (replace the forced `font-weight:400!important`), `text-wrap:balance`. Body: Open Sans 1rem/1.65, `text-wrap:pretty`, measure ≤68ch. Labels, eyebrows, counters: Space Grotesk 700, uppercase via CSS, letter-spacing .08em, never below .75rem. Pull quotes and outcome statements: Georgia.

**Fonts:** load only Lexend Deca (400/600/700), Open Sans (400/600/700, italic 400) and Space Grotesk (500/700) for the non-OS7 themes, with `display=swap` and `preconnect`. Remove DM Sans, DM Serif Display, Archivo and IBM Plex Mono from in-scope pages unless a file outside scope needs them (the 400–503 pages load DM Sans; move them to the DS fonts).

## 4. Components (non-OS7 themes)
Implement as shared classes in `assets/design-system/components.css`, inside `@layer components`, then restyle existing markup to them. Prefer adding classes to existing elements over rewriting markup.
- **Lines, not boxes:** structure from 1px `--line` hairlines; raised areas are flat `--panel`. No gradients, no decorative shadows, no glassmorphism.
- **Buttons:** one primary style: `--accent` fill, `--accent-ink` text, `--radius-soft`, min-height 48px, Space Grotesk 700. Hover lifts 2px; in Contrast the hover colour is `--accent-hover`. In Wild keep the current blue `--button` fill, pink `--button-edge` and ink text. Secondary = text link with a visible underline.
- **Links:** underlined by default in body copy (offset .16em, thickness .12em). Hover never relies on colour alone. In Wild, pink, blue and chartreuse are fills only, never text colours (all under 3.1:1 on the cream paper); links stay ink with an underline.
- **Accent rules:** problem/risk cards get a 4px top rule, callouts a 5px left rule, the closing "next" panel a 6px top rule, all `--accent`.
- **Hero H1** "Human Agency Design…" uses `--headline` (#f16070). It passes only as large text (3.08:1 on Clear, 3.1:1 on Contrast); keep it ≥ 24px regular / 18.66px bold. On Wild's cream the original coral is 2.97:1 and fails, so Wild uses `#d94a5b` (3.9:1) via `--headline`.
- **Case-study cards:** `--radius-soft` media, 16:9, top-line client label, verb-first h3, skills as text pills. Wild: keep the current hard pink offset shadow on media.
- **Recommendations carousel:** keep scroll-snap and the existing roles; no autoplay; visible prev/next buttons ≥44px (drag must never be the only way, WCAG 2.5.7); `aria-live="polite"` "1–3 of N" status (N = the real slide count); arrow keys on the focused track.
- **Status colours (Contrast):** `--success` and `--danger` pass for text; `--warning` and `--brand-dark` (both 4.0:1) are for large text, icons and borders only. Always pair status colour with a text label.
- **Footer theme control:** keep the native radio `fieldset`/`legend`; 44px targets; selected state shown by fill and a 2px outline, not colour alone.
- **Wave footer art:** decorative, `aria-hidden="true"`; recolour white strokes to `--ink` on light themes; stop animation under reduced motion.
- **No icon libraries, no emoji.** Typographic marks only.

## 5. Modern CSS standards to apply
- **Cascade layers** in this order: `@layer reset, tokens, base, legacy, components, themes, utilities;`. Wrap the existing site CSS in `legacy` and remove `!important` from in-scope files wherever layering makes it unnecessary (there are ~160 today outside OS7). Do **not** layer `mac-os-7.css`: leave it unlayered and import it last, so it keeps beating layered rules for theme4.
- Logical properties (`margin-inline`, `padding-block`, `inset-inline`), `clamp()` fluid type and spacing, `:has()`, `:focus-visible`, `color-mix()` for tints, container queries (`container-type:inline-size`) for cards, the carousel and case grids instead of more viewport breakpoints.
- Cross-document **View Transitions** (`@view-transition{navigation:auto}`) for page-to-page fades in the non-OS7 themes only, disabled under `prefers-reduced-motion`.
- Respect user settings: `prefers-reduced-motion` (no transforms, smooth scroll or animation), `prefers-contrast: more` (thicker lines, ink-soft → ink), and `forced-colors: active` (system colours, visible borders on buttons and cards, no background-only states).
- Images: `loading="lazy"` and `decoding="async"` below the fold, explicit `width`/`height` to prevent layout shift, `fetchpriority="high"` on the hero image only.
- Update the `?v=` cache-busting strings on every stylesheet and script you change, to `?v=20261117-ds`.

## 6. Accessibility (WCAG 2.2 AA / ADA) — verify each, every non-OS7 theme
- Wild-specific fixes: `--ink-soft` on the pink panel is 3.2:1 and fails, so all text on pink uses `--ink` (`tokens.css` already forces this; keep it). The blue focus ring `#144dff` is 1.8:1 on pink, so inside pink panels the focus outline uses `--ink`. Teal card ink `#123b38` passes (8.9:1); its `#427b75` border is decorative only.
- Text ≥4.5:1, large text ≥3:1, UI components and focus indicators ≥3:1 against adjacent colours. Report a contrast table per theme.
- Visible focus on every interactive element: `outline:3px solid var(--focus-ring); outline-offset:3px`. Focus must not be hidden by sticky headers (2.4.11): add `scroll-padding-top` equal to the header height.
- Target size: ≥24×24 minimum everywhere (2.5.8), 44×44 for nav, footer, carousel, lightbox and theme controls.
- One `h1` per page, no skipped heading levels, landmarks (`header`, labelled `nav`s, `main`, `footer`), working skip link on every in-scope page, `lang` set.
- Text resizes to 200% and reflows at 320 CSS px with no horizontal scrolling (1.4.10); text spacing overrides (1.4.12) don't clip content.
- Forms on `studio.html` and `work.html`: visible labels, programmatic error messages tied with `aria-describedby`, no colour-only errors, autocomplete attributes.
- New-tab links keep the existing sr-only "(opens in a new tab)".
- Run axe-core (via Playwright) on every in-scope page in theme1, theme3 and theme5; zero serious or critical violations. Keyboard-test the menu, carousel, theme control, lightbox and forms.

## 7. Done means
1. `npm run static:check` (from `/Library/WebServer/Documents`) passes.
2. axe results and the contrast table are saved to `qa/design-system-2026-11/`.
3. OS7 pixel diff is clean.
4. Screenshots of each in-scope page type in Clear, Contrast and Wild at 375 and 1280px are saved alongside, plus a before/after of Wild showing it matches today apart from the listed accessibility fixes.
5. Your summary lists: files changed, every `!important` you kept and why, any contrast exception, and anything you couldn't apply without touching OS7.

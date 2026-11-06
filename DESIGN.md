# Miracle Web Technologies design system

Light, plain and left-aligned, for business owners in India and abroad rather than engineers. The aim is a polished, premium feel: midnight navy and sapphire bands with the cut-gem shape (`images/gem.svg`) open every marketing page, and the sections below stay light. The direction comes from the existing logo and the site's navy; it was not based on a comparison with other sites. The tokens live at the top of `frontend/web/css/site.css`, and pages use the token names. There is no dark mode.

## Colour
| Token | Value | Role |
|---|---|---|
| `--mw-ink` | `#0a1428` | Headings, footer, dark section and panel backgrounds |
| `--mw-text` | `#1b2a41` | Body text |
| `--mw-muted` | `#55627a` | Secondary text on light backgrounds |
| `--mw-line` | `#dde2e9` | Borders and rules |
| `--mw-bg` / `--mw-bg-soft` | `#ffffff` / `#f4f6f8` | Page / alternate section |
| `--mw-primary` | `#0b6fa4` | Buttons, links, focus outline (white text is 5.5:1) |
| `--mw-primary-hover` | `#095a86` | Hover and active |
| `--mw-primary-soft` | `#e6f2f8` | Icon tiles, tags, highlight rows |
| `--mw-brand` | `#00aeef` | Logo, gem, accents and bright buttons (with ink text) on dark only: too light for text on white |
| `--mw-on-ink` / `--mw-on-ink-muted` | `#ffffff` / `#b4bfce` | Text on dark |

Logo red (`#ed2a30`) stays in the logo. Bootstrap's own variables are mapped to these tokens, so the portal follows them.

## Typography
Figtree, self-hosted in `frontend/web/fonts` (SIL Open Font License); the latin-ext file carries the ₹ sign.

| Style | Size | Weight / line height |
|---|---|---|
| `.mw-h1` | 36 to 56px | 700 / 1.1 |
| `.mw-h2` | 28 to 36px | 700 / 1.2 |
| Card heading | 20 to 24px | 700 |
| `.mw-lead` | 18px | 400 / 1.6, max 38rem wide |
| Body | 16px | 400 |
| Small | 14px | 400 to 600 |

## Spacing
Bootstrap's 4px-based scale. Sections are 80px apart vertically (56px on phones), inside Bootstrap's `.container`. Text blocks stay within 44rem (`.mw-prose`).

## Shape
Radius 8px for controls and 12px for cards. Cards use a 1px border; `--mw-shadow` is only for the recommended plan, the portal illustration and hovered portal stats.

## Components
- **Buttons:** `btn-primary` and `btn-outline-primary` on light backgrounds; `btn-mw-bright` and `btn-outline-light` on dark ones. Nothing else. Hover darkens; focus shows a 3px outline.
- **Cards:** `.mw-card`, `.mw-plan`, `.mw-panel`. Don't add coloured top borders.
- **Forms:** visible label on every field, in plain words. Placeholders only give examples.
- **Header:** one line from 1200px, smaller widths use the menu button. It stays at the top while scrolling, and a back-to-top button appears after 400px.
- **Marketing pages:** set `$this->params['bare'] = true` to get full-width sections, with `.mw-section` and `.mw-page-head` inside.

## Imagery and icons
Font Awesome solid only, never emoji. Show the product (illustrations labelled as examples) rather than stock photos. Logo files are in `frontend/web/images`: `logo.svg` (mark), `logo-full.svg` and `logo-full-white.svg` (with the name), `logo.png` (for email and login).

## Copy
Lead with development: shopping carts, web applications, websites and upgrades. Write for a business owner in India or abroad, in plain international English. Keep hosting, INR and tax details on the hosting page. Only state what the terms, policies or the owner confirm: no invented numbers, testimonials or plan features.

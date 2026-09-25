# DESIGN — Daet Listens

## Brand Identity

### Name
**Daet Listens** — Daet Public Service Complaint & Transparency Management System

### Platform
Web (responsive, mobile-first)

### Color System
| Token | Value | Usage |
|-------|-------|-------|
| `--navy` | `#0B1F3A` | Primary dark background, headers, navbar |
| `--navy-mid` | `#12294d` | Secondary backgrounds, cards |
| `--navy-light` | `#1a3560` | Hover states, gradients |
| `--gold` | `#C9A84C` | Primary accent, CTAs, logos, highlights |
| `--gold-light` | `#E2C06A` | Secondary gold, gradients, hover |
| `--gold-pale` | `rgba(201,168,76,0.12)` | Subtle backgrounds, badges |
| `--cream` | `#F5F0E8` | Authenticated pages background |
| `--cream-dark` | `#EDE7D9` | Features section background |
| `--white` | `#ffffff` | Card backgrounds, text on dark |
| `--text-body` | `#4B5563` | Body text on light backgrounds |
| `--text-muted` | `#6B7280` | Secondary text |
| `--text-dim` | `rgba(255,255,255,0.55)` | Text on dark backgrounds |
| `--border-gold` | `rgba(201,168,76,0.20)` | Gold borders |
| `--border-navy` | `rgba(11,31,58,0.08)` | Subtle navy borders |
| `--red` | `#EF4444` | Error states, urgent indicators |

### Typography
| Token | Font | Usage |
|-------|------|-------|
| `--font-sans` | DM Sans | Body, navigation, UI elements |
| `--font-display` | Cormorant Garamond | Headlines, titles, display text |

### Brand Mark
- Official LGU crest: `public/images/lgulogo.png`
- Gold gradient logo accent: `linear-gradient(135deg, #C9A84C, #E2C06A)`

## Layout Principles

### Grid System
- Max-width: `1280px` (centered)
- Mobile-first responsive breakpoints: `540px`, `768px`, `1100px`
- Consistent padding: `20px` mobile, `32px` tablet, `40px` desktop

### Navigation
- Fixed top navbar, `64px` height
- Gold accent bar on hero sections
- Mobile hamburger menu with dropdown
- Auth state switches between user dropdown and public nav

### Spacing Scale
- Tight: `4px`, `8px`, `12px`
- Standard: `16px`, `20px`, `24px`
- Generous: `32px`, `40px`, `48px`
- Hero: `56px`, `72px`, `80px`

## Component Patterns

### Buttons
- Primary: Gold gradient, navy text, `4px` border-radius, `box-shadow: 0 4px 24px rgba(201,168,76,0.32)`
- Secondary: Transparent, white border/text
- Nav CTA: Gold gradient, navy text, `6px` border-radius

### Cards
- White background, `1px` border with `border-gold`
- `8px` border-radius
- Subtle hover: `translateY(-2px)` + enhanced shadow
- Gold accent line on hover (features)

### Form Elements
- Input background: `#fafaf8` (light) or `rgba(255,255,255,0.04)` (dark)
- Focus: Gold border + `box-shadow: 0 0 0 3px rgba(201,168,76,0.07)`
- `4px` border-radius

### Status Badges
- Submitted: Blue
- Review: Amber
- Progress: Purple
- Resolved: Green
- Rejected: Red
- Closed: Gray

## Accessibility Standards
- WCAG 2.1 AA compliance
- All interactive elements have `:focus-visible` styles
- Text contrast ≥ 4.5:1 for body text, ≥ 3:1 for large text
- Keyboard navigable dropdowns and menus
- `prefers-reduced-motion` supported
- Text is selectable (no `user-select: none` on body)
- ARIA labels on all interactive elements
- Semantic HTML with proper landmarks

## Motion Design
- Primary animation: `fadeUp` (0.7s, cubic-bezier(.22,.68,0,1.2))
- Staggered delays: `.d1` (0.04s), `.d2` (0.16s), `.d3` (0.28s), `.d4` (0.40s), `.d5` (0.52s)
- Hover transitions: `0.2s` for color/border, `0.28s` for transforms
- `prefers-reduced-motion` reduces all animations to near-instant

## Responsive Breakpoints
| Breakpoint | Max Width | Key Changes |
|------------|-----------|-------------|
| Mobile | `540px` | Single column, stacked CTAs, smaller stats |
| Tablet | `768px` | 2-column grids, mobile nav |
| Desktop | `1100px` | Multi-column layouts |
| Wide | `1280px` | Max content width |

## Do's and Don'ts

### Do
- Use gold (`#C9A84C`) as the sole accent color
- Use Cormorant Garamond for display, DM Sans for UI
- Maintain the navy/gold color scheme consistently
- Use `border-radius: 4px` for buttons, `6-8px` for cards
- Keep the gold accent bar on hero sections
- Use `linear-gradient(135deg, var(--gold), var(--gold-light))` for CTA buttons

### Don't
- Don't use more than 2 font families
- Don't use `user-select: none` on body or interactive elements
- Don't hard-code colors — use CSS custom properties
- Don't duplicate CSS across blade files
- Don't add `border-radius` above `8px` for standard components
- Don't use gradient text effects
- Don't add decorative elements that can't be themed

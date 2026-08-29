# StockLane Design System

Industrial slate + emerald stock status. Ops inventory dashboard.

## Dials

- Variance: 6 | Motion: 4 | Density: 7

## Colors

| Role | Hex |
|------|-----|
| Background | `#09090b` (zinc-950) |
| Surface | `#18181b` (zinc-900) |
| Foreground | `#fafafa` |
| Muted | `#a1a1aa` |
| Border | `#27272a` |
| Accent | `#059669` emerald |
| Warn | `#fbbf24` amber |
| Danger / sale action | zinc-100 on zinc (high contrast) |

## Typography

- UI: `Fira Sans` (body)
- Data/SKU: `Fira Code` (mono tabular)
- No Inter. No purple. No emoji icons.

## Layout

- Mobile-first: card stack `< md`, table `>= md`
- Touch targets min 44px
- `min-h-[100dvh]`
- Metrics as divide/border surfaces, not decorative cards overload
- Focus rings visible; `prefers-reduced-motion: reduce` respected for transitions

## States

- Loading: disabled buttons + busy id
- Empty: dashed border seed instructions
- Low stock: amber pill; Healthy: emerald pill

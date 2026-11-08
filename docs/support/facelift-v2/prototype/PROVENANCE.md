# UI provenance

The following local UI primitives are adapted from the official shadcn/ui
`new-york-v4` registry, inspected 29 October 2026:

- https://github.com/shadcn-ui/ui/blob/main/apps/v4/registry/new-york-v4/ui/button.tsx
- https://github.com/shadcn-ui/ui/blob/main/apps/v4/registry/new-york-v4/ui/dialog.tsx
- https://github.com/shadcn-ui/ui/blob/main/apps/v4/registry/new-york-v4/ui/input.tsx
- https://github.com/shadcn-ui/ui/blob/main/apps/v4/registry/new-york-v4/ui/native-select.tsx
- https://github.com/shadcn-ui/ui/blob/main/apps/v4/registry/new-york-v4/ui/textarea.tsx
- https://github.com/shadcn-ui/ui/blob/main/apps/v4/registry/new-york-v4/ui/label.tsx
- https://github.com/shadcn-ui/ui/blob/main/apps/v4/registry/new-york-v4/ui/badge.tsx
- https://github.com/shadcn-ui/ui/blob/main/apps/v4/registry/new-york-v4/ui/table.tsx
- https://github.com/shadcn-ui/ui/blob/main/apps/v4/registry/new-york-v4/ui/alert.tsx

MIT license retained in [SHADCN-LICENSE.md](SHADCN-LICENSE.md). Adaptations:
split Radix package imports; local `cn`; semantic theme tokens; readable groups
of Tailwind utilities; 44px controls; Indonesian close label. Button, Badge,
Input and Alert use reusable CVA variants. Input adds score/search/native
checkbox/radio variants; all native controls retain native properties and labels.
Label uses a native `label` rather than installing an otherwise unnecessary
Radix package. NativeSelect's wrapper uses a `span` so it remains valid within
a native Label. Table retains native table/row/header/cell elements and accepts
responsive utility overrides. Alert adds warning/success tones; routine demo
save feedback explicitly overrides the registry's default `alert` to `status`.
`dialog.tsx` is an application wrapper with explicit opener focus restoration.

Registry source is vendored, not loaded from CDN at runtime. Button variants,
`asChild` slot composition, portal/overlay, accessible title/description and
Radix keyboard behavior remain. Application forms use the local shared labeled
controls. There are no added runtime dependencies for this styling refactor.

Global `styles.css` contains only the Tailwind import, CSS-variable theme,
`@theme inline` mappings and documented `@layer base` baseline declarations.
Layout, breakpoints, peer/ARIA/readonly states, focus, reduced motion and overlay
styling use Tailwind utilities in the relevant React component. There are no
custom component selectors, semantic CSS class shortcuts, bulk component
`@apply` blocks or manual CSS exceptions.

The implementation uses the installed Tailwind **4.1.8**; it does not upgrade
the runtime to match the current documentation website. The utility syntax
used here was compiled by that installed version. Official references:

- https://tailwindcss.com/docs/theme
- https://tailwindcss.com/docs/adding-custom-styles
- https://tailwindcss.com/docs/hover-focus-and-other-states
- https://tailwindcss.com/docs/responsive-design
- https://ui.shadcn.com/docs/theming

All fixture names/IDs/scores are invented demonstration data. No assets are
derived from real pupil records. System fonts only; Lucide icons from the
pinned `lucide-react` package.

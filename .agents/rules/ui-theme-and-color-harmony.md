# UI Theme & Color Harmony Rules

## 1. Dynamic User-Configurable Theme Awareness

- **Theme-Agnostic Design**: We Creatif includes a dynamic theme customization engine where administrators configure the primary color palette (Sunset Orange, Emerald, Ocean Blue, Violet, Zinc, Amber, Rose, Cyan, or Custom Hex), font family, border radius, card border width, and card shadows.
- **Never Assume a Fixed Primary Color**:
    - Never assume `--primary` is black, dark zinc, or any specific hue.
    - Applying `text-primary` or `bg-primary` renders elements in the administrator's chosen theme color.
    - Colors that look subtle in monochrome zinc (such as near-black `text-primary` icons) become vibrant accents in orange or violet. Always use semantic design tokens intentionally.

## 2. Semantic Color Hierarchy & Icon Placement

### A. Primary vs Secondary Actions

- **Primary CTA (`variant="default"` or `bg-primary text-primary-foreground`)**:
    - Reserved strictly for the single primary call to action on the screen or major section (e.g. _"Continuer le Pipeline"_, _"Enregistrer"_, _"Créer un projet"_).
    - Never apply `bg-primary` or primary styling to secondary, passive, or utility buttons in a toolbar.
- **Secondary Buttons (`variant="outline"`, `variant="ghost"`)**:
    - Secondary buttons and toolbars must remain neutral.
    - Icons inside secondary buttons must use neutral styling (`className="h-3.5 w-3.5"` or `text-muted-foreground transition-colors group-hover:text-foreground`).
    - **NEVER** apply `text-primary` to an icon in an outline or ghost button (such as `BarChart3`, `Settings`, `QrCode`, `SlidersHorizontal`, `Globe`) unless that button represents an active/selected toggle state.

### B. Dropdown Menus

- **Standard Navigation Items**: Icons must use neutral styling (`size-3.5` or `size-3.5 text-muted-foreground`).
- **Destructive Items**: Use `text-destructive` on both the item and its icon.
- **Active / Selected Items**: Use `text-primary` for the active item or checkmark (`<Check className="size-3.5 text-primary" />`).
- **Never** give arbitrary `text-primary` to ordinary navigation links in a dropdown (e.g. Analytics, Preview, Demo).

### C. Card Header Actions

- Standardize card header secondary links/actions using ghost buttons:
    ```tsx
    <Link href="...">
        <Button
            variant="ghost"
            size="sm"
            className="text-muted-foreground hover:text-foreground h-7 gap-1 text-xs"
        >
            {t('...')}
        </Button>
    </Link>
    ```
- Avoid mixing raw underlined primary text links (`text-primary hover:underline`) in card headers alongside ghost action buttons.

### D. Separation of Status Colors vs Theme Primary

- **Status Colors Must Never Be Replaced by Theme Primary**:
    - **Success / Completed / Published**: Emerald (`text-emerald-500`, `bg-emerald-500/10`, `border-emerald-500/20`).
    - **Warning / In Progress / Pending**: Amber (`text-amber-500`, `bg-amber-500/10`, `border-amber-500/20`).
    - **Danger / Destructive / Failed**: Destructive / Rose (`text-destructive`, `bg-destructive/10`, `border-destructive/20`).
    - **Informational / Analytics**: Sky / Indigo (`text-sky-500`, `bg-sky-500/10`).
- **Where Theme Primary is Appropriate**:
    1. Primary CTA buttons (`bg-primary text-primary-foreground`).
    2. Active/selected state indicators (active step badge in CPS stepper, active tab border, active page in tree).
    3. Form focus rings (`focus-visible:ring-ring`, `border-primary` on focused inputs).
    4. Brand accent icons specifically tied to We Creatif AI capabilities (e.g. `<Sparkles className="h-4 w-4 text-primary" />` on the CPS Stepper or AI prompt cards).
    5. Selected radio/checkbox markers.

## 3. Design Tokens & Component Styling

- **Card Styling**: Cards automatically inherit `--card-border-width` and `--shadow-card`. Avoid inline border overrides that break user-customized card border widths.
- **Radius Tokens**: Use `rounded-lg`, `rounded-xl`, or `rounded-2xl` which resolve against the user's `--radius` configuration.
- **Dark Mode Compatibility**: Always verify that color combinations maintain WCAG contrast ratios in both light and dark modes. Ensure `text-muted-foreground` and `bg-muted` are used instead of fixed slate or gray classes.

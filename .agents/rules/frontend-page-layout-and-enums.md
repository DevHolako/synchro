# Frontend Page Layout & Enum Localization Rules

## 1. Full-Width Application Layout (No Boxed Centering)
- **Standing Rule**: Never restrict project pipeline views (such as Brief, Sitemap, Studio, Batch Pages, Quality, Analytics, Settings) with centering containers like `mx-auto` or fixed max-width wrappers like `max-w-7xl` / `max-w-5xl`.
- **Standard Container**: Always follow the existing application convention using the full available width:
  ```tsx
  <div className="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
  ```
- **Inertia v3 Layout Props**: Always register page breadcrumbs at the component level:
  ```tsx
  const breadcrumbs = [
      { title: t('dashboardBrief.breadcrumbsDashboard'), href: '/dashboard' },
      { title: project.name, href: `/projects/${project.id}` },
      { title: 'Step Label', href: `/projects/${project.id}/...` },
  ];

  setLayoutProps({ breadcrumbs });
  ```

## 2. Mandatory Enum Translation & Status Badges
- **Standing Rule**: Never render raw database enum strings (such as `project.status` -> `index_approved`, `pages_generating`, `ready`, etc.) directly in the UI.
- **Badge Helper**: Always wrap project statuses using the localized enum helpers from `@/lib/enums`:
  ```tsx
  import { getProjectStatusBadgeClass, getProjectStatusLabel } from '@/lib/enums';

  <Badge
      variant="outline"
      className={`text-xs font-medium ${getProjectStatusBadgeClass(project.status)}`}
  >
      {getProjectStatusLabel(project.status, t)}
  </Badge>
  ```
- Never display `project.status` without `getProjectStatusLabel(project.status, t)`.

## 3. Asynchronous Generation Queue Safeguards & Status Bars
- **Button Locking**: Whenever an asynchronous generation action (batch generation or single page generation) is launched or is actively running, all generation triggers and buttons MUST be disabled immediately (`disabled={is_locked || isAnyGenerating}`). This prevents users from clicking multiple times and flooding the queue workers.
- **Status & Progress Bar Feedback**: Provide prominent visual progress feedback:
  - Global status bar showing completion percentage, an animated gradient/pulse indicator, and clear text status.
  - Card-level status bar with spinner on individual items undergoing AI synthesis.
- **Backend Idempotency**: Controller endpoints must check current status (`ProjectStatus::PagesGenerating` or `PageStatus::Generating`) and return early with a warning response if a generation is already executing.

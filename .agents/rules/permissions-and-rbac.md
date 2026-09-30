---
title: Permission-Based Authorization & RBAC Architecture
globs: "app/**,tests/**"
---

# Permission-Based Authorization & RBAC Architecture

Synchro enforces a strict **permission-based authorization model**. Permissions are the single source of truth and the sole gate of check throughout the application.

## 1. Core Principles

1. **Permissions are the Gate of Check**:
   - Every authorization boundary (Gates, Policies, Form Requests, Controllers, and Single-Actions) must check against specific, atomic **permissions** (e.g. `Permission::CreateRooms`, `Permission::ManageReferentials`, `Permission::EnterGrades`).
   - Standard Laravel Gate definitions exist for every `Permission` case via `AppServiceProvider`.
   - Authorized checks use `$user->hasPermission(Permission::...)`, `$user->can('permission:name')`, or `Gate::authorize(...)`.

2. **Roles are Strictly Bundles of Permissions**:
   - Roles (`Administrator`, `Coordinator`, `Teacher`, `Student` in `App\Enums\UserRole`) are **groupings of permissions**; they are never authorization gates themselves.
   - Each role defines its bundled permissions in `UserRole::permissions(): array`.
   - Users inherit permissions through their assigned role bundle (and future custom/override permission pivots if introduced).

3. **Strict Prohibitions**:
   - **NEVER** check `$user->role === ...`, `$user->isCoordinator()`, or `$user->isAdministrator()` to authorize access to domain logic or endpoints.
   - **NEVER** write policy methods that compare roles directly. All policies (`RoomPolicy`, `CampusPolicy`, `BuildingPolicy`, etc.) must inspect permissions.
   - **NEVER** hardcode permission strings arbitrarily; always reference the `App\Enums\Permission` backed enum.

## 2. Code Examples

### Policy Method
```php
public function create(User $user): bool
{
    return $user->hasPermission(Permission::CreateRooms)
        || $user->hasPermission(Permission::ManageReferentials);
}
```

### Form Request
```php
public function authorize(): bool
{
    return $this->user()?->can('create', Room::class) ?? false;
}
```

### Direct Gate Check
```php
Gate::authorize('manage:referentials');
```

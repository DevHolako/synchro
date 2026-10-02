# 03: Versioned Mobile REST API (`/api/v1/`) for Timetables and Auth

**What to build:** Versioned RESTful API endpoints authenticated via Laravel Sanctum Personal Access Tokens. Provides mobile clients with login, user profile, and personal course schedule feeds formatted as standardized JSON API Resources, executing the exact same Single-Action classes as the web app.

**Blocked by:** Part 03 - 01: React FullCalendar Timetable Views with Open Operational Grid

**Status:** completed

- [x] Laravel Sanctum authentication setup for API guard with route `/api/v1/auth/login` returning Bearer token
- [x] API route `/api/v1/auth/logout` revoking current token and `/api/v1/auth/user` returning authenticated profile
- [x] API routes `/api/v1/schedules/my-schedule` and `/api/v1/schedules/group/{id}` returning timetable sessions
- [x] Standardized Eloquent API Resources (`CourseSessionResource`, `RoomResource`, `ModuleResource`, `UserResource`)
- [x] RFC 7807 Problem Details compliant error responses
- [x] Rate limiting (`throttle:60,1`) configured on API routes
- [x] Automated tests verifying token issuance, token revocation, and JSON resource payload schemas

## Design decisions (2026-10-02, Ticket 03)

- **Sanctum Authentication Setup**:
  - `personal_access_tokens` table migrated.
  - `HasApiTokens` trait added to `App\Models\User`.
  - Inactive or invited users (`$user->isActive() === false`) are refused authentication at the action level with translated message.
  - `POST /api/v1/auth/login` returns `{ token, token_type: 'Bearer', user }`.
  - `POST /api/v1/auth/logout` revokes `$user->currentAccessToken()`.
  - `GET /api/v1/auth/user` returns authenticated user profile, permissions, and unread notification counter.
- **RFC 7807 Problem Details**:
  - `App\Http\Responses\ProblemDetailsResponse` generates compliant `application/problem+json` envelopes (`type`, `title`, `status`, `detail`, `instance`, `errors`, `invalid_params`, `conflicts`).
  - Integrated via `bootstrap/app.php` `withExceptions()` rendering for all `$request->is('api/*')` requests.
- **Dual-Engine Single-Action Integration (ADR 0009)**:
  - `AuthenticateApiUserAction` and `RevokeApiTokenAction` encapsulate authentication logic.
  - `MyScheduleController` and `GroupScheduleController` reuse `ListTimetableSessionsAction` and `TimetableScope`.
  - Mobile date ranges accept explicit `from`/`until` bounds, anchor dates, or default to current school week.
- **Eloquent API Resources**:
  - `CourseSessionResource` formats sessions with ISO 8601 timestamps and nested resources (`ModuleResource`, `RoomResource`).
  - `UserResource` formats user profile, permissions array, and role/status attributes.
- **Security & Rate Limiting**:
  - `throttle:60,1` applied to `/api/v1` routes group.

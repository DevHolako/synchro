# 03: Versioned Mobile REST API (`/api/v1/`) for Timetables and Auth

**What to build:** Versioned RESTful API endpoints authenticated via Laravel Sanctum Personal Access Tokens. Provides mobile clients with login, user profile, and personal course schedule feeds formatted as standardized JSON API Resources, executing the exact same Single-Action classes as the web app.

**Blocked by:** Part 03 - 01: React FullCalendar Timetable Views with Open Operational Grid

**Status:** ready-for-agent

- [ ] Laravel Sanctum authentication setup for API guard with route `/api/v1/auth/login` returning Bearer token
- [ ] API route `/api/v1/auth/logout` revoking current token and `/api/v1/auth/user` returning authenticated profile
- [ ] API routes `/api/v1/schedules/my-schedule` and `/api/v1/schedules/group/{id}` returning timetable sessions
- [ ] Standardized Eloquent API Resources (`CourseSessionResource`, `RoomResource`, `ModuleResource`)
- [ ] RFC 7807 Problem Details compliant error responses
- [ ] Rate limiting (`throttle:60,1`) configured on API routes
- [ ] Automated tests verifying token issuance, token revocation, and JSON resource payload schemas

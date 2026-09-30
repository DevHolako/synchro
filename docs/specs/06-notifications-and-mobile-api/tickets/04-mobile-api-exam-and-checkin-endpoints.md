# 04: Mobile API Endpoints for Exam Convocations and QR Check-in

**What to build:** Specialized mobile REST API routes allowing future native mobile apps to retrieve candidate exam convocations and submit door QR scan check-ins with complete validation, powered by the core check-in Single-Action.

**Blocked by:** 03: Versioned Mobile REST API (`/api/v1/`), Part 04 - 04: Interactive Mobile QR Scan Exam Check-in Interface

**Status:** ready-for-agent

- [ ] API route `/api/v1/exams/my-exams` returning student's published exams and assigned split rooms
- [ ] API route `/api/v1/exams/convocation/{id}/download` streaming the official PDF convocation
- [ ] API route `POST /api/v1/check-in/scan` accepting QR code UUID token and executing live door check-in
- [ ] Clear JSON response indicating check-in success, wrong room warning, or superseded convocation error
- [ ] Automated tests asserting API check-in success, invalid token rejection, and role authorization

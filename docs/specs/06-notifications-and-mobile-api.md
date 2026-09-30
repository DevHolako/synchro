# Spec 06: Notifications & Mobile REST API

## Problem Statement

Schedule adjustments, room relocations, or emergency cancellations fail to reach working professionals in evening and weekend programs in a timely manner when relying solely on portal banners. Furthermore, supporting future mobile applications (Flutter/React Native) often leads to duplicate business logic, fragmented validation, and insecure API implementations.

## Solution

A decoupled multi-channel Emergency Notification Gateway paired with a complete, mobile-ready RESTful API. The notification engine utilizes a Manager/Driver architecture (local Mock/Log driver for development and testing, swappable for live Twilio SMS or Meta WhatsApp in production) providing in-app notification counters, automated emails, and urgent broadcast alerts. Symmetrically, a versioned `/api/v1/` REST API authenticated via Laravel Sanctum exposes the exact same Single-Action business logic to external and mobile clients with zero redundancy.

## User Stories

1. As a User, I want an in-app notification bell with an unread badge counter in the top bar, so that I see timetable announcements and schedule updates immediately upon login.
2. As a User, I want to receive an automated email whenever a new course timetable or official exam convocation is published, so that I have an external record.
3. As a Student or Teacher, I want to receive an urgent SMS or WhatsApp message when an exam or course session is relocated or postponed within 2 hours of its start time (Emergency Reschedule), so that I am notified on my phone without checking email.
4. As an Administrator, I want to switch notification drivers in configuration (e.g. from local log mock to Twilio or WhatsApp) without modifying any application code, so that production deployment is seamless.
5. As a Mobile App User, I want to authenticate against `/api/v1/auth/login` and receive a Sanctum Bearer token, so that I can access my account from a native mobile application.
6. As a Mobile App Developer, I want `/api/v1/schedules` and `/api/v1/exams` endpoints returning standardized JSON Resource payloads with ISO 8601 timestamps, so that mobile clients can easily parse and render data.
7. As a Mobile Invigilator, I want an API endpoint `/api/v1/check-in/scan` to verify QR codes and submit attendance directly from a native mobile camera view, so that check-in works seamlessly inside a native app.
8. As a Developer, I want Web and API controllers to call identical Single-Action classes, so that bug fixes and business rules apply equally to both platforms.
9. As a Developer, I want all background notification jobs dispatched onto asynchronous queues, so that user requests never lag during bulk notification broadcasts.

## Implementation Decisions

- **Notification Gateway Manager/Driver (ADR 0003)**:
  - Interface: `UrgentAlertGatewayInterface`
  - Implementation: `UrgentAlertManager` supporting drivers: `log` (writes formatted JSON alerts to `storage/logs`), `database` (stores in `urgent_alerts` table for inspection), `twilio` (SMS), and `whatsapp` (Meta Cloud API).
- **Asynchronous Queue Pipeline**: All mailables and urgent broadcasts implement `ShouldQueue` utilizing Laravel Horizon or Redis/Database queues.
- **Dual-Engine Single-Action Parity (ADR 0009)**:
  - Web controllers (`App\Http\Controllers\Web\...`) call Actions and return Inertia responses.
  - API controllers (`App\Http\Controllers\Api\V1\...`) call identical Actions and return `JsonResource` collections/objects.
- **Sanctum Authentication**: API routes protected via `auth:sanctum` middleware with token revocation on `/api/v1/auth/logout`.
- **API Error Format**: Conforms to RFC 7807 (Problem Details for HTTP APIs) providing structured error codes and field validation messages.

## Testing Decisions

- **Seam**: Full integration and API feature tests on notification dispatch and `/api/v1/` routes.
- **Coverage**:
  - Assert notification dispatch triggers mock driver without network errors.
  - Sanctum token issuance, authenticated route access, and token revocation.
  - Symmetrical behavior verification: executing a schedule action via web vs API produces identical database states.
  - Queue dispatch assertions verifying jobs are pushed to queue with correct serialization.

## Out of Scope

- Setting up Apple Developer (APNs) and Google Firebase (FCM) credentials for native push notifications (reserved for the native mobile app implementation project).
- Payment gateways for outgoing SMS top-ups.

## Further Notes

- Rate limiting (`throttle:60,1`) must be applied to all public authentication and scan endpoints to prevent brute-force attacks.

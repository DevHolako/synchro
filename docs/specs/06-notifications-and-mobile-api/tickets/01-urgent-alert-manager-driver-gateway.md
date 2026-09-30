# 01: Swappable Emergency Notification Gateway (Manager/Driver Pattern)

**What to build:** Pluggable notification gateway utilizing Laravel's Manager/Driver pattern. Provides an abstract interface for urgent dispatch, defaulting to a local log/database mock driver in development/testing, and seamlessly delegating to Twilio SMS or Meta WhatsApp Cloud API in production without code refactoring.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] `UrgentAlertGatewayInterface` contract defining `sendUrgentAlert(recipientPhone, message, metadata)`
- [ ] `UrgentAlertManager` implementing driver resolution via configuration (`config/services.php`)
- [ ] `LogDriver` implementation writing formatted JSON alerts to logs during local development
- [ ] `DatabaseDriver` implementation storing dispatched alerts in an `urgent_alerts` table for test assertions
- [ ] `TwilioDriver` and `WhatsAppDriver` stubs ready for live production credentials
- [ ] Automated tests asserting driver resolution, mock execution, and graceful error handling on provider failure

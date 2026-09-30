# 05: Emergency Reschedule Workflow with Convocation Invalidation

**What to build:** Administrative Emergency Reschedule action for modifying a `Published` exam. Requires explicit confirmation, invalidates previously issued QR codes, regenerates updated Convocations, and queues emergency notification alerts.

**Blocked by:** 04: Interactive Mobile QR Scan Exam Check-in Interface

**Status:** ready-for-agent

- [ ] Emergency Reschedule modal triggered when editing a `Published` exam's date, time, or room
- [ ] Single-Action `EmergencyRescheduleExamAction` updating schedule attributes and incrementing revision number
- [ ] Automatic revocation of previous convocation UUIDs; scanned revoked tokens display "SUPERSEDED" banner
- [ ] Generation of updated Convocation PDFs with new QR codes
- [ ] Automated queuing of urgent SMS/WhatsApp and email alerts to all affected students and invigilators
- [ ] Automated tests asserting token revocation, superseded error handling on scan, and notification job dispatch

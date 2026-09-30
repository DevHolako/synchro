# 03: Coordinator Deliberation Review, Official PV PDF Archival, and Grade Locking

**What to build:** Deliberation interface allowing coordinators to inspect submitted grades, view statistical summaries (class average, pass rate, failure count), formally lock the deliberation, generate the official signed PDF PV, and publish final marks to student portals.

**Blocked by:** 02: Teacher Grade Entry Grid with Real-Time Calculations

**Status:** ready-for-agent

- [ ] Deliberation dashboard displaying submitted grades with class statistics (average, median, pass rate)
- [ ] Single-Action `LockDeliberationAction` setting `locked_at` and coordinator signature record
- [ ] Permanent immutability: locked grades reject all subsequent edit attempts (HTTP 403)
- [ ] Automated generation of the official Procès-Verbal (PV) PDF document matching ISGA academic format
- [ ] Secure archiving of the PV PDF to permanent disk storage
- [ ] Student grade portal unlocked upon PV lock, displaying published module marks
- [ ] Automated tests asserting locking immutability, PDF PV creation, and student visibility gates

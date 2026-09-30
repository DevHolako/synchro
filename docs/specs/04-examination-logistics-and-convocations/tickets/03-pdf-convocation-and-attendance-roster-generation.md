# 03: Official PDF Convocation and Door Attendance Sheet Generation

**What to build:** Automated PDF generation producing print-ready student Convocations featuring personalized exam timetables, assigned split room details, and cryptographic verification QR codes, alongside door sign-in attendance rosters (Feuille d'émargement).

**Blocked by:** 02: Alphabetical Room Splitting and Anti-Clash Invigilator Allocation

**Status:** ready-for-agent

- [ ] PDF generation service utilizing clean print-ready templates with ISGA official header
- [ ] Personalized Convocation PDF per student with timetable, allocated room, seat reference, and instructions
- [ ] Cryptographic QR code generated on each convocation encoding signed verification URL with HMAC SHA-256
- [ ] Official Attendance Roster (Feuille d'émargement) PDF grouped by allocated room for paper sign-in backup
- [ ] Student portal download button allowing enrolled students to download their Convocations once exam is `Published`
- [ ] Automated tests verifying PDF compilation, binary output validity, and QR payload integrity

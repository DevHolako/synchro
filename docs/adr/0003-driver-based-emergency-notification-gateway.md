# Driver-Based Emergency Notification Gateway

To support urgent alerts (e.g., room change at H-2) via SMS or WhatsApp without incurring paid third-party API dependencies during local development and testing, we decided to implement a Manager/Driver pattern for urgent messaging. The application code dispatches against an abstract gateway interface that defaults to a local log/database driver in development and delegates to configured SMS or WhatsApp API providers in production.

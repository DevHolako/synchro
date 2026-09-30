# Dual-Engine Single-Action API Parity

To ensure zero business logic duplication between the Inertia.js React SPA and the mobile-ready REST API, 100% of domain logic, validation, and conflict checks reside in Single-Action classes. Web controllers invoke these actions and render Inertia responses, while versioned `/api/v1/` controllers invoke the identical actions and return Sanctum-authenticated JSON API resources.

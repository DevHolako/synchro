# Permission-Based Authorization with Role Bundles

To ensure precise, adaptable access control across institutional roles and future fine-grained overrides, Synchro strictly enforces a permission-based authorization architecture. Permissions (`App\Enums\Permission`) are the sole gate of check across all Policies, Form Requests, and Gates. Roles (`Administrator`, `Coordinator`, `Teacher`, `Student` in `App\Enums\UserRole`) act strictly as permission bundles/groupings and must never be evaluated directly as authorization gates.

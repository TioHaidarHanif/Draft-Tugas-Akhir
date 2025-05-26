# Helpdesk Ticketing System Progress

## Completed Tasks

### Database Setup (May 22, 2025)

#### Migrations Created
- Updated `users` table with role field
- Created `categories` table
- Created `sub_categories` table
- Created `tickets` table with UUID and soft deletes
- Created `ticket_attachments` table
- Created `ticket_histories` table
- Created `ticket_feedbacks` table
- Created `notifications` table with UUID

#### Models Created
- Enhanced `User` model with relationships
- Created `Category` model
- Created `SubCategory` model
- Created `Ticket` model with soft deletes and UUID
- Created `TicketAttachment` model
- Created `TicketHistory` model
- Created `TicketFeedback` model
- Created `Notification` model with UUID

#### Seeders Created
- Created `RoleSeeder` for default user roles (admin, disposisi, user)
- Created `CategorySeeder` for default categories and subcategories
- Updated `DatabaseSeeder` to call the new seeders

#### Tests Created
- Created `UserTest` to test user creation and roles
- Created `CategoryTest` to test category creation and relationships
- Created `SubCategoryTest` to test subcategory creation and relationships
- Created `TicketTest` to test ticket functionality including soft deletes
- Created `TicketAttachmentTest` to test attachment functionality
- Created `TicketHistoryTest` to test history tracking
- Created `TicketFeedbackTest` to test feedback functionality
- Created `NotificationTest` to test notification functionality including UUIDs

### Authentication & User Management (May 22, 2025)

#### Controllers Created
- Created `AuthController` for user registration, login, and logout

#### Routes Created
- Added authentication routes for register, login, logout

#### Middleware Created
- Created `CheckRole` middleware for role-based authorization
  - Supports role-based access control for `admin`, `disposisi`, and `student` users
  - Returns appropriate JSON responses for unauthorized (401) and forbidden (403) requests
  - Tested with various scenarios using `RoleMiddlewareTest`

#### Tests Created
- Created `AuthTest` to test authentication functionality
- Created `RoleMiddlewareTest` to test role-based access control

### User Management (May 23, 2025)

#### Controller Created
- Created `UserController` for user CRUD and statistics operations

#### Routes Created
- Added user management endpoints (GET /users, GET /users/{id}, PATCH /users/{id}, PATCH /users/{id}/role, DELETE /users/{id}, GET /users/statistics) with admin-only access

#### Middleware
- Ensured all user management endpoints are protected by `CheckRole` middleware (admin only)

#### Tests Created
- Created `UserManagementTest` to test all user management endpoints and role restrictions

#### Implementation
- Implemented all methods in `UserController` for CRUD and statistics
- All endpoints return JSON responses and proper error handling

### Category & Subcategory Management (May 24, 2025)

#### Controller Created
- Created `CategoryController` for category and subcategory CRUD operations

#### Routes Created
- Added endpoints:
  - GET /categories (all authenticated users)
  - POST /categories (admin only)
  - POST /categories/{category_id}/sub-categories (admin only)

#### Middleware
- Protected create/update/delete category & subcategory endpoints with `CheckRole` middleware (admin only)

#### Validation
- Implemented input validation for category and subcategory creation

#### Tests Created
- Added tests in `CategoryTest` for:
  - Admin and non-admin access to create category/subcategory
  - Input validation
  - Fetching categories with subcategories

#### Implementation
- Implemented all methods in `CategoryController` for required endpoints
- All endpoints return JSON responses and proper error handling

### [Tanggal: 2025-05-24] Implementasi Fitur Manajemen Ticket

- Menambahkan endpoint manajemen ticket (POST, GET, PATCH, DELETE, RESTORE) di TicketController.
- Implementasi validasi input menggunakan FormRequest (StoreTicketRequest, UpdateTicketStatusRequest, AssignTicketRequest, AddTicketFeedbackRequest, TicketListFilterRequest).
- Implementasi notifikasi otomatis pada event ticket (pembuatan, perubahan status, assignment, feedback) sesuai spesifikasi API.
- Menambah relasi model Ticket: attachment, histories, feedbacks.
- Menambah feature test untuk TicketController (create, list, assign, update status, soft delete, restore).
- Semua endpoint sudah membatasi akses berbasis role sesuai kebutuhan.

### [Notifikasi] Implementasi Fitur Manajemen Notifikasi

- Menambah NotificationController dengan endpoint:
  - GET /notifications (daftar notifikasi, filter read/type)
  - PATCH /notifications/{id}/read (tandai notifikasi dibaca)
  - PATCH /notifications/read-all (tandai semua notifikasi dibaca)
  - POST /notifications (buat notifikasi manual)
- Menambah validasi input StoreNotificationRequest
- Menambah event & listener untuk notifikasi otomatis (new_ticket, assignment, status_change, feedback)
- Menambah NotificationFactory untuk keperluan testing
- Menambah NotificationTest (feature test)
- Registrasi event-listener di EventServiceProvider
- Semua endpoint menggunakan otorisasi berbasis user login
- Response dan struktur sesuai api.md

### Design Decisions
1. Used UUIDs for primary keys on key entities (tickets, notifications) for security and scalability
2. Kept regular auto-increment IDs for users to simplify integration with existing Laravel features
3. Implemented soft deletes for tickets to maintain data integrity
4. Added comprehensive relationship definitions to all models for easy data access
5. Set up appropriate foreign key constraints to maintain referential integrity
6. Designed database schema to support both authenticated and anonymous ticket submissions

### Next Steps
1. Create ticket management API endpoints
2. Implement ticket feedback and history tracking
3. Create notification system
4. Set up authorization policies

## Files Created/Modified

### Migrations
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/database/migrations/0001_01_01_000010_update_users_table_with_uuid_and_role.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/database/migrations/0001_01_01_000011_create_categories_table.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/database/migrations/0001_01_01_000012_create_sub_categories_table.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/database/migrations/0001_01_01_000013_create_tickets_table.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/database/migrations/0001_01_01_000014_create_ticket_attachments_table.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/database/migrations/0001_01_01_000015_create_ticket_histories_table.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/database/migrations/0001_01_01_000016_create_ticket_feedbacks_table.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/database/migrations/0001_01_01_000017_create_notifications_table.php`

### Models
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/app/Models/User.php` (modified)
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/app/Models/Category.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/app/Models/SubCategory.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/app/Models/Ticket.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/app/Models/TicketAttachment.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/app/Models/TicketHistory.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/app/Models/TicketFeedback.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/app/Models/Notification.php`

### Seeders
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/database/seeders/RoleSeeder.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/database/seeders/CategorySeeder.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/database/seeders/DatabaseSeeder.php` (modified)

### Controllers
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/app/Http/Controllers/Auth/AuthController.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/app/Http/Controllers/UserController.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/app/Http/Controllers/CategoryController.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/app/Http/Controllers/TicketController.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/app/Http/Controllers/NotificationController.php`

### Middleware
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/app/Http/Middleware/CheckRole.php`

### Routes
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/routes/api.php`

### Tests
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/tests/Feature/Models/UserTest.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/tests/Feature/Models/CategoryTest.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/tests/Feature/Models/SubCategoryTest.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/tests/Feature/Models/TicketTest.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/tests/Feature/Models/TicketAttachmentTest.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/tests/Feature/Models/TicketHistoryTest.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/tests/Feature/Models/TicketFeedbackTest.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/tests/Feature/Models/NotificationTest.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/tests/Feature/Auth/AuthTest.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/tests/Feature/Middleware/RoleMiddlewareTest.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/tests/Feature/Auth/UserManagementTest.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/tests/Feature/Ticket/TicketControllerTest.php`
- `/workspaces/Draft-Tugas-Akhir/helpdesk-api/tests/Feature/Notification/NotificationControllerTest.php`

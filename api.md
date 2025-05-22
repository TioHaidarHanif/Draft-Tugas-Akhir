# Sistem Keluhan Mahasiswa - Laravel REST API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication

### Register
Register a new user account.

- **URL**: `/auth/register`
- **Method**: `POST`
- **Request Body**:
```json
{
    "name": "string",
    "email": "string (must end with @student.telkomuniversity.ac.id, @telkomuniversity.ac.id, or @adminhelpdesk.ac.id)",
    "password": "string (min: 6 characters)",
    "password_confirmation": "string",
 
}
```
- **Success Response**: 
```json
{
    "status": "success",
    "message": "User registered successfully",
    "token": "string"
}
```
- **Error Response**:
```json
{
    "status": "error",
    "message": "Registration failed",
    "errors": {
        "email": [
            "Email must be a valid Telkom University email address"
        ],
        "password": [
            "Password must be at least 6 characters"
        ]
    },
    "code": 422
}
```

### Login
Authenticate user and get token.

- **URL**: `/auth/login`
- **Method**: `POST`
- **Request Body**:
```json
{
    "email": "string",
    "password": "string"
}
```
- **Success Response**:
```json
{
    "status": "success",
    "message": "Login successful",
    "data": {
        "user": {
            "id": "string",
            "name": "string",
            "email": "string",
            "role": "string"
        },
        "token": "string"
    }
}
```

### Logout
Invalidate user token.

- **URL**: `/auth/logout`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer <token>`
- **Success Response**:
```json
{
    "status": "success",
    "message": "Logged out successfully"
}
```
### Get Current User Profile
Get the profile information of the currently authenticated user.

- **URL**: `/auth/profile`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer <token>`
- **Success Response**:
```json
{
    "status": "success",
    "data": {
        "user": {
            "id": "string",
            "name": "string",
            "email": "string",
            "role": "string",
            "created_at": "timestamp"
        }
    }
}
```
## Tickets

### Create Ticket
Create a new complaint ticket.

- **URL**: `/tickets`
- **Method**: `POST`
- **Headers**: 
  - `Authorization: Bearer <token>`
  - `Content-Type: multipart/form-data` (when including attachments)
- **Request Body**:
```json
{
    "nama": "string (auto-filled from token)",
    "nim": "string (required for students)",
    "prodi": "string (required)",
    "semester": "string (required)",
    "email": "string (auto-filled from token)",
    "no_hp": "string (required)",
    "anonymous": "boolean (default: false)",
    "judul": "string (required)",
    "category_id": "integer (required)",
    "sub_category_id": "integer (required)",
    "deskripsi": "string (required)",
    "lampiran": "file (optional, max: 5MB, types: jpg,jpeg,png,pdf)"
}
```
- **Success Response**:
```json
{
    "status": "success",
    "message": "Ticket created successfully",
    "data": {
        "id": "string",
        "user_id": "string",
        "nama": "string",
        "nim": "string",
        "prodi": "string",
        "semester": "string",
        "email": "string",
        "no_hp": "string",
        "category_id": "integer",
        "sub_category_id": "integer",
        "judul": "string",
        "deskripsi": "string",
        "anonymous": "boolean",
        "status": "string",
        "assigned_to": "string",
        "read_by_admin": "boolean",
        "read_by_disposisi": "boolean",
        "read_by_student": "boolean",
        "lampiran": {
            "id": "integer",
            "file_name": "string",
            "file_type": "string",
            "file_url": "string"
        },
        "created_at": "timestamp",
        "updated_at": "timestamp"
    }
}
```

### Get All Tickets
Get list of tickets based on user role.

- **URL**: `/tickets`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer <token>`
- **Query Parameters**:
  - `status`: string (optional) - Filter by status (new, in_progress, resolved, closed)
  - `category_id`: integer (optional) - Filter by category
  - `sub_category_id`: integer (optional) - Filter by sub-category
  - `search`: string (optional) - Search in judul or deskripsi
  - `startDate`: date (optional) - Filter by creation date range start
  - `endDate`: date (optional) - Filter by creation date range end
  - `sortBy`: string (optional) - Sort field (created_at, status, category_id)
  - `sortOrder`: string (optional) - Sort direction (asc, desc)
  - `page`: integer (optional) - Page number
  - `per_page`: integer (optional) - Items per page
- **Success Response**:
```json
{
    "status": "success",
    "data": {
        "tickets": [
            {
                "id": "string",
                "user_id": "string",
                "nama": "string",
                "nim": "string",
                "prodi": "string",
                "semester": "string",
                "email": "string",
                "no_hp": "string",
                "category_id": "integer",
                "sub_category_id": "integer",
                "judul": "string",
                "deskripsi": "string",
                "anonymous": "boolean",
                "status": "string",
                "assigned_to": "string",
                "read_by_admin": "boolean",
                "read_by_disposisi": "boolean",
                "read_by_student": "boolean",
                "lampiran": {
                    "id": "integer",
                    "file_name": "string",
                    "file_type": "string",
                    "file_url": "string"
                },
                "created_at": "timestamp",
                "updated_at": "timestamp"
            }
        ],
        "pagination": {
            "total": "integer",
            "per_page": "integer",
            "current_page": "integer",
            "last_page": "integer"
        }
    }
}
```

### Get Ticket Detail
Get detailed information of a specific ticket.

- **URL**: `/tickets/{id}`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer <token>`
- **Success Response**:
```json
{
    "status": "success",
    "data": {
        "ticket": {
            "id": "string",
            "user_id": "string",
            "nama": "string",
            "nim": "string",
            "prodi": "string",
            "semester": "string",
            "email": "string",
            "no_hp": "string",
            "category_id": "integer",
            "sub_category_id": "integer",
            "judul": "string",
            "deskripsi": "string",
            "anonymous": "boolean",
            "status": "string",
            "assigned_to": "string",
            "read_by_admin": "boolean",
            "read_by_disposisi": "boolean",
            "read_by_student": "boolean",
            "lampiran": {
                "id": "integer",
                "file_name": "string",
                "file_type": "string",
                "file_url": "string"
            },
            "ticket_histories": [
                {
                    "id": "integer",
                    "action": "string",
                    "old_status": "string",
                    "new_status": "string",
                    "assigned_by": "string",
                    "assigned_to": "string",
                    "updated_by": "string",
                    "timestamp": "timestamp"
                }
            ],
            "ticket_feedbacks": [
                {
                    "id": "integer",
                    "created_by": "string",
                    "text": "string",
                    "created_by_role": "string",
                    "created_at": "timestamp"
                }
            ],
            "created_at": "timestamp",
            "updated_at": "timestamp"
        }
    }
}
```

### Update Ticket Status
Update the status of a ticket.

- **URL**: `/tickets/{id}/status`
- **Method**: `PATCH`
- **Headers**: `Authorization: Bearer <token>`
- **Request Body**:
```json
{
    "status": "string", // "open", "in_progress", "resolved", "closed"
    "comment": "string" // optional
}
```
- **Success Response**:
```json
{
    "status": "success",
    "message": "Ticket status updated successfully",
    "data": {
        "id": "string",
        "status": "string",
        "updated_at": "timestamp",
        "ticket_history": {
            "id": "integer",
            "action": "status_change",
            "old_status": "string",
            "new_status": "string",
            "updated_by": "string",
            "timestamp": "timestamp"
        }
    }
}
```

### Assign Ticket
Admin Assign a ticket to a disposisi member.

- **URL**: `/tickets/{id}/assign`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer <token>`
- **Request Body**:
```json
{
    "assigned_to": "string"
}
```
- **Success Response**:
```json
{
    "status": "success",
    "message": "Ticket assigned successfully",
    "data": {
        "id": "string",
        "assigned_to": "string",
        "updated_at": "timestamp",
        "ticket_history": {
            "id": "integer",
            "action": "assignment",
            "assigned_by": "string",
            "assigned_to": "string",
            "timestamp": "timestamp"
        }
    }
}
```

### Statistics Ticket
Get ticket statistics.

- **URL**: `/tickets/statistics`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer <token>`
- **Success Response**:
```json
{
    "status": "success",
    "data": {
        "total": "integer",
        "new": "integer",
        "in_progress": "integer",
        "resolved": "integer",
        "closed": "integer",
        "unread": "integer",
        "by_category": [
            {
                "category_id": "integer",
                "category_name": "string",
                "count": "integer"
            }
        ]
    }
}
```

### Add Feedback
Add a feedback/comment to a ticket.

- **URL**: `/tickets/{id}/feedback`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer <token>`
- **Request Body**:
```json
{
    "text": "string (required)"
}
```
- **Success Response**:
```json
{
    "status": "success",
    "message": "Feedback added successfully",
    "data": {
        "id": "integer",
        "ticket_id": "string",
        "created_by": "string",
        "text": "string",
        "created_by_role": "string",
        "created_at": "timestamp"
    }
}
```

### Soft Delete Ticket
Soft delete a ticket (mark as deleted but keep in database).

- **URL**: `/tickets/{id}`
- **Method**: `DELETE`
- **Headers**: `Authorization: Bearer <token>`
- **Success Response**:
```json
{
    "status": "success",
    "message": "Ticket has been soft deleted",
    "data": {
        "id": "string",
        "deleted_at": "timestamp"
    }
}
```
- **Error Response**:
```json
{
    "status": "error",
    "message": "Failed to delete ticket",
    "code": 400
}
```

### Restore Deleted Ticket
Restore a previously soft-deleted ticket.

- **URL**: `/tickets/{id}/restore`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer <token>`
- **Success Response**:
```json
{
    "status": "success",
    "message": "Ticket has been restored",
    "data": {
        "id": "string",
        "deleted_at": null
    }
}
```
- **Error Response**:
```json
{
    "status": "error",
    "message": "Failed to restore ticket",
    "code": 400
}
```

## Categories

### Get All Categories
Get list of all categories.

- **URL**: `/categories`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer <token>`
- **Success Response**:
```json
{
    "status": "success",
    "data": {
        "categories": [
            {
                "id": "integer",
                "name": "string",
                "sub_categories": [
                    {
                        "id": "integer",
                        "name": "string"
                    }
                ]
            }
        ]
    }
}
```

### Create Category
Create a new category (Admin only).

- **URL**: `/categories`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer <token>`
- **Request Body**:
```json
{
    "name": "string (required)"
}
```
- **Success Response**:
```json
{
    "status": "success",
    "message": "Category created successfully",
    "data": {
        "id": "integer",
        "name": "string"
    }
}
```

### Create Sub-Category
Create a new sub-category (Admin only).

- **URL**: `/categories/{category_id}/sub-categories`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer <token>`
- **Request Body**:
```json
{
    "name": "string (required)"
}
```
- **Success Response**:
```json
{
    "status": "success",
    "message": "Sub-category created successfully",
    "data": {
        "id": "integer",
        "category_id": "integer",
        "name": "string"
    }
}
```

## User Management

### Get All Users
Get list of all users (Admin only).

- **URL**: `/users`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer <token>`
- **Query Parameters**:
  - `role`: string (optional) - Filter by role
  - `search`: string (optional) - Search by name or email
  - `page`: integer (optional) - Page number
  - `per_page`: integer (optional) - Items per page
- **Success Response**:
```json
{
    "status": "success",
    "data": {
        "users": [
            {
                "id": "string",
                "name": "string",
                "email": "string",
                "role": "string",
                "created_at": "timestamp"
            }
        ],
        "pagination": {
            "total": "integer",
            "per_page": "integer",
            "current_page": "integer",
            "last_page": "integer"
        }
    }
}
```

### Update User Role
Update user role (Admin only).

- **URL**: `/users/{id}/role`
- **Method**: `PATCH`
- **Headers**: `Authorization: Bearer <token>`
- **Request Body**:
```json
{
    "role": "string" // "student", "admin", "disposisi"
}
```
- **Success Response**:
```json
{
    "status": "success",
    "message": "User role updated successfully",
    "data": {
        "id": "string",
        "name": "string",
        "role": "string"
    }
}
```

### Get User Detail
Get detailed information of a specific user (Admin only).

- **URL**: `/users/{id}`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer <token>`
- **Success Response**:
```json
{
    "status": "success",
    "data": {
        "user": {
            "id": "string",
            "name": "string",
            "email": "string",
            "role": "string",
            "created_at": "timestamp"
        }
    }
}
```

### Update User
Update user information (Admin only).

- **URL**: `/users/{id}`
- **Method**: `PATCH`
- **Headers**: `Authorization: Bearer <token>`
- **Request Body**:
```json
{
    "name": "string (optional)",
    "email": "string (optional, must be valid Telkom University email)",
    "role": "string (optional, student|admin|disposisi)"
}
```
- **Success Response**:
```json
{
    "status": "success",
    "message": "User updated successfully",
    "data": {
        "id": "string",
        "name": "string",
        "email": "string",
        "role": "string"
    }
}
```

### Delete User
Delete a user (Admin only).

- **URL**: `/users/{id}`
- **Method**: `DELETE`
- **Headers**: `Authorization: Bearer <token>`
- **Success Response**:
```json
{
    "status": "success",
    "message": "User deleted successfully",
    "data": {
        "id": "string",
        "deleted_at": "timestamp"
    }
}
```
- **Error Response**:
```json
{
    "status": "error",
    "message": "Failed to delete user",
    "code": 400
}
```
### User Statistics
Get statistics of users (Admin only).

- **URL**: `/users/statistics`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer <token>`
- **Success Response**:
```json
{
    "status": "success",
    "data": {
        "total_users": "integer",
        "students": "integer",
        "admins": "integer",
        "disposisi": "integer"
    }
}
```

## Notifications

### Get User Notifications
Get list of notifications for the authenticated user.

- **URL**: `/notifications`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer <token>`
- **Query Parameters**:
  - `read`: boolean (optional) - Filter by read status
  - `type`: string (optional) - Filter by notification type
  - `page`: integer (optional) - Page number
  - `per_page`: integer (optional) - Items per page
- **Success Response**:
```json
{
    "status": "success",
    "data": {
        "notifications": [
            {
                "id": "string",
                "recipient_id": "string",
                "recipientRole": "string",
                "sender_id": "string",
                "ticket_id": "string",
                "title": "string",
                "message": "string",
                "type": "string",
                "read_at": "timestamp|null",
                "created_at": "timestamp"
            }
        ],
        "pagination": {
            "total": "integer",
            "per_page": "integer",
            "current_page": "integer",
            "last_page": "integer"
        }
    }
}
```

### Mark Notification as Read
Mark a notification as read.

- **URL**: `/notifications/{id}/read`
- **Method**: `PATCH`
- **Headers**: `Authorization: Bearer <token>`
- **Success Response**:
```json
{
    "status": "success",
    "message": "Notification marked as read",
    "data": {
        "id": "string",
        "read_at": "timestamp"
    }
}
```

### Mark All Notifications as Read
Mark all notifications as read.

- **URL**: `/notifications/read-all`
- **Method**: `PATCH`
- **Headers**: `Authorization: Bearer <token>`
- **Success Response**:
```json
{
    "status": "success",
    "message": "All notifications marked as read"
}
```

### Create Notification
Create a notification.

- **URL**: `/notifications`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer <token>`
- **Request Body**:
```json
{
    "recipient_id": "string (optional)",
    "recipientRole": "string (optional)",
    "ticket_id": "string (optional)",
    "title": "string (required)",
    "message": "string (required)",
    "type": "string (required)"
}
```
- **Success Response**:
```json
{
    "status": "success",
    "message": "Notification created successfully",
    "data": {
        "id": "string",
        "recipient_id": "string",
        "recipientRole": "string",
        "sender_id": "string",
        "ticket_id": "string",
        "title": "string",
        "message": "string",
        "type": "string",
        "read_at": null,
        "created_at": "timestamp"
    }
}
```

### Notification Types and Auto-generation

#### New Ticket Notification
System automatically generates when a new ticket is created.
- Recipients: All users with 'admin' role
- Type: "new_ticket"
- Title: "Tiket Baru"
- Message format: "Tiket baru telah dibuat: {ticket.judul}"

#### Assignment Notification
System automatically generates when a ticket is assigned.
- Recipients: The assigned disposisi member
- Type: "assignment"
- Title: "Tiket Didisposisikan"
- Message format: "Tiket telah didisposisikan kepada Anda: {ticket.judul}"

#### Status Change Notification
System automatically generates when ticket status changes.
- Recipients: Based on context:
  - If changed by admin/disposisi: Notifies student (ticket creator)
  - If changed by disposisi: Notifies admin
  - If changed by admin: Notifies assigned disposisi
- Type: "status_change"
- Title: "Status Tiket Diperbarui"
- Message format: "Status tiket telah diperbarui dari {oldStatus} menjadi {newStatus}"

#### Feedback Notification
System automatically generates when new feedback is added.
- Recipients: Based on sender:
  - If from admin/disposisi: Notifies student
  - If from student: Notifies admin and assigned disposisi
- Type: "feedback"
- Title: "Feedback Baru"
- Message format: "Feedback baru untuk tiket: {ticket.judul}"

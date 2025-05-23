# Helpdesk API Postman Collection

This repository contains a Postman collection and environment for testing the Helpdesk API.

## Contents

- `Helpdesk_API.postman_collection.json` - The Postman collection file containing all API endpoints
- `Helpdesk_API.postman_environment.json` - Environment variables for the collection

## How to Use

1. Install [Postman](https://www.postman.com/downloads/) if you haven't already
2. Import the collection and environment files into Postman:
   - Click on "Import" in the top left
   - Select both JSON files
   - Click "Import"
3. Select the "Helpdesk API Environment" from the environment dropdown in the top right
4. Set the `base_url` value in the environment if different from the default (`http://localhost:8000`)

## Authentication Flow

1. First, use the "Register" or "Login" endpoints to obtain an authentication token
2. The token is automatically saved to the environment variables when using these endpoints
3. All other protected endpoints will use this token automatically

## Available Endpoints

The collection includes the following endpoint groups:

1. **Authentication** - Register, login, logout, and get user profile
2. **Dashboard** - User, admin, and staff dashboards
3. **Tickets** - Create, read, update, delete, and manage tickets
4. **Ticket History** - View ticket history and add comments
5. **Ticket Feedback** - Submit and view feedback for resolved tickets
6. **Categories** - View categories and subcategories
7. **Notifications** - View and manage user notifications

## Notes

- The collection assumes you're using Laravel Sanctum for authentication
- Protected endpoints require authentication via Bearer token
- The environment variables include:
  - `base_url`: The base URL of your API (default: http://localhost:8000)
  - `auth_token`: The authentication token (set automatically after login)

## Development

This collection is based on the Helpdesk API project structure and models. If the API endpoints change, you should update the collection to match.

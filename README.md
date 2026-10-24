# Enterprise Employee Management System (EMS) — Backend API

A REST API for an Employee Management System. The project demonstrates Laravel application structure, role-based access control, relational data modeling, audit logging, and server-side data handling.

## 🛠️ Tech Stack & Dependencies

*   **Backend:** PHP 8.3+, Laravel 12, Laravel Sanctum (personal access token authentication)
*   **Database:** SQLite by default

🌐 **Frontend SPA Repository:** https://github.com/balazs78/hrsystem-frontend-angular

📖 **[API Reference](API.md)**

---

## 🛠️ Architecture & Key Features

*   **API Authentication:** Powered by **Laravel Sanctum** personal access tokens sent as bearer tokens; API authentication is stateless and does not require cookies or CSRF tokens.
*   **Role-Based Access Control (RBAC):** Driven by custom Laravel **Policies** and authenticated API routes across four application roles.
*   **Audit Logging:** Implemented with an **Eloquent Observer** that records employee creation, updates, and deletion events for review through the audit-log endpoint.
*   **Performant Server-Side Data Handling:** Optimized the core Employee directory with native Eloquent querying to handle full **Server-Side Pagination, Filtering, and Searching**.
*   **Decoupled Request Lifecycle:** Uses **Form Requests** for validation and request normalization, services for application operations, and **API Resources** for response transformation.
*   **Relational Database Seeding:** Factories and seeders generate realistic organizational data for local development.

---

## 👥 Role-Based Access Control (RBAC) Matrix

The backend API enforces strict server-side validation based on the user's authenticated policy:

| Resource / Endpoint | Admin | HR Manager | Manager | Auditor |
| :--- | :---: | :---: | :---: | :---: |
| **Dashboard Analytics** | Read | Read | Read | Read |
| **Departments & Positions** | Read / Write | Read / Write | Read Only | Read Only |
| **Employees Directory** | Read / Write | Read / Write | Read all employees | Read Only |
| **Employee Details** | Read / Write | Read / Write | **Direct subordinates only** | Read Only |
| **User Management** | Read / Write | Unauthorized | Unauthorized | User list only |
| **Audit Logs** | Read Only | Unauthorized | Unauthorized | Read Only |

---

## 💻 Local Installation & Setup

1. Clone this repository and navigate into the project directory:
   ```bash
   git clone https://github.com/balazs78/hrsystem-backend.git
   cd hrsystem-backend
   ```
2. Install the PHP dependencies via Composer:
   ```bash
   composer install
   ```
3. Create your environment file from the template:
   ```bash
   cp .env.example .env
   ```
4. The project uses SQLite by default. Ensure `DB_CONNECTION=sqlite` is set in `.env` and that the configured SQLite database file exists.
5. Generate the application encryption key:
   ```bash
   php artisan key:generate
   ```
6. Run the database migrations and seed the demo data:
   ```bash
   php artisan migrate --seed
   ```
7. Fire up the local development server:
   ```bash
   php artisan serve
   ```
   The API will be available at `http://localhost:8000`. The seeded demo users are listed below.

| E-mail | Role |
| :--- | :---: |
| admin@example.com | Administrator | 
| manager@example.com | Manager | 
| hrmanager@example.com | HR Manager | 
| auditor@example.com | Auditor | 

   The initial password for each seeded user is `password`.

## 🔐 API Authentication

Sign in by sending a JSON `POST` request to `http://localhost:8000/api/login` with `email`, `password`, and an optional `device_name`. The response contains a personal access `token`. Include it on protected API requests as `Authorization: Bearer <token>`, then call `POST /api/logout` with the same header to revoke it. Password resets and password changes revoke existing tokens.

Set `FRONTEND_URL` to the frontend origin to configure CORS. Do not send session cookies or request Sanctum's CSRF-cookie endpoint for API authentication. See the [API reference](API.md) for request examples and all authentication routes.
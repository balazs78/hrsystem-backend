# HR System API Reference

This document describes the HTTP API implemented by this Laravel application. The default local API base URL is `http://localhost:8000/api`.

## Authentication

The API uses Laravel Sanctum personal access tokens. Obtain a token by posting credentials to `/api/login`, then send it with every protected API request in the `Authorization: Bearer <token>` header. API authentication is stateless: cookies, session credentials, and CSRF-cookie requests are not used. Configure the allowed frontend origin with `FRONTEND_URL`.

Typical sign-in flow:

1. Send `POST http://localhost:8000/api/login` with `email`, `password`, and an optional `device_name`.
2. Keep the returned `token` private and send it as a bearer token on subsequent requests.
3. Send `POST http://localhost:8000/api/logout` with that bearer token to revoke it.

Login returns `200 OK` with a `token`, `token_type`, and authenticated `user`; logout returns `204 No Content`. Password reset revokes the user's existing tokens, requiring the user to sign in again. Force password update revokes existing tokens and returns a fresh `token` and `token_type`.

Example browser requests:

```js
const loginResponse = await fetch('http://localhost:8000/api/login', {
  method: 'POST',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'admin@example.com',
    password: 'password',
    device_name: 'angular-browser',
  }),
});

const { token } = await loginResponse.json();

const response = await fetch('http://localhost:8000/api/employees', {
  headers: {
    Accept: 'application/json',
    Authorization: `Bearer ${token}`,
  },
});
```

For JSON requests, send `Accept: application/json`. Employee image uploads must use `multipart/form-data` and include the bearer token. Do not enable credentialed CORS or store access tokens in source code; use HTTPS in production and choose frontend token storage with XSS exposure in mind.

## Roles

All protected `/api/*` routes require a valid Sanctum bearer token unless stated otherwise. Role checks are enforced by policies for the operations below.

| Operation | Admin | HR Manager | Manager | Auditor |
| --- | --- | --- | --- | --- |
| Employee directory and search | Read | Read | Read all employees | Read |
| Employee details | Read | Read | Direct reports only | Read |
| Create/update/delete employees | Yes | Yes | No | No |
| Restore employees | Yes | No | No | No |
| Departments and positions | Read/write | Read/write | Read | Read |
| User list | Yes | No | No | Yes |
| Create/update/delete users | Yes | No | No | No |
| Audit logs | Read | No | No | Read |
| Dashboard reports | Read | Read | Read | Read |

## Common Responses

Laravel resource endpoints wrap single resources in `data`. Collections use `data` and, when paginated, Laravel pagination `links` and `meta`. Validation failures return `422 Unprocessable Content` with a `message` and field-keyed `errors` object. Policy denials return `403 Forbidden`; unauthenticated API requests return `401 Unauthorized`; missing records return `404 Not Found`.

## Authentication Routes

| Method | Path | Authentication | Description |
| --- | --- | --- | --- |
| `POST` | `/api/login` | Public | Sign in with `email`, `password`, and optional `device_name`. Returns a bearer token and user. |
| `POST` | `/api/logout` | Bearer token | Revoke the current token. Returns `204`. |
| `POST` | `/api/register` | Bearer token; Admin policy required | Create a user; same behavior as `POST /api/users`. |
| `POST` | `/api/forgot-password` | Public | Request a reset link with `email`. Returns a JSON `status`. |
| `POST` | `/api/reset-password` | Public | Reset with `token`, `email`, `password`, and `password_confirmation`; revokes existing tokens. |
| `POST` | `/api/force-password-update` | Bearer token | Change the current password using `current_password`, `password`, and `password_confirmation`; revokes existing tokens. |
| `GET` | `/verify-email/{id}/{hash}` | Signed URL | Verify the email address associated with the signed URL. |
| `POST` | `/api/email/verification-notification` | Bearer token | Resend the verification email. |

## Employees

### Routes

| Method | Path | Description |
| --- | --- | --- |
| `GET` | `/api/employees` | Paginated, filterable directory. |
| `POST` | `/api/employees` | Create an employee. Admin and HR Manager. |
| `GET` | `/api/employees/{employee}` | Employee details. Managers can view direct reports only. |
| `PUT`, `PATCH` | `/api/employees/{employee}` | Update an employee. Admin and HR Manager. All core employee fields remain required by validation, including for `PATCH`. |
| `DELETE` | `/api/employees/{employee}` | Soft-delete an employee. Admin and HR Manager. Returns `204`. |
| `PUT` | `/api/employees/{id}/restore` | Restore a soft-deleted employee. Admin only. |
| `GET` | `/api/employees/search` | Search employee names for selectors. |
| `GET` | `/api/employees/managers` | List active employees who have direct reports. |

### Directory query parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `search` | string, max 50 | Match first name, last name, or email. |
| `page` | integer, minimum 0 | Page number; defaults to Laravel's first page. |
| `pageSize` | integer, 1–1000 | Results per page; defaults to 10. |
| `sort` | `first_name` or `status` | Sort field. |
| `direction` | `asc` or `desc` | Sort direction; defaults to `asc` when `sort` is provided. |
| `department[]` | department IDs | Filter to one or more departments. |
| `position[]` | position IDs | Filter to one or more positions. |
| `manager[]` | employee IDs | Filter by one or more managers. |
| `status[]` | status values | `active`, `on_leave`, or `terminated`. |

Example:

```http
GET /api/employees?search=ada&department[]=2&status[]=active&page=1&pageSize=20&sort=first_name&direction=asc
```

The list resource includes `id`, `fullName`, `profileThumbnail`, capitalized `status`, `department`, `position`, `manager`, and `user_id`. The response includes Laravel pagination data and an additional `meta.total_users` field representing the number of results on the current page.

### Create and update fields

Employee create and update accept these camelCase fields. Create requires all non-nullable fields; update currently also requires the core fields listed as required.

| Field | Rules |
| --- | --- |
| `firstName`, `lastName` | Required strings, maximum 50 characters. |
| `gender` | Required; `male` or `female`. |
| `email` | Valid email; unique on create. Update permits null and checks uniqueness. |
| `phone` | Required string, maximum 20 characters. |
| `address` | Required string, maximum 100 characters. |
| `birthdate`, `hiredAt` | Required dates. |
| `departmentId`, `positionId` | Required existing department and position IDs. |
| `managerId` | Optional existing employee ID. |
| `status` | Required; `active`, `on_leave`, or `terminated`. |
| `profileImage` | Optional JPG/JPEG/PNG image, maximum 2048 KB. |
| `contractEndDate` | Optional date. |

Example JSON request (omit `profileImage` for JSON; send it as a file in multipart form data):

```json
{
  "firstName": "Ada",
  "lastName": "Lovelace",
  "gender": "female",
  "email": "ada@example.com",
  "phone": "+1-555-0100",
  "address": "London",
  "birthdate": "1815-12-10",
  "hiredAt": "2024-03-01",
  "departmentId": 1,
  "positionId": 2,
  "managerId": null,
  "status": "active",
  "contractEndDate": null
}
```

The detail resource contains `id`, `fullName`, `firstName`, `lastName`, `gender`, `birthdate`, `phone`, `address`, `email`, `profileImage`, `status`, `hiredAt`, `contractEndDate`, `department`, `position`, `manager`, and `updatedAt`. Audit `history` is not part of this response.

`GET /api/employees/search` accepts optional `query` (prefix match on first or last name). By default it returns active employees only; include the `allstatuses` query key to disable that status filter. Each result has `id`, `firstName`, `lastName`, and `fullName`.

`GET /api/employees/managers` returns an array of `{ id, firstName, lastName, fullName }` objects.

## Departments

| Method | Path | Access |
| --- | --- | --- |
| `GET` | `/api/departments` | All authenticated roles |
| `POST` | `/api/departments` | Admin, HR Manager |
| `GET` | `/api/departments/{department}` | All authenticated roles |
| `PUT`, `PATCH` | `/api/departments/{department}` | Admin, HR Manager |
| `DELETE` | `/api/departments/{department}` | Admin, HR Manager |

Create fields: `name` (required string, max 255), `costCenterCode` (required, unique, format `AA-0000`), and optional `managerId` (existing employee ID).

Update fields are optional: `name`, `managerId`, and `costCenterCode`. A department with employees cannot be deleted; this returns `422` with a message asking the client to reassign them.

Department responses include `id`, `name`, `costCenterCode`, `stats.headcount` when the count is loaded, `manager` (nullable, with `id` and `fullName`), and `createdAt`.

## Positions

| Method | Path | Access |
| --- | --- | --- |
| `GET` | `/api/positions` | All authenticated roles |
| `POST` | `/api/positions` | Admin, HR Manager |
| `GET` | `/api/positions/{position}` | All authenticated roles |
| `PUT`, `PATCH` | `/api/positions/{position}` | Admin, HR Manager |
| `DELETE` | `/api/positions/{position}` | Admin, HR Manager |

Create fields: `title` (required, unique string, max 255), `salaryGrade` (required string, max 10), and optional `description`.

Update accepts optional `title`, `salaryGrade`, and `description`. A position assigned to employees cannot be deleted; this returns `409 Conflict`.

Position responses include `id`, `title`, `salaryGrade`, `description`, `createdAt`, and `headcount` when the employee count is loaded (the list includes it; the detail response may omit it).

## Users

| Method | Path | Access | Description |
| --- | --- | --- | --- |
| `GET` | `/api/users` | Admin, Auditor | List users. Auditor access is read-only. |
| `POST` | `/api/users` | Admin | Create a user. Returns `204`. |
| `PUT`, `PATCH` | `/api/users/{user}` | Admin | Update a user. Returns `204`. |
| `DELETE` | `/api/users/{user}` | Admin | Soft-delete a user. Returns `204`. |

Create fields: `name`, `email`, `password`, `password_confirmation`, `role`, and optional `employee_id`. Roles are `Admin`, `HR Manager`, `Manager`, and `Auditor`.

Update accepts optional `name`, `email`, `role`, and `employee_id`.

The user list currently returns serialized User models directly rather than a resource collection. `GET /api/users/{user}` is registered by Laravel's resource routing but there is no `show()` controller method; treat this endpoint as unsupported.

## Reports

All report endpoints require authentication and are readable by each role.

| Method | Path | Response |
| --- | --- | --- |
| `GET` | `/api/reports/summary` | Dashboard summary object. |
| `GET` | `/api/reports/yearly-trend` | Array of `{ month_label, total_headcount }` rows for the latest 12 months. |
| `GET` | `/api/reports/department-headcounts` | Array of `{ name, count }` objects. |

Summary response shape:

```json
{
  "summary": [
    { "title": "Total Employees", "value": 42 },
    { "title": "Contractors", "value": 4 },
    { "title": "Active Positions", "value": 12 },
    { "title": "Departments", "value": 5 }
  ],
  "onboardingEmployees": [{ "fullName": "Ada Lovelace" }],
  "expiringContracts": [
    { "fullName": "Ada Lovelace", "contractExpireDate": "2026-11-30" }
  ],
  "employeeTypeChartData": { "contractorCount": 4, "employeeCount": 38 },
  "managerVsEmployeeChartData": {
    "managerCount": 5,
    "individualContributorCount": 37
  }
}
```

Counts above are illustrative. Reports return raw JSON rather than a `data` resource envelope.

## Audit Logs

`GET /api/audit-logs` is available to Admin and Auditor roles only. It returns a paginated resource collection with each item containing `id`, `action`, `old_value`, `new_value`, `created_at`, `user`, and `employee`.

Supported query parameters:

| Parameter | Type | Description |
| --- | --- | --- |
| `page` | integer, minimum 0 | Page number. |
| `pageSize` | integer, 1–1000 | Results per page; defaults to 10. |
| `userId` | existing user ID | Filter by audit actor. |
| `employeeId` | existing employee ID | Filter by affected employee. |
| `period` | `today`, `yesterday`, `last_7_days`, `last_30_days` | Predefined date range. When valid, this takes precedence over custom dates. |
| `startDate`, `endDate` | dates | Inclusive custom date bounds, used when no valid `period` is selected. |
| `sort` | `asc` or `desc` | Created-at sort direction; defaults to newest first. |

## Notes

- API routes are defined in `routes/api.php`; authentication and password routes are defined in `routes/auth.php`.
- Resource creation typically returns `201 Created`; successful updates return `200 OK`; deletes and session operations return `204 No Content`.
- Demo seed credentials are listed in the project README. Do not use those credentials outside local development.

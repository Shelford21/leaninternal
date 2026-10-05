# API Specification

## LEAN ENTERPRISE (LIMS)

> **Version:** 1.0  
> **Date:** 2026-09-04  
> **Base URL:** `http://localhost:8000/api` (dev)  
> **Authentication:** Laravel Sanctum Bearer Token  

---

## Table of Contents

1. [API Overview](#1-api-overview)
2. [Authentication](#2-authentication)
3. [Request & Response Format](#3-request--response-format)
4. [Error Handling](#4-error-handling)
5. [Pagination, Filtering & Sorting](#5-pagination-filtering--sorting)
6. [Rate Limiting](#6-rate-limiting)
7. [Endpoints: Authentication](#7-endpoints-authentication)
8. [Endpoints: Users](#8-endpoints-users)
9. [Endpoints: Factories](#9-endpoints-factories)
10. [Endpoints: Departments](#10-endpoints-departments)
11. [Endpoints: Production Lines](#11-endpoints-production-lines)
12. [Endpoints: Articles](#12-endpoints-articles)
13. [Endpoints: Operators](#13-endpoints-operators)
14. [Endpoints: Processes](#14-endpoints-processes)
15. [Endpoints: Process Versions](#15-endpoints-process-versions)
16. [Endpoints: GSD Categories](#16-endpoints-gsd-categories)
17. [Endpoints: GSD Elements](#17-endpoints-gsd-elements)
18. [Endpoints: MTM Elements](#18-endpoints-mtm-elements)
19. [Endpoints: Sewing Factors](#19-endpoints-sewing-factors)
20. [Endpoints: Sewing Stop Factors](#20-endpoints-sewing-stop-factors)
21. [Endpoints: PTMS Reports](#21-endpoints-ptms-reports)
22. [Endpoints: Dashboard](#22-endpoints-dashboard)
23. [Role-Based Access Matrix](#23-role-based-access-matrix)

---

## 1. API Overview

| Property | Value |
|----------|-------|
| **Protocol** | HTTPS (production), HTTP (development) |
| **Base URL** | `/api` |
| **Format** | JSON |
| **Charset** | UTF-8 |
| **Auth** | Bearer Token (Laravel Sanctum) |
| **Content-Type** | `application/json` |
| **Accept** | `application/json` |

### Standard Headers

```
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

---

## 2. Authentication

### Flow

```
1. POST /api/login → Returns { token, user }
2. Store token in client
3. Include token in all subsequent requests: Authorization: Bearer <token>
4. POST /api/logout → Invalidates token
```

### Token Details

| Property | Value |
|----------|-------|
| **Type** | Personal Access Token (Sanctum) |
| **Length** | 40+ characters |
| **Storage** | Client-side (localStorage via Zustand) |
| **Expiry** | Configurable (default: no expiry, recommended: 24h) |
| **Revocation** | On logout or password change |

---

## 3. Request & Response Format

### Success Response Envelope

```json
{
  "data": { ... },                    // Single resource
  "message": "Operation successful"   // Optional message
}
```

### List Response Envelope

```json
{
  "data": [ ... ],                    // Array of resources
  "links": {
    "first": "http://...",
    "last": "http://...",
    "prev": null,
    "next": "http://..."
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 72
  }
}
```

### Error Response Envelope

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Validation error message."]
  }
}
```

---

## 4. Error Handling

| HTTP Code | Meaning | When |
|-----------|---------|------|
| `200` | OK | Successful GET, PUT, PATCH |
| `201` | Created | Successful POST (resource created) |
| `204` | No Content | Successful DELETE |
| `400` | Bad Request | Malformed request |
| `401` | Unauthorized | Missing or invalid token |
| `403` | Forbidden | Valid token but insufficient role |
| `404` | Not Found | Resource doesn't exist |
| `422` | Unprocessable Entity | Validation failed |
| `429` | Too Many Requests | Rate limit exceeded |
| `500` | Server Error | Unexpected server error |

### Error Response Examples

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden:**
```json
{
  "message": "Unauthorized. You do not have the required role."
}
```

**404 Not Found:**
```json
{
  "message": "No query results for model [App\\Models\\Factory] 99"
}
```

**422 Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "factory_name": ["The factory name field is required."],
    "factory_code": ["The factory code has already been taken."]
  }
}
```

---

## 5. Pagination, Filtering & Sorting

### Pagination

All list endpoints support pagination via query parameters:

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 15 | Items per page (max: 100) |

**Example:**
```
GET /api/factories?page=2&per_page=20
```

### Filtering

Filter using query parameters matching field names:

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Full-text search across searchable fields |
| `field_name` | string | Exact match filter on any field |
| `factory_id` | integer | Filter by factory (for departments, lines) |
| `department_id` | integer | Filter by department (for lines, operators) |
| `status` | string | Filter by status |

**Example:**
```
GET /api/operators?department_id=3&search=john
```

### Sorting

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `sort` | string | `created_at` | Field to sort by |
| `order` | string | `desc` | Sort direction (`asc` or `desc`) |

**Example:**
```
GET /api/articles?sort=article_name&order=asc
```

### Including Relations

Use `with` parameter to eager-load relationships:

```
GET /api/departments?with=factory,productionLines
```

---

## 6. Rate Limiting

| Endpoint Type | Limit | Window |
|--------------|-------|--------|
| General API | 60 requests | 1 minute |
| Login | 5 attempts | 1 minute |

Rate limit headers are included in every response:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
Retry-After: 60          (only on 429 response)
```

---

## 7. Endpoints: Authentication

### POST /api/login

Authenticate a user and return a token.

**Auth Required:** No

**Request Body:**
```json
{
  "username": "admin",
  "password": "password"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `username` | string | Yes | Username (not email) |
| `password` | string | Yes | User password |

**Success Response (200):**
```json
{
  "data": {
    "token": "1|abc123def456...",
    "user": {
      "user_id": 1,
      "name": "Admin User",
      "username": "admin",
      "employee_number": "EMP001",
      "role": {
        "role_id": 1,
        "role_name": "developer"
      }
    }
  },
  "message": "Login successful"
}
```

**Error Response (401):**
```json
{
  "message": "Invalid credentials."
}
```

---

### POST /api/logout

Invalidate the current token.

**Auth Required:** Yes

**Success Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

---

### GET /api/me

Get the authenticated user's profile.

**Auth Required:** Yes

**Success Response (200):**
```json
{
  "data": {
    "user_id": 1,
    "name": "Admin User",
    "username": "admin",
    "employee_number": "EMP001",
    "description": "System administrator",
    "role": {
      "role_id": 1,
      "role_name": "developer",
      "description": "Full system access"
    },
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-01-01T00:00:00.000000Z"
  }
}
```

---

### PUT /api/me/password

Change the authenticated user's password.

**Auth Required:** Yes

**Request Body:**
```json
{
  "current_password": "oldpassword",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

**Success Response (200):**
```json
{
  "message": "Password updated successfully"
}
```

---

## 8. Endpoints: Users

### GET /api/users

List all users with pagination.

**Auth Required:** Yes (developer, admin)

| Query Param | Type | Description |
|-------------|------|-------------|
| `search` | string | Search by name, username, employee_number |
| `role_id` | integer | Filter by role |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |

**Success Response (200):**
```json
{
  "data": [
    {
      "user_id": 1,
      "name": "Admin User",
      "username": "admin",
      "employee_number": "EMP001",
      "description": "System administrator",
      "role": {
        "role_id": 1,
        "role_name": "developer"
      },
      "created_at": "2026-01-01T00:00:00.000000Z"
    }
  ],
  "meta": { "current_page": 1, ... }
}
```

---

### POST /api/users

Create a new user.

**Auth Required:** Yes (developer, admin)

**Request Body:**
```json
{
  "name": "John Doe",
  "username": "johndoe",
  "employee_number": "EMP002",
  "password": "securepass",
  "password_confirmation": "securepass",
  "role_id": 3,
  "description": "IE Engineer"
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `name` | string | Yes | max:255 |
| `username` | string | Yes | unique:users, max:255 |
| `employee_number` | string | Yes | unique:users, max:50 |
| `password` | string | Yes | min:8, confirmed |
| `role_id` | integer | Yes | exists:roles,role_id |
| `description` | string | No | max:500 |

**Success Response (201):**
```json
{
  "data": { "user_id": 5, "name": "John Doe", ... },
  "message": "User created successfully"
}
```

---

### GET /api/users/{id}

Get a single user by ID.

**Auth Required:** Yes (developer, admin)

**Success Response (200):**
```json
{
  "data": {
    "user_id": 1,
    "name": "Admin User",
    "username": "admin",
    "employee_number": "EMP001",
    "description": "System administrator",
    "role": { "role_id": 1, "role_name": "developer" },
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-01-01T00:00:00.000000Z"
  }
}
```

---

### PUT /api/users/{id}

Update a user.

**Auth Required:** Yes (developer, admin)

**Request Body:** (all fields optional)
```json
{
  "name": "John Doe Updated",
  "employee_number": "EMP002",
  "description": "Senior IE Engineer",
  "role_id": 2,
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

**Success Response (200):**
```json
{
  "data": { "user_id": 5, "name": "John Doe Updated", ... },
  "message": "User updated successfully"
}
```

---

### DELETE /api/users/{id}

Delete a user. Cannot delete self.

**Auth Required:** Yes (developer, admin)

**Success Response (200):**
```json
{
  "message": "User deleted successfully"
}
```

**Error Response (400):**
```json
{
  "message": "Cannot delete your own account."
}
```

---

## 9. Endpoints: Factories

### GET /api/factories

List all factories.

**Auth Required:** Yes (all authenticated)

| Query Param | Type | Description |
|-------------|------|-------------|
| `search` | string | Search by factory_name, factory_code |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |
| `with` | string | Eager load: `departments` |

**Success Response (200):**
```json
{
  "data": [
    {
      "factory_id": 1,
      "factory_name": "Main Factory",
      "factory_code": "F001",
      "location": "Building A",
      "departments_count": 5,
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ],
  "meta": { "current_page": 1, ... }
}
```

---

### POST /api/factories

Create a new factory.

**Auth Required:** Yes (developer, admin)

**Request Body:**
```json
{
  "factory_name": "New Factory",
  "factory_code": "F003",
  "location": "Building C"
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `factory_name` | string | Yes | max:255 |
| `factory_code` | string | Yes | unique:factories, max:50 |
| `location` | string | No | max:500 |

**Success Response (201):**
```json
{
  "data": { "factory_id": 3, "factory_name": "New Factory", ... },
  "message": "Factory created successfully"
}
```

---

### GET /api/factories/{id}

Get a single factory.

**Auth Required:** Yes (all authenticated)

**Success Response (200):**
```json
{
  "data": {
    "factory_id": 1,
    "factory_name": "Main Factory",
    "factory_code": "F001",
    "location": "Building A",
    "departments": [ ... ],
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-01-01T00:00:00.000000Z"
  }
}
```

---

### PUT /api/factories/{id}

Update a factory.

**Auth Required:** Yes (developer, admin)

**Request Body:** (all fields optional)
```json
{
  "factory_name": "Updated Factory Name",
  "location": "New Location"
}
```

**Success Response (200):**
```json
{
  "data": { ... },
  "message": "Factory updated successfully"
}
```

---

### DELETE /api/factories/{id}

Delete a factory.

**Auth Required:** Yes (developer, admin)

**Success Response (200):**
```json
{
  "message": "Factory deleted successfully"
}
```

---

## 10. Endpoints: Departments

### GET /api/departments

List all departments.

**Auth Required:** Yes (all authenticated)

| Query Param | Type | Description |
|-------------|------|-------------|
| `search` | string | Search by department_name |
| `factory_id` | integer | Filter by factory |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |
| `with` | string | Eager load: `factory`, `productionLines` |

**Success Response (200):**
```json
{
  "data": [
    {
      "department_id": 1,
      "department_name": "Sewing A",
      "factory_id": 1,
      "description": "Main sewing department",
      "factory": {
        "factory_id": 1,
        "factory_name": "Main Factory"
      },
      "production_lines_count": 8,
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ],
  "meta": { ... }
}
```

---

### POST /api/departments

Create a new department.

**Auth Required:** Yes (developer, admin)

**Request Body:**
```json
{
  "department_name": "Cutting B",
  "factory_id": 1,
  "description": "Secondary cutting department"
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `department_name` | string | Yes | max:255 |
| `factory_id` | integer | Yes | exists:factories,factory_id |
| `description` | string | No | max:500 |

**Success Response (201):**
```json
{
  "data": { "department_id": 5, ... },
  "message": "Department created successfully"
}
```

---

### GET /api/departments/{id}

**Auth Required:** Yes (all authenticated)

**Success Response (200):** Single department with relations.

---

### PUT /api/departments/{id}

**Auth Required:** Yes (developer, admin)

---

### DELETE /api/departments/{id}

**Auth Required:** Yes (developer, admin)

---

## 11. Endpoints: Production Lines

### GET /api/production-lines

List all production lines.

**Auth Required:** Yes (all authenticated)

| Query Param | Type | Description |
|-------------|------|-------------|
| `search` | string | Search by line_name, line_code |
| `department_id` | integer | Filter by department |
| `factory_id` | integer | Filter by factory (via department) |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |
| `with` | string | Eager load: `department`, `department.factory` |

**Success Response (200):**
```json
{
  "data": [
    {
      "line_id": 1,
      "line_name": "Line 1",
      "line_code": "L001",
      "department_id": 1,
      "description": "Main sewing line",
      "department": {
        "department_id": 1,
        "department_name": "Sewing A",
        "factory": { "factory_id": 1, "factory_name": "Main Factory" }
      },
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ],
  "meta": { ... }
}
```

---

### POST /api/production-lines

**Auth Required:** Yes (developer, admin)

**Request Body:**
```json
{
  "line_name": "Line 5",
  "line_code": "L005",
  "department_id": 2,
  "description": "New production line"
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `line_name` | string | Yes | max:255 |
| `line_code` | string | Yes | unique:production_lines, max:50 |
| `department_id` | integer | Yes | exists:departments,department_id |
| `description` | string | No | max:500 |

---

### GET /api/production-lines/{id}

**Auth Required:** Yes (all authenticated)

---

### PUT /api/production-lines/{id}

**Auth Required:** Yes (developer, admin)

---

### DELETE /api/production-lines/{id}

**Auth Required:** Yes (developer, admin)

---

## 12. Endpoints: Articles

### GET /api/articles

List all articles (styles).

**Auth Required:** Yes (all authenticated)

| Query Param | Type | Description |
|-------------|------|-------------|
| `search` | string | Search by article_name, article_number |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |

**Success Response (200):**
```json
{
  "data": [
    {
      "article_id": 1,
      "article_name": "Polo Shirt Basic",
      "article_number": "ART-001",
      "description": "Basic polo shirt style",
      "smv": 12.5,
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ],
  "meta": { ... }
}
```

---

### POST /api/articles

**Auth Required:** Yes (developer, admin, ie_engineer)

**Request Body:**
```json
{
  "article_name": "T-Shirt V2",
  "article_number": "ART-015",
  "description": "Updated t-shirt design",
  "smv": 10.8
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `article_name` | string | Yes | max:255 |
| `article_number` | string | Yes | unique:articles, max:50 |
| `description` | string | No | max:1000 |
| `smv` | numeric | No | min:0 |

---

### GET /api/articles/{id}

**Auth Required:** Yes (all authenticated)

---

### PUT /api/articles/{id}

**Auth Required:** Yes (developer, admin, ie_engineer)

---

### DELETE /api/articles/{id}

**Auth Required:** Yes (developer, admin)

---

## 13. Endpoints: Operators

### GET /api/operators

List all operators.

**Auth Required:** Yes (all authenticated)

| Query Param | Type | Description |
|-------------|------|-------------|
| `search` | string | Search by operator_name, operator_code |
| `department_id` | integer | Filter by department |
| `line_id` | integer | Filter by production line |
| `skill_level` | string | Filter by skill level |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |
| `with` | string | Eager load: `department`, `productionLine` |

**Success Response (200):**
```json
{
  "data": [
    {
      "operator_id": 1,
      "operator_name": "Budi Santoso",
      "operator_code": "OP-001",
      "department_id": 1,
      "line_id": 3,
      "skill_level": "advanced",
      "join_date": "2024-01-15",
      "department": { ... },
      "productionLine": { ... },
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ],
  "meta": { ... }
}
```

---

### POST /api/operators

**Auth Required:** Yes (developer, admin, supervisor)

**Request Body:**
```json
{
  "operator_name": "Siti Rahayu",
  "operator_code": "OP-050",
  "department_id": 1,
  "line_id": 3,
  "skill_level": "intermediate",
  "join_date": "2026-03-01"
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `operator_name` | string | Yes | max:255 |
| `operator_code` | string | Yes | unique:operators, max:50 |
| `department_id` | integer | Yes | exists:departments,department_id |
| `line_id` | integer | No | exists:production_lines,line_id |
| `skill_level` | string | No | in:beginner,intermediate,advanced,expert |
| `join_date` | date | No | date format: Y-m-d |

---

### GET /api/operators/{id}

**Auth Required:** Yes (all authenticated)

---

### PUT /api/operators/{id}

**Auth Required:** Yes (developer, admin, supervisor)

---

### DELETE /api/operators/{id}

**Auth Required:** Yes (developer, admin)

---

## 14. Endpoints: Processes

### GET /api/processes

List all processes (operations).

**Auth Required:** Yes (all authenticated)

| Query Param | Type | Description |
|-------------|------|-------------|
| `search` | string | Search by process_name, process_code |
| `gsd_category_id` | integer | Filter by GSD category |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |
| `with` | string | Eager load: `gsdCategory`, `latestVersion` |

**Success Response (200):**
```json
{
  "data": [
    {
      "process_id": 1,
      "process_name": "Collar Attach",
      "process_code": "PRC-001",
      "description": "Attach collar to body",
      "gsd_category_id": 1,
      "default_smv": 0.45,
      "gsd_category": { "gsd_category_id": 1, "category_name": "Assembly" },
      "latest_version": { ... },
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ],
  "meta": { ... }
}
```

---

### POST /api/processes

**Auth Required:** Yes (developer, admin, ie_engineer)

**Request Body:**
```json
{
  "process_name": "Sleeve Hem",
  "process_code": "PRC-025",
  "description": "Hem the sleeve edge",
  "gsd_category_id": 2,
  "default_smv": 0.32
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `process_name` | string | Yes | max:255 |
| `process_code` | string | Yes | unique:processes, max:50 |
| `description` | string | No | max:1000 |
| `gsd_category_id` | integer | No | exists:gsd_categories,gsd_category_id |
| `default_smv` | numeric | No | min:0 |

---

### GET /api/processes/{id}

**Auth Required:** Yes (all authenticated)

---

### PUT /api/processes/{id}

**Auth Required:** Yes (developer, admin, ie_engineer)

---

### DELETE /api/processes/{id}

**Auth Required:** Yes (developer, admin)

---

## 15. Endpoints: Process Versions

### GET /api/process-versions

List all process versions.

**Auth Required:** Yes (all authenticated)

| Query Param | Type | Description |
|-------------|------|-------------|
| `process_id` | integer | Filter by process |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |
| `with` | string | Eager load: `process`, `mtmElement` |

**Success Response (200):**
```json
{
  "data": [
    {
      "version_id": 1,
      "process_id": 1,
      "version_number": 1,
      "smv": 0.45,
      "allowed_time": 0.48,
      "mtm_element_id": 5,
      "notes": "Standard method",
      "is_active": true,
      "process": { ... },
      "mtmElement": { ... },
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ],
  "meta": { ... }
}
```

---

### POST /api/process-versions

**Auth Required:** Yes (developer, admin, ie_engineer)

**Request Body:**
```json
{
  "process_id": 1,
  "smv": 0.42,
  "allowed_time": 0.45,
  "mtm_element_id": 8,
  "notes": "Optimized method v2"
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `process_id` | integer | Yes | exists:processes,process_id |
| `smv` | numeric | Yes | min:0 |
| `allowed_time` | numeric | No | min:0 |
| `mtm_element_id` | integer | No | exists:mtm_elements,mtm_element_id |
| `notes` | string | No | max:1000 |

> `version_number` is auto-incremented per process.

---

### GET /api/process-versions/{id}

**Auth Required:** Yes (all authenticated)

---

### PUT /api/process-versions/{id}

**Auth Required:** Yes (developer, admin, ie_engineer)

---

### DELETE /api/process-versions/{id}

**Auth Required:** Yes (developer, admin)

---

## 16. Endpoints: GSD Categories

### GET /api/gsd-categories

List all GSD categories.

**Auth Required:** Yes (all authenticated)

| Query Param | Type | Description |
|-------------|------|-------------|
| `search` | string | Search by category_name |

**Success Response (200):**
```json
{
  "data": [
    {
      "gsd_category_id": 1,
      "category_name": "Assembly",
      "description": "Assembly operations",
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ],
  "meta": { ... }
}
```

---

### POST /api/gsd-categories

**Auth Required:** Yes (developer, admin, ie_engineer)

**Request Body:**
```json
{
  "category_name": "Finishing",
  "description": "Finishing operations"
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `category_name` | string | Yes | max:255 |
| `description` | string | No | max:500 |

---

### GET /api/gsd-categories/{id}

**Auth Required:** Yes (all authenticated)

---

### PUT /api/gsd-categories/{id}

**Auth Required:** Yes (developer, admin, ie_engineer)

---

### DELETE /api/gsd-categories/{id}

**Auth Required:** Yes (developer, admin)

---

## 17. Endpoints: GSD Elements

### GET /api/gsd-elements

List all GSD elements.

**Auth Required:** Yes (all authenticated)

| Query Param | Type | Description |
|-------------|------|-------------|
| `search` | string | Search by element_name, element_code |
| `gsd_category_id` | integer | Filter by GSD category |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |

**Success Response (200):**
```json
{
  "data": [
    {
      "gsd_element_id": 1,
      "element_name": "Pick Up",
      "element_code": "GSD-001",
      "gsd_category_id": 1,
      "default_time": 0.10,
      "description": "Pick up fabric/component",
      "gsd_category": { ... },
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ],
  "meta": { ... }
}
```

---

### POST /api/gsd-elements

**Auth Required:** Yes (developer, admin, ie_engineer)

**Request Body:**
```json
{
  "element_name": "Trim",
  "element_code": "GSD-015",
  "gsd_category_id": 2,
  "default_time": 0.08,
  "description": "Trim excess thread"
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `element_name` | string | Yes | max:255 |
| `element_code` | string | Yes | unique:gsd_elements, max:50 |
| `gsd_category_id` | integer | Yes | exists:gsd_categories,gsd_category_id |
| `default_time` | numeric | No | min:0 |
| `description` | string | No | max:500 |

---

### GET /api/gsd-elements/{id}

**Auth Required:** Yes (all authenticated)

---

### PUT /api/gsd-elements/{id}

**Auth Required:** Yes (developer, admin, ie_engineer)

---

### DELETE /api/gsd-elements/{id}

**Auth Required:** Yes (developer, admin)

---

## 18. Endpoints: MTM Elements

### GET /api/mtm-elements

List all MTM elements.

**Auth Required:** Yes (all authenticated)

| Query Param | Type | Description |
|-------------|------|-------------|
| `search` | string | Search by element_name, element_code |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |

**Success Response (200):**
```json
{
  "data": [
    {
      "mtm_element_id": 1,
      "element_name": "Reach",
      "element_code": "MTM-001",
      "tmu_value": 2.0,
      "description": "Reach to object",
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ],
  "meta": { ... }
}
```

---

### POST /api/mtm-elements

**Auth Required:** Yes (developer, admin, ie_engineer)

**Request Body:**
```json
{
  "element_name": "Grasp",
  "element_code": "MTM-010",
  "tmu_value": 3.5,
  "description": "Grasp object"
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `element_name` | string | Yes | max:255 |
| `element_code` | string | Yes | unique:mtm_elements, max:50 |
| `tmu_value` | numeric | Yes | min:0 |
| `description` | string | No | max:500 |

---

### GET /api/mtm-elements/{id}

**Auth Required:** Yes (all authenticated)

---

### PUT /api/mtm-elements/{id}

**Auth Required:** Yes (developer, admin, ie_engineer)

---

### DELETE /api/mtm-elements/{id}

**Auth Required:** Yes (developer, admin)

---

## 19. Endpoints: Sewing Factors

### GET /api/sewing-factors

List all sewing factors.

**Auth Required:** Yes (all authenticated)

| Query Param | Type | Description |
|-------------|------|-------------|
| `search` | string | Search by factor_name |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |

**Success Response (200):**
```json
{
  "data": [
    {
      "sewing_factor_id": 1,
      "factor_name": "Fabric Type - Cotton",
      "description": "Cotton fabric sewing factor",
      "percentage": 5.0,
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ],
  "meta": { ... }
}
```

---

### POST /api/sewing-factors

**Auth Required:** Yes (developer, admin, ie_engineer)

**Request Body:**
```json
{
  "factor_name": "Fabric Type - Silk",
  "description": "Silk fabric sewing factor",
  "percentage": 12.0
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `factor_name` | string | Yes | max:255 |
| `description` | string | No | max:500 |
| `percentage` | numeric | Yes | min:0, max:100 |

---

### GET /api/sewing-factors/{id}

**Auth Required:** Yes (all authenticated)

---

### PUT /api/sewing-factors/{id}

**Auth Required:** Yes (developer, admin, ie_engineer)

---

### DELETE /api/sewing-factors/{id}

**Auth Required:** Yes (developer, admin)

---

## 20. Endpoints: Sewing Stop Factors

### GET /api/sewing-stop-factors

List all sewing stop factors.

**Auth Required:** Yes (all authenticated)

| Query Param | Type | Description |
|-------------|------|-------------|
| `search` | string | Search by factor_name |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |

**Success Response (200):**
```json
{
  "data": [
    {
      "stop_factor_id": 1,
      "factor_name": "Thread Break",
      "description": "Thread breakage during sewing",
      "frequency": "common",
      "impact_percentage": 3.5,
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ],
  "meta": { ... }
}
```

---

### POST /api/sewing-stop-factors

**Auth Required:** Yes (developer, admin, ie_engineer)

**Request Body:**
```json
{
  "factor_name": "Bobbin Change",
  "description": "Bobbin thread changeover",
  "frequency": "frequent",
  "impact_percentage": 2.0
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `factor_name` | string | Yes | max:255 |
| `description` | string | No | max:500 |
| `frequency` | string | No | in:rare,occasional,common,frequent |
| `impact_percentage` | numeric | No | min:0, max:100 |

---

### GET /api/sewing-stop-factors/{id}

**Auth Required:** Yes (all authenticated)

---

### PUT /api/sewing-stop-factors/{id}

**Auth Required:** Yes (developer, admin, ie_engineer)

---

### DELETE /api/sewing-stop-factors/{id}

**Auth Required:** Yes (developer, admin)

---

## 21. Endpoints: PTMS Reports

### GET /api/ptms-reports

List all PTMS reports.

**Auth Required:** Yes (all authenticated)

| Query Param | Type | Description |
|-------------|------|-------------|
| `search` | string | Search by report_number |
| `article_id` | integer | Filter by article |
| `line_id` | integer | Filter by production line |
| `status` | string | Filter by status |
| `date_from` | date | Filter by date range start |
| `date_to` | date | Filter by date range end |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |
| `with` | string | Eager load: `article`, `productionLine`, `operator`, `processVersion` |

**Success Response (200):**
```json
{
  "data": [
    {
      "ptms_report_id": 1,
      "report_number": "PTMS-2026-0001",
      "article_id": 1,
      "line_id": 3,
      "operator_id": 5,
      "process_version_id": 2,
      "date": "2026-03-15",
      "target_output": 500,
      "actual_output": 480,
      "efficiency": 96.0,
      "smv": 0.45,
      "status": "approved",
      "remarks": "Good performance",
      "article": { ... },
      "productionLine": { ... },
      "operator": { ... },
      "processVersion": { ... },
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-01T00:00:00.000000Z"
    }
  ],
  "meta": { ... }
}
```

---

### POST /api/ptms-reports

Create a new PTMS report.

**Auth Required:** Yes (developer, admin, ie_engineer, supervisor)

**Request Body:**
```json
{
  "article_id": 1,
  "line_id": 3,
  "operator_id": 5,
  "process_version_id": 2,
  "date": "2026-03-15",
  "target_output": 500,
  "actual_output": 480,
  "smv": 0.45,
  "remarks": "Good performance"
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `article_id` | integer | Yes | exists:articles,article_id |
| `line_id` | integer | Yes | exists:production_lines,line_id |
| `operator_id` | integer | Yes | exists:operators,operator_id |
| `process_version_id` | integer | Yes | exists:process_versions,version_id |
| `date` | date | Yes | date format: Y-m-d |
| `target_output` | integer | Yes | min:1 |
| `actual_output` | integer | Yes | min:0 |
| `smv` | numeric | Yes | min:0 |
| `remarks` | string | No | max:1000 |

> `efficiency` is calculated server-side: `(actual_output / target_output) * 100`  
> `report_number` is auto-generated: `PTMS-YYYY-NNNN`  
> `status` defaults to `draft`

**Success Response (201):**
```json
{
  "data": { "ptms_report_id": 15, ... },
  "message": "PTMS report created successfully"
}
```

---

### GET /api/ptms-reports/{id}

Get a single PTMS report with all relations.

**Auth Required:** Yes (all authenticated)

**Success Response (200):**
```json
{
  "data": {
    "ptms_report_id": 1,
    "report_number": "PTMS-2026-0001",
    "article_id": 1,
    "line_id": 3,
    "operator_id": 5,
    "process_version_id": 2,
    "date": "2026-03-15",
    "target_output": 500,
    "actual_output": 480,
    "efficiency": 96.0,
    "smv": 0.45,
    "status": "approved",
    "remarks": "Good performance",
    "article": { "article_id": 1, "article_name": "Polo Shirt Basic", ... },
    "productionLine": { "line_id": 3, "line_name": "Line 1", ... },
    "operator": { "operator_id": 5, "operator_name": "Budi Santoso", ... },
    "processVersion": { "version_id": 2, "smv": 0.45, ... },
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-01-01T00:00:00.000000Z"
  }
}
```

---

### PUT /api/ptms-reports/{id}

Update a PTMS report.

**Auth Required:** Yes (developer, admin, ie_engineer, supervisor)

**Request Body:** (all fields optional)
```json
{
  "actual_output": 490,
  "remarks": "Updated after review"
}
```

---

### DELETE /api/ptms-reports/{id}

Delete a PTMS report.

**Auth Required:** Yes (developer, admin)

---

### PUT /api/ptms-reports/{id}/status

Update PTMS report status (workflow transition).

**Auth Required:** Yes (developer, admin, ie_engineer)

**Request Body:**
```json
{
  "status": "approved",
  "remarks": "Approved by IE manager"
}
```

| Status Value | Description |
|-------------|-------------|
| `draft` | Initial state |
| `submitted` | Submitted for review |
| `approved` | Approved by IE/Admin |
| `rejected` | Rejected with reason |

---

## 22. Endpoints: Dashboard

### GET /api/dashboard/stats

Get dashboard statistics.

**Auth Required:** Yes (all authenticated)

**Success Response (200):**
```json
{
  "data": {
    "factories_count": 2,
    "departments_count": 8,
    "lines_count": 24,
    "articles_count": 45,
    "operators_count": 320,
    "processes_count": 156,
    "ptms_reports_count": 1250,
    "average_efficiency": 87.5,
    "top_efficient_lines": [
      { "line_id": 3, "line_name": "Line 1", "efficiency": 95.2 },
      { "line_id": 7, "line_name": "Line 5", "efficiency": 93.8 }
    ],
    "recent_reports": [
      { "ptms_report_id": 15, "report_number": "PTMS-2026-0015", ... }
    ]
  }
}
```

---

## 23. Role-Based Access Matrix

| Endpoint | developer | admin | ie_engineer | supervisor | viewer |
|----------|:---------:|:-----:|:-----------:|:----------:|:------:|
| **Auth** | | | | | |
| POST /login | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /logout | ✅ | ✅ | ✅ | ✅ | ✅ |
| GET /me | ✅ | ✅ | ✅ | ✅ | ✅ |
| PUT /me/password | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Users** | | | | | |
| GET /users | ✅ | ✅ | ❌ | ❌ | ❌ |
| POST /users | ✅ | ✅ | ❌ | ❌ | ❌ |
| PUT /users/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| DELETE /users/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Factories** | | | | | |
| GET /factories | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /factories | ✅ | ✅ | ❌ | ❌ | ❌ |
| PUT /factories/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| DELETE /factories/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Departments** | | | | | |
| GET /departments | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /departments | ✅ | ✅ | ❌ | ❌ | ❌ |
| PUT /departments/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| DELETE /departments/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Production Lines** | | | | | |
| GET /production-lines | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /production-lines | ✅ | ✅ | ❌ | ❌ | ❌ |
| PUT /production-lines/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| DELETE /production-lines/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Articles** | | | | | |
| GET /articles | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /articles | ✅ | ✅ | ✅ | ❌ | ❌ |
| PUT /articles/{id} | ✅ | ✅ | ✅ | ❌ | ❌ |
| DELETE /articles/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Operators** | | | | | |
| GET /operators | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /operators | ✅ | ✅ | ❌ | ✅ | ❌ |
| PUT /operators/{id} | ✅ | ✅ | ❌ | ✅ | ❌ |
| DELETE /operators/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Processes** | | | | | |
| GET /processes | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /processes | ✅ | ✅ | ✅ | ❌ | ❌ |
| PUT /processes/{id} | ✅ | ✅ | ✅ | ❌ | ❌ |
| DELETE /processes/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Process Versions** | | | | | |
| GET /process-versions | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /process-versions | ✅ | ✅ | ✅ | ❌ | ❌ |
| PUT /process-versions/{id} | ✅ | ✅ | ✅ | ❌ | ❌ |
| DELETE /process-versions/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| **GSD Categories** | | | | | |
| GET /gsd-categories | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /gsd-categories | ✅ | ✅ | ✅ | ❌ | ❌ |
| PUT /gsd-categories/{id} | ✅ | ✅ | ✅ | ❌ | ❌ |
| DELETE /gsd-categories/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| **GSD Elements** | | | | | |
| GET /gsd-elements | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /gsd-elements | ✅ | ✅ | ✅ | ❌ | ❌ |
| PUT /gsd-elements/{id} | ✅ | ✅ | ✅ | ❌ | ❌ |
| DELETE /gsd-elements/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| **MTM Elements** | | | | | |
| GET /mtm-elements | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /mtm-elements | ✅ | ✅ | ✅ | ❌ | ❌ |
| PUT /mtm-elements/{id} | ✅ | ✅ | ✅ | ❌ | ❌ |
| DELETE /mtm-elements/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Sewing Factors** | | | | | |
| GET /sewing-factors | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /sewing-factors | ✅ | ✅ | ✅ | ❌ | ❌ |
| PUT /sewing-factors/{id} | ✅ | ✅ | ✅ | ❌ | ❌ |
| DELETE /sewing-factors/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Sewing Stop Factors** | | | | | |
| GET /sewing-stop-factors | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /sewing-stop-factors | ✅ | ✅ | ✅ | ❌ | ❌ |
| PUT /sewing-stop-factors/{id} | ✅ | ✅ | ✅ | ❌ | ❌ |
| DELETE /sewing-stop-factors/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| **PTMS Reports** | | | | | |
| GET /ptms-reports | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /ptms-reports | ✅ | ✅ | ✅ | ✅ | ❌ |
| PUT /ptms-reports/{id} | ✅ | ✅ | ✅ | ✅ | ❌ |
| DELETE /ptms-reports/{id} | ✅ | ✅ | ❌ | ❌ | ❌ |
| PUT /ptms-reports/{id}/status | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Dashboard** | | | | | |
| GET /dashboard/stats | ✅ | ✅ | ✅ | ✅ | ✅ |

---

### Access Logic

- **developer** — Full access to everything
- **admin** — Full access except IE-specific operations
- **ie_engineer** — Can manage processes, process versions, GSD, MTM, sewing factors, PTMS reports. Read-only on master data.
- **supervisor** — Can manage operators and PTMS reports. Read-only on everything else.
- **viewer** — Read-only on everything. No create/update/delete access.

---

*End of API Specification*

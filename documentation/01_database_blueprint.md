=====================================================

LEAN & IE MANAGEMENT SYSTEM (LIMS)

DATABASE BLUEPRINT

Version 1.0

Author:
Fauzan Fadhillah Arisandi

Technology

Backend : Laravel 10
Frontend : Next.js
Database : MySQL
Styling : Tailwind CSS

=====================================================

Table of Contents~

1. Authentication

2. Organization

3. Master Data

4. Process Library

5. Production

6. Reports

7. System

8. Relationship Diagram

Authentication Module~~~~~~

--------------------------------
Table : roles

Purpose~
Stores every user role inside the system.
One Role can belong to many Users.

Columns~

| Column      | Type      | Length | PK | FK | Unique | Null | Default |
| ----------- | --------- | ------ | -- | -- | ------ | ---- | ------- |
| id          | BIGINT    | -      | ✅  |    |        | ❌    | AI      |
| role_name   | VARCHAR   | 50     |    |    | ✅      | ❌    |         |
| description | VARCHAR   | 255    |    |    |        | ✅    | NULL    |
| created_at  | TIMESTAMP |        |    |    |        |      |         |
| updated_at  | TIMESTAMP |        |    |    |        |      |         |
				
Relationships~
Role

↓

Many Users

Example Data~
| id | role_name     |
| -- | ------------- |
| 1  | Administrator |
| 2  | User          |
| 3  | IE Engineer   |
| 4  | Supervisor    |


--------------------------------

Table : users
Purpose

Stores system login accounts.

Columns~
| Column          | Type      | Length | PK | FK | Unique |
| --------------- | --------- | ------ | -- | -- | ------ |
| id              | BIGINT    |        | ✅  |    |        |
| role_id         | BIGINT    |        |    | ✅  |        |
| employee_number | VARCHAR   | 20     |    |    | ✅      |
| name            | VARCHAR   | 100    |    |    |        |
| email           | VARCHAR   | 255    |    |    | ✅      |
| password        | VARCHAR   | 255    |    |    |        |
| remember_token  | VARCHAR   | 100    |    |    |        |
| created_at      | TIMESTAMP |        |    |    |        |
| updated_at      | TIMESTAMP |        |    |    |        |

Relationship
Role

↓

User
Every User belongs to exactly one Role.
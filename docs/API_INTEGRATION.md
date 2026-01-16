# Motoshapi API Integration Guide

This document explains how to integrate with the Motoshapi REST API used by the website and external systems (e.g., Pizzeria integration).

## Base URL
- Localhost: http://localhost/motoshapi/api/
- Local network: http://<server-ip>/motoshapi/api/

You can see the server IP and test the endpoints here:
- http://localhost/motoshapi/api/test_api.php

## Response Format
All endpoints return JSON in the same format:

```json
{
  "success": true,
  "message": "...",
  "data": { ... },
  "timestamp": "YYYY-MM-DD HH:MM:SS",
  "api_version": "v1"
}
```

On error, `success` is `false` and `message` describes the issue.

## Authentication
- API key checks are optional for now.
- Toggle in [api/config.php](api/config.php):
  - `API_KEY_REQUIRED = false` (default)
  - When enabled, pass `X-API-Key` header or `?api_key=...` query string.

## Endpoints

### Auth
**File:** [api/auth.php](api/auth.php)

- **POST** `/api/auth.php?action=login`
  - Body: `{ "email": "...", "password": "..." }`
  - Returns user + token

- **POST** `/api/auth.php?action=register`
  - Body: `{ "username": "...", "email": "...", "password": "...", "full_name": "..." }`
  - Returns created user

- **POST** `/api/auth.php?action=validate`
  - Body: `{ "user_id": 1 }`
  - Returns user if valid

- **POST** `/api/auth.php?action=sync`
  - Body: `{ "username": "...", "email": "...", "full_name": "...", "phone": "...", "address": "..." }`
  - Used by external systems to sync users

---

### Products
**File:** [api/products.php](api/products.php)

- **GET** `/api/products.php`
  - Query filters:
    - `page` (default 1)
    - `limit` (default 20, max 100)
    - `category_id`
    - `featured` (0 or 1)
    - `search` (name/description)

- **GET** `/api/products.php?id=1`
  - Returns one product

- **POST** `/api/products.php`
  - Create product

- **PUT** `/api/products.php?id=1`
  - Update product

- **DELETE** `/api/products.php?id=1`
  - Soft delete (`is_active = 0`)

---

### Categories
**File:** [api/categories.php](api/categories.php)

- **GET** `/api/categories.php`
  - Returns all categories with product counts

- **GET** `/api/categories.php?id=1`
  - Returns one category and its products

---

### Orders
**File:** [api/orders.php](api/orders.php)

- **GET** `/api/orders.php`
  - Query filters:
    - `page`, `limit`
    - `user_id`
    - `status` (pending, processing, shipped, delivered, cancelled)

- **GET** `/api/orders.php?id=1`
  - Returns one order + items

- **POST** `/api/orders.php`
  - Create order
  - Required body fields:
    - `user_id`, `items[]`, `payment_mode_id`, `shipping_address`

- **PUT** `/api/orders.php?id=1`
  - Update order status
  - Body: `{ "status": "shipped" }`

---

### Users
**File:** [api/users.php](api/users.php)

- **GET** `/api/users.php`
  - Query filters: `page`, `limit`, `search`

- **GET** `/api/users.php?id=1`
  - Returns one user + order count

- **POST** `/api/users.php`
  - Create user

- **PUT** `/api/users.php?id=1`
  - Update user profile fields

## Pagination
Most list endpoints use the same pagination:
- `page` (1-based)
- `limit` (1–100)

Response includes:
```json
"pagination": {
  "page": 1,
  "limit": 20,
  "total": 150,
  "total_pages": 8
}
```

## CORS
CORS is enabled for all origins by default. This allows external clients (like mobile apps) to call the API without extra configuration.

## Testing
Use the built-in tester page:
- http://localhost/motoshapi/api/test_api.php

It provides one-click tests for the most common endpoints.

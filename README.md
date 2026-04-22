# Backend(yii2)
Order Management System
# Order Management System (Yii2 RESTful API)

This is a backend technical task for a simple Order Management System built using the **Yii2 Framework**.

## 🚀 Getting Started

Follow these steps to set up the project locally.

### 1. Prerequisites
- **PHP**: 7.4 or higher
- **Composer**: [Installation Guide](https://getcomposer.org/doc/00-intro.md#installation-nix)
- **MySQL**: 5.7 or higher

### 2. Installation
Clone the repository and install dependencies:
```bash
composer install
```

### 3. Database Configuration
1.  Create a new MySQL database (e.g., `order_management`).
2.  Open `config/db.php` and update the connection details to match your local environment:
    ```php
    return [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=localhost;dbname=order_management', // Update dbname
        'username' => '', // Your DB username
        'password' => '', // Your DB password
        'charset' => 'utf8',
    ];
    ```

### 4. Database Migrations
Run the following command to create the necessary tables (`user`, `orders`, `order_items`):
```bash
php yii migrate
```

### 5. Running the Application
Start the built-in Yii2 web server:
```bash
php yii serve
```
The API will be available at `http://localhost:8080`.

---

## 🛠 API Documentation

### 🔐 Authentication Flow
All protected endpoints require a Bearer Token in the headers:
`Authorization: Bearer <your_token>`

1.  **Register**: `POST /auth/register`
    - Body: `username`, `email`, `password`
2.  **Login**: `POST /auth/login`
    - Body: `username`, `password`
    - Returns: `token` (this is your `auth_key`)

### 👤 User Profile
- **Get Profile**: `GET /user/profile` (Requires Auth)
- **Update Profile**: `PUT /user/update-profile` (Requires Auth)

### 📦 Orders Management
1.  **Create Order**: `POST /order/create`
    - Body: `{ "notes": "...", "items": [{ "product_name": "...", "quantity": 1, "unit_price": 10 }] }`
2.  **List My Orders**: `GET /order/index`
    - Supports Pagination (see below).
3.  **View Order**: `GET /order/view?id={id}`
4.  **View Order with Items**: `GET /order/details?id={id}`
5.  **Update Status**: `PATCH /order/update-status?id={id}`
    - Body: `{ "status": "in_progress" }`
    - **Note**: Follows forward-only flow: `pending` → `in_progress` → `delivered`.
6.  **Delete Order**: `DELETE /order/delete?id={id}`
    - Only allowed for orders with `pending` status.

---

## 📄 Pagination Details

The "List My Orders" API supports pagination via query parameters.

### Request Parameters
| Parameter  | Description                        | Default |
| :--------- | :--------------------------------- | :------ |
| `page`     | The page number you want to fetch  | 1       |
| `per_page` | Number of items per page           | 10      |

**Example Request:**
`GET http://localhost:8080/order/index?page=1&per_page=2`

### Response Structure
The response includes a `meta` object to help the frontend handle pagination:
```json
{
  "data": [...],
  "meta": {
    "total_count": 2,
    "current_page": 1,
    "per_page": 2,
    "total_pages": 1
  }
}
```

---

## 📂 Project Structure
- `controllers/`: Contains the logic for Auth, Users, and Orders.
- `models/`: Database models and validation rules (e.g., Forward-only status logic in `Order.php`).
- `migrations/`: Database schema definitions.
- `config/`: Application and Database configuration.


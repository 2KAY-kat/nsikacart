# Nsikacart Developer Documentation

## Table of Contents

1. [Project Overview](#project-overview)
2. [Architecture](#architecture)
3. [Setup &amp; Installation](#setup--installation)
4. [Database Schema](#database-schema)
5. [Authentication System](#authentication-system)
6. [API Documentation](#api-documentation)
7. [Frontend Architecture](#frontend-architecture)
8. [User Role System](#user-role-system)
9. [File Upload System](#file-upload-system)
10. [Development Guidelines](#development-guidelines)
11. [Security Considerations](#security-considerations)
12. [Troubleshooting](#troubleshooting)

## Project Overview

Nsikacart is a PHP-based e-commerce marketplace targeting the Malawian market. It features user authentication, product management, role-based access control, and a modern dashboard interface.

### Key Features

- User registration and authentication
- Product upload and management
- Role-based access control (Admin, Monitor, User)
- Shopping cart functionality
- Dashboard for product management
- User management system
- Email notifications
- Responsive design

### Technology Stack

- **Backend**: PHP 7.4+, MySQL
- **Frontend**: Vanilla JavaScript (ES6 modules), HTML5, CSS3
- **Database**: MySQL/MariaDB
- **Email**: PHPMailer
- **Icons**: FontAwesome 6.7.2
- **Environment**: XAMPP/WAMP

## Architecture

### Directory Structure

```
nsikacart/
├── api/                          # Backend API endpoints
│   ├── admin/                    # Admin-specific endpoints
│   ├── auth/                     # Authentication endpoints
│   ├── config/                   # Database configuration
│   ├── middleware/               # Authentication middleware
│   └── products/                 # Product management endpoints
├── auth/                         # Authentication pages
├── css/                          # Global stylesheets
├── error/                        # Error pages
├── helpers/                      # Utility classes and helpers
├── logs/                         # Application logs
└── public/                       # Public assets and pages
    ├── dashboard/                # Dashboard interface
    ├── scripts/                  # Frontend JavaScript modules
    └── css/                      # Frontend stylesheets
```

## Setup & Installation

### Prerequisites

- XAMPP/WAMP with PHP 7.4+
- MySQL/MariaDB
- Git

### Installation Steps

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd nsikacart
   ```

2. **Database Setup**
   ```sql
   CREATE DATABASE your_database_name;
   CREATE USER 'your_db_user'@'localhost' IDENTIFIED BY 'your_secure_password';
   GRANT ALL PRIVILEGES ON your_database_name.* TO 'your_db_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Environment Configuration**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials and other settings
   ```

4. **Database Configuration**
   ```bash
   cp api/config/db.example.php api/config/db.php
   # Update database credentials in db.php to match your .env settings
   ```

5. **File Permissions**
   ```bash
   mkdir public/dashboard/uploads
   chmod 755 public/dashboard/uploads
   ```

### Database Schema

#### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'monitor', 'admin') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Products Table
```sql
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    images JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### Sessions Table
```sql
CREATE TABLE sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### User Remember Tokens Table
```sql
CREATE TABLE user_remember_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(128) NOT NULL,
    expires_at INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Authentication System

### Session Management
The authentication system uses custom session management with database storage:

- **Session Creation**: `api/auth/login.php`
- **Session Validation**: `api/auth/ping.php`
- **Session Cleanup**: Automatic cleanup on logout

### Remember Me Functionality
The system includes "Remember Me" functionality for persistent login:

- **Token Storage**: Remember tokens stored in `user_remember_tokens` table
- **Token Validation**: Long-lived tokens for automatic login
- **Security**: Tokens expire and are single-use for security
- **Cleanup**: Expired tokens are automatically cleaned up

#### Remember Token Flow
1. User checks "Remember Me" during login
2. Server generates secure random token with expiration
3. Token stored in database and browser cookie
4. On subsequent visits, token validates automatic login
5. Token is refreshed on successful validation

### Password Security
- Passwords are hashed using `password_hash()` with `PASSWORD_DEFAULT`
- Password strength validation on frontend
- Password reset functionality via email tokens

### Authentication Flow
1. User submits login credentials
2. Server validates credentials and creates session token
3. If "Remember Me" selected, creates remember token
4. Session token stored in database and browser sessionStorage
5. Remember token stored in secure HTTP-only cookie
6. Protected routes verify session token via middleware

## API Documentation

### Authentication Endpoints

#### POST `/api/auth/login.php`

Login user and create session.

**Request Body:**

```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "role": "user"
    }
}
```

#### POST `/api/auth/register.php`

Register new user account.

**Request Body:**

```json
{
    "name": "John Doe",
    "email": "user@example.com",
    "phone": "1234567890",
    "password": "password123",
    "confirm_password": "password123"
}
```

### Product Endpoints

#### POST `/api/products/upload.php`

Upload new product (multipart/form-data).

**Form Fields:**

- `name`: Product name
- `description`: Product description
- `price`: Product price
- `category`: Product category
- `images[]`: Product images (files)

#### GET `/api/products/list.php`

Get user's products with pagination.

**Query Parameters:**

- `page`: Page number (default: 1)
- `limit`: Items per page (default: 10)

#### POST `/api/products/update.php`

Update existing product.

#### POST `/api/products/delete.php`

Delete product and associated files.

### Admin Endpoints

#### GET `/api/admin/users.php`

Get paginated user list (Admin/Monitor only).

#### POST `/api/admin/change-user-role.php`

Change user role (Admin only).

#### POST `/api/admin/toggle-user-status.php`

Activate/deactivate user (Admin/Monitor only).

## Frontend Architecture

### Module System

The frontend uses ES6 modules for organization:

- **`public/scripts/index.js`**: Main page logic
- **`public/scripts/cart.js`**: Shopping cart functionality
- **`public/scripts/data.js`**: Static data and product utilities
- **`public/scripts/checkout/`**: Checkout process modules

### Dashboard Architecture

The dashboard uses a modular component system:

- **`dashboard/js/dashboard-render.js`**: Main rendering engine
- **`dashboard/js/dashboard-sections.js`**: Section definitions
- **`dashboard/js/session-manager.js`**: Session validation

### State Management

- User session stored in `sessionStorage`
- Shopping cart stored in `localStorage`
- Dashboard state managed via URL hash navigation

### Event Handling

```javascript
// Example: Product deletion with confirmation modal
window.deleteProduct = function(productId, productName) {
    const modal = document.getElementById('delete-modal');
    modal.style.display = 'flex';
    modal.dataset.productId = productId;
    // Show confirmation dialog
};
```

## User Role System

### Role Hierarchy

1. **Admin** - Full system access
2. **Monitor** - User management (limited)
3. **User** - Basic marketplace access

### Permission Matrix

| Action          | User | Monitor | Admin |
| --------------- | ---- | ------- | ----- |
| View Products   | ✅   | ✅      | ✅    |
| Upload Products | ✅   | ✅      | ✅    |
| View All Users  | ❌   | ✅      | ✅    |
| Suspend Users   | ❌   | ✅*     | ✅    |
| Delete Users    | ❌   | ❌      | ✅    |
| Change Roles    | ❌   | ❌      | ✅    |

*Monitors cannot suspend Admins

### Implementation

```php
// api/middleware/auth_required.php
if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user']['id'];
$current_user_role = $_SESSION['user']['role'] ?? 'user';
```

## File Upload System

### Image Upload Process

1. **Client-side validation** in `upload/upload.js`
2. **Server-side processing** in `api/products/upload.php`
3. **File storage** in `public/dashboard/uploads/`

### Security Measures

- File type validation (images only)
- File size limits
- Unique filename generation
- Directory traversal prevention

### File Organization

```
public/dashboard/uploads/
├── user_1_product_123_1.jpg
├── user_1_product_123_2.jpg
└── user_2_product_456_1.png
```

## Development Guidelines

### Code Style

- **PHP**: PSR-4 autoloading, camelCase for variables
- **JavaScript**: ES6+ features, module imports
- **CSS**: BEM naming convention, CSS custom properties

### Error Handling

```php
// PHP Error Handling
try {
    // Database operation
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
```

```javascript
// JavaScript Error Handling
try {
    const response = await fetch('/api/endpoint');
    const data = await response.json();
    if (!data.success) throw new Error(data.message);
} catch (error) {
    console.error('Operation failed:', error);
    showToast('An error occurred', 'error');
}
```

### Toast Notifications

Universal toast system for user feedback:

```javascript
// Show success message
showToast('Product uploaded successfully!', 'success');

// Show error message
showToast('Upload failed. Please try again.', 'error');
```

## Security Considerations

### Input Validation

- **Server-side**: All inputs sanitized and validated
- **SQL Injection**: Prepared statements only
- **XSS Prevention**: HTML escaping for output

### Authentication Security

- Session tokens stored securely
- Password complexity requirements
- Session timeout and cleanup
- CSRF protection on forms

### File Upload Security

- Mime type validation
- File extension whitelist
- Upload directory outside document root
- Filename sanitization

### Database Security

```php
// Example: Secure user verification
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
$stmt->execute([$product_id, $current_user_id]);
```

## Troubleshooting

### Common Issues

#### 1. Database Connection Failed

**Symptoms**: 500 errors on API calls
**Solution**: Check `api/config/db.php` credentials

#### 2. File Upload Errors

**Symptoms**: Upload fails silently
**Solution**: Check directory permissions and PHP upload limits

#### 3. Session Issues

**Symptoms**: Users logged out unexpectedly
**Solution**: Verify session configuration and database connectivity

#### 4. JavaScript Module Errors

**Symptoms**: "Cannot resolve module" errors
**Solution**: Check file paths and module imports

### Debug Mode

Enable debug mode by setting:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Logging

Application logs stored in `logs/` directory:

- Error logs for debugging
- User activity logs
- System events

## Contributing

### Git Workflow

1. Create feature branch: `git checkout -b feature/new-feature`
2. Make changes and test thoroughly
3. Commit with descriptive messages
4. Create pull request with detailed description

### Testing

- Test all user roles and permissions
- Verify mobile responsiveness
- Cross-browser compatibility
- Database transaction integrity

### Documentation

- Update this documentation for new features
- Comment complex business logic
- Maintain API documentation
- Update setup instructions as needed

---

**Note**: This is a living document. Please keep it updated as the project evolves.

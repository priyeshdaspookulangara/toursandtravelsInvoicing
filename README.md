# Plain PHP/MySQL Invoice Generator

A simple web-based invoice generator tailored for a tours and travels company. The application allows an administrator to create, view, manage, edit, and delete invoices.

## Features

*   Admin Login System
*   Dashboard listing all invoices
*   Create New Invoice
*   View Invoice (Print-friendly format)
*   Edit Invoice
*   Delete Invoice
*   Basic Client Management (Add/List)
*   Basic Package/Service Management (Add/List)
*   Manual SQL input sanitization (as per project requirements)

## Technology Stack

*   Plain PHP (No frameworks, No ORMs)
*   MySQL Database
*   HTML, CSS, and minimal JavaScript for frontend interactions.

## Core Files

*   `config.php`: Database credentials, company details, application settings.
*   `db.php`: Database connection, sanitization functions, query helpers.
*   `schema.sql`: SQL script to create database tables.
*   `setup.php`: Script to initialize the database and create a default admin user.
*   `login.php`, `logout.php`, `auth.php`: User authentication and session management.
*   `index.php`: Dashboard, lists all invoices.
*   `create_invoice.php`, `edit_invoice.php`, `view_invoice.php`, `delete_invoice.php`: Invoice management.
*   `add_client.php`, `list_clients.php`: Client management.
*   `add_package.php`, `list_packages.php`: Package/Service management.
*   `templates/header.php`, `templates/footer.php`: Common HTML structure.

## Setup Instructions

### 1. Prerequisites

*   A web server with PHP support (e.g., Apache, Nginx).
*   MySQL server.
*   A web browser.

### 2. Configure the Application

1.  **Clone or download the project files** to your web server's document root (e.g., `htdocs`, `www`). Let's assume you place it in a directory named `invoice_generator`.
2.  **Edit `config.php`**:
    *   Open `config.php` in a text editor.
    *   Update the database credentials:
        ```php
        define('DB_HOST', 'your_mysql_host'); // e.g., 'localhost' or IP address
        define('DB_USER', 'your_mysql_username');
        define('DB_PASS', 'your_mysql_password');
        define('DB_NAME', 'invoice_system'); // You can keep this or change it
        ```
    *   Update `APP_BASE_URL` to match your local setup:
        ```php
        // Example if your project is in http://localhost/invoice_generator/
        define('APP_BASE_URL', 'http://localhost/invoice_generator/');
        // Example if project is in root http://localhost/
        // define('APP_BASE_URL', 'http://localhost/');
        ```
        Ensure the URL has a trailing slash `/`.
    *   Optionally, update the pre-defined `COMPANY_` constants with your company's details.

### 3. Database Setup & Initial Admin User

1.  **Ensure your MySQL server is running.**
2.  **Open your web browser and navigate to the `setup.php` script.**
    *   For example, if your project is accessible at `http://localhost/invoice_generator/`, go to:
        `http://localhost/invoice_generator/setup.php`
3.  This script will:
    *   Attempt to create the database specified in `DB_NAME` (from `config.php`) if it doesn't exist.
    *   Create all necessary tables using `schema.sql`.
    *   Create a default administrator user with the following credentials:
        *   **Username:** `admin`
        *   **Password:** `password123`
        *   **Email:** `admin@example.com`
4.  You should see success messages in your browser if the setup is completed correctly. If there are errors, they will typically indicate issues with database connection (check `config.php`) or permissions.
5.  **IMPORTANT SECURITY NOTE:** After running `setup.php` successfully, it is highly recommended to either:
    *   **Delete `setup.php`** from your server.
    *   Rename `setup.php` to something unpredictable.
    *   Restrict access to `setup.php` (e.g., via `.htaccess` if using Apache).
    This prevents it from being run again accidentally or maliciously.

### 4. Running the Application

1.  Navigate to the main application URL you configured in `APP_BASE_URL`.
    *   Example: `http://localhost/invoice_generator/`
2.  You will be redirected to `login.php`.
3.  Log in with the default admin credentials (or the ones you set up if you modified `setup.php`).
    *   Username: `admin`
    *   Password: `password123`
4.  **It is strongly recommended to change the default admin password immediately after your first login.** (Note: Password change functionality is not built into this version of the application. You would need to update it directly in the `users` table via a MySQL client or phpMyAdmin, ensuring you use `password_hash()` if doing it manually via PHP).

## Usage

*   **Dashboard (`index.php`):** View a list of all invoices. From here you can View, Edit, or Delete invoices.
*   **Create Invoice:** Navigate to "Create Invoice" to generate a new invoice. Select a client, fill in details, and add line items.
*   **Manage Clients:** Add new clients or view existing ones.
*   **Manage Packages:** Add new travel packages/services with their prices or view existing ones.
*   **Logout:** Securely end your session.

## Security Considerations (Manual Sanitization)

This application, as per the project requirements, **does not use prepared statements or ORMs**. Instead, it relies on manual input sanitization:

*   All user inputs are intended to be passed through sanitization functions in `db.php` (e.g., `sanitize_string()`, `sanitize_int()`, `sanitize_decimal()`, `sanitize_email()`) before being used in SQL queries. These functions primarily use `mysqli_real_escape_string()` and PHP's filter functions.
*   Passwords are stored hashed using `password_hash()` and verified with `password_verify()`.
*   Session management is implemented for user authentication.

**Disclaimer:** While efforts have been made to manually sanitize inputs, this approach is inherently more prone to errors and vulnerabilities (like SQL injection) than using prepared statements. This project serves as an exercise in understanding manual sanitization but is **not recommended for production environments handling sensitive data without extensive security audits and potentially switching to prepared statements.**

## Further Development (Potential Enhancements)

*   Implement "Edit" and "Delete" for Clients and Packages.
*   Add password change functionality for users.
*   More robust unique invoice number generation.
*   Advanced search and filtering for invoices.
*   User roles and permissions.
*   PDF generation for invoices.
*   JavaScript enhancements for a richer user experience (e.g., dynamically adding/removing invoice items without page reload).
*   Implement proper CSRF protection for all state-changing actions.
*   Transition to prepared statements for improved security.
*   Comprehensive logging.
*   Unit and integration tests.
---

This README should provide a good overview and setup instructions.
Remember to replace placeholder values like `your_mysql_host` when actually setting up.Tool output for `create_file_with_block`:

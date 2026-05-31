# Library Inventory Management System

A secure PHP CRUD web application for cataloging and managing books in a local library inventory. This project demonstrates clean PHP code with PDO database access, secure form handling, and a simple responsive interface built with Bootstrap.

## Overview

This application supports the full CRUD lifecycle:

* **Create:** Add a new book record with title, author, ISBN, and optional cover image upload.
* **Read:** View the catalog in a responsive table with sequential UI numbering and optional cover thumbnails.
* **Update:** Edit existing book metadata and optionally replace the cover image.
* **Delete:** Remove a book record securely using a confirmation modal.

The interface uses Bootstrap 5, and the application follows secure PHP best practices including prepared statements, CSRF protection, file validation, and output escaping.

## Design Choices

* **PDO with prepared statements:** Used for all MySQL interactions to prevent SQL injection and provide consistent error handling.
* **CSRF token validation:** Every form includes a session-backed token to stop unauthorized submissions.
* **Server-side validation:** Title, author, and ISBN fields are required, and ISBN must be exactly 13 digits.
* **File upload security:** Cover images are validated by extension, MIME type, and file size before being stored in `uploads/`.
* **Modular templates:** The header/footer layout is separated in `templates/header.php` and `templates/footer.php` for maintainability.
* **Responsive UI:** The app uses Bootstrap 5 and icons to keep the experience clean and mobile-friendly.

## System Directory Structure

```text
library_crud/
├── config.php            # Database connection and session / CSRF setup
├── index.php             # Dashboard view listing all books and delete confirmation modal
├── create.php            # Add new book form and insert logic
├── update.php            # Edit existing book logic and form
├── delete.php            # Delete record handler
├── schema.sql            # SQL script to create the database and books table
└── templates/
    ├── header.php        # Shared page header, Bootstrap includes, and styles
    └── footer.php        # Shared footer and Bootstrap JS bundle
```

## Database Schema Blueprint

The system utilizes a lightweight, high-performance InnoDB relational table optimized for data integrity:

| Field Name    | Data Type     | Key Type | Properties / Validations        | Description                                |
|---------------|---------------|----------|---------------------------------|--------------------------------------------|
| `id`          | `INT`         | PRIMARY  | `AUTO_INCREMENT`, `NOT NULL`    | Unique internal system tracking sequence   |
| `title`       | `VARCHAR(150)`| -        | `NOT NULL`                      | Full text-string title of the book volume  |
| `author`      | `VARCHAR(100)`| -        | `NOT NULL`                      | Full name of the primary author/writer     |
| `isbn`        | `VARCHAR(13)` | UNIQUE   | `NOT NULL`                      | 13-digit ISBN code                         |
| `cover_image` | `VARCHAR(255)`| -        | `DEFAULT NULL`                  | Optional filename for uploaded book cover  |
| `created_at`  | `TIMESTAMP`   | -        | `DEFAULT CURRENT_TIMESTAMP`     | Record creation timestamp                  |

## Usage Guide

### Add a Book

1. Click **Add New Book** on the dashboard or open `create.php`.
2. Enter title, author, and a 13-digit ISBN.
3. Optionally upload a cover image in JPG/JPEG/PNG format (max 2MB).
4. Submit the form to save the book.

### View Catalog

* `index.php` displays all saved books in a responsive table.
* Records are shown with sequential UI numbering; the database ID is kept intact but UI numbering is rendered at runtime.
* Each row includes **Edit** and **Delete** controls.

### Update a Book

1. Click **Edit** on the desired record.
2. Modify title, author, or ISBN.
3. Optionally replace the current cover image.
4. Submit the form to update the record.

### Delete a Book

1. Click **Delete** on the desired record.
2. Confirm deletion in the modal dialog.
3. The record is removed from the database and the cover file is deleted from disk if it exists.

## Frontend and Behavior

* Uses **Bootstrap 5** for layout, responsive grids, and UI components.
* Includes **Bootstrap Icons** for action buttons and feedback visuals.
* A small JavaScript block in `index.php` handles the delete confirmation modal by injecting the selected book title and ID.


## Deployment Manual

Follow these operational steps to deploy the management system inside your local development server context:

### 1. Environment Requirements

* Local Web Server Environment (e.g., XAMPP, WAMP, or Laragon)
* PHP Version: `7.4` or higher
* Database Engine: MariaDB / MySQL

### 2. Database Initialization

1. Launch your local MySQL server engine along with your web server controller dashboard.
2. Access your administration space (e.g., **phpMyAdmin** via `http://localhost/phpmyadmin`).
3. Navigate to the SQL input container console, paste the query definitions provided inside `schema.sql`, and run the script execution command:

```sql
CREATE DATABASE IF NOT EXISTS library_db;
USE library_db;

CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    author VARCHAR(100) NOT NULL,
    isbn VARCHAR(13) NOT NULL UNIQUE,
    cover_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

```

### 3. Application Deployment

1. Transfer or extract your complete `library_crud/` project folder into your web server root (for example, `C:/xampp/htdocs/library_crud`).
2. Open `config.php` and verify your MySQL credentials:
   * Host: `localhost`
   * Database: `library_db`
   * Username: `root`
   * Password: `""` (empty string)
   * Port: `3306`
3. Optionally, use environment variables instead of editing `config.php`:
   * `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_PORT`

### 4. Running the Application

Open your browser and visit:

```text
http://localhost/library_crud/index.php
```

## Notes

* Uploaded cover images are stored in the `uploads/` directory.
* All operations use secure server-side validation and sanitization.
* The user interface is intentionally minimal and functional to support easy review and extension.

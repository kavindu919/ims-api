# Inventory Management System (IMS) - Backend API

A professional, robust backend API built with Laravel for managing inventory, tracking borrowings, and monitoring storage locations.

## Technical Stack

- **Framework:** Laravel 12.x
- **Language:** PHP 8.2+
- **Authentication:** Laravel Sanctum
- **Database Support:** SQLite (Development), PostgreSQL (Production/Configurable)
- **Dev Tools:** Laravel Sail, Vite (for assets), PHPUnit (Testing)

## Core Functional Modules

- **Authentication System:** Secure API-based authentication using Laravel Sanctum with user registration and login management.
- **Inventory Management:** Full CRUD operations for inventory items, including advanced search and categorization.
- **Storage Management:** Hierarchical storage location tracking, including physical storage units such as cupboards and shelves.
- **Borrowing System:** Workflow for issuing items to users, tracking returns, and maintaining a historical record of borrowings.
- **Activity Logging:** Comprehensive audit trails for modifications across the system to ensure accountability.
- **Dashboard Analytics:** High-level summary of system status, inventory health, and active borrowings.

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and NPM
- SQL Database (SQLite by default, or PostgreSQL/MySQL)

### Installation Steps

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd ims-api
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup:**
   Copy the example environment file and configure your database settings.
   ```bash
   cp .env.example .env
   ```

4. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

5. **Run Migrations and Seeders:**
   ```bash
   php artisan migrate --seed
   ```

6. **Start the Development Server:**
   ```bash
   php artisan serve
   ```
   The API will be available at `http://localhost:8000`.

## Project Structure

- `app/Http/Controllers`: Contains the API controllers for all functional modules.
- `app/Models`: Eloquent models representing the database schema.
- `routes/api.php`: The primary entry point for all RESTful API endpoints.
- `database/migrations`: Database schema definitions.
- `database/seeders`: Initial data and test records.

## API Usage

All API endpoints are prefixed with `/api`. Authentication is required for most endpoints and follows the Bearer Token pattern via Sanctum.

Example endpoints:
- `POST /api/login`: Authenticate a user.
- `GET /api/inventory`: List all inventory items.
- `POST /api/borrow`: Record a new borrowing event.

## Testing

The project uses PHPUnit for automated testing. Run the following command to execute the test suite:

```bash
php artisan test
```

## Contributing

Please ensure that any contributions follow the standard Laravel coding guidelines (PSR-12) and include appropriate test coverage.

## License

This project is licensed under the MIT License.

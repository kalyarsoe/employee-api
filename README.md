# Employee Management Backend API 

Built using **GraphQL (`nuwave/lighthouse`)**, **Laravel Passport (OAuth2 Authentication)**, **Maatwebsite Excel (CSV Import/Export)**, **Soft Deletes**, **Uploaded File Name Validation**, **Row-Level Database Duplicate Detection**, and the **Repository Pattern**.

---

## Technical Stack & Architecture

- **Framework**: Laravel 12
- **Database**: MySQL (`employee_api_db`)
- **API Protocol**: GraphQL (`nuwave/lighthouse`) & REST API
- **Authentication**: Laravel Passport (Username & Password OAuth2 Token authentication)
- **Data Export & Import**: Maatwebsite/Laravel-Excel (Optimized CSV format with chunked reading/streaming)
- **Data Seeding**: Faker (High-speed bulk creation up to 50,000 records)

---

## Setup & Running Instructions

```bash
# Navigate to project directory
cd C:\laragon\www\employee-api

# Run migrations and seed admin user
php artisan migrate:fresh --seed

# Generate Passport encryption keys and Personal Access Client
php artisan passport:keys --force
php artisan passport:client --personal --name="Employee API Personal Access Client" --no-interaction

# Run server
php artisan serve
```

---

## Credentials (Default Admin Account)

- **Username**: `admin`
- **Password**: `password`

---

## Automated Tests

Run test suite verifying Passport authentication, Repository CRUD, SoftDeletes, File Name Validation, Employee ID controls, and CSV database duplicate error reporting:
```bash
php artisan test
```

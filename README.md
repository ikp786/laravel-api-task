# Laravel Secure API with Authentication and Encryption

This project demonstrates how to build a secure RESTful API using Laravel. The API includes user authentication with Laravel Passport, CRUD operations on a `Transaction` resource, and encryption/decryption of request and response data.

## Prerequisites

- PHP 8.2
- Composer
- Laravel 11.x

## Setup and Installation

Follow these steps to set up and run the project locally:

### 1. Clone the Repository

```bash
git clone https://github.com/ikp786/laravel-api-task.git
cd laravel-api-task

composer install

cp .env.example .env

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Other environment settings

php artisan key:generate

php artisan migrate

php artisan passport:client --personal

after this showing a promt in this type Auth API and enter

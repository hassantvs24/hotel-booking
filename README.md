# Getting started of Hotel Booking Application

## Installation

Clone the repository

    git clone https://github.com/hassantvs24/hotel-booking.git

Switch to the repo folder

    cd hotel-booking

Install all the dependencies using composer

    composer install

Copy the example env file and make the required configuration changes in the .env file

    cp .env.example .env

Generate a new application key. <b>We already have a key in the .env file, but if you want to generate a new one, you can run the following command</b>

    php artisan key:generate

Run the database migrations (**Set the database connection in .env before migrating**)

    php artisan migrate:fresh --seed

Start the local development server

    php artisan serve

You can now access the server at http://localhost:8000

**TL;DR command list**

    git clone https://github.com/hassantvs24/hotel-booking.git
    cd hotel-booking
    composer install
    cp .env.example .env
    php artisan key:generate

**Make sure you set the correct database connection information before running the migrations** [Environment variables](#environment-variables)

    php artisan optimize:clear
    php artisan migrate:fresh --seed
    php artisan serve


**Make sure you have installed PHP with >= 8.2. Because we are using Laravel 11**

### Some Package I pre-installed in this App
- Intervention Image for image manipulation <a href="https://image.intervention.io/v3">doc link</a>
- Laravel Telescope for live activity check on the APP <a href="https://laravel.com/docs/11.x/telescope">doc link</a>
- Laravel Activity log, using spatie/laravel-activitylog <a href="https://spatie.be/docs/laravel-activitylog/v4/introduction">doc link</a>
- Laravel User Role Permission, using spatie/permission <a href="https://spatie.be/docs/laravel-permission/v6/introduction">doc link</a>
- Preventing spam submitted through forms, using spatie/laravel-honeypot <a href="https://github.com/spatie/laravel-honeypot">doc link</a>
- Jwt Authentication for API, using php-open-source-saver/jwt-auth <a href="https://laravel-jwt-auth.readthedocs.io/en/latest/">doc link</a>


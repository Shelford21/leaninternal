@echo off
:: Change the directory path below to your actual Laravel project path
cd /d "%~dp0"

:: Run the Laravel development server
php artisan serve --host=0.0.0.0 --port=8000
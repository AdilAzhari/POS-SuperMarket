# Authentication System

## Overview
The authentication system provides secure user access control for the POS application using Laravel Breeze.

## Features
- User registration and login
- Password reset functionality
- Email verification
- Session management
- Multi-factor authentication support

## Controllers
- `AuthenticatedSessionController` - Handle login/logout
- `RegisteredUserController` - User registration
- `PasswordController` - Password management
- `EmailVerificationController` - Email verification

## Frontend Components
- Login/Register forms (`resources/js/Pages/Auth/`)
- Password reset forms
- Email verification pages

## Routes
Authentication routes are defined in `routes/auth.php`

## Security Features
- CSRF protection
- Rate limiting
- Secure password hashing
- Session management
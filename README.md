# 🚀 Laravel 12 API Boilerplate

A powerful boilerplate to quickly start a Laravel 12 API project with:

- ✅ JWT Authentication
- ✅ Social Login (Google, Facebook, GitHub)
- ✅ Image Upload with Intervention Image
- ✅ Sentry Error Monitoring
- ✅ Modular Code (Service, FormRequest, Controller separated)
- ✅ Clean & Scalable Structure
- ✅ Localization

---

## 📦 Requirements

- PHP >= 8.2
- Laravel 12.x
- Composer
- MySQL/PostgreSQL
- Redis (optional)
- Node.js (only if building Swagger UI frontend)

---

## 🚀 Installation

```bash
git clone <repo-url> laravel-boilerplate
cd laravel-boilerplate

composer install
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate

# Create storage link
php artisan storage:link
```

## 🔐 JWT Authentication

This boilerplate uses [`tymon/jwt-auth`](https://github.com/tymondesigns/jwt-auth) for handling JSON Web Token authentication.

### 🔑 Core API Endpoints

| Method | Endpoint       | Description           |
|--------|----------------|-----------------------|
| POST   | `/api/auth/login`   | Login                 |
| POST   | `/api/auth/register`| Register              |
| POST   | `/api/auth/logout`  | Logout                |
| POST   | `/api/auth/refresh` | Refresh token         |
| POST   | `/api/me`      | Get current user info |

### ⚙️ Generate key Commands

```bash
php artisan jwt:secret
```

## 🔗 Social Login (Google, Facebook, GitHub)

Powered by [Laravel Socialite](https://laravel.com/docs/12.x/socialite) for OAuth authentication.

### 🌐 OAuth Routes

| Method | Endpoint                                | Description            |
|--------|-----------------------------------------|------------------------|
| GET    | `/api/auth/{provider}/redirect`         | Redirect to OAuth URL |
| GET    | `/api/auth/{provider}/callback`         | Handle OAuth callback |

> `{provider}` must be one of: `google`, `facebook`, `github`

---

### 🛠️ `.env` Example

```env
GOOGLE_CLIENT_ID=xxx
GOOGLE_CLIENT_SECRET=xxx
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback

GITHUB_CLIENT_ID=xxx
GITHUB_CLIENT_SECRET=xxx
GITHUB_REDIRECT_URI=http://localhost:8000/api/auth/github/callback

FACEBOOK_CLIENT_ID=xxx
FACEBOOK_CLIENT_SECRET=xxx
FACEBOOK_REDIRECT_URI=http://localhost:8000/api/auth/facebook/callback
```

## 🛠 Folder Structure

```plaintext
app/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Middleware/
├── Models/
├── Services/
├── Providers/
routes/
│   └── api.php
config/
database/
```

## ⚙️ Customization Guide

- **Add new social provider**: Update `SocialProviderRequest` + `config/services.php`
- **Change image disk**: Modify `config/filesystems.php`
- **Multiple image sizes**: Expand `ImageUploadService` for thumbnail, medium, original
- **Add roles/permissions**: Integrate [`spatie/laravel-permission`](https://github.com/spatie/laravel-permission) package

---

## ✅ TODO / Roadmap

- [ ] Add unit tests for auth flows
- [ ] Add soft delete support for users
- [ ] Add unlink social account feature
- [ ] Add rate limiting / throttling

---

## 👤 Author

- **Name**: Edric Cao
- **Repo**: [github.com/edric-cao-goldenowl](https://github.com/edric-cao-goldenowl)

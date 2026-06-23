# Syncro - Property Management System (PMS)

Syncro is a comprehensive, multi-tenant Property Management System built with a secure, native PHP architecture. Designed specifically to thrive on shared hosting environments (like Serverbyt), it empowers hoteliers to seamlessly oversee their operations from reservations to reporting.

## Features

- **Multi-Tenant Architecture**: Support for multiple hotels/properties under a single master dashboard.
- **Role-Based Access Control**: Separate admin and hotel staff roles with granular permissions.
- **Reservation Management**: Create, update, and manage bookings and guest profiles.
- **Financial Idempotency**: Strict database transactions and PhonePe checksum verification to prevent duplicate payments.
- **Real-Time Notifications**: Pusher (WebSocket) integration for instant cross-module alerts, completely bypassing shared hosting firewall restrictions.
- **Asynchronous Queueing**: Background `DatabaseQueue` with a cron worker designed specifically to adhere to standard 60-second shared hosting limits.
- **Optimized Caching**: File-based `CacheManager` to drastically reduce database load without requiring Redis.
- **OTA Channel Manager API**: Secure API endpoints with exponential backoff and chunking to reliably handle incoming OTA requests (Booking.com, Expedia, etc.).
- **Zero-Downtime Deployments**: Fully automated FTP/SFTP sync via GitHub Actions (`deploy.yml`).

## System Requirements

- PHP 8.1+
- MySQL 8.0+ or MariaDB
- Composer
- Web Server (Apache/Nginx)
- Basic Cron access (1-minute interval)

## Installation & Deployment

1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-org/syncro.git
   cd syncro
   ```

2. **Environment Configuration:**
   Copy `.env.example` to `.env` and configure your production database credentials, Pusher keys, and PhonePe Salt keys.
   ```bash
   cp .env.example .env
   ```

3. **Web Server Setup (Crucial):**
   Syncro strictly enforces a Front Controller pattern. Your document root MUST point to the `public/` directory, or your server must support the included root `.htaccess` which securely proxies traffic to `public/` while explicitly blocking access to `.env` and `core/`.

4. **Background Queue Setup:**
   Add the following entry to your server's crontab (via cPanel or CLI) to run every minute:
   ```bash
   * * * * * php /path/to/syncro/public_html/cron/worker.php > /dev/null 2>&1
   ```

## Architecture Notes

Syncro was heavily refactored for absolute security and compatibility on constrained shared-hosting environments:
- **`public/` vs `core/` Isolation**: The application's core logic is permanently shielded from direct web access.
- **Front Controller Pattern**: All traffic routes through `public/index.php` and is parsed by the `Router.php`.
- **Validation Engine**: Direct access to `$_POST` and `$_GET` is strictly prohibited. All data passes through the `Validator` to prevent injections and XSS.
- **Views**: Native PHP templates injected with TailwindCSS.

## License

Syncro is proprietary software. All rights reserved.
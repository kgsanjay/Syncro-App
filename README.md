# Syncro - Property Management System (PMS)

Syncro is a comprehensive, multi-tenant Property Management System built with native PHP (no external heavy frameworks) that empowers hoteliers and property managers to oversee their operations from reservations to reporting.

## Features

- **Multi-Tenant Architecture**: Support for multiple hotels/properties under a single master dashboard.
- **Role-Based Access Control**: Separate admin and hotel staff roles with granular permissions.
- **Reservation Management**: Create, update, and manage bookings and guest profiles.
- **Interactive Calendar**: Drag-and-drop React-based property calendar component.
- **Financial & Accounting Export**: Export transactions and revenue data to CSV for accounting tools.
- **Automated Night Audit**: Cron-based scheduled jobs to compute daily occupancy and RevPAR.
- **Integrated Payments**: Built-in Stripe payment processing integration.
- **Real-Time Notifications**: Server-Sent Events (SSE) backbone for live cross-module alerts.
- **OTA Channel Manager API**: Secure API endpoints to receive reservations from external aggregators (Booking.com, Expedia, etc.).
- **Progressive Web App (PWA)**: Offline caching and installable web app experience.

## System Requirements

- PHP 8.1+
- MySQL 8.0+ or MariaDB
- Composer (for minor internal dependencies)
- Web Server (Apache/Nginx)
- Cron access (for scheduled jobs)

## Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-org/syncro.git
   cd syncro
   ```

2. **Database Setup:**
   Create a new MySQL database named `syncro`.
   Initialize the database schema and seed data using the Phinx migration system:
   ```bash
   vendor/bin/phinx migrate
   ```

3. **Environment Configuration:**
   Copy `.env.example` to `.env` and fill in your database credentials and API keys.
   ```bash
   cp .env.example .env
   ```

4. **Web Server Setup:**
   Point your virtual host document root to the `syncro` folder.
   Ensure that `AllowOverride All` is set for Apache so the `.htaccess` routes requests to `index.php`.

5. **Cron Job (Night Audit):**
   Add the following entry to your server's crontab to run the night audit script daily at 2:00 AM:
   ```bash
   0 2 * * * php /path/to/syncro/scripts/night_audit.php >> /path/to/syncro/logs/cron.log 2>&1
   ```

## Architecture Notes

Syncro uses a custom lightweight MVC architecture with a central `index.php` front controller.
- **Controllers** handle HTTP requests.
- **Services** encapsulate core business logic.
- **Views** use native PHP templates with TailwindCSS for styling.
- **Security** is maintained using a custom `SecurityManager` for input sanitization and XSS protection.

## License

Syncro is proprietary software. All rights reserved.
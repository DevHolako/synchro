# Deploying Synchro on Coolify

This guide covers deploying Synchro on [Coolify](https://coolify.io/) using either a full **Docker Compose Stack** (recommended) or a **Single Container Application** with Coolify managed databases.

---

## 🏛️ Architecture Overview

Synchro is powered by **FrankenPHP** (Caddy with PHP 8.5 embedded), providing native performance, HTTP/3, and automatic compression.

In production, the stack requires:
1. **Web Container (`app`)**: FrankenPHP serving HTTP traffic on port 80. Runs migrations automatically on boot.
2. **Worker Container (`horizon`)**: Supervised queue worker processing notifications, Excel imports, and document jobs on Redis.
3. **Scheduler Container (`scheduler`)**: Runs cron jobs (`horizon:snapshot`, exam completion, imports cleanup).
4. **Database (`mysql`)**: MySQL 8.0.
5. **Redis (`redis`)**: Cache, sessions, and Horizon queue pipeline.

---

## 🚀 Deployment Method 1: Docker Compose (Recommended)

This method spins up the complete Synchro stack (`app`, `horizon`, `scheduler`, `redis`, `mysql`, and optional `phpmyadmin`) using Docker Compose.

### Step 1: Create the Resource in Coolify
1. In your Coolify dashboard, select your **Project** and **Environment**.
2. Click **+ Add Resource** → Select **Docker Compose**.
3. Choose your Git Source (e.g. GitHub/GitLab repository: `synchro`), branch `main`.
4. Compose File Location: `docker-compose.yml` (default).

### Step 2: Configure Environment Variables
In the **Environment Variables** tab of your new Coolify resource, copy values from [`.env.docker.example`](file:///.env.docker.example) and configure:

```ini
APP_NAME=Synchro
APP_ENV=production
APP_DEBUG=false
APP_URL=https://synchro.yourdomain.com

# Generate a strong key (e.g., using: php artisan key:generate --show)
APP_KEY=base64:...

APP_LOCALE=fr
SCHEDULE_TIMEZONE=Africa/Casablanca

# Bundled services to run inside Compose
COMPOSE_PROFILES=mysql,redis

# Database settings for the bundled mysql service
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=synchro
DB_USERNAME=synchro
DB_PASSWORD=your_strong_db_password
DB_ROOT_PASSWORD=your_strong_root_password

# Redis settings for the bundled redis service
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true

# Outgoing Mail (SMTP for invitations and notices)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_FROM_ADDRESS="no-reply@isga.ma"
MAIL_FROM_NAME="Synchro ISGA"

# Urgent Message Gateway (Part 06): log, database, twilio, or whatsapp
URGENT_MESSAGES_DRIVER=log
```

### Step 3: Configure Domain & Routing
1. Go to the **General** settings tab in Coolify.
2. In the domain settings for the `app` service, enter your public domain (e.g. `https://synchro.yourdomain.com`).
3. Port mapping is pre-configured on port **`80`**. Coolify's reverse proxy will automatically provision a Let's Encrypt SSL certificate and route HTTPS traffic to the container.

### Step 4: Deploy
Click **Deploy**!
Coolify will:
1. Build the production multi-stage Docker image (`Dockerfile`).
2. Start `mysql` and `redis` with health checks.
3. Start `app`, run `php artisan migrate --force`, and boot FrankenPHP.
4. Start `horizon` and `scheduler`.

---

## ⚡ Deployment Method 2: Single Container (with Coolify Managed MySQL & Redis)

If you prefer using Coolify's 1-click Managed Databases (PostgreSQL/MySQL & Redis), you can deploy Synchro as a single application container.

### Step 1: Provision MySQL and Redis in Coolify
1. In Coolify, click **+ Add Resource** → **Databases** → **MySQL** (version 8.0).
2. Click **+ Add Resource** → **Databases** → **Redis**.
3. Note the internal hostnames, ports, and credentials created by Coolify.

### Step 2: Create the Application Container
1. Click **+ Add Resource** → **Public/Private Git Repository**.
2. Select your repository, branch `main`.
3. Build Pack: select **Dockerfile**.

### Step 3: Configure Environment Variables
Set the following environment variables in Coolify:

```ini
APP_NAME=Synchro
APP_ENV=production
APP_DEBUG=false
APP_URL=https://synchro.yourdomain.com
APP_KEY=base64:...

# Enable Single-Container Mode (runs Web + Horizon + Scheduler in 1 container)
CONTAINER_ROLE=all

# Connect to Coolify's Managed MySQL
DB_CONNECTION=mysql
DB_HOST=mysql-internal-hostname
DB_PORT=3306
DB_DATABASE=synchro
DB_USERNAME=coolify_user
DB_PASSWORD=coolify_password

# Connect to Coolify's Managed Redis
REDIS_CLIENT=phpredis
REDIS_HOST=redis-internal-hostname
REDIS_PORT=6379
REDIS_PASSWORD=coolify_redis_password
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
```

### Step 4: Configure Storage & Port
1. Under **Ports Exposes**, set `80`.
2. Under **Storage / Persistent Volumes**, map:
   - Volume: `synchro_storage`
   - Destination Path: `/app/storage`
3. Click **Deploy**.

The entrypoint script will detect `CONTAINER_ROLE=all`, verify database connectivity, run migrations, spawn Horizon and Scheduler in the background, and start FrankenPHP.

---

## 🛠️ Post-Deployment Administration

Once your deployment is green in Coolify:

### 1. Seed Demo Data (Optional)
To populate the database with a complete academic structure, 300+ students, timetables, and exams:
1. Open the Coolify dashboard for the `app` container.
2. Go to **Terminal** (or click **Execute Command**).
3. Run:
   ```bash
   php artisan db:seed --class=FakeDataSeeder
   ```

### 2. Initial Administrator Account
If starting with an empty database:
1. In the container terminal, open Tinker:
   ```bash
   php artisan tinker
   ```
2. Create your administrator account:
   ```php
   $user = \App\Models\User::create([
       'name' => 'Admin ISGA',
       'email' => 'admin@isga.ma',
       'password' => bcrypt('SecurePassword123!'),
       'role' => \App\Enums\UserRole::Administrator,
       'status' => \App\Enums\AccountStatus::Active,
       'activated_at' => now(),
   ]);
   ```
3. Log in at `https://synchro.yourdomain.com/login`.

### 3. Monitor Queues
- Navigate to `https://synchro.yourdomain.com/horizon`.
- Monitored queues: `notifications` (instant alerts & emails), `imports` (bulk Excel/CSV), `default` (PDF document generation).
- Access is strictly permission-gated to administrators holding `Permission::MonitorQueues`.

### 4. Native Mobile API
The versioned REST API is available for mobile apps at:
- `https://synchro.yourdomain.com/api/v1/auth/login`
- `https://synchro.yourdomain.com/api/v1/schedules/my-schedule`
- `https://synchro.yourdomain.com/api/v1/exams/my-exams`
- `https://synchro.yourdomain.com/api/v1/check-in/scan` (QR door scanning)

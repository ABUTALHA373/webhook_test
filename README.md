# HookForge &bull; Dynamic Webhook Testing &amp; Callback Engine

[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Tests](https://img.shields.io/badge/Tests-17%20Passed-10B981?logo=checkmarx&logoColor=white)](tests/)

HookForge is an enterprise-grade, high-profile platform designed to test incoming webhooks, inspect complex payloads with microsecond precision, simulate real-world service latencies and failure modes, and execute **fully dynamic automated callbacks** with HMAC signatures and exponential retry policies.

---

## Key Capabilities

### 1. Dynamic Webhook Ingestion (`/hook/{slug}/{any?}`)
- **Universal HTTP Support**: Ingests `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`, `HEAD`.
- **Full Traceability**: Captures raw bytes, parsed JSON/form bodies, full request headers, query string parameters, IP address, and server roundtrip time.
- **Dynamic Response Engine**:
  - Configurable HTTP status codes (`200`, `201`, `204`, `400`, `429`, `500`).
  - **Artificial Latency Simulation**: Test downstream client timeouts (0ms to 30,000ms).
  - **Dynamic Response Templating**: Interpolate runtime variables into response headers and body (e.g., `{{req.id}}`, `{{timestamp_iso}}`, `{{body.order_id}}`).
  - **Conditional Override Rules**: Return custom responses when specific payload conditions are met (e.g. if `body.event == 'charge.failed'`, return HTTP `422`).

### 2. Automated Dynamic Callbacks &amp; Relays
- **Dynamic Target Resolution**: Resolve the callback target URL dynamically from the incoming request payload (e.g., `{{body.callback_url}}` or `{{headers.x-callback-url}}`).
- **Dynamic Payload Transformation**: Pass through raw payloads or generate custom JSON envelopes containing runtime context.
- **Cryptographic Signatures**: Built-in HMAC generation supporting `SHA-256`, `SHA-1`, `SHA-512`, with `hex`, `base64`, `prefix_hex` (GitHub-style `sha256=...`), and `stripe` (`t=...,v1=...`) formats.
- **Resilient Retry Policy**: Automatic background job retry with configurable exponential backoff and comprehensive audit logs.

### 3. Outbound Webhook Dispatcher / Simulator
- Fire test webhooks directly to your own local or remote APIs.
- Pre-loaded industry standard presets:
  - **Stripe**: `payment_intent.succeeded`, `charge.failed`
  - **GitHub**: `push` event
  - **Shopify**: `orders/create`
- One-click single dispatch or **Burst Stress Testing** (5x concurrent execution).

### 4. Real-Time Inspection Dashboard
- Dual-pane split view inspired by Linear, Stripe, and Cloudflare.
- Real-time **Server-Sent Events (SSE)** streaming (`/api/stream`) for zero-polling instant live feed updates.
- 1-click **Copy as cURL**, **Copy as JavaScript Fetch**, and interactive **Replay Webhook** dialog.

---

## Architecture Overview

```
                      +---------------------------------------+
                      |       Incoming Webhook Client         |
                      +---------------------------------------+
                                          |
                                          | (POST /hook/{slug})
                                          v
                      +---------------------------------------+
                      |      WebhookIngestionController       |
                      +---------------------------------------+
                                    |           |
            +-----------------------+           +-----------------------+
            |                                                           |
            v                                                           v
+-----------------------+                                   +-----------------------+
| DynamicTemplateEngine |                                   |     MySQL wh_test     |
| (Interpolation & Rules)                                   | (Endpoints, Requests, |
+-----------------------+                                   |     Audit Logs)       |
            |                                               +-----------------------+
            v                                                           |
+-----------------------+                                               |
|  Simulated Response   |                                               v
| (Status, Delay, Body) |                                   +-----------------------+
+-----------------------+                                   |  ExecuteCallbackJob   |
                                                            | (Queued / Sync Relay) |
                                                            +-----------------------+
                                                                        |
                                                                        v
                                                            +-----------------------+
                                                            | Dynamic Target URL    |
                                                            | (with HMAC Signature) |
                                                            +-----------------------+
```

---

## Quick Start &amp; Setup

### Prerequisites
- **PHP 8.4+** with PDO MySQL & OpenSSL extensions enabled.
- **Composer 2.x**.
- **MySQL 8.x** running on `127.0.0.1:3306` with database `wh_test` and user `root` (empty password).

### 1. Database Configuration
Ensure your `.env` contains:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wh_test
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database
```

### 2. Run Database Migrations &amp; Seed Default Endpoints
```sh
php artisan migrate --seed
```

### 3. Start the Webhook Server &amp; Background Queue Worker
In your terminal, start the web server:
```sh
php artisan serve --port=8000
```

In a second terminal, start the queue worker to process asynchronous callbacks:
```sh
php artisan queue:work --sleep=1 --tries=3
```

Open your browser to:
```
http://localhost:8000/
```

---

## Quick cURL Recipes

### Send a Standard Webhook
```sh
curl -X POST "http://localhost:8000/hook/default" \
  -H "Content-Type: application/json" \
  -H "X-Client-Trace: cl_9921" \
  -d '{
    "event": "order.completed",
    "id": "ord_992819",
    "amount": 149.50,
    "customer": {
      "name": "Jane Doe",
      "email": "jane@example.com"
    }
  }'
```

### Trigger an Automated Dynamic Outbound Callback
Pass a dynamic callback URL in the payload. HookForge's configured rule will automatically forward an HMAC-signed event to that URL:
```sh
curl -X POST "http://localhost:8000/hook/default" \
  -H "Content-Type: application/json" \
  -d '{
    "event": "payment.succeeded",
    "id": "pay_554422",
    "callback_url": "http://localhost:8000/hook/stripe-mock"
  }'
```

---

## Running the Automated Test Suite

Run the full PHPUnit test suite:
```sh
php artisan test --compact
```

Format code according to Laravel standards:
```sh
vendor/bin/pint --format agent
```

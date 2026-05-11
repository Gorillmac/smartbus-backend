# SmartBus Backend With Ngrok

Use this when the frontend is hosted online, but the backend is running on a PC with XAMPP.

## 1. Pull Backend From GitHub

On the backend PC:

```bash
cd C:\xampp\htdocs
git clone https://github.com/Gorillmac/smartbus-backend.git smartbus-backend
```

If the folder already exists:

```bash
cd C:\xampp\htdocs\smartbus-backend
git pull
```

## 2. Start XAMPP

Open XAMPP Control Panel and start:

```text
Apache
MySQL
```

## 3. Run One-Time Setup

Open:

```text
http://localhost/smartbus-backend/public/setup.php
```

Use:

```text
MySQL Host: 127.0.0.1
Port: 3306
Database Name: smartbus
Username: root
Password: leave empty
Backend URL: http://localhost/smartbus-backend/public
Allowed Frontend Origin: *
```

Click `Run One-Time Setup`.

Test:

```text
http://localhost/smartbus-backend/public/api/health
```

## 4. Start Ngrok

Install ngrok and add your auth token once:

```bash
ngrok config add-authtoken YOUR_NGROK_TOKEN
```

Start a tunnel to XAMPP Apache:

```bash
ngrok http 80
```

Ngrok will show a public HTTPS URL, for example:

```text
https://abc123.ngrok-free.app
```

Your public API URL becomes:

```text
https://abc123.ngrok-free.app/smartbus-backend/public/api
```

Test:

```text
https://abc123.ngrok-free.app/smartbus-backend/public/api/health
```

## 5. Connect Hosted Frontend

Open the hosted frontend with this format:

```text
https://your-frontend-site.com/?api=https://abc123.ngrok-free.app/smartbus-backend/public/api
```

The frontend saves that API URL in browser storage, so normal navigation continues to use it.

## 6. Demo Logins

```text
Passenger: user@smartbus.com / password123
Driver: driver@smartbus.com / driver123
Admin: admin@smartbus.com / admin123
```

## Important

Free ngrok URLs can change when ngrok restarts. If the ngrok URL changes, open the frontend again with the new `?api=` URL.

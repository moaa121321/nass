# Mini Online Shop (PHP) — MySQL Integration

Short demo application: `signup.php`, `login.php`, `index.php`.

Important files:
- `config.php` — Creates PDO connection using `DATABASE_URL`.
- `db_init.php` — When executed, creates the `users` table.

Quick start (development):

1) Optional: Set the `DATABASE_URL` environment variable. Example format:

```
mysql://user:pass@host:port/dbname?ssl-mode=REQUIRED
```

2) Create the database table:

```bash
php db_init.php
```

3) Run the application:

```bash
php -S localhost:8000
```

4) Open in browser: http://localhost:8000

Notes:
- `signup.php` now takes the `address` field and registers the user in the MySQL `users` table.
- `login.php` validates from the database.

DB SSL and connection notes:
- If you get a connection error, first make sure PHP extensions are installed:

```bash
php -m | grep -E "pdo|pdo_mysql|openssl"
```

- Remote MySQL servers usually require TLS (SSL). `config.php` reads the `ssl-mode` parameter in the URL and tries to use the common CA bundle path on the system if available. If you get a TLS error, specify the CA bundle path or get the CA file from the server administrator.
- During development, you can test the connection with this command:

- If a CA certificate is provided with the project, I saved it as `certs/ca.pem`; `config.php` tries to use this file first.
- If you want to add your own CA file, put the file in the `certs/ca.pem` path and give appropriate access permissions (e.g. `chmod 644 certs/ca.pem`).

During development, you can test the connection with this command:

```bash
php -r "require 'config.php'; echo 'OK';"
```

Connecting with public MySQL client (example):

```bash
mysql --user=upadmin --password=AVNS_i6tNjDLSLM6RPUXvlTP \
	--host=public-test-dgsyoehcssyt.db.upclouddatabases.com --port=11569 defaultdb \
	--ssl-ca=certs/ca.pem --ssl-mode=REQUIRED
```

Note: Writing the password directly in the command creates a security risk; if possible, remove the `--password` parameter and let the client ask for the password.

# fjhsdgfjhksdf
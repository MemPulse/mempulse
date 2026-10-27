# Password reset setup

1. Install the project dependencies from the repository root:

   ```sh
   composer install --no-interaction
   ```

2. Run `authentication/password_reset_schema.sql` once against `mempulse_db`.

3. Configure these environment variables for the PHP process running Apache:

   - `MEMPULSE_PUBLIC_URL`: public application origin and base path, without a trailing slash; for local XAMPP, `http://localhost/mempulse`.
   - `MEMPULSE_SMTP_HOST`: SMTP server hostname.
   - `MEMPULSE_SMTP_PORT`: SMTP port; defaults to `587` for TLS and `465` for SSL.
   - `MEMPULSE_SMTP_USERNAME` and `MEMPULSE_SMTP_PASSWORD`: SMTP credentials. Both may be empty only when the SMTP server allows unauthenticated relay.
   - `MEMPULSE_SMTP_ENCRYPTION`: `tls` (default), `ssl`, or `none`.
   - `MEMPULSE_SMTP_FROM_EMAIL`: verified sender address.
   - `MEMPULSE_SMTP_FROM_NAME`: optional sender label; defaults to `MemPulse`.

Do not commit SMTP credentials. Restart Apache after changing its environment. Until the database table, Composer dependency, public URL, and SMTP settings are configured, requests still show the same generic confirmation message for privacy, while the reason email delivery failed is written to the PHP error log.

# College Event Hub

A responsive college event portal built with PHP, MongoDB Atlas, HTML, CSS, and JavaScript.

## Features

- A role-selection home page with separate student and admin portals
- Student profiles with unique roll number, branch, section, year, and phone
- Student dashboard with upcoming events and registration history
- Admin dashboard with student/event/registration totals and recent activity
- Admin event create, edit, delete, poster upload, deadline, and participant limit
- Student registration requests with pending/confirmed/rejected statuses
- Admin participant search, branch/section filters, and CSV export
- Password hashing, CSRF protection, session rotation, and role checks

## Requirements and setup

- PHP 8.1+, Composer, and the PHP MongoDB extension (`ext-mongodb`)
- MongoDB Atlas (or another MongoDB deployment)

From the project folder, install dependencies if needed:

```powershell
php composer.phar install
```

Set the connection URI in the same PowerShell session used to run PHP. Keep it private:

```powershell
$env:MONGODB_URI = 'mongodb+srv://<username>:<password>@<cluster-host>/?retryWrites=true&w=majority'
php -S 127.0.0.1:8000
```

Open `http://127.0.0.1:8000`. The application uses the `college_event_db` database and the `students`, `admins`, `events`, and `registrations` collections. It creates required indexes when the relevant account or registration flow is used.

## Admin account setup

On a fresh database with no admins, the Admin portal offers a one-time public form to create the first admin using an admin name and password. The form closes after an admin account exists. **Anyone who can reach the site can claim that first account**, so do not expose the site publicly before initial setup. For production, create the first admin with the CLI before opening the site; the public first-admin form will then remain closed.

To provision an admin from a PowerShell session where `MONGODB_URI` is set (or set it again in a second terminal), enter the password securely so it is not placed in command history:

```powershell
$securePassword = Read-Host "Admin password (12–72 characters)" -AsSecureString
$passwordPointer = [IntPtr]::Zero
try {
    $passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
    $env:ADMIN_INITIAL_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)
    php scripts/create_admin.php campusadmin "Campus Administrator"
} finally {
    Remove-Item Env:ADMIN_INITIAL_PASSWORD -ErrorAction SilentlyContinue
    if ($passwordPointer -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
    }
}
```

Admin sign-in accepts the admin name or CLI username with its password. Student accounts are created from the Student portal and include roll number, name, branch, section, year, phone, and password. New event registrations begin as pending and reserve a participant place until an admin confirms or rejects them.

## Event posters and deployment

Posters are validated JPG, PNG, or WebP images (up to 5 MB) and stored under `uploads/events/`. If PHP's upload limits are lower than 5 MB, raise `upload_max_filesize` and `post_max_size` in `php.ini`. Keep the upload directory writable and persistent in production. Use HTTPS in deployment so session cookies are secure. Never commit MongoDB credentials or uploaded private data.

Run `php -m` to confirm `mongodb` is enabled and `php composer.phar check-platform-reqs` to check PHP platform requirements.

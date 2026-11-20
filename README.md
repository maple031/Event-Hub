# Campus EventHub — College Event Management System

A responsive college event management web app built with HTML, CSS, PHP, XML and MongoDB Atlas.

## Features
- Dashboard with student, event and registration totals
- Add and list students
- Create and list campus events
- Register a student for an event
- Prevent duplicate student/event registrations
- Registration report
- Responsive layout for desktop and mobile

## Requirements
- PHP 8.1+
- Composer
- PHP MongoDB extension (`ext-mongodb`)
- MongoDB Atlas cluster (or MongoDB server)

## Setup
1. Extract this project folder.
2. Install/enable the PHP MongoDB extension. Check with `php -m` and confirm `mongodb` appears.
3. In this folder, install the PHP dependencies: `php composer.phar install` (or `composer install` if Composer is installed globally).
4. In PowerShell, set your MongoDB Atlas connection string for this terminal session. Keep it private; do not commit it or share screenshots containing it:
   ```powershell
   $env:MONGODB_URI = 'mongodb+srv://<username>:<password>@<cluster-host>/?retryWrites=true&w=majority'
   ```
   Replace the placeholders with your Atlas database user's details. URL-encode special characters in the password.
5. In MongoDB Atlas, ensure the database user exists and your current IP is allowed under Network Access.
6. Start the app from the project folder: `php -S 127.0.0.1:8000`
7. Open `http://127.0.0.1:8000`. The header should say **MongoDB connected**. If it says **not configured**, check that `MONGODB_URI` was set in the same PowerShell window that started PHP.

The database `college_event_db` and its collections are created automatically when the first records are inserted.

To confirm PHP and Composer dependencies are available, run `php -m` and check for `mongodb`, then run `php composer.phar check-platform-reqs`.

## Important security note
For a real deployment, read the URI from an environment variable rather than hard-coding it. Never commit your real connection string or database password to GitHub.

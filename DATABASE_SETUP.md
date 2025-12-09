# Online Manipal Website - Database Setup Instructions

## Files Overview

### PHP Files Created:
1. **config.php** - Database configuration file
2. **setup_database.php** - One-time database setup script
3. **submit_lead.php** - Hero form submission handler
4. **submit_brochure.php** - Brochure download form handler
5. **submit_application.php** - Apply Now form handler

### Database Tables:
1. **leads** - Unified table storing all form submissions (hero, brochure, application)

## Setup Instructions for cPanel Hosting:

### Step 1: Create MySQL Database

1. Log in to your **cPanel**
2. Go to **MySQL Databases**
3. Create a new database:
   - Database name: `manipal_db` (or any name you prefer)
4. Create a database user:
   - Username: Choose a username
   - Password: Create a strong password
5. Add the user to the database with **ALL PRIVILEGES**
6. Note down:
   - Database name
   - Database username
   - Database password
   - Database host (usually `localhost`)

### Step 2: Configure Database Connection

1. Open **config.php** file
2. Update the following lines with your database details:

```php
define('DB_HOST', 'localhost'); // Usually 'localhost'
define('DB_USER', 'your_cpanel_username'); // Replace with your database username
define('DB_PASS', 'your_database_password'); // Replace with your database password
define('DB_NAME', 'your_database_name'); // Replace with your database name
```

### Step 3: Upload Files to cPanel

1. Go to **File Manager** in cPanel
2. Navigate to `public_html` folder (or your website's root directory)
3. Upload all website files including:
   - index.html
   - script.js
   - styles.css
   - config.php
   - setup_database.php
   - submit_lead.php
   - submit_brochure.php
   - submit_application.php
   - images/ folder

### Step 4: Run Database Setup

1. Open your browser and go to:
   ```
   https://yourdomain.com/setup_database.php
   ```
2. This will create all necessary tables in your database
3. You should see a success message
4. **IMPORTANT:** After successful setup, delete the `setup_database.php` file for security

### Step 5: Test the Forms

1. Visit your website: `https://yourdomain.com`
2. Test all three forms:
   - Hero section enrollment form
   - Brochure download modal
   - Apply Now modal
3. Check your database to verify data is being saved

### Step 5: Verify Database Entries

1. In cPanel, go to **phpMyAdmin**
2. Select your database
3. Check the **leads** table
4. You should see your test entries with different `form_type` values:
   - 'hero' - from hero section form
   - 'brochure' - from brochure download modal
   - 'application' - from Apply Now modal

## Security Recommendations:

1. **Delete setup_database.php** after running it once
2. Make sure **config.php** is not accessible directly by adding this to .htaccess:
   ```
   <Files config.php>
   Order Allow,Deny
   Deny from all
   </Files>
   ```
3. Use strong database passwords
4. Regularly backup your database
5. Consider adding CAPTCHA to prevent spam submissions

## Troubleshooting:

### If forms are not submitting:
1. Check browser console (F12) for JavaScript errors
2. Verify PHP files are uploaded correctly
3. Check config.php has correct database credentials
4. Ensure database user has proper permissions

### If you see "Database connection failed":
1. Verify database credentials in config.php
2. Check if database user is added to the database
3. Confirm database exists in cPanel

### If setup_database.php shows errors:
1. Check database permissions
2. Verify MySQL version compatibility
3. Check PHP error logs in cPanel

## Viewing Submitted Data:

### Option 1: phpMyAdmin (in cPanel)
1. Go to phpMyAdmin
2. Select your database
3. Click on table name to view entries

### Option 2: Create Admin Panel (Optional)
You can create a simple admin page to view submissions. Let me know if you need this.

## Database Structure:

### leads table (unified for all forms):
- id (INT, Auto Increment, Primary Key)
- full_name (VARCHAR 255)
- email (VARCHAR 255)
- phone (VARCHAR 20)
- course (VARCHAR 255)
- form_type (ENUM: 'hero', 'brochure', 'application')
- consent (TINYINT)
- created_at (TIMESTAMP)

**Note:** The `form_type` field identifies which form the submission came from:
- 'hero' = Hero section enrollment form
- 'brochure' = Brochure download modal
- 'application' = Apply Now modal

## Support:

If you encounter any issues during setup, check:
1. PHP version (should be 7.0 or higher)
2. MySQL version (should be 5.6 or higher)
3. Error logs in cPanel
4. Browser console for JavaScript errors

---

**Note:** All forms now submit data to the MySQL database. Make sure to test thoroughly after setup!

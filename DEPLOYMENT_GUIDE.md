# BookingGo Deployment Guide for CloudPanel VPS (Tailored)

## Prerequisites Checklist
- ✅ Linux VPS with CloudPanel installed
- ✅ Domain name pointed to your VPS IP
- ✅ SSH access to your server
- ✅ Root or sudo access

---

## Quick Vars (copy-paste first)

Paste this once per SSH session so all commands below just work:

```bash
SITE_USER=amaddiagnosticcentre-portal
DOMAIN=portal.amaddiagnosticcentre.com.pk
SITE_ROOT=/home/$SITE_USER/htdocs/$DOMAIN
PUBLIC_ROOT=$SITE_ROOT/public
```

You can verify paths with:

```bash
echo "$SITE_USER | $SITE_ROOT | $PUBLIC_ROOT"
```

## STEP 1: Create Database in CloudPanel

### 1.1 Login to CloudPanel
Open browser: `https://your-server-ip:8443`

### 1.2 Create Database
1. Click **Databases** in left menu
2. Click **Add Database**
3. Fill in:
   - **Database Name**: `bookinggo_db`
   - **Database User**: `bookinggo_user`
   - **Password**: Generate strong password and **SAVE IT!**
4. Click **Add Database**

**⚠️ IMPORTANT**: Save these credentials:
```
Database Name: bookinggo_db
Database User: bookinggo_user
Database Password: [YOUR_PASSWORD_HERE]
Database Host: localhost
```

---

## STEP 2: Create Website in CloudPanel

### 2.1 Add Site
1. Click **Sites** in left menu
2. Click **Add Site**
3. Fill in:
   - **Domain Name**: `portal.amaddiagnosticcentre.com.pk`
   - **Site User**: `amaddiagnosticcentre-portal`
   - **PHP Version**: Select **PHP 8.3** (8.2+ required)
   - **Vhost Template**: Laravel
4. Click **Add Site**

### 2.2 Note Your Paths
CloudPanel will create:
- **Site Root**: `/home/amaddiagnosticcentre-portal/htdocs/portal.amaddiagnosticcentre.com.pk`
- **Public Root**: `/home/amaddiagnosticcentre-portal/htdocs/portal.amaddiagnosticcentre.com.pk/public`

---

## STEP 3: Upload Project Files

### Option A: Using FileZilla/WinSCP (Recommended for beginners)

1. **Download FileZilla**: https://filezilla-project.org/download.php?type=client
2. **Connect to your server**:
   - Host: `sftp://your-server-ip`
   - Username: `root` (or your SSH user)
   - Password: Your SSH password
   - Port: `22`

3. **Upload files**:
   - Navigate LOCAL side to: `C:\laragon\www\bookinggo\`
   - Navigate REMOTE side to: `/home/amaddiagnosticcentre-portal/htdocs/portal.amaddiagnosticcentre.com.pk/`
   - Delete the empty folders on remote
   - Select ALL files/folders locally and drag to remote
   - Wait for upload to complete (may take 15-30 minutes)

### Option B: Using Git (Faster)

```bash
# SSH into your server
ssh root@your-server-ip

# Use the variables defined above (paste them if not set)
SITE_USER=amaddiagnosticcentre-portal; DOMAIN=portal.amaddiagnosticcentre.com.pk; SITE_ROOT=/home/$SITE_USER/htdocs/$DOMAIN

# Navigate to site directory
cd "$SITE_ROOT"

# Safely remove all existing contents (keeps the directory itself)
find . -mindepth 1 -maxdepth 1 -exec rm -rf {} +

# Clone from GitHub
git clone https://github.com/jerryboganda/bookinggo.git .

# If private repo, you'll need to authenticate
```

---

## STEP 4: SSH Commands Setup

Connect to your server via SSH:
```bash
ssh root@your-server-ip
```

Now run these commands **one by one**:

### 4.1 Navigate to Project Directory
```bash
cd "$SITE_ROOT"
```

### 4.2 Set Correct Ownership
```bash
chown -R "$SITE_USER":"$SITE_USER" "$SITE_ROOT"
```

### 4.3 Set Directory Permissions
```bash
find "$SITE_ROOT" -type d -exec chmod 755 {} \;
find "$SITE_ROOT" -type f -exec chmod 644 {} \;
```

### 4.4 Set Storage Permissions
```bash
chmod -R 775 storage bootstrap/cache
chown -R "$SITE_USER":"$SITE_USER" storage bootstrap/cache
```

---

## STEP 5: Install Composer Dependencies

### 5.1 Check PHP Version
```bash
php -v
```
Should show PHP 8.3.x

### 5.2 Install Composer Dependencies
```bash
cd "$SITE_ROOT"
sudo -u "$SITE_USER" composer install --no-dev --optimize-autoloader
```

**If you see "composer: command not found"**, install it:
```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
composer --version
```

Then retry the composer install command above.

---

## STEP 6: Configure Environment File

### 6.1 Copy Environment File
```bash
cd "$SITE_ROOT"
cp .env.example .env
```

### 6.2 Edit .env File
```bash
nano .env
```

### 6.3 Update These Values (Use Arrow keys to navigate, Ctrl+O to save, Ctrl+X to exit):

```env
APP_NAME="BookingGo"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://portal.amaddiagnosticcentre.com.pk

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bookinggo_db
DB_USERNAME=bookinggo_user
DB_PASSWORD=YOUR_DATABASE_PASSWORD_HERE

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_DRIVER=file
```

**Press**: `Ctrl + O` → `Enter` → `Ctrl + X`

---

## STEP 7: Generate Application Key

```bash
cd "$SITE_ROOT"
sudo -u "$SITE_USER" php artisan key:generate
```

---

## STEP 8: Run Database Migrations

### 8.1 Clear Config Cache
```bash
sudo -u "$SITE_USER" php artisan config:clear
sudo -u "$SITE_USER" php artisan cache:clear
```

### 8.2 Run Migrations
```bash
sudo -u "$SITE_USER" php artisan migrate --force
```

### 8.3 Seed Database (if needed)
```bash
sudo -u "$SITE_USER" php artisan db:seed --force
```

---

## STEP 9: Optimize Laravel

```bash
cd "$SITE_ROOT"

# Generate optimized autoload
sudo -u "$SITE_USER" composer dump-autoload --optimize

# Cache configuration
sudo -u "$SITE_USER" php artisan config:cache

# Cache routes
sudo -u "$SITE_USER" php artisan route:cache

# Cache views
sudo -u "$SITE_USER" php artisan view:cache

# Optimize
sudo -u "$SITE_USER" php artisan optimize
```

---

## STEP 10: Create Storage Link

```bash
cd "$SITE_ROOT"
sudo -u "$SITE_USER" php artisan storage:link
```

---

## STEP 11: Set Up SSL Certificate (CloudPanel)

### 11.1 In CloudPanel Dashboard:
1. Go to **Sites** → Click `portal.amaddiagnosticcentre.com.pk`
2. Click **SSL/TLS** tab
3. Click **Actions** → **New Let's Encrypt Certificate**
4. Click **Create and Install**

### 11.2 Wait 2-3 minutes for certificate issuance

### 11.3 Enable HTTPS Redirect:
1. In same SSL/TLS tab
2. Enable **HTTPS Redirect**
3. Click **Save**

---

## STEP 12: Configure Cron Jobs (Required for Laravel)

```bash
crontab -e -u "$SITE_USER"
```

Add this line at the bottom:
```
* * * * * cd /home/amaddiagnosticcentre-portal/htdocs/portal.amaddiagnosticcentre.com.pk && php artisan schedule:run >> /dev/null 2>&1
```

Save: Press `Esc`, type `:wq`, press `Enter`

---

## STEP 13: Restart Services

```bash
systemctl restart nginx
# Auto-detect and restart the PHP-FPM service (works for 8.2/8.3/etc.)
PHPSVC=$(systemctl list-units --type=service --all | awk '/php.*fpm/ {print $1; exit}')
systemctl restart "$PHPSVC"
```

---

## STEP 14: Test Your Installation

### 14.1 Visit Your Domain
Open browser: `https://portal.amaddiagnosticcentre.com.pk`

### 14.2 Check Installation Page
If you see Laravel installer, complete it with:
- Database details from Step 1
- Admin credentials

### 14.3 Login
Default credentials (if seeded): See `INSTALLATION_CREDENTIALS.txt` in the project root.

---

## TROUBLESHOOTING

### Issue: 500 Internal Server Error

**Fix 1: Check Permissions**
```bash
cd "$SITE_ROOT"
chmod -R 775 storage bootstrap/cache
chown -R "$SITE_USER":"$SITE_USER" storage bootstrap/cache
```

**Fix 2: Check Error Logs**
```bash
tail -f /home/$SITE_USER/logs/$DOMAIN/error.log
```

**Fix 3: Clear All Caches**
```bash
cd "$SITE_ROOT"
sudo -u "$SITE_USER" php artisan cache:clear
sudo -u "$SITE_USER" php artisan config:clear
sudo -u "$SITE_USER" php artisan view:clear
sudo -u "$SITE_USER" php artisan route:clear
```

### Issue: Database Connection Error

**Check database credentials in .env**
```bash
nano /home/bookinggo/htdocs/yourdomain.com/.env
```

**Test database connection**
```bash
mysql -u bookinggo_user -p bookinggo_db
# Enter password when prompted
# If successful, type: exit
```

### Issue: CSS/JS Not Loading

**Regenerate storage link**
```bash
cd "$SITE_ROOT"
rm -f public/storage
sudo -u "$SITE_USER" php artisan storage:link
```

**Set public permissions**
```bash
chmod -R 755 "$PUBLIC_ROOT"
```

### Issue: Permission Denied Errors

**Reset all permissions**
```bash
cd "$SITE_ROOT"
chown -R "$SITE_USER":"$SITE_USER" .
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache
chown -R "$SITE_USER":"$SITE_USER" storage bootstrap/cache
```

### Issue: Composer Memory Limit

```bash
export COMPOSER_MEMORY_LIMIT=-1
sudo -u "$SITE_USER" composer install --no-dev --optimize-autoloader
```

### Issue: Node Modules/Assets Missing

**If you need to build assets**
```bash
cd "$SITE_ROOT"

# Install Node.js if not present
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

# Install dependencies and build
sudo -u "$SITE_USER" npm install
sudo -u "$SITE_USER" npm run build
```

---

## STEP 15: Post-Deployment Security

### 15.1 Disable Directory Listing
Already handled by CloudPanel's Laravel vhost template

### 15.2 Protect .env File
```bash
chmod 600 "$SITE_ROOT/.env"
chown "$SITE_USER":"$SITE_USER" "$SITE_ROOT/.env"
```

### 15.3 Set Production Mode
In `.env`:
```env
APP_ENV=production
APP_DEBUG=false
```

### 15.4 Clear Config Cache
```bash
cd "$SITE_ROOT"
sudo -u "$SITE_USER" php artisan config:cache
```

---

## MAINTENANCE COMMANDS

### Update Application
```bash
cd "$SITE_ROOT"
git pull origin main
sudo -u "$SITE_USER" composer install --no-dev --optimize-autoloader
sudo -u "$SITE_USER" php artisan migrate --force
sudo -u "$SITE_USER" php artisan optimize
PHPSVC=$(systemctl list-units --type=service --all | awk '/php.*fpm/ {print $1; exit}')
systemctl restart "$PHPSVC"
```

### View Application Logs
```bash
tail -f "$SITE_ROOT"/storage/logs/laravel.log
```

### Backup Database
```bash
mysqldump -u bookinggo_user -p bookinggo_db > backup_$(date +%Y%m%d).sql
```

### Restore Database
```bash
mysql -u bookinggo_user -p bookinggo_db < backup_20241229.sql
```

---

## IMPORTANT NOTES

1. **Replace** `yourdomain.com` with your actual domain everywhere
2. **Replace** `bookinggo_user` password with the actual password from Step 1
3. **Save** all credentials in a secure password manager
4. **Enable** CloudPanel firewall if not already enabled
5. **Set up** regular backups in CloudPanel (Backups section)
6. **Monitor** disk space: `df -h`
7. **Monitor** logs regularly for errors

---

## QUICK REFERENCE COMMANDS

```bash
# Navigate to project
cd "$SITE_ROOT"

# Clear all caches
sudo -u "$SITE_USER" php artisan cache:clear
sudo -u "$SITE_USER" php artisan config:clear
sudo -u "$SITE_USER" php artisan view:clear
sudo -u "$SITE_USER" php artisan route:clear

# Restart PHP (auto-detected service)
PHPSVC=$(systemctl list-units --type=service --all | awk '/php.*fpm/ {print $1; exit}')
systemctl restart "$PHPSVC"

# Restart Nginx
systemctl restart nginx

# Check PHP-FPM errors (auto-detected service log path may vary)
journalctl -u "$PHPSVC" -e

# Check Nginx errors
tail -f /var/log/nginx/error.log

# Check Laravel logs
tail -f storage/logs/laravel.log
```

---

## SUPPORT

If something goes wrong:
1. Check the Troubleshooting section above
2. Check Laravel logs: `storage/logs/laravel.log`
3. Check Nginx logs: `/home/$SITE_USER/logs/$DOMAIN/error.log`
4. Check PHP-FPM logs: `/var/log/php8.3-fpm.log`

**Need help?** Post the error message and which step you're on.

---

🎉 **Deployment Complete!** Your BookingGo application should now be live at https://yourdomain.com

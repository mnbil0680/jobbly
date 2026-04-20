# Jobbly Project - Complete Running & Testing Guide

## Prerequisites Check

Your project is located at: `C:\xampp\htdocs\jobbly`

XAMPP includes:
- **Apache** (Web Server)
- **PHP** (7.4+)
- **MySQL** (Database)

## Step 1: Start XAMPP Services

### Option A: Using XAMPP Control Panel (GUI)
1. Open **XAMPP Control Panel** (usually in Start Menu or `C:\xampp\xampp-control.exe`)
2. Click **Start** next to Apache
3. Click **Start** next to MySQL
4. Wait for both to show "Running" in green

### Option B: Via Command Line
```bash
# Open Command Prompt or PowerShell as Administrator
cd C:\xampp\apache\bin
httpd -k start

# In another admin Command Prompt
cd C:\xampp\mysql\bin
mysqld --console
```

### Option C: Via Git Bash (recommended - all-in-one)
```bash
# Start Apache
"/c/xampp/apache/bin/httpd" -k start

# In new tab, start MySQL
"/c/xampp/mysql/bin/mysqld" --console
```

**Verify Services Running:**
```bash
# Check Apache
curl http://localhost
# Should return HTML (success) not "connection refused"

# Check MySQL
"/c/xampp/mysql/bin/mysql" -u root
# Type: exit (to quit)
```

## Step 2: Access Your Project

### In Web Browser
Open one of these URLs:

```
http://localhost/jobbly
http://localhost/jobbly/index.php
http://localhost/jobbly/fetch_sources.php
```

**Expected responses:**
- `index.php` → Main SPA page
- `fetch_sources.php` → JSON with job sources diagnostics

### View in File Explorer
```
C:\xampp\htdocs\jobbly
├── index.php                    (Main SPA)
├── fetch_sources.php            (API endpoint)
├── fetch_sources_cli.php        (CLI tool)
├── SourceFetcher.php            (Core class)
├── job_sources.json             (Source registry)
└── config.php                   (Your config - keys here)
```

## Step 3: Test the Job Sources API

### Method 1: Terminal (Fastest & Best for Testing)

```bash
# Navigate to project
cd "C:\xampp\htdocs\jobbly"

# Run diagnostics on all sources
php fetch_sources_cli.php

# Test single source
php fetch_sources_cli.php --source remotive

# Show error details
php fetch_sources_cli.php --verbose

# JSON output (for scripting)
php fetch_sources_cli.php --json

# Help
php fetch_sources_cli.php --help
```

**Expected Output:**
```
=== Job Sources Diagnostics ===
Run at: 2026-04-20 10:30:00

✓ Remotive        [OK]    200   342ms  21 jobs
✓ Jobicy          [OK]    200   521ms  100 jobs
✓ RemoteOK        [OK]    200   1043ms 97 jobs
✓ The Muse        [OK]    200   178ms  20 jobs

⊘ JSearch         [SKIP]  ---   ---    MISSING RAPIDAPI_KEY
⊘ Adzuna          [SKIP]  ---   ---    MISSING ADZUNA_APP_ID

✗ LinkedIn        [FAIL]  404   201ms  Auth blocked
✗ Himalayas       [FAIL]  404   156ms  Not Found

Summary: 4 OK, 5 SKIPPED, 6 FAILED
```

### Method 2: Web Browser

**Simple Test:**
1. Open: `http://localhost/jobbly/fetch_sources.php`
2. You'll see JSON output with all sources

**Test Single Source:**
```
http://localhost/jobbly/fetch_sources.php?source=remotive
```

**Pretty-Print JSON:**
- Install browser extension: **JSON Formatter** (Chrome/Firefox)
- Refresh the page to see formatted output

### Method 3: JavaScript Console (Browser)

Open your browser console (F12 → Console tab) and run:

```javascript
// Test the API endpoint
fetch('http://localhost/jobbly/fetch_sources.php')
  .then(r => r.json())
  .then(data => {
    console.log('Total sources:', data.total_sources);
    console.log('Working sources:', data.summary.ok);
    console.log('Failed sources:', data.summary.failed);
    console.table(data.results);
  })
  .catch(e => console.error('Error:', e));
```

### Method 4: cURL from Command Line

```bash
# All sources
curl http://localhost/jobbly/fetch_sources.php

# Single source
curl http://localhost/jobbly/fetch_sources.php?source=remotive

# Pretty JSON (if jq installed)
curl -s http://localhost/jobbly/fetch_sources.php | jq '.summary'

# Save to file
curl http://localhost/jobbly/fetch_sources.php > response.json
```

### Method 5: Postman (Advanced)

1. Download & install **Postman** (postman.com)
2. Create new request:
   - **Method:** GET
   - **URL:** `http://localhost/jobbly/fetch_sources.php`
   - **Params:** `source=remotive` (optional)
   - Click **Send**

## Step 4: Add API Keys (Optional - To Unlock More Sources)

### Get Free API Keys
Visit these links and sign up:
- **JSearch/RapidAPI** → https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch
- **Adzuna** → https://developer.adzuna.com/
- **Findwork.dev** → https://findwork.dev/developers/
- **Jooble** → https://jooble.org/api/about
- **Reed.co.uk** → https://www.reed.co.uk/developers

### Add Keys to Config

1. Open `C:\xampp\htdocs\jobbly\config.php` in text editor
2. Fill in the API keys you got:

```php
<?php
// ... database config ...

// Add your API keys here:
define('RAPIDAPI_KEY', 'your-key-from-rapidapi');
define('ADZUNA_APP_ID', 'your-app-id');
define('ADZUNA_APP_KEY', 'your-app-key');
define('FINDWORK_API_KEY', 'your-key');
define('JOOBLE_API_KEY', 'your-key');
define('REED_API_KEY', 'your-key');
?>
```

3. Save and test again:
```bash
php fetch_sources_cli.php
```

You'll now see more sources as "OK" instead of "SKIPPED"!

## Step 5: Troubleshooting

### Issue: "Connection refused" when accessing `http://localhost`

**Solution:**
```bash
# Check if Apache is running
netstat -ano | findstr :80

# If not running, start it
"C:\xampp\apache\bin\httpd" -k start

# Check Apache error log
type "C:\xampp\apache\logs\error.log"
```

### Issue: "config.php not found"

**Solution:**
```bash
cd C:\xampp\htdocs\jobbly
cp config.example.php config.php
```

Then edit `config.php` with your database and API keys.

### Issue: "502 Bad Gateway" or "PHP Fatal Error"

**Solution:**
```bash
# Check PHP error log
type "C:\xampp\php\logs\php_error.log"

# Or run from command line to see errors
php fetch_sources_cli.php --verbose
```

### Issue: "Connection timeout" when fetching sources

**Solution:**
- Check your internet connection
- Some job sites may be temporarily down
- Try individual sources: `php fetch_sources_cli.php --source remotive`
- Add `--verbose` flag to see exact error

### Issue: "403 Forbidden" or "401 Unauthorized"

**Solution:**
- Some APIs changed their endpoints
- Check your API keys are correct and not expired
- Test with terminal first: `php fetch_sources_cli.php --verbose`

## Complete Testing Workflow

```bash
# 1. Navigate to project
cd C:\xampp\htdocs\jobbly

# 2. Quick diagnostic test
php fetch_sources_cli.php

# 3. Test specific source
php fetch_sources_cli.php --source remotive

# 4. See all details
php fetch_sources_cli.php --verbose

# 5. Export as JSON
php fetch_sources_cli.php --json > results.json

# 6. Open in browser
start http://localhost/jobbly/fetch_sources.php
```

## What to Expect

### First Run (No API Keys)
- ✓ 4 sources will work (238 jobs)
- ⊘ 5 sources will be skipped
- ✗ 6 sources may fail (endpoints need updates)

### After Adding API Keys
- More sources will activate
- Expect 500+ jobs from keyed sources
- Some sources may still fail (we're updating them)

## Next Steps

Once you've tested and it's working:

1. **View Sample Data**
   ```bash
   php fetch_sources_cli.php --source jobicy --json | jq '.results[0]'
   ```

2. **Check Latency**
   ```bash
   php fetch_sources_cli.php | grep "ms"
   ```

3. **Set Up Database** (Next Phase)
   - We'll create `jobs` table to store fetched data
   - Create UI to browse and apply to jobs

4. **Background Sync** (Future)
   - Auto-fetch every 4-6 hours
   - Store results in database

## Quick Reference

| Command | Purpose |
|---------|---------|
| `php fetch_sources_cli.php` | Test all sources |
| `php fetch_sources_cli.php --source remotive` | Test one source |
| `php fetch_sources_cli.php --verbose` | Show errors |
| `php fetch_sources_cli.php --json` | JSON output |
| `curl http://localhost/jobbly/fetch_sources.php` | Web API test |
| `http://localhost/jobbly/fetch_sources.php?source=remotive` | Browser test |

## Questions?

- **For quick testing:** Use terminal commands
- **For UI testing:** Use browser (http://localhost/jobbly)
- **For debugging:** Add `--verbose` flag or check error logs
- **For API integration:** See FETCHER_IMPLEMENTATION.md for JSON structure

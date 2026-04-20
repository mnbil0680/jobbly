# Jobbly - Complete Setup & Testing Guide

## 🚀 Quick Start (2 minutes)

### Option A: Automatic (Windows)
```bash
# Just double-click one of these:
C:\xampp\htdocs\jobbly\START_PROJECT.bat      # Batch file (any Windows)
C:\xampp\htdocs\jobbly\START_PROJECT.ps1      # PowerShell (Windows 7+)
```

Then open in browser: **http://localhost/jobbly**

### Option B: Manual Terminal
```bash
# Navigate to project
cd C:\xampp\htdocs\jobbly

# Verify setup
php verify_setup.php

# Start XAMPP (in separate admin Command Prompt)
"C:\xampp\apache\bin\httpd" -k start
"C:\xampp\mysql\bin\mysqld" --console

# Test the APIs
php fetch_sources_cli.php
```

Then open in browser: **http://localhost/jobbly**

---

## 📋 Full Documentation

### Setup & Running
📖 **See: RUN_AND_TEST.md**
- Step-by-step XAMPP startup
- 5 different testing methods
- Troubleshooting guide
- API key setup

### Quick Setup
📖 **See: QUICK_START.md**
- 2-minute setup
- Usage examples
- Current status
- Next steps

### Architecture
📖 **See: FETCHER_IMPLEMENTATION.md**
- Technical documentation
- Class and method details
- Test results
- Database schema design (next phase)

### Job Sources Registry
📖 **See: 15_JOB_SOURCES.md**
- All 15 job sources listed
- API key registration links
- Coverage areas

---

## ✅ Verification

Run the setup verification:

```bash
php verify_setup.php
```

**Expected output:**
```
✓ All checks passed! Your project is ready.

Next steps:
  1. Start XAMPP (Apache + MySQL)
  2. Run: php fetch_sources_cli.php
  3. Open: http://localhost/jobbly
```

---

## 🧪 Testing the APIs

### Test 1: Terminal Diagnostics (Recommended)

```bash
cd C:\xampp\htdocs\jobbly

# Test all sources
php fetch_sources_cli.php

# Test single source
php fetch_sources_cli.php --source remotive

# Show errors
php fetch_sources_cli.php --verbose

# JSON output
php fetch_sources_cli.php --json
```

**Example output:**
```
=== Job Sources Diagnostics ===

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

### Test 2: Web Browser

Open: **http://localhost/jobbly/fetch_sources.php**

You'll see JSON output with all source diagnostics.

To test single source:
**http://localhost/jobbly/fetch_sources.php?source=remotive**

### Test 3: JavaScript Console

Open **http://localhost/jobbly** and press `F12`, then go to Console tab:

```javascript
fetch('/jobbly/fetch_sources.php')
  .then(r => r.json())
  .then(data => {
    console.log(`Found ${data.summary.ok} working sources`);
    console.log(`Job count: ${data.results.reduce((sum, r) => sum + r.job_count, 0)}`);
    console.table(data.results);
  });
```

### Test 4: cURL Command Line

```bash
# Get all sources
curl http://localhost/jobbly/fetch_sources.php

# Pretty print JSON
curl -s http://localhost/jobbly/fetch_sources.php | jq '.'

# Test single source
curl "http://localhost/jobbly/fetch_sources.php?source=remotive"

# Save response to file
curl http://localhost/jobbly/fetch_sources.php > response.json
```

### Test 5: Postman (Advanced)

1. Download Postman from postman.com
2. Create new GET request:
   - **URL:** `http://localhost/jobbly/fetch_sources.php`
   - **Params:** `source=remotive` (optional)
3. Click **Send**

---

## 🔑 Adding API Keys (Optional)

### Get Free Keys

Visit these links and sign up:
- **JSearch/RapidAPI** → https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch
- **Adzuna** → https://developer.adzuna.com/
- **Findwork.dev** → https://findwork.dev/developers/
- **Jooble** → https://jooble.org/api/about
- **Reed.co.uk** → https://www.reed.co.uk/developers

### Add Keys to Config

Edit `C:\xampp\htdocs\jobbly\config.php`:

```php
<?php
// Database config...

// Add your API keys:
define('RAPIDAPI_KEY', 'paste-your-key-here');
define('ADZUNA_APP_ID', 'paste-your-id-here');
define('ADZUNA_APP_KEY', 'paste-your-key-here');
define('FINDWORK_API_KEY', 'paste-your-key-here');
define('JOOBLE_API_KEY', 'paste-your-key-here');
define('REED_API_KEY', 'paste-your-key-here');
?>
```

Save and test again:
```bash
php fetch_sources_cli.php
```

More sources will now show as "OK" instead of "SKIPPED"!

---

## 📊 Current Status (No API Keys)

| Source | Status | Jobs | Notes |
|--------|--------|------|-------|
| **Remotive** | ✓ OK | 21 | Remote worldwide |
| **Jobicy** | ✓ OK | 100 | Remote worldwide |
| **RemoteOK** | ✓ OK | 97 | Remote worldwide |
| **The Muse** | ✓ OK | 20 | Software engineering |
| **JSearch/RapidAPI** | ⊘ Skip | — | Needs RAPIDAPI_KEY |
| **Adzuna** | ⊘ Skip | — | Needs ADZUNA keys |
| **Findwork.dev** | ⊘ Skip | — | Needs FINDWORK_API_KEY |
| **Jooble** | ⊘ Skip | — | Needs JOOBLE_API_KEY |
| **Reed.co.uk** | ⊘ Skip | — | Needs REED_API_KEY |
| **Himalayas** | ✗ Fail | — | Endpoint changed (404) |
| **LinkedIn** | ✗ Fail | — | Access restricted (404) |
| **USAJobs** | ✗ Fail | — | Auth required (401) |
| **We Work Remotely** | ✗ Fail | — | Blocked (403) |
| **Working Nomads** | ✗ Fail | — | Blocked (403) |
| **Arbeitnow** | ✗ Fail | — | DNS error |

**Summary:**
- ✓ **4 working sources** = 238 jobs available now
- ⊘ **5 ready sources** = Just add API keys
- ✗ **6 failing sources** = Will be fixed soon

---

## 🛠️ Troubleshooting

### "Connection refused" or Apache won't start

**Problem:** Port 80 already in use

**Solution:**
```bash
# Check what's using port 80
netstat -ano | findstr :80

# Try using PHP built-in server instead
cd C:\xampp\htdocs\jobbly
php -S localhost:8080

# Then visit: http://localhost:8080
```

### "config.php not found"

**Solution:**
```bash
cd C:\xampp\htdocs\jobbly
cp config.example.php config.php
```

### "Could not resolve host" or "Connection timeout"

**Problem:** Internet issue or API endpoint down

**Solution:**
```bash
# Test with single source
php fetch_sources_cli.php --source remotive

# Add verbose to see exact error
php fetch_sources_cli.php --verbose

# Check your internet connection
ping google.com
```

### "403 Forbidden" or "401 Unauthorized"

**Problem:** API key invalid or missing

**Solution:**
- Check your API key is correct in config.php
- Verify key hasn't expired at provider website
- Try re-registering for a new key

### "502 Bad Gateway" or PHP error

**Problem:** PHP script error

**Solution:**
```bash
# Run from CLI to see errors
php fetch_sources_cli.php

# Check PHP error log
type "C:\xampp\php\logs\php_error.log"

# Or run verify script
php verify_setup.php
```

---

## 📁 File Structure

```
C:\xampp\htdocs\jobbly\
├── 📖 Documentation
│   ├── README.md                    (Project overview)
│   ├── RUN_AND_TEST.md             (Complete testing guide)
│   ├── QUICK_START.md              (Quick setup)
│   ├── FETCHER_IMPLEMENTATION.md   (Technical docs)
│   ├── 15_JOB_SOURCES.md           (Source registry)
│   └── SETUP_GUIDE.md              (This file)
│
├── 🚀 Startup Helpers
│   ├── START_PROJECT.bat           (Auto-launch Windows Batch)
│   ├── START_PROJECT.ps1           (Auto-launch PowerShell)
│   └── verify_setup.php            (Setup verification)
│
├── 🔧 API Fetcher
│   ├── SourceFetcher.php           (Core fetcher class)
│   ├── fetch_sources.php           (Web endpoint)
│   ├── fetch_sources_cli.php       (CLI tool)
│   └── job_sources.json            (Source registry)
│
├── 🌐 Main App
│   ├── index.php                   (Main SPA page)
│   ├── header.php                  (Site header)
│   ├── footer.php                  (Site footer)
│   ├── API_Ops.php                 (API operations)
│   ├── API_Ops.js                  (AJAX client)
│   ├── DB_Ops.php                  (Database operations)
│   ├── Upload.php                  (File upload)
│   ├── assets/css/style.css        (Styles)
│   └── assets/js/main.js           (Client script)
│
├── ⚙️ Configuration
│   ├── config.php                  (Your config - NOT in git)
│   └── config.example.php          (Template)
│
└── 📦 Git
    ├── .git/                       (Git repository)
    ├── .gitignore                  (Git ignore rules)
    └── Team_Members.txt            (Contributors)
```

---

## 🎯 Next Steps

### Immediate (Week 1)
1. ✅ Run `php fetch_sources_cli.php` to verify everything works
2. ✅ Test in browser: http://localhost/jobbly/fetch_sources.php
3. ⏳ (Optional) Get API keys and add to config.php

### Short Term (Week 2)
1. 🔲 Create database schema (`jobs` and `job_applications` tables)
2. 🔲 Build ingestion layer (save fetched jobs to database)
3. 🔲 Create UI to browse jobs

### Medium Term (Week 3+)
1. 🔲 One-click apply functionality
2. 🔲 User dashboard with application history
3. 🔲 Background sync (auto-refresh every 4-6 hours)
4. 🔲 Job alerts and saved searches

---

## 📞 Support & Help

- **Setup issues?** → See RUN_AND_TEST.md → Troubleshooting section
- **API not working?** → Run `php fetch_sources_cli.php --verbose`
- **Want to understand code?** → See FETCHER_IMPLEMENTATION.md
- **Need quick setup?** → See QUICK_START.md

---

## ✨ What You Have

A fully functional **job source fetcher** that:
- ✓ Fetches from multiple providers simultaneously
- ✓ Handles different API formats (JSON, RSS, XML)
- ✓ Manages API authentication automatically
- ✓ Provides diagnostic information
- ✓ Works via CLI, web endpoint, and AJAX
- ✓ Returns structured JSON data
- ✓ Ready for database integration

**238+ jobs already available from 4 sources with zero API keys needed.**

---

## 🎉 Ready to Go!

```bash
# Start XAMPP
# Option 1: Double-click START_PROJECT.bat
# Option 2: httpd -k start && mysqld --console

# Then run:
php fetch_sources_cli.php

# Or visit:
http://localhost/jobbly
```

**That's it! Your job fetcher is live.**

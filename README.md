# Jobbly - Job Application Manager

## Description
Jobbly is a Single-Page Application (SPA) that helps users search, track, and manage their job applications from multiple job sources in one place.

**Current Status:** ✅ Multi-source job fetcher complete with 15 providers, 238+ jobs available

## Technologies Used
- **Backend:** PHP 8.2+
- **Database:** MySQL
- **Frontend:** AJAX (Fetch API), Vanilla JavaScript
- **Job Sources:** 15 different APIs (Remotive, Jobicy, RemoteOK, The Muse, and more)

## Quick Start

### 1. Verify Setup
```bash
php scripts/verify_setup.php
```

### 2. Start the Application
```bash
# Option A: Auto-launch
cd bin
START_PROJECT.bat          # Windows

# Option B: Manual
php src/fetch_sources_cli.php
```

### 3. Open in Browser
```
http://localhost/jobbly
http://localhost/jobbly/src/fetch_sources.php (API endpoint)
```

## Project Structure

```
jobbly/
├── docs/                          📖 Documentation (6 comprehensive guides)
│   ├── START_HERE.md             ← Read this first!
│   ├── SETUP_GUIDE.md            ← Master documentation
│   ├── RUN_AND_TEST.md           ← Testing guide (5 methods)
│   ├── QUICK_START.md            ← 2-minute setup
│   ├── FETCHER_IMPLEMENTATION.md ← Technical architecture
│   └── 15_JOB_SOURCES.md         ← Source registry with API links
│
├── src/                           🔧 Job Fetcher System
│   ├── SourceFetcher.php         ← Core fetcher class (508 lines)
│   ├── fetch_sources.php         ← Web API endpoint
│   ├── fetch_sources_cli.php     ← Terminal diagnostic tool
│   └── job_sources.json          ← Registry of 15 job providers
│
├── bin/                           🚀 Startup Scripts
│   ├── START_PROJECT.bat         ← Windows launcher (double-click)
│   └── START_PROJECT.ps1         ← PowerShell launcher
│
├── scripts/                       🛠️  Utility Scripts
│   └── verify_setup.php          ← Setup verification
│
├── config/                        ⚙️  Configuration
│   ├── config.php                ← Your configuration (NOT in git)
│   └── config.example.php        ← Template with API key slots
│
├── app/                           🌐 Main Application
│   ├── index.php                 ← Main SPA page
│   ├── header.php                ← Site header
│   ├── footer.php                ← Site footer
│   ├── DB_Ops.php                ← Database operations
│   ├── API_Ops.php               ← API operations
│   ├── Upload.php                ← File upload handler
│   └── assets/
│       ├── css/style.css
│       ├── js/main.js            ← Main JavaScript
│       └── js/API_Ops.js         ← AJAX client
│
├── README.md                      ← This file
└── .git/                          ← Git repository with full history
```

## Features

### ✅ Phase 1 Complete: Job Fetcher System
- ✓ Multi-source job fetcher (15 providers)
- ✓ Support for JSON APIs, RSS feeds, XML
- ✓ Automatic API key management
- ✓ Web endpoint + CLI tool
- ✓ Error handling & diagnostics
- ✓ 238+ jobs available immediately
- ✓ Complete documentation

### ⏳ Phase 2 Planned: Database & UI
- [ ] Job storage schema
- [ ] Application tracking
- [ ] Job browsing UI
- [ ] One-click apply

### ⏳ Phase 3 Planned: Advanced Features
- [ ] Background sync
- [ ] Job alerts
- [ ] User dashboard

## Current Live Status

### ✅ Working (238 jobs)
- Remotive (21 jobs)
- Jobicy (100 jobs)
- RemoteOK (97 jobs)
- The Muse (20 jobs)

### ⊘ Ready with API Keys
- JSearch/RapidAPI
- Adzuna
- Findwork.dev
- Jooble
- Reed.co.uk

### ✗ Needs Updates
- 6 sources (endpoints need fixing)

## Getting Started

### New User?
1. Read `docs/START_HERE.md` (5 min)
2. Read `docs/QUICK_START.md` (5 min)
3. Run `php scripts/verify_setup.php`
4. Double-click `bin/START_PROJECT.bat`

### Want to Test APIs?
```bash
cd /path/to/jobbly
php src/fetch_sources_cli.php
```

### Want to Understand the Code?
- Read `docs/FETCHER_IMPLEMENTATION.md`
- Review `src/SourceFetcher.php`
- Check `src/job_sources.json`

## Configuration

### Setup Config
```bash
cp config/config.example.php config/config.php
```

### Add API Keys (Optional)
Edit `config/config.php` and add your keys:
```php
define('RAPIDAPI_KEY', 'your-key');
define('ADZUNA_APP_ID', 'your-id');
define('ADZUNA_APP_KEY', 'your-key');
// ... see config.example.php for all keys
```

## Documentation

All documentation is in `docs/`:

| Document | Purpose |
|----------|---------|
| **START_HERE.md** | Overview & quick start (5 min) |
| **SETUP_GUIDE.md** | Master guide (everything) |
| **RUN_AND_TEST.md** | Setup & testing (5 methods) |
| **QUICK_START.md** | Quick setup (2 min) |
| **FETCHER_IMPLEMENTATION.md** | Technical architecture |
| **15_JOB_SOURCES.md** | Source registry with API links |

## Common Commands

```bash
# Verify setup
php scripts/verify_setup.php

# Test all sources
php src/fetch_sources_cli.php

# Test single source
php src/fetch_sources_cli.php --source remotive

# Show errors
php src/fetch_sources_cli.php --verbose

# JSON output
php src/fetch_sources_cli.php --json

# Web API
curl http://localhost/jobbly/src/fetch_sources.php

# Main app
open http://localhost/jobbly
```

## Development

### Project Layout Conventions
- `docs/` - All documentation
- `src/` - Job fetcher system source code
- `bin/` - Executable startup scripts
- `scripts/` - Utility scripts
- `config/` - Configuration files
- `app/` - Main SPA application

### Adding New API Sources
1. Edit `src/job_sources.json` to add source definition
2. Test with `php src/fetch_sources_cli.php --source <id>`
3. Update documentation

### Database Integration (Next Phase)
- Create `jobs` table from schema in `docs/FETCHER_IMPLEMENTATION.md`
- Build ingestion layer
- Integrate with UI

## Git History

All file reorganization preserves full git history. Use:
```bash
git log --follow src/SourceFetcher.php  # See history of moved file
```

## Support

- **Setup issues?** → `docs/RUN_AND_TEST.md` → Troubleshooting
- **Want to test?** → Run `php src/fetch_sources_cli.php`
- **API keys?** → See `docs/15_JOB_SOURCES.md`
- **Understanding code?** → See `docs/FETCHER_IMPLEMENTATION.md`

## License
[Add your license here]

## Authors
- Backend: AI Assistant (OpenCode)
- Project Lead: [Your Name]

---

**Ready to get started?** Read `docs/START_HERE.md` or run:
```bash
php scripts/verify_setup.php && php src/fetch_sources_cli.php
```

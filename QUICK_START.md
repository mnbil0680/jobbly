# Job Sources Fetcher - Quick Start Guide

## Setup (2 minutes)

### 1. Copy config template
```bash
cp config.example.php config.php
```

### 2. Add API keys (Optional)
Edit `config.php` and fill in any keys you have from:
- https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch (RAPIDAPI_KEY)
- https://developer.adzuna.com/ (ADZUNA_APP_ID + ADZUNA_APP_KEY)
- https://findwork.dev/developers/ (FINDWORK_API_KEY)
- https://jooble.org/api/about (JOOBLE_API_KEY)
- https://www.reed.co.uk/developers (REED_API_KEY)

**Leave blank keys you don't have** — the system skips them automatically.

## Test It

### Terminal Diagnostics
```bash
# Quick test of all sources
php fetch_sources_cli.php

# Single source test
php fetch_sources_cli.php --source remotive

# See error details
php fetch_sources_cli.php --verbose

# Export as JSON
php fetch_sources_cli.php --json | jq '.summary'

# Help
php fetch_sources_cli.php --help
```

### Web Browser
Open your browser and visit:
```
http://localhost/jobbly/fetch_sources.php
http://localhost/jobbly/fetch_sources.php?source=remotive
```

### JavaScript/AJAX
```javascript
// Fetch all sources
fetch('/jobbly/fetch_sources.php')
  .then(r => r.json())
  .then(data => {
    console.log(`Found ${data.summary.ok} working sources`);
    data.results.forEach(r => {
      if (r.status === 'ok') {
        console.log(`${r.name}: ${r.job_count} jobs`);
      }
    });
  });
```

## Current Status (No API Keys)

| Source | Status | Jobs | Details |
|--------|--------|------|---------|
| Remotive | ✓ OK | 21 | Remote worldwide |
| Jobicy | ✓ OK | 100 | Remote worldwide |
| RemoteOK | ✓ OK | 97 | Remote worldwide |
| The Muse | ✓ OK | 20 | Software engineering |
| JSearch/RapidAPI | ⊘ Skipped | — | Need RAPIDAPI_KEY |
| Adzuna | ⊘ Skipped | — | Need ADZUNA keys |
| Findwork.dev | ⊘ Skipped | — | Need FINDWORK_API_KEY |
| Jooble | ⊘ Skipped | — | Need JOOBLE_API_KEY |
| Reed.co.uk | ⊘ Skipped | — | Need REED_API_KEY |
| Himalayas | ✗ Failed | — | Endpoint changed |
| LinkedIn | ✗ Failed | — | Access restricted |
| USAJobs | ✗ Failed | — | Needs auth |
| *5 others* | ✗ Failed | — | Blocked or offline |

## What You Get

### ✓ Working Immediately (238 jobs total)
No API keys needed — start fetching from 4 providers right now.

### ⊘ Ready When You Add Keys (5 sources)
Free API keys available — just add them to `config.php` and the system will fetch from these providers automatically.

### ✗ Needs Attention (6 sources)
Endpoints have changed or are access-restricted. We'll update these as providers change their APIs.

## Next Steps

1. **Get API Keys** → Register at the provider sites listed above
2. **Add Keys** → Update `config.php` with your keys
3. **Re-test** → Run `php fetch_sources_cli.php` again
4. **Build Database** → We'll create a jobs table next to store these results
5. **Add UI** → Browse and apply to jobs from the web dashboard

## Code Files

| File | Purpose |
|------|---------|
| `job_sources.json` | Registry of all 15 sources with endpoints and auth |
| `SourceFetcher.php` | Core class that fetches, parses, and normalizes job data |
| `fetch_sources.php` | Web endpoint — visit in browser or call via AJAX |
| `fetch_sources_cli.php` | Terminal tool for quick testing and diagnostics |
| `config.example.php` | Template for your local config (contains API key slots) |
| `config.php` | Your actual config (DO NOT commit to git) |
| `FETCHER_IMPLEMENTATION.md` | Full technical documentation |

## Troubleshooting

**"config.php not found"**
```bash
cp config.example.php config.php
```

**"Connection timed out"**
- Check your internet connection
- Some providers may be temporarily down
- Add `--verbose` flag to see exact error

**"401 Unauthorized"**
- Your API key is invalid or missing
- Check `config.php` has the right key
- Verify key hasn't expired at provider website

**"403 Forbidden"**
- Provider blocked the request
- May need User-Agent header or referrer
- Will be fixed in next update

## Questions?

Check:
- `FETCHER_IMPLEMENTATION.md` — Full documentation
- `15_JOB_SOURCES.md` — Source registry with registration links
- `SourceFetcher.php` comments — Code documentation

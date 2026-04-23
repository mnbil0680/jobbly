<?php
require_once 'header.php';
require_once '../src/SourceFetcher.php';
require_once '../config/config.php';

$fetcher = new SourceFetcher();
$sources = $fetcher->get_sources();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Seeder | Jobbly</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
</head>
<body class="bg-surface">
    <main class="main-layout">
        <div class="test-endpoints-header">
            <h1>🌱 Database Seeder</h1>
            <p class="subtitle">Populate your database with the latest jobs from all 15 sources</p>
            <div class="test-header-controls">
                <button id="runSeederBtn" class="search-btn" onclick="startSeeding()">🚀 Run Full Seeder</button>
            </div>
        </div>

        <div id="progressBox" class="job-row" style="display: none; margin-bottom: 30px; border-left: 4px solid var(--primary);">
            <div style="width: 100%;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span id="statusLabel" style="font-weight: 700;">Seeding in progress...</span>
                    <span id="percentLabel">0%</span>
                </div>
                <div style="width: 100%; height: 8px; background: var(--surface); border-radius: 4px; overflow: hidden;">
                    <div id="progressBar" style="width: 0%; height: 100%; background: var(--primary); transition: width 0.3s;"></div>
                </div>
                <p id="currentSourceLabel" style="margin-top: 10px; font-size: 0.9rem; color: var(--text-muted);"></p>
            </div>
        </div>

        <div class="sources-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($sources as $s): ?>
                <div id="card-<?php echo $s['id']; ?>" class="job-row source-card" style="flex-direction: column; align-items: start; gap: 10px; opacity: 0.6; transition: all 0.3s;">
                    <div style="display: flex; justify-content: space-between; width: 100%;">
                        <strong style="font-size: 1.1rem;"><?php echo htmlspecialchars($s['name']); ?></strong>
                        <span id="status-<?php echo $s['id']; ?>" class="badge" style="background: var(--surface); color: var(--text-muted);">PENDING</span>
                    </div>
                    <p id="msg-<?php echo $s['id']; ?>" style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Waiting to start...</p>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
    const sources = <?php echo json_encode(array_values($sources)); ?>;
    
    async function startSeeding() {
        const btn = document.getElementById('runSeederBtn');
        btn.disabled = true;
        btn.innerText = 'Seeding...';
        
        document.getElementById('progressBox').style.display = 'flex';
        let savedTotal = 0;

        for (let i = 0; i < sources.length; i++) {
            const s = sources[i];
            const card = document.getElementById(`card-${s.id}`);
            const status = document.getElementById(`status-${s.id}`);
            const msg = document.getElementById(`msg-${s.id}`);
            
            card.style.opacity = '1';
            card.style.borderColor = 'var(--primary)';
            status.innerText = 'FETCHING';
            status.style.background = 'var(--primary-soft)';
            status.style.color = 'var(--primary)';
            document.getElementById('currentSourceLabel').innerText = `Current: ${s.name}`;

            try {
                const res = await fetch(`API_Ops.php?action=sync&source_id=${s.id}`);
                const data = await res.json();
                
                if (data.success) {
                    status.innerText = 'DONE';
                    status.style.background = '#dcfce7';
                    status.style.color = '#166534';
                    msg.innerText = `Saved ${data.saved_count} jobs.`;
                    savedTotal += data.saved_count;
                } else {
                    throw new Error(data.message || 'Failed');
                }
            } catch (e) {
                status.innerText = 'ERROR';
                status.style.background = '#fee2e2';
                status.style.color = '#991b1b';
                msg.innerText = e.message;
            }

            const percent = Math.round(((i + 1) / sources.length) * 100);
            document.getElementById('progressBar').style.width = `${percent}%`;
            document.getElementById('percentLabel').innerText = `${percent}%`;
        }

        document.getElementById('statusLabel').innerText = 'Seeding Complete!';
        document.getElementById('currentSourceLabel').innerText = `Finished! Total new jobs saved: ${savedTotal}`;
        btn.innerText = 'Seeding Finished';
    }
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>

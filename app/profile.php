<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Jobbly</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-surface">
    <?php include 'header.php'; ?>

    <main class="main-layout">
        <div class="profile-layout">
            <aside class="profile-card">
                <div class="profile-avatar" id="avatarDisplay">
                    <span class="material-symbols-outlined" style="font-size: 64px; color: var(--text-muted);">account_circle</span>
                </div>
                <h3 id="profileNameDisplay">User Name</h3>
                <p id="profileEmailDisplay" style="color: var(--text-muted); font-size: 0.9rem;">user@example.com</p>
                
                <div class="profile-actions">
                    <input type="file" id="photoInput" style="display: none;" accept="image/*">
                    <button class="btn-outline" onclick="document.getElementById('photoInput').click()">
                        <span class="material-symbols-outlined">photo_camera</span> Update Photo
                    </button>
                    
                    <input type="file" id="cvInput" style="display: none;" accept=".pdf,.doc,.docx">
                    <button class="btn-outline" onclick="document.getElementById('cvInput').click()">
                        <span class="material-symbols-outlined">description</span> Upload CV
                    </button>
                    <div id="cvStatus" style="font-size: 0.8rem; color: var(--primary);"></div>
                </div>
            </aside>

            <section class="auth-container" style="margin: 0; max-width: none;">
                <h2>Profile Details</h2>
                <form id="profileForm">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" class="form-control" placeholder="Update your name">
                    </div>
                    <div class="form-group">
                        <label for="details">About Me / Professional Summary</label>
                        <textarea id="details" class="form-control" rows="4" placeholder="Tell us about yourself..."></textarea>
                    </div>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </form>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            await loadUserProfile();
            setupUploadHandlers();
        });

        async function loadUserProfile() {
            const res = await fetch('API_Ops.php?action=get_user');
            const data = await res.json();
            if (data.success) {
                const user = data.user;
                document.getElementById('name').value = user.name || '';
                document.getElementById('details').value = user.details || '';
                document.getElementById('profileNameDisplay').innerText = user.name;
                document.getElementById('profileEmailDisplay').innerText = user.email;
                
                if (user.profile_photo) {
                    document.getElementById('avatarDisplay').innerHTML = `<img src="${user.profile_photo}" alt="Avatar">`;
                }
                
                if (user.cv_path) {
                    document.getElementById('cvStatus').innerText = '✓ CV Uploaded';
                }
            }
        }

        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = document.getElementById('name').value;
            const details = document.getElementById('details').value;

            try {
                const res = await fetch('API_Ops.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'update_user', name, details })
                });
                const data = await res.json();
                if (data.success) {
                    alert('Profile updated!');
                    location.reload();
                }
            } catch (err) {
                console.error(err);
            }
        });

        function setupUploadHandlers() {
            const uploadFile = async (file, type) => {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('type', type);
                
                try {
                    const res = await fetch('Upload.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert(`${type.toUpperCase()} uploaded successfully!`);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                } catch (err) {
                    console.error(err);
                }
            };

            document.getElementById('photoInput').addEventListener('change', (e) => {
                if (e.target.files[0]) uploadFile(e.target.files[0], 'photo');
            });

            document.getElementById('cvInput').addEventListener('change', (e) => {
                if (e.target.files[0]) uploadFile(e.target.files[0], 'cv');
            });
        }
    </script>
</body>
</html>

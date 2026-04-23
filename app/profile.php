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

                <hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--border);">

                <h2>Change Email</h2>
                <form id="emailForm">
                    <div class="form-group">
                        <label for="newEmail">New Email Address</label>
                        <input type="email" id="newEmail" class="form-control" placeholder="new@example.com" required>
                    </div>
                    <div class="form-group">
                        <label for="emailPassword">Password</label>
                        <input type="password" id="emailPassword" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn-primary">Update Email</button>
                </form>

                <hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--border);">

                <h2>Change Password</h2>
                <form id="passwordForm">
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" id="currentPassword" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <input type="password" id="newPassword" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn-primary">Change Password</button>
                </form>

                <hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--border);">

                <h2>Delete Account</h2>
                <p style="color: var(--text-muted); margin-bottom: 1rem;">
                    Permanently delete your account and all associated data. This action cannot be undone.
                </p>
                <button type="button" class="btn-danger" onclick="showDeleteModal()" style="background-color: #dc3545; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer;">
                    Delete My Account
                </button>

                <!-- Delete Account Modal -->
                <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; flex-items: center; justify-content: center;">
                    <div style="background: white; padding: 2rem; border-radius: 8px; max-width: 400px; margin: auto; margin-top: 10vh; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <h3>Delete Account</h3>
                        <p style="color: var(--text-muted);">
                            Are you sure? This action cannot be undone. All your data will be permanently deleted.
                        </p>
                        <form id="deleteForm" style="margin-top: 1.5rem;">
                            <div class="form-group">
                                <label for="deletePassword">Enter your password to confirm:</label>
                                <input type="password" id="deletePassword" class="form-control" placeholder="••••••••" required>
                            </div>
                            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                                <button type="button" class="btn-outline" onclick="hideDeleteModal()" style="padding: 0.75rem 1.5rem; border: 1px solid var(--border); background: white; cursor: pointer; border-radius: 4px;">
                                    Cancel
                                </button>
                                <button type="submit" style="padding: 0.75rem 1.5rem; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                    Delete Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
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

        function showDeleteModal() {
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            document.getElementById('deletePassword').value = '';
        }

        document.getElementById('emailForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const newEmail = document.getElementById('newEmail').value;
            const password = document.getElementById('emailPassword').value;

            try {
                const res = await fetch('API_Ops.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'change_email', new_email: newEmail, password })
                });
                const data = await res.json();
                if (data.success) {
                    alert('Email updated successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Error updating email');
                }
            } catch (err) {
                console.error(err);
                alert('Error updating email');
            }
        });

        document.getElementById('passwordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (newPassword !== confirmPassword) {
                alert('New passwords do not match!');
                return;
            }

            try {
                const res = await fetch('API_Ops.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'change_password',
                        current_password: currentPassword,
                        new_password: newPassword,
                        confirm_password: confirmPassword
                    })
                });
                const data = await res.json();
                if (data.success) {
                    alert('Password changed successfully!');
                    document.getElementById('passwordForm').reset();
                } else {
                    alert(data.message || 'Error changing password');
                }
            } catch (err) {
                console.error(err);
                alert('Error changing password');
            }
        });

        document.getElementById('deleteForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const password = document.getElementById('deletePassword').value;

            try {
                const res = await fetch('API_Ops.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_user', password })
                });
                const data = await res.json();
                if (data.success) {
                    alert('Account deleted successfully!');
                    window.location.href = 'login.php';
                } else {
                    alert(data.message || 'Error deleting account');
                    hideDeleteModal();
                }
            } catch (err) {
                console.error(err);
                alert('Error deleting account');
                hideDeleteModal();
            }
        });
    </script>
</body>
</html>

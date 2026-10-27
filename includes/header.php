<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/rbac.php';
$current_user = get_logged_in_user();
?>
<div class="header sticky-top">
    <nav class="navbar navbar-expand-lg navbar-light py-2">
        <a class="navbar-brand" href="index.php">
            <img src="img/logo.png" alt="PGLife Logo" />
        </a>

        <!-- Search Command Palette Trigger -->
        <button class="btn btn-outline-primary btn-sm ml-2 d-none d-md-inline-block" onclick="toggleCmdModal()" style="border-radius: 20px; font-weight: 600;">
            <i class="fas fa-search"></i> Command <span class="badge badge-light ml-1" style="border: 1px solid #cbd5e1;">Ctrl+K</span>
        </button>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#my-navbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="my-navbar">
            <ul class="navbar-nav align-items-center">
                <!-- Mandatory Links -->
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="property_list.php?city=Delhi"><i class="fas fa-building"></i> Browse PGs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="event.preventDefault(); toggleChatbot();"><i class="fas fa-robot text-primary"></i> AI Chat Assistant</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-toggle="modal" data-target="#modalContact"><i class="fas fa-headset"></i> Contact</a>
                </li>

                <!-- Mandatory Owner Action Buttons (ALWAYS VISIBLE) -->
                <li class="nav-item ml-lg-2">
                    <button class="btn btn-sm btn-primary font-weight-bold text-white" data-toggle="modal" data-target="#modalOwnerRegister">
                        <i class="fas fa-plus-circle"></i> Become a Property Owner
                    </button>
                </li>
                <li class="nav-item ml-1">
                    <button class="btn btn-sm btn-outline-warning text-dark font-weight-bold" data-toggle="modal" data-target="#modalOwnerLogin">
                        <i class="fas fa-store"></i> Owner Login
                    </button>
                </li>

                <div class="nav-vl d-none d-lg-block"></div>

                <?php if (!$current_user) { ?>
                    <!-- Guest User Auth Links -->
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-toggle="modal" data-target="#signup-modal">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-toggle="modal" data-target="#login-modal">
                            <i class="fas fa-sign-in-alt"></i> User Login
                        </a>
                    </li>
                <?php } else { ?>
                    <!-- Wishlist / Saved PGs -->
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="profile.php?tab=saved" title="Wishlist / Favorites">
                            <i class="fas fa-heart"></i> Saved
                        </a>
                    </li>

                    <!-- Notifications Dropdown -->
                    <li class="nav-item position-relative mr-1">
                        <button class="btn btn-link nav-link position-relative text-dark p-2" id="notifBellBtn" onclick="toggleNotifDropdown(event)" style="border: none; background: transparent;">
                            <i class="fas fa-bell fa-lg"></i>
                            <span id="notifUnreadBadge" class="badge badge-danger position-absolute" style="top: 0px; right: 0px; font-size: 9px; display: none;">0</span>
                        </button>
                        <div id="notifDropdownMenu" class="dropdown-menu dropdown-menu-right shadow-lg border-0" style="display: none; position: absolute; right: 0; top: 42px; width: 340px; max-height: 400px; overflow-y: auto; z-index: 1050; border-radius: 12px; background: white;">
                            <div class="dropdown-header font-weight-bold d-flex justify-content-between align-items-center p-3" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <span class="text-dark"><i class="fas fa-bell text-primary mr-1"></i> Notifications</span>
                                <a href="#" class="text-primary font-weight-bold" style="font-size: 11px;" onclick="markNotifsRead(event)">Mark all read</a>
                            </div>
                            <div id="notifFeedItems">
                                <div class="p-3 text-center text-muted" style="font-size: 12px;">Loading notifications...</div>
                            </div>
                        </div>
                    </li>

                    <!-- User Profile & Role Dashboard Link -->
                    <div class='nav-name mr-2 font-weight-bold' style="font-size: 13px;">
                        Hi, <?php echo htmlspecialchars($current_user['full_name']); ?>
                        <span class="badge <?php echo $current_user['role'] === 'admin' ? 'badge-danger' : ($current_user['role'] === 'owner' ? 'badge-warning' : 'badge-info'); ?>" style="font-size: 9px; text-transform: uppercase;">
                            <?php echo $current_user['role']; ?>
                        </span>
                    </div>

                    <li class="nav-item">
                        <?php if ($current_user['role'] === 'admin') { ?>
                            <a class="nav-link font-weight-bold text-danger" href="admin_portal.php"><i class="fas fa-user-shield"></i> Admin Portal</a>
                        <?php } elseif ($current_user['role'] === 'owner') { ?>
                            <a class="nav-link font-weight-bold text-warning" href="owner_dashboard.php"><i class="fas fa-store"></i> Owner Dashboard</a>
                        <?php } else { ?>
                            <a class="nav-link font-weight-bold" href="profile.php"><i class="fas fa-user-circle"></i> Profile</a>
                        <?php } ?>
                    </li>

                    <li class="nav-item ml-1">
                        <a class="nav-link text-muted" href="logout.php"><i class="fas fa-sign-out-alt"></i></a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </nav>
</div>

<!-- Includes Component Modals & Widgets -->
<?php require_once __DIR__ . '/command_palette.php'; ?>
<?php require_once __DIR__ . '/chatbot_widget.php'; ?>
<?php require_once __DIR__ . '/terms_modal.php'; ?>
<?php require_once __DIR__ . '/owner_register_modal.php'; ?>
<?php require_once __DIR__ . '/owner_login_modal.php'; ?>
<?php require_once __DIR__ . '/contact_modal.php'; ?>

<script>
function toggleNotifDropdown(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('notifDropdownMenu');
    if (!menu) return;

    if (menu.style.display === 'block') {
        menu.style.display = 'none';
    } else {
        menu.style.display = 'block';
        fetchHeaderNotifs();
    }
}

// Close notification menu on outside click
document.addEventListener('click', function(e) {
    const menu = document.getElementById('notifDropdownMenu');
    const bellBtn = document.getElementById('notifBellBtn');
    if (menu && menu.style.display === 'block') {
        if (!menu.contains(e.target) && !bellBtn.contains(e.target)) {
            menu.style.display = 'none';
        }
    }
});

function fetchHeaderNotifs() {
    fetch('api/get_notifications.php')
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('notifUnreadBadge');
            const feed = document.getElementById('notifFeedItems');
            if (!badge || !feed) return;

            if (data.unread_count > 0) {
                badge.innerText = data.unread_count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }

            if (data.notifications && data.notifications.length > 0) {
                feed.innerHTML = '';
                data.notifications.forEach(n => {
                    const item = document.createElement('a');
                    item.className = 'dropdown-item p-3 border-bottom text-decoration-none ' + (!n.is_read ? 'bg-light font-weight-bold' : '');
                    item.href = n.link || '#';
                    item.style.whiteSpace = 'normal';
                    item.style.fontSize = '12px';
                    item.style.display = 'block';
                    item.innerHTML = `
                        <div class="text-dark font-weight-bold mb-1">${n.title}</div>
                        <div class="text-muted small mb-1">${n.message}</div>
                        <div class="text-muted text-right" style="font-size: 10px;">${n.created_at}</div>
                    `;
                    feed.appendChild(item);
                });
            } else {
                feed.innerHTML = '<div class="p-3 text-center text-muted" style="font-size: 12px;">No notifications yet.</div>';
            }
        })
        .catch(() => {});
}

function markNotifsRead(e) {
    if (e) e.preventDefault();
    fetch('api/get_notifications.php?action=mark_read').then(() => fetchHeaderNotifs());
}

document.addEventListener('DOMContentLoaded', () => {
    fetchHeaderNotifs();
    // Auto-poll notifications every 30 seconds
    setInterval(fetchHeaderNotifs, 30000);
});
</script>

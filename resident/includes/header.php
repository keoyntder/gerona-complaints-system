<?php
/**
 * Shared site header + horizontal navigation.
 * Include AFTER session_start() and AFTER setting:
 *   $active_page  = 'dashboard' | 'complaint' | 'my_complaints' | 'public_complaints' | 'rewards' | 'profile' | 'home'
 *   $page_title   = string shown in the page title strip
 *
 * Works for logged-in residents, logged-in admins, and logged-out guests
 * (public_complaints.php is reachable without logging in).
 */
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$role         = $_SESSION['role'] ?? null;

if ($is_logged_in && $role === 'resident') {
    $nav_items = [
        'dashboard'         => ['label' => 'Dashboard',      'href' => 'dashboard.php',         'icon' => 'fa-house'],
        'complaint'         => ['label' => 'File Complaint', 'href' => 'complaint.php',         'icon' => 'fa-triangle-exclamation'],
        'my_complaints'     => ['label' => 'My Complaints',  'href' => 'my_complaints.php',     'icon' => 'fa-list'],
        'public_complaints' => ['label' => 'Public Board',   'href' => 'public_complaints.php', 'icon' => 'fa-bullhorn'],
        'rewards'           => ['label' => 'My Rewards',     'href' => 'rewards.php',           'icon' => 'fa-trophy'],
        'redeem_board'      => ['label' => 'Redeem Rewards', 'href' => 'redeem_board.php',      'icon' => 'fa-gift'],
        'profile'           => ['label' => 'Profile',        'href' => 'profile.php',           'icon' => 'fa-user'],
    ];
} else {
    $nav_items = [
        'home'              => ['label' => 'Home',                     'href' => '../index.php',          'icon' => 'fa-house'],
        'public_complaints' => ['label' => 'Public Complaints Board',  'href' => 'public_complaints.php', 'icon' => 'fa-list'],
    ];
}
$active_page = $active_page ?? '';
$page_title  = $page_title  ?? '';
?>
<header class="gov-header">

    <div class="gov-topbar">
        <div class="gov-topbar-inner">
            <span><i class="fa-solid fa-landmark"></i> Republic of the Philippines</span>
            <div class="gov-topbar-right">
                <span>Province of Tarlac</span>
                <span>Municipality of Gerona</span>
            </div>
        </div>
    </div>

    <div class="gov-brand">
        <div class="gov-brand-inner">
            <div class="gov-brand-left">
                <img src="../assets/img/gerona-seal.png" alt="Municipality of Gerona Seal">
                <div class="gov-brand-text">
                    <h1>Ragsak Ranyag Gerona</h1>
                    <p>Complaints Management System &mdash; Resident Portal</p>
                </div>
            </div>
            <div class="gov-brand-right">
                <?php if ($is_logged_in && $role === 'resident'): ?>
                    <div class="gov-user-chip">
                        <div class="gov-user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
                        <div class="gov-user-meta">
                            <span class="gov-user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                            <span class="gov-user-role">Resident</span>
                        </div>
                    </div>
                <?php elseif ($is_logged_in && $role === 'admin'): ?>
                    <a href="../admin/dashboard.php" class="btn-login"><i class="fa-solid fa-gauge"></i> Admin Panel</a>
                <?php else: ?>
                    <a href="../login.php" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                    <a href="../register.php" class="btn-register"><i class="fa-solid fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <nav class="gov-nav">
        <div class="gov-nav-inner">
            <div class="gov-nav-links">
                <?php foreach ($nav_items as $key => $item): ?>
                    <a href="<?= $item['href'] ?>" class="<?= $active_page === $key ? 'active' : '' ?>">
                        <i class="fa-solid <?= $item['icon'] ?>"></i> <?= $item['label'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if ($is_logged_in && $role === 'resident'): ?>
                <a href="../logout.php" class="gov-nav-logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            <?php endif; ?>
        </div>
    </nav>

</header>

<?php if ($page_title): ?>
<div class="page-title-bar">
    <div class="page-title-bar-inner">
        <h2><?= htmlspecialchars($page_title) ?></h2>
        <span class="page-date"><?= date('l, F d, Y') ?></span>
    </div>
</div>
<?php endif; ?>

<script>
    (function () {
        function setHeaderHeight() {
            var header = document.querySelector('.gov-header');
            if (header) {
                document.documentElement.style.setProperty('--gov-header-height', header.offsetHeight + 'px');
            }
        }
        setHeaderHeight();
        window.addEventListener('resize', setHeaderHeight);
        window.addEventListener('load', setHeaderHeight);
    })();
</script>
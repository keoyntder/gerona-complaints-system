<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

$complaints = $pdo->query("
    SELECT c.*, u.full_name, u.email 
    FROM complaints c 
    JOIN users u ON c.user_id = u.id 
    ORDER BY c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pending_users = $pdo->query("
    SELECT COUNT(*) 
    FROM users 
    WHERE status = 'pending'
")->fetchColumn();

$pending_complaints = $pdo->query("
    SELECT COUNT(*) 
    FROM complaints 
    WHERE status = 'pending'
")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Complaints — Ragsak Ranyag Gerona</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f4f6f8;
            display: flex;
            min-height: 100vh;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            width: 220px;
            background: #fff;
            border-right: 1px solid #eee;
            display: flex;
            flex-direction: column;
            padding: 24px 0;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 20px 24px;
            border-bottom: 1px solid #f0f0f0;
        }

        .sidebar-logo img {
            width: 36px;
            height: 36px;
            object-fit: contain;
        }

        .sidebar-logo span {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1.3;
        }

        .nav-label {
            font-size: 10px;
            font-weight: 600;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 20px 20px 8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            font-size: 14px;
            color: #555;
            text-decoration: none;
            transition: all 0.15s;
        }

        .nav-item:hover {
            background: #f4f6f8;
            color: #1a1a1a;
        }

        .nav-item.active {
            background: #FFF8E7;
            color: #8B6914;
            font-weight: 600;
            border-right: 3px solid #8B6914;
        }

        .nav-item i {
            width: 18px;
            text-align: center;
            font-size: 15px;
        }

        .badge-count {
            margin-left: auto;
            background: #8B6914;
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 20px;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding: 16px 20px;
            border-top: 1px solid #f0f0f0;
        }

        .sidebar-bottom a {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #e53935;
            text-decoration: none;
            font-weight: 500;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 220px;
            flex: 1;
            min-width: 0;
        }

        /* =========================
           TOPBAR
        ========================= */

        .topbar {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .topbar h1 {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #8B6914;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 600;
        }

        /* =========================
           CONTENT
        ========================= */

        .content {
            padding: 28px;
        }

        .table-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .table-header {
            padding: 18px 24px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .table-header h3 {
            font-size: 15px;
            font-weight: 700;
            color: #1a1a1a;
        }

        /* =========================
           SEARCH
        ========================= */

        .search-wrap {
            position: relative;
        }

        .search-wrap i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 13px;
        }

        .search-wrap input {
            padding: 8px 12px 8px 32px;
            border: 1.5px solid #eee;
            border-radius: 6px;
            font-size: 13px;
            outline: none;
            width: 220px;
            font-family: 'Inter', sans-serif;
        }

        .search-wrap input:focus {
            border-color: #8B6914;
        }

        /* =========================
           TABLE
        ========================= */

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1150px;
            border-collapse: collapse;
            font-size: 14px;
        }

        th {
            text-align: left;
            padding: 12px 16px;
            background: #fafafa;
            color: #888;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #f0f0f0;
            white-space: nowrap;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #f8f8f8;
            color: #333;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #fafafa;
        }

        /* =========================
           STATUS BADGES
        ========================= */

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }

        .badge-pending {
            background: #FFF3CD;
            color: #856404;
        }

        .badge-in_progress {
            background: #CCE5FF;
            color: #004085;
        }

        .badge-resolved {
            background: #D1E7DD;
            color: #0A6640;
        }

        /* =========================
           ACTION BUTTONS
        ========================= */

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            min-width: 190px;
        }

        .action-buttons form {
            display: inline-flex;
            margin: 0;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            white-space: nowrap;
            transition: background 0.15s;
        }

        .btn-progress {
            background: #CCE5FF;
            color: #004085;
        }

        .btn-progress:hover {
            background: #b0d4f7;
        }

        .btn-resolve {
            background: #D1E7DD;
            color: #0A6640;
        }

        .btn-resolve:hover {
            background: #b8ddc9;
        }

        .btn-delete {
            background: #F8D7DA;
            color: #842029;
            width: 34px;
            height: 30px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-delete:hover {
            background: #f1aeb5;
        }

        .btn-pdf {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 10px;
            background: #eef4fb;
            color: #0E4D92;
            text-decoration: none;
        }

        .btn-pdf:hover {
            background: #dceafb;
        }

        /* =========================
           VIEW PHOTO
        ========================= */

        .view-link {
            color: #8B6914;
            font-size: 13px;
            text-decoration: none;
            font-weight: 500;
            white-space: nowrap;
        }

        .view-link:hover {
            text-decoration: underline;
        }

        /* =========================
           EMPTY STATE
        ========================= */

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #aaa;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 768px) {

            .sidebar {
                width: 190px;
            }

            .main {
                margin-left: 190px;
            }

            .content {
                padding: 18px;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .search-wrap,
            .search-wrap input {
                width: 100%;
            }

            .topbar {
                padding: 14px 18px;
            }
        }
    </style>
</head>

<body>

    <!-- =========================
         SIDEBAR
    ========================= -->

    <div class="sidebar">

        <div class="sidebar-logo">
            <img src="../assets/img/gerona-seal.png" alt="Seal">
            <span>
                Ragsak Ranyag<br>
                Gerona
            </span>
        </div>

        <div class="nav-label">
            Main Menu
        </div>

        <a class="nav-item" href="dashboard.php">
            <i class="fa-solid fa-house"></i>
            Dashboard
        </a>

        <a class="nav-item" href="residents.php">
            <i class="fa-solid fa-users"></i>
            Residents

            <?php if ($pending_users > 0): ?>
                <span class="badge-count">
                    <?= $pending_users ?>
                </span>
            <?php endif; ?>
        </a>

        <a class="nav-item active" href="complaints.php">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Complaints

            <?php if ($pending_complaints > 0): ?>
                <span class="badge-count">
                    <?= $pending_complaints ?>
                </span>
            <?php endif; ?>
        </a>

        <a class="nav-item" href="rewards.php">
            <i class="fa-solid fa-trophy"></i>
            Rewards
        </a>

        <a class="nav-item" href="reports.php">
            <i class="fa-solid fa-chart-bar"></i>
            Reports
        </a>
        <a class="nav-item" href="verify_reward.php">
        <i class="fa-solid fa-chart-bar"></i> Verify Rewards
    </a>

        <div class="sidebar-bottom">
            <a href="../logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>
        </div>

    </div>


    <!-- =========================
         MAIN CONTENT
    ========================= -->

    <div class="main">

        <!-- TOPBAR -->

        <div class="topbar">

            <h1>
                Complaints
            </h1>

            <div style="display:flex;align-items:center;gap:12px">

                <span style="font-size:14px;font-weight:600">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </span>

                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                </div>

            </div>

        </div>


        <!-- CONTENT -->

        <div class="content">

            <div class="table-card">

                <!-- TABLE HEADER -->

                <div class="table-header">

                    <h3>
                        All Complaints
                    </h3>

                    <div class="search-wrap">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="text"
                            id="searchInput"
                            placeholder="Search complaints..."
                        >

                    </div>

                </div>


                <!-- TABLE -->

                <?php if (empty($complaints)): ?>

                    <div class="empty-state">

                        <i class="fa-solid fa-folder-open"></i>

                        <p>
                            No complaints filed yet.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="table-container">

                        <table id="complaintsTable">

                            <thead>

                                <tr>
                                    <th>#</th>
                                    <th>Resident</th>
                                    <th>Category</th>
                                    <th>Barangay</th>
                                    <th>Location</th>
                                    <th>Description</th>
                                    <th>Photo</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th style="min-width:210px;">
                                        Action
                                    </th>
                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($complaints as $i => $c): ?>

                                    <tr>

                                        <!-- NUMBER -->

                                        <td>
                                            <?= $i + 1 ?>
                                        </td>


                                        <!-- RESIDENT -->

                                        <td>

                                            <div style="font-weight:600">
                                                <?= htmlspecialchars($c['full_name']) ?>
                                            </div>

                                            <div style="font-size:12px;color:#888">
                                                <?= htmlspecialchars($c['email']) ?>
                                            </div>

                                        </td>


                                        <!-- CATEGORY -->

                                        <td>
                                            <?= htmlspecialchars($c['category']) ?>
                                        </td>


                                        <!-- BARANGAY -->

                                        <td>
                                            <?= htmlspecialchars($c['barangay']) ?>
                                        </td>


                                        <!-- LOCATION -->

                                        <td style="
                                            max-width:140px;
                                            white-space:nowrap;
                                            overflow:hidden;
                                            text-overflow:ellipsis;
                                        ">
                                            <?= htmlspecialchars($c['location']) ?>
                                        </td>


                                        <!-- DESCRIPTION -->

                                        <td style="
                                            max-width:180px;
                                            white-space:nowrap;
                                            overflow:hidden;
                                            text-overflow:ellipsis;
                                        ">
                                            <?= htmlspecialchars($c['description']) ?>
                                        </td>


                                        <!-- PHOTO -->

                                        <td>

                                            <?php if ($c['photo']): ?>

                                                <a
                                                    class="view-link"
                                                    href="../<?= htmlspecialchars($c['photo']) ?>"
                                                    target="_blank"
                                                >
                                                    <i class="fa-solid fa-image"></i>
                                                    View
                                                </a>

                                            <?php else: ?>

                                                <span style="color:#bbb;font-size:13px">
                                                    —
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <span class="badge badge-<?= htmlspecialchars($c['status']) ?>">

                                                <?= ucfirst(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $c['status']
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- DATE -->

                                        <td style="color:#888;font-size:13px">

                                            <?= date(
                                                'M d, Y \a\t h:i A',
                                                strtotime($c['created_at'])
                                            ) ?>

                                        </td>


                                        <!-- ACTION -->

                                        <td>

                                            <div class="action-buttons">

                                                <a
                                                    class="action-btn btn-pdf"
                                                    href="../resident/complaint_pdf.php?id=<?= (int) $c['id'] ?>"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                    PDF
                                                </a>

                                                <?php if ($c['status'] === 'pending'): ?>

                                                    <form
                                                        action="update_complaint.php"
                                                        method="POST"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="complaint_id"
                                                            value="<?= $c['id'] ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="status"
                                                            value="in_progress"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="action-btn btn-progress"
                                                        >
                                                            In Progress
                                                        </button>

                                                    </form>

                                                <?php endif; ?>


                                                <?php if ($c['status'] !== 'resolved'): ?>

                                                    <form
                                                        action="update_complaint.php"
                                                        method="POST"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="complaint_id"
                                                            value="<?= $c['id'] ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="status"
                                                            value="resolved"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="action-btn btn-resolve"
                                                        >
                                                            Resolve
                                                        </button>

                                                    </form>

                                                <?php endif; ?>


                                                <!-- DELETE -->

                                                <form
                                                    action="delete_complaint.php"
                                                    method="POST"
                                                    onsubmit="return confirm('Are you sure you want to delete this complaint?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="complaint_id"
                                                        value="<?= $c['id'] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="action-btn btn-delete"
                                                        title="Delete Complaint"
                                                    >
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- =========================
         SEARCH SCRIPT
    ========================= -->

    <script>

        const searchInput = document.getElementById('searchInput');

        if (searchInput) {

            searchInput.addEventListener('input', function () {

                const val = this.value.toLowerCase();

                document
                    .querySelectorAll('#complaintsTable tbody tr')
                    .forEach(row => {

                        row.style.display =
                            row.textContent.toLowerCase().includes(val)
                                ? ''
                                : 'none';

                    });

            });

        }

    </script>

</body>

</html>
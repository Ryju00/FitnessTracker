<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!Auth::isLoggedIn() || Auth::getRole() !== 'admin') {
    header('Location: ../index.php');
    exit();
}

header('Location: admin_dashboard.php');
exit();

if ($_POST['action'] ?? '') {
    header('Content-Type: application/json');
    switch ($_POST['action']) {
        case 'delete_user':
            $id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
            if ($id) {
                $stmt = $db->getConnection()->prepare("DELETE FROM users WHERE id=? AND role!='admin'");
                echo json_encode(['success' => $stmt->execute([$id])]);
            }
            break;
        case 'export_csv':
            $sql = "SELECT u.email, w.* FROM workouts w JOIN users u ON w.user_id=u.id ORDER BY w.date DESC";
            $stmt = $db->getConnection()->query($sql);
            $filename = 'raport_treningi_' . date('Y-m-d') . '.csv';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Email', 'Typ', 'Data', 'Reps', 'Waga']);
            foreach ($stmt->fetchAll() as $row) fputcsv($output, $row);
            exit();
        case 'active_users':
            $count = $db->getConnection()->query("SELECT COUNT(DISTINCT user_id) FROM workouts WHERE created_at > NOW() - INTERVAL 1 DAY")->fetchColumn();
            echo json_encode(['count' => (int)$count]);
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Fitness Tracker Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .gradient-text {
            background: linear-gradient(to right, #ffd700, #ff8c00);
            background-size: 400% auto;
            background-clip: text;
            color: transparent;
            animation: move-bg 8s linear infinite;
        }

        @keyframes move-bg {
            to {
                background-position: 400% 0;
            }
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand gradient-text">Admin Panel</a>
            <div class="navbar-nav ms-auto">
                <a href="../user/dashboard.php" class="nav-link">Dashboard</a>
                <a href="../auth/logout.php" class="nav-link">Wyloguj</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="gradient-text text-center mb-4">Zarządzanie Użytkownikami & Raporty</h1>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <h5>Użytkownicy (<?php echo $db->getConnection()->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?>)</h5>
                <button class="btn btn-success" onclick="exportCSV()">📥 Eksport CSV Treningi</button>
            </div>
            <div class="card-body">
                <table class="table table-striped" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Rola</th>
                            <th>Utworzono</th>
                            <th>Weryfikacja</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $users = $db->getConnection()->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
                        foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><span class="badge <?= $user['role'] == 'admin' ? 'bg-danger' : 'bg-primary' ?>"><?= $user['role'] ?></span></td>
                                <td><?= $user['created_at'] ?></td>
                                <td><?= $user['email_verified'] ? '✅' : '❌' ?></td>
                                <td>
                                    <?php if ($user['role'] != 'admin'): ?>
                                        <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $user['id'] ?>)">Usuń</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center bg-primary text-white">
                    <div class="card-body">
                        <h5><?php echo $db->getConnection()->query("SELECT COUNT(*) FROM workouts")->fetchColumn(); ?></h5>
                        <p>Całkowitych Treningów</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-success text-white">
                    <div class="card-body">
                        <h5><?php echo $db->getConnection()->query("SELECT SUM(calories) FROM nutrition_logs")->fetchColumn() ?? 0; ?></h5>
                        <p>Suma Kcal (wszystkie logi)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-info text-white">
                    <div class="card-body">
                        <h5 id="activeUsers">Aktywni dziś</h5>
                        <p>Użytkownicy (ostatnie 24h)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function deleteUser(id) {
            if (confirm('Usunąć użytkownika i jego dane?')) {
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('user_id', id);
                const res = await fetch('admin.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) location.reload();
            }
        }

        async function exportCSV() {
            const formData = new FormData();
            formData.append('action', 'export_csv');
            window.location = 'admin.php?' + new URLSearchParams(formData);
        }

        fetch('admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=active_users'
        }).then(r => r.json()).then(d => {
            document.getElementById('activeUsers').textContent = d.count || 0;
        });
    </script>
</body>

</html>
<?php
session_start();
require_once 'includes/auth.php';

require_once 'includes/db.php';

$auth = new Auth();
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

if (Auth::isLoggedIn()) {
    header('Location: user/dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        $res = $auth->register($_POST['email'], $_POST['password']);
        if ($res['success']) {
            $_SESSION['success'] = $res['message'];
            header('Location: index.php');
            exit();
        } else {
            $error = $res['message'];
        }
    } elseif (isset($_POST['login'])) {
        $res = $auth->login($_POST['email'], $_POST['password']);
        if ($res['success']) {
            if ($res['role'] === 'admin') {
                header('Location: admin/admin.php');
                exit();
            }
            
            $db = Database::getInstance();
            $userId = $_SESSION['user_id'] ?? null;
            if ($userId) {
                $stmt = $db->getConnection()->prepare("SELECT data FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $row = $stmt->fetch();
                if ($row && (int)$row['data'] === 0) {
                    header('Location: user/profile.php');
                    exit();
                }
            }
            header('Location: user/dashboard.php');
            exit();
        } else {
            $error = $res['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #003049 0%, #003049 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .gradient-text {
            background: linear-gradient(135deg, #f77f00 0%, #fcbf49 100%);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            font-size: clamp(2rem, 6vw, 3rem);
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .auth-container {
            max-width: 900px;
            width: 100%;
        }

        .auth-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            background: white;
            overflow: hidden;
        }

        .auth-card .card-body {
            padding: 3rem;
        }

        .subtitle {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .form-control {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #f77f00;
            box-shadow: 0 0 0 3px rgba(247, 127, 0, 0.1);
        }

        .btn-auth {
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            background: linear-gradient(135deg, #f77f00 0%, #fcbf49 100%);
            color: #003049;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(247, 127, 0, 0.3);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }

        .divider span {
            padding: 0 1rem;
            color: #718096;
            font-weight: 500;
        }

        .feature-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #003049 0%, #f77f00 100%);
            color: white;
            border-radius: 20px;
            font-size: 0.9rem;
            margin: 0.25rem;
        }

        .btn-success {
            background: linear-gradient(135deg, #f77f00, #fcbf49) !important;
            border: none !important;
            color: #003049 !important;
            font-weight: 600;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #fcbf49, #f77f00) !important;
            color: #003049 !important;
        }
    </style>
</head>

<body class="d-flex align-items-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="auth-card card">
                    <div class="card-body">
                        <h1 class="gradient-text text-center mb-5">Fitness Tracker</h1>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($success) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="row g-5">
                            <div class="col-md-6 pe-md-4 border-end">
                                <h4 class="mb-4 text-center">Zaloguj się</h4>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" placeholder="twoj@email.pl" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Hasło</label>
                                        <input type="password" name="password" class="form-control" placeholder="••••••••" required minlength="8">
                                    </div>
                                    <button type="submit" name="login" class="btn btn-auth btn-primary w-100">Zaloguj się</button>
                                </form>
                            </div>

                            <div class="col-md-6 ps-md-4">
                                <h4 class="mb-4 text-center">Zarejestruj się</h4>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" placeholder="twoj@email.pl" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Hasło (min 8 znaków)</label>
                                        <input type="password" name="password" class="form-control" placeholder="••••••••" required minlength="8">
                                    </div>
                                    <button type="submit" name="register" class="btn btn-auth btn-success w-100">Załóż konto</button>
                                </form>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
session_start();
require_once '../includes/auth.php';
if (!Auth::isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}
$userId = Auth::getUserId();
$db = Database::getInstance();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Posiłków</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            min-height: 100vh;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            background: white;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: linear-gradient(135deg, #ffb347 0%, #ffe5b4 100%) !important;
            border: none !important;
            border-radius: 16px 16px 0 0 !important;
            padding: 1.5rem !important;
        }

        .card-header h3 {
            color: #000 !important;
            font-weight: 600 !important;
            margin: 0 !important;
        }

        .table-scroll-wrapper {
            max-height: 600px;
            overflow-y: auto;
            overflow-x: hidden;
            border-radius: 10px;
        }

        .table {
            margin-bottom: 0;
            table-layout: fixed;
            width: 100%;
        }

        .table td, .table th {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }

        .table thead th {
            border-bottom: 2px solid #fcbf49;
            color: #fcbf49;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(252, 191, 73, 0.1) !important;
            transform: scale(1.01);
        }

        .btn-light {
            background: white !important;
            color: #fcbf49 !important;
            border: none !important;
        }

        .btn-light:hover {
            background: #fcbf49 !important;
            color: #003049 !important;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #d62828, #003049) !important;
            border: none !important;
            color: white !important;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #d62828, #003049) !important;
            color: white !important;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #d62828, #003049);
            border: none;
            font-weight: 500;
            transition: all 0.3s ease;
            color: white;
        }

        .btn-danger:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(238, 90, 111, 0.4);
        }
    </style>
</head>
<body class="py-5">
    <div class="container mt-5">
        <a href="../user/dashboard.php" class="btn btn-light mb-4 shadow-sm" style="border-radius: 10px;">
            ← Powrót do Dashboard
        </a>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h3>🍽️ Historia Posiłków</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4 g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Szukaj po nazwie:</label>
                                <input type="text" id="searchName" class="form-control" placeholder="Wpisz nazwę posiłku..." style="border-radius: 10px;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Data od:</label>
                                <input type="date" id="dateFrom" class="form-control" style="border-radius: 10px;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Data do:</label>
                                <input type="date" id="dateTo" class="form-control" style="border-radius: 10px;">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-success w-100" onclick="clearSearch()" style="border-radius: 10px; background: linear-gradient(135deg, #f77f00, #fcbf49) !important; border: none !important; color: #003049 !important;">Wyczyść</button>
                            </div>
                        </div>
                        <div class="table-scroll-wrapper">
                            <table class="table table-striped" id="nutritionTable">
                                <thead class="sticky-top bg-light">
                                    <tr>
                                        <th>Data</th>
                                        <th>Posiłek</th>
                                        <th>Kcal</th>
                                        <th>B</th>
                                        <th>W</th>
                                        <th>T</th>
                                        <th>Akcja</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function copyMealToToday(mealName, calories, protein, carbs, fats) {
            try {
                const today = new Date().toISOString().split('T')[0];
                
                const payload = {
                    meal_name: mealName,
                    calories: calories,
                    protein: protein,
                    carbs: carbs,
                    fats: fats,
                    date: today
                };

                const res = await fetch('../api/nutrition.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (data.success) {
                    alert('✅ Posiłek został dodany do dzisiejszych!');
                } else {
                    alert('❌ Błąd: ' + (data.message || 'Nie udało się skopiować posiłku'));
                }
            } catch (err) {
                console.error('Błąd kopiowania posiłku:', err);
                alert('❌ Błąd podczas kopiowania posiłku');
            }
        }

        let allNutritionData = [];

        async function loadAllNutrition() {
            try {
                const res = await fetch('../api/nutrition.php', { credentials: 'same-origin' });
                const data = await res.json();
                allNutritionData = (data && data.data) ? data.data : [];
                console.log('All nutrition:', allNutritionData);
                renderNutrition(allNutritionData);
            } catch (err) {
                console.error('Błąd ładowania posiłków:', err);
            }
        }

        function renderNutrition(nutrition) {
            const tbody = document.querySelector('#nutritionTable tbody');
            if (!nutrition || nutrition.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Brak posiłków</td></tr>';
            } else {
                tbody.innerHTML = nutrition.map(n => {
                    const date = new Date(n.date).toLocaleDateString('pl-PL');
                    return `
                        <tr>
                            <td><strong>${date}</strong></td>
                            <td>${n.meal_name || n.name || '-'}</td>
                            <td>${n.calories || 0}</td>
                            <td>${n.protein || 0}</td>
                            <td>${n.carbs || 0}</td>
                            <td>${n.fats || 0}</td>
                            <td><button class="btn btn-sm" style="background: linear-gradient(135deg, #fcbf49, #f77f00); color: #003049; border: none;" onclick="copyMealToToday('${(n.meal_name || n.name || '').replace(/'/g, "\\'")}', ${n.calories || 0}, ${n.protein || 0}, ${n.carbs || 0}, ${n.fats || 0})">📋 Kopiuj</button></td>
                        </tr>
                    `;
                }).join('');
            }
        }

        function filterNutrition() {
            const searchName = document.getElementById('searchName').value.toLowerCase();
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;

            const filtered = allNutritionData.filter(n => {
                const mealName = (n.meal_name || n.name || '').toLowerCase();
                const matchName = !searchName || mealName.includes(searchName);
                const nutritionDate = new Date(n.date).toISOString().split('T')[0];
                const matchDateFrom = !dateFrom || nutritionDate >= dateFrom;
                const matchDateTo = !dateTo || nutritionDate <= dateTo;

                return matchName && matchDateFrom && matchDateTo;
            });

            renderNutrition(filtered);
        }

        function clearSearch() {
            document.getElementById('searchName').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            renderNutrition(allNutritionData);
        }

        document.getElementById('searchName').addEventListener('input', filterNutrition);
        document.getElementById('dateFrom').addEventListener('change', filterNutrition);
        document.getElementById('dateTo').addEventListener('change', filterNutrition);


        loadAllNutrition();
    </script>
</body>
</html>

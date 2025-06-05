<?php
require_once 'ddos_detector.php';

if (isset($_POST['remove_blacklist'])) {
    $ip = $_POST['remove_blacklist'];
    if (($key = array_search($ip, $_SESSION['blacklist'])) !== false) {
        unset($_SESSION['blacklist'][$key]);
        $_SESSION['blacklist'] = array_values($_SESSION['blacklist']);
    }
    header('Location: index.php');
    exit;
}
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    $logs = $_SESSION['ddos_logs'];
    if ($type === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="ddos_logs.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array_keys(reset($logs)));
        foreach ($logs as $row) fputcsv($out, $row);
        fclose($out);
        exit;
    } elseif ($type === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="ddos_logs.json"');
        echo json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DDoS Tespit Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-dark text-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-lg border-0 bg-gradient bg-dark text-light mb-4">
                    <div class="card-body">
                        <h1 class="card-title text-center mb-4">DDoS Tespit Paneli</h1>
                        <hr class="mb-4">
                        <div class="alert <?php echo $ddos_alert_class; ?> text-center" role="alert">
                            <?php echo $ddos_message; ?>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-8 mx-auto">
                                <!-- IP takip formu kaldırıldı -->
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h5>En Çok İstek Atan IP'ler (Son 1 dk)</h5>
                                <ul class="list-group mb-3">
                                    <?php $i=0; foreach(array_slice($ip_stats,0,5) as $ip=>$count): $i++; ?>
                                    <li class="list-group-item bg-dark text-light d-flex justify-content-between align-items-center">
                                        <span><?php echo $ip; ?></span>
                                        <span class="badge bg-primary rounded-pill"><?php echo $count; ?></span>
                                        <?php if (in_array($ip, $_SESSION['blacklist'])): ?>
                                            <span class="badge bg-danger ms-2">Kara Liste</span>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; if($i==0): ?>
                                    <li class="list-group-item bg-dark text-light">Kayıt yok</li>
                                    <?php endif; ?>
                                </ul>
                                <h6 class="mt-3">Sizin IP'niz: <strong><?php echo $_SERVER['REMOTE_ADDR']; ?></strong></h6>
                                <p>Son 1 dakikadaki istek sayınız: <strong><?php echo isset($ip_stats[$_SERVER['REMOTE_ADDR']]) ? $ip_stats[$_SERVER['REMOTE_ADDR']] : 0; ?></strong></p>
                                <h6 class="mt-4">Kara Liste Yönetimi</h6>
                                <ul class="list-group">
                                    <?php foreach($_SESSION['blacklist'] as $bl_ip): ?>
                                    <li class="list-group-item bg-dark text-light d-flex justify-content-between align-items-center">
                                        <span><?php echo $bl_ip; ?></span>
                                        <form method="post" style="display:inline">
                                            <input type="hidden" name="remove_blacklist" value="<?php echo $bl_ip; ?>">
                                            <button class="btn btn-sm btn-outline-danger">Çıkar</button>
                                        </form>
                                    </li>
                                    <?php endforeach; if(count($_SESSION['blacklist'])==0): ?>
                                    <li class="list-group-item bg-dark text-light">Kara listede IP yok</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Son 10 Dakika Trafik Grafiği</h5>
                                <canvas id="trafficChart" height="180"></canvas>
                                <script>
                                    const trafficLabels = <?php echo json_encode(array_keys($_SESSION['traffic'])); ?>;
                                    const trafficData = <?php echo json_encode(array_values($_SESSION['traffic'])); ?>;
                                    new Chart(document.getElementById('trafficChart').getContext('2d'), {
                                        type: 'line',
                                        data: {
                                            labels: trafficLabels,
                                            datasets: [{
                                                label: 'İstek Sayısı',
                                                data: trafficData,
                                                borderColor: '#0d6efd',
                                                backgroundColor: 'rgba(13,110,253,0.2)',
                                                tension: 0.3,
                                                fill: true
                                            }]
                                        },
                                        options: {
                                            plugins: { legend: { display: false } },
                                            scales: {
                                                x: { ticks: { color: '#fff' } },
                                                y: { beginAtZero: true, ticks: { color: '#fff' } }
                                            }
                                        }
                                    });
                                </script>
                                <h5 class="mt-4">Canlı Trafik Akışı (Son 1 dk)</h5>
                                <div class="table-responsive" style="max-height:200px;overflow:auto;">
                                    <table class="table table-dark table-striped table-bordered align-middle">
                                        <thead>
                                            <tr>
                                                <th>Zaman</th>
                                                <th>IP</th>
                                                <th>User-Agent</th>
                                                <th>Port</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($_SESSION['live_traffic'] as $row): ?>
                                            <tr>
                                                <td><?php echo $row['time']; ?></td>
                                                <td><?php echo $row['ip']; ?></td>
                                                <td><?php echo htmlspecialchars($row['user_agent']); ?></td>
                                                <td><?php echo $row['port']; ?></td>
                                            </tr>
                                            <?php endforeach; if(count($_SESSION['live_traffic'])==0): ?>
                                            <tr><td colspan="4" class="text-center">Kayıt yok</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="col-12">
                                <h5>Saldırı Logları</h5>
                                <div class="mb-2">
                                    <a href="?export=csv" class="btn btn-sm btn-outline-light">CSV Olarak İndir</a>
                                    <a href="?export=json" class="btn btn-sm btn-outline-light">JSON Olarak İndir</a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-dark table-striped table-bordered align-middle">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>IP</th>
                                                <th>Ülke</th>
                                                <th>Şehir</th>
                                                <th>Bayrak</th>
                                                <th>Zaman</th>
                                                <th>İstek</th>
                                                <th>User-Agent</th>
                                                <th>Port</th>
                                                <th>Kara Liste</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $logs = array_reverse($_SESSION['ddos_logs']); $i=0; foreach($logs as $log): $i++; ?>
                                            <tr>
                                                <td><?php echo $i; ?></td>
                                                <td><?php echo $log['ip']; ?></td>
                                                <td><?php echo $log['country'] ?? ''; ?></td>
                                                <td><?php echo $log['city'] ?? ''; ?></td>
                                                <td><?php if(!empty($log['countryCode'])): ?><img src="https://flagcdn.com/16x12/<?php echo strtolower($log['countryCode']); ?>.png" alt="flag"><?php endif; ?></td>
                                                <td><?php echo $log['time']; ?></td>
                                                <td><?php echo $log['count']; ?></td>
                                                <td><?php echo htmlspecialchars($log['user_agent'] ?? ''); ?></td>
                                                <td><?php echo $log['port'] ?? ''; ?></td>
                                                <td><?php echo !empty($log['blacklisted']) ? '<span class="badge bg-danger">Evet</span>' : '<span class="badge bg-success">Hayır</span>'; ?></td>
                                            </tr>
                                            <?php endforeach; if($i==0): ?>
                                            <tr><td colspan="10" class="text-center">Kayıt yok</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <footer class="text-center mt-5 text-secondary">
                    <small>&copy; <?php echo date('Y'); ?> Modern DDoS Tespit Sistemi</small>
                </footer>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
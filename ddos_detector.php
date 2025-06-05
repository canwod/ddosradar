<?php
session_start();

$limit = 30; // 1 dakika içinde izin verilen maksimum istek
$interval = 60; // saniye cinsinden zaman aralığı
$ip = $_SERVER['REMOTE_ADDR'];
$now = time();

// Session'da gerekli alanları başlat
if (!isset($_SESSION['ddos'])) {
    $_SESSION['ddos'] = [];
}
if (!isset($_SESSION['ddos'][$ip])) {
    $_SESSION['ddos'][$ip] = [];
}
if (!isset($_SESSION['ddos_logs'])) {
    $_SESSION['ddos_logs'] = [];
}
if (!isset($_SESSION['traffic'])) {
    $_SESSION['traffic'] = [];
}

// IP'nin istek zamanını kaydet
array_push($_SESSION['ddos'][$ip], $now);

// Eski istekleri temizle (1 dakika)
$_SESSION['ddos'][$ip] = array_filter(
    $_SESSION['ddos'][$ip],
    function ($t) use ($interval, $now) {
        return $t >= ($now - $interval);
    }
);

// Son 10 dakika için trafik verisi (her dakika başı toplam istek)
$minute = date('Y-m-d H:i', $now);
if (!isset($_SESSION['traffic'][$minute])) {
    $_SESSION['traffic'][$minute] = 0;
}
$_SESSION['traffic'][$minute]++;
// 10 dakikadan eski verileri temizle
foreach ($_SESSION['traffic'] as $min => $count) {
    if (strtotime($min . ':00') < ($now - 600)) {
        unset($_SESSION['traffic'][$min]);
    }
}

// IP Geolocation fonksiyonu (ip-api.com)
function get_ip_info($ip) {
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return ['country' => 'Local', 'city' => 'Localhost', 'countryCode' => ''];
    }
    $url = "http://ip-api.com/json/" . $ip . "?fields=status,country,city,countryCode";
    $resp = @file_get_contents($url);
    if ($resp) {
        $data = json_decode($resp, true);
        if ($data['status'] === 'success') {
            return $data;
        }
    }
    return ['country' => 'Unknown', 'city' => '', 'countryCode' => ''];
}

// Blacklist başlat
if (!isset($_SESSION['blacklist'])) {
    $_SESSION['blacklist'] = [];
}
// Canlı trafik akışı başlat
if (!isset($_SESSION['live_traffic'])) {
    $_SESSION['live_traffic'] = [];
}
// Son 1 dakikadaki tüm istekleri kaydet
$_SESSION['live_traffic'][] = [
    'ip' => $ip,
    'time' => date('Y-m-d H:i:s', $now),
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'port' => $_SERVER['REMOTE_PORT'],
];
// 1 dakikadan eski kayıtları temizle
$_SESSION['live_traffic'] = array_filter($_SESSION['live_traffic'], function($row) use ($now) {
    return strtotime($row['time']) >= ($now - 60);
});

// Blacklist kontrolü
if (in_array($ip, $_SESSION['blacklist'])) {
    $ddos_message = 'IP adresiniz kara listede!';
    $ddos_alert_class = 'alert-danger';
    // Saldırı loguna ekle
    $_SESSION['ddos_logs'][] = [
        'ip' => $ip,
        'time' => date('Y-m-d H:i:s', $now),
        'count' => count($_SESSION['ddos'][$ip]),
        'country' => '',
        'city' => '',
        'countryCode' => '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'port' => $_SERVER['REMOTE_PORT'],
        'blacklisted' => true
    ];
} else if (count($_SESSION['ddos'][$ip]) > $limit) {
    $ip_info = get_ip_info($ip);
    $ddos_message = 'Çok fazla istek tespit edildi! Olası DDoS saldırısı.';
    $ddos_alert_class = 'alert-danger';
    // Log kaydı
    $_SESSION['ddos_logs'][] = [
        'ip' => $ip,
        'time' => date('Y-m-d H:i:s', $now),
        'count' => count($_SESSION['ddos'][$ip]),
        'country' => $ip_info['country'],
        'city' => $ip_info['city'],
        'countryCode' => $ip_info['countryCode'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'port' => $_SERVER['REMOTE_PORT'],
        'blacklisted' => false
    ];
    // Otomatik kara listeye ekle
    if (!in_array($ip, $_SESSION['blacklist'])) {
        $_SESSION['blacklist'][] = $ip;
    }
    // E-posta bildirimi (dummy)
    // mail('admin@site.com', 'DDoS Saldırı Tespiti', "IP: $ip, Ülke: {$ip_info['country']}, Şehir: {$ip_info['city']}");
} else {
    $ddos_message = 'Trafik normal. Herhangi bir saldırı tespit edilmedi.';
    $ddos_alert_class = 'alert-success';
}

// En çok istek atan IP'leri bul
$ip_stats = [];
foreach ($_SESSION['ddos'] as $ip_addr => $times) {
    $ip_stats[$ip_addr] = count(array_filter($times, function($t) use ($now, $interval) {
        return $t >= ($now - $interval);
    }));
}
arsort($ip_stats);

// Sunucunun kendi IP adresini al
$target_ip = $_SERVER['SERVER_ADDR'];

// Log dosyasının yolu
$log_file = 'ip_log.txt';

// İstek yapan IP adresini al
$client_ip = $_SERVER['REMOTE_ADDR'];

// Log dosyasına IP'yi ekle
file_put_contents($log_file, $client_ip . PHP_EOL, FILE_APPEND);

// Log dosyasını oku ve toplam istek sayısını bul
$log_data = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$total_requests = count($log_data);

// Sonucu göster
// echo "Sunucuya gelen toplam istek sayısı: $total_requests";
?> 
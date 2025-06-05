# Radar - IP İzleme ve İstek Sayacı

Bu proje, belirli bir IP adresine gelen istekleri izlemek ve bu IP'ye gelen toplam istek sayısını göstermek için geliştirilmiştir.

## Özellikler
- Sunucuya gelen her isteğin IP adresi `ip_log.txt` dosyasına kaydedilir.
- Belirlenen hedef IP adresine gelen toplam istek sayısı ekranda gösterilir.

## Kurulum ve Kullanım
1. **Dosyalar:**
    - `ddos_detector.php`: İstekleri loglar ve hedef IP'ye gelen istekleri sayar.
    - `ip_log.txt`: İstek yapan IP adreslerinin kaydedildiği dosya. Otomatik olarak oluşturulur.
2. **Hedef IP Adresi:**
    - İzlemek istediğiniz IP adresini `ddos_detector.php` dosyasındaki `$target_ip` değişkenine yazın.
    - Varsayılan olarak `45.95.214.204` olarak ayarlanmıştır.
3. **Kullanım:**
    - Sunucunuzda `ddos_detector.php` dosyasını çalıştırın veya tarayıcıdan açın.
    - Her istek geldiğinde, IP adresi loglanır ve hedef IP'ye gelen toplam istek sayısı ekranda gösterilir.

## Notlar
- `ip_log.txt` dosyası zamanla büyüyebilir. Gerektiğinde temizleyebilirsiniz.
- Farklı bir IP adresini izlemek için `$target_ip` değişkenini güncelleyin.
- Test için farklı cihazlardan veya VPN ile sunucuya istek gönderebilirsiniz.

## Güvenlik
- Bu sistem temel bir izleme ve sayaç mantığı sunar. Gerçek zamanlı saldırı tespiti veya gelişmiş güvenlik için ek önlemler alınmalıdır.

---

Herhangi bir sorunuz olursa, lütfen iletişime geçin. 
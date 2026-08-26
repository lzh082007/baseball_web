$OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$XamppPath       = "C:\xampp"
$CloudflaredPath = "C:\cloudflared\cloudflared.exe"
$ProjectPath     = "C:\xampp\htdocs\NUTC_baseball_team_web"
$StateFile       = "$env:TEMP\nutc_tunnel.state"
$LogPath         = "$env:TEMP\nutc_cloudflared.log"

Write-Host "======================================================" -ForegroundColor Cyan
Write-Host "  NUTC Baseball Team Web - Cloudflare Tunnel 啟動器" -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host ""

# [1/6] 環境檢查
Write-Host "[1/6] 正在檢查執行環境..." -ForegroundColor Yellow
if (-not (Test-Path $CloudflaredPath)) {
    Write-Host "[ERROR] 找不到 cloudflared.exe: $CloudflaredPath" -ForegroundColor Red
    Write-Host "請確認已完成工具下載安裝。" -ForegroundColor Gray
    exit 1
}
if (-not (Test-Path $XamppPath)) {
    Write-Host "[ERROR] 找不到 XAMPP 目錄: $XamppPath" -ForegroundColor Red
    exit 1
}
if (-not (Test-Path $ProjectPath)) {
    Write-Host "[ERROR] 找不到專案目錄: $ProjectPath" -ForegroundColor Red
    exit 1
}
Write-Host "[OK] 環境檢查通過。" -ForegroundColor Green

# [2/6] MariaDB 檢查與自動啟動 (純連線健康檢測，絕無破壞性修復)
Write-Host "[2/6] 正在檢查 MariaDB 服務..." -ForegroundColor Yellow
$mysqlAdminPath = "$XamppPath\mysql\bin\mysqladmin.exe"
if (-not (Test-Path $mysqlAdminPath)) {
    Write-Host "[ERROR] 找不到 XAMPP mysqladmin.exe: $mysqlAdminPath" -ForegroundColor Red
    Write-Host "請確認 XAMPP MariaDB 安裝是否完整。" -ForegroundColor Gray
    exit 1
}

$mysqlProc = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if (-not $mysqlProc) {
    Write-Host "[INFO] MariaDB 未在執行，正在自動啟動..." -ForegroundColor Cyan
    Start-Process -FilePath "$XamppPath\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=$XamppPath\mysql\bin\my.ini","--standalone" -WorkingDirectory "$XamppPath\mysql" -WindowStyle Hidden
    Start-Sleep -Seconds 2
}

$mariaAlive = $false
for ($i = 0; $i -lt 10; $i++) {
    try {
        $pingOutput = & "$mysqlAdminPath" -u root ping 2>&1
        if ($pingOutput -match "mysqld is alive") {
            $mariaAlive = $true
            break
        }
    } catch {}
    Start-Sleep -Seconds 1
}

if (-not $mariaAlive) {
    Write-Host "[ERROR] MariaDB 無法正常連線。" -ForegroundColor Red
    Write-Host "請檢查 XAMPP MariaDB 狀態與設定（注意：為保護資料，腳本絕不自動修改或修復資料庫）。" -ForegroundColor Gray
    exit 1
}
Write-Host "[OK] MariaDB 正常連線中 (mysqld is alive)。" -ForegroundColor Green

# [3/6] Apache 檢查、自動啟動與 Port 實體主動探測
Write-Host "[3/6] 正在檢查 Apache 服務與主動探測 HTTP Port..." -ForegroundColor Yellow

# 收集候選 Port
$candidatePorts = [System.Collections.Generic.List[int]]::new()
$httpdConfPath = "$XamppPath\apache\conf\httpd.conf"
if (Test-Path $httpdConfPath) {
    $listenLines = Get-Content $httpdConfPath | Where-Object { $_ -match '^\s*Listen\s+' }
    foreach ($line in $listenLines) {
        if ($line -match '^\s*Listen\s+(?:.*:)?([0-9]+)') {
            $p = [int]$matches[1]
            if ($p -ne 443 -and -not $candidatePorts.Contains($p)) {
                $candidatePorts.Add($p)
            }
        }
    }
}

# 預設候選 Port 防底
if (-not $candidatePorts.Contains(8080)) { $candidatePorts.Add(8080) }
if (-not $candidatePorts.Contains(80)) { $candidatePorts.Add(80) }

$apacheProc = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if (-not $apacheProc) {
    # 檢查第一個候選 Port 是否被其他非 Apache 程式占用
    $primaryPort = $candidatePorts[0]
    $occupied = $null
    try {
        $occupied = Get-NetTCPConnection -LocalPort $primaryPort -State Listen -ErrorAction SilentlyContinue
    } catch {}
    if ($occupied) {
        Write-Host "[ERROR] Apache 預定使用的 Port $primaryPort 目前已被其他程式占用。" -ForegroundColor Red
        exit 1
    }

    Write-Host "[INFO] Apache 未在執行，正在自動啟動..." -ForegroundColor Cyan
    Start-Process -FilePath "$XamppPath\apache\bin\httpd.exe" -WorkingDirectory "$XamppPath\apache" -WindowStyle Hidden
    Start-Sleep -Seconds 3
}

# 若 Apache 正在運行，追加當前 TCP 監聽的 Port
$runningApache = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if ($runningApache) {
    try {
        $tcpConns = Get-NetTCPConnection -State Listen -OwningProcess ($runningApache | Select-Object -ExpandProperty Id) -ErrorAction SilentlyContinue
        if ($tcpConns) {
            foreach ($conn in $tcpConns) {
                if ($conn.LocalPort -ne 443 -and -not $candidatePorts.Contains($conn.LocalPort)) {
                    $candidatePorts.Add($conn.LocalPort)
                }
            }
        }
    } catch {}
}

# 實體服務探測 (Active Probe) 確定真正提供 /NUTC_baseball_team_web/ 的 Port
$ApachePort = $null
foreach ($p in $candidatePorts) {
    try {
        $testUrl = "http://localhost:$p/NUTC_baseball_team_web/"
        $testRes = Invoke-WebRequest -Uri $testUrl -UseBasicParsing -TimeoutSec 3 -ErrorAction Stop
        if ($testRes.StatusCode -ge 200 -and $testRes.StatusCode -lt 400) {
            $ApachePort = $p
            break
        }
    } catch {}
}

if (-not $ApachePort) {
    Write-Host "[ERROR] Apache 啟動失敗或無法找到提供 NUTC Baseball Team Web 的有效 HTTP Port。" -ForegroundColor Red
    Write-Host "請檢查 XAMPP 控制面板、Port 衝突或 Apache Error Log。" -ForegroundColor Gray
    exit 1
}
Write-Host "[OK] Apache 正在運行，實測有效服務 Port: $ApachePort" -ForegroundColor Green

# [4/6] 本地專案健康檢查
Write-Host "[4/6] 正在檢查 NUTC Baseball Team Web 本地可用性..." -ForegroundColor Yellow
$localWebUrl = "http://localhost:$ApachePort/NUTC_baseball_team_web/"
$localOk = $false
try {
    $webRes = Invoke-WebRequest -Uri $localWebUrl -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
    if ($webRes.StatusCode -ge 200 -and $webRes.StatusCode -lt 400) {
        $localOk = $true
    }
} catch {}

if (-not $localOk) {
    Write-Host "[ERROR] NUTC Baseball Team Web 無法正常存取 ($localWebUrl)。" -ForegroundColor Red
    Write-Host "請確認 Apache、PHP、專案檔案及資料庫連線設定。" -ForegroundColor Gray
    exit 1
}
Write-Host "[OK] 本地網站運作正常 ($localWebUrl)。" -ForegroundColor Green

# [5/6] 啟動 Cloudflare Quick Tunnel 並擷取公開網址
Write-Host "[5/6] 正在建立 Cloudflare Quick Tunnel (Port $ApachePort)..." -ForegroundColor Yellow

$tunnelProc = $null
$existingPid = $null
if (Test-Path $StateFile) {
    $existingPid = (Get-Content $StateFile -ErrorAction SilentlyContinue) | Out-String
    if ($existingPid) { $existingPid = $existingPid.Trim() }
}

if ($existingPid -and (Get-Process -Id $existingPid -ErrorAction SilentlyContinue)) {
    Write-Host "[INFO] 偵測到現有 Cloudflare Tunnel 進程正在運行 (PID: $existingPid)。" -ForegroundColor Cyan
    $tunnelProc = Get-Process -Id $existingPid
} else {
    if (Test-Path $LogPath) { Remove-Item $LogPath -Force -ErrorAction SilentlyContinue }
    $tunnelProc = Start-Process -FilePath $CloudflaredPath -ArgumentList "tunnel","--url","http://localhost:$ApachePort" -RedirectStandardError $LogPath -PassThru -WindowStyle Hidden
    Set-Content -Path $StateFile -Value $tunnelProc.Id -Force
}

$url = $null
for ($i = 0; $i -lt 30; $i++) {
    Start-Sleep -Seconds 1
    if (Test-Path $LogPath) {
        $match = Select-String -Path $LogPath -Pattern 'https://([a-zA-Z0-9-]+)\.trycloudflare\.com' | Select-Object -First 1
        if ($match) {
            $url = $match.Matches.Value
            break
        }
    }
}

if (-not $url) {
    Write-Host "[ERROR] Cloudflare Tunnel 啟動失敗，30 秒內沒有取得公開網址。" -ForegroundColor Red
    Write-Host "請參考日誌檔案: $LogPath" -ForegroundColor Gray
    exit 1
}
Write-Host "[OK] 成功取得 Cloudflare Tunnel 公開網址: $url" -ForegroundColor Green

# [6/6] 驗證公開 HTTPS 網址連通性
Write-Host "[6/6] 正在驗證公開 HTTPS 連通性..." -ForegroundColor Yellow
$publicTargetUrl = "$url/NUTC_baseball_team_web/"
$domain = ($url -replace 'https://','').Trim()
$publicOk = $false

for ($retry = 1; $retry -le 10; $retry++) {
    # 方式 1: 直接使用 Invoke-WebRequest
    try {
        $pubRes = Invoke-WebRequest -Uri $publicTargetUrl -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
        if ($pubRes.StatusCode -ge 200 -and $pubRes.StatusCode -lt 400) {
            $publicOk = $true
            break
        }
    } catch {}

    # 方式 2: 若本地 ISP DNS 尚未同步，使用 Cloudflare Public DNS (1.1.1.1) 解析 IP 透過 curl 驗證
    if (-not $publicOk) {
        try {
            $dns = Resolve-DnsName -Name $domain -Server 1.1.1.1 -Type A -ErrorAction SilentlyContinue
            if ($dns) {
                $cfIp = ($dns | Select-Object -First 1).IPAddress
                if ($cfIp) {
                    $curlOut = (& curl.exe -s -k --resolve "${domain}:443:${cfIp}" -o NUL -w "%{http_code}" "$publicTargetUrl") | Out-String
                    if ($curlOut) {
                        $codeStr = $curlOut.Trim()
                        if ($codeStr -match '^[23]\d{2}$') {
                            $publicOk = $true
                            break
                        }
                    }
                }
            }
        } catch {}
    }

    Start-Sleep -Seconds 2
}

if (-not $publicOk) {
    Write-Host "[ERROR] Cloudflare Tunnel 已建立，但公開網址無法正常存取。" -ForegroundColor Red
    exit 1
}
Write-Host "[OK] 公開 HTTPS 網址連通測試成功！" -ForegroundColor Green

# 輸出完整成功面板
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host " NUTC Baseball Team Web 啟動成功" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Apache Port:   $ApachePort" -ForegroundColor Yellow
Write-Host "Local URL:     $localWebUrl" -ForegroundColor Yellow
Write-Host "Public URL:    $publicTargetUrl" -ForegroundColor Yellow
Write-Host ""
Write-Host "Apache:        OK" -ForegroundColor Green
Write-Host "MariaDB:       OK" -ForegroundColor Green
Write-Host "Local Website: OK" -ForegroundColor Green
Write-Host "Cloudflare:    OK" -ForegroundColor Green
Write-Host "Public HTTPS:  OK" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "正在為您自動開啟預設瀏覽器..." -ForegroundColor Cyan
Start-Process $publicTargetUrl
Write-Host "（請保持此視窗開啟，關閉此視窗將中斷外網通道）" -ForegroundColor Gray
Write-Host ""

if ($tunnelProc) {
    $tunnelProc.WaitForExit()
}
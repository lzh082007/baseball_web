@echo off
chcp 65001 >nul
title 關閉 Cloudflare Tunnel

echo 正在關閉 Cloudflare Tunnel 進程...
taskkill /F /IM cloudflared.exe >nul 2>&1

if exist "%TEMP%\nutc_tunnel.state" (
    del /f /q "%TEMP%\nutc_tunnel.state" >nul 2>&1
)

echo [OK] Cloudflare Tunnel 已成功關閉！
timeout /t 2 >nul

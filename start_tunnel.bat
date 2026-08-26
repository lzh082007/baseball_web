@echo off
cd /d "%~dp0"
chcp 65001 >nul
title NUTC Baseball Team Web - Cloudflare Tunnel Launcher
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0start_tunnel.ps1"
if errorlevel 1 pause
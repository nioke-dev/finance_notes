@echo off
title One-Click Deployment Ke Server
echo Memulai deployment...
powershell -ExecutionPolicy Bypass -File "%~dp0deploy.ps1"
echo.
pause

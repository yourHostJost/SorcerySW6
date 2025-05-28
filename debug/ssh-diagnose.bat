@echo off
REM SSH Diagnose Script für Hetzner Cloud Server
REM Usage: debug\ssh-diagnose.bat

set HETZNER_IP=91.99.27.91
set SSH_KEY=server_key

echo ==========================================
echo SSH Diagnose fuer Hetzner Cloud Server
echo ==========================================
echo Server: %HETZNER_IP%
echo SSH-Key: %SSH_KEY%
echo Zeit: %date% %time%
echo.

echo 1. Ping Test...
echo ----------------
ping -n 3 %HETZNER_IP%
echo.

echo 2. SSH Port Test (Port 22)...
echo ------------------------------
echo Teste ob SSH-Port offen ist...
powershell -Command "Test-NetConnection -ComputerName %HETZNER_IP% -Port 22 -InformationLevel Detailed"
echo.

echo 3. SSH-Key Datei pruefen...
echo ---------------------------
if exist %SSH_KEY% (
    echo [OK] SSH-Key Datei existiert
    echo Dateigroesse:
    dir %SSH_KEY% | findstr %SSH_KEY%
) else (
    echo [FEHLER] SSH-Key Datei nicht gefunden!
)
echo.

echo 4. SSH-Key Berechtigungen (Windows)...
echo ---------------------------------------
icacls %SSH_KEY%
echo.

echo 5. SSH Verbindungstest (Verbose)...
echo ------------------------------------
echo Teste SSH-Verbindung mit Debug-Output...
ssh -v -i %SSH_KEY% -o ConnectTimeout=10 -o StrictHostKeyChecking=no root@%HETZNER_IP% "echo SSH Test erfolgreich" 2>&1
echo.

echo 6. Alternative SSH-Test (ohne Key)...
echo -------------------------------------
echo Teste ob SSH grundsaetzlich funktioniert...
ssh -o ConnectTimeout=5 -o PasswordAuthentication=no root@%HETZNER_IP% "echo Test" 2>&1
echo.

echo 7. Netzwerk-Route zu Server...
echo ------------------------------
tracert -h 10 %HETZNER_IP%
echo.

echo ==========================================
echo Diagnose abgeschlossen
echo ==========================================
echo.
echo Moegliche Probleme:
echo - Ping fehlschlaegt: Server/Netzwerk Problem
echo - Port 22 geschlossen: SSH-Service Problem
echo - SSH-Key Berechtigung: Windows-Berechtigungsproblem
echo - SSH-Verbindung timeout: Firewall/Routing Problem
echo - Permission denied: SSH-Key Problem
echo ==========================================

pause

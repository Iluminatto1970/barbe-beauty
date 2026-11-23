@echo off
title Barbearia PRO - Parando servidor local
set RUNTIME=%USERPROFILE%\.local\barbearia-runtime

echo Parando PHP...
taskkill /F /IM php.exe /T >nul 2>&1

echo Parando MariaDB...
"%RUNTIME%\mariadb\mariadb-11.4.8-winx64\bin\mysqladmin.exe" -u root --port=3306 shutdown >nul 2>&1
taskkill /F /IM mysqld.exe /T >nul 2>&1

echo.
echo Servidores locais parados. Os dados do banco continuam em %RUNTIME%\mariadb-data
pause

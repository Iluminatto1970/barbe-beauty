@echo off
title Barbearia PRO - Servidor Local (PHP + MariaDB)
set RUNTIME=%USERPROFILE%\.local\barbearia-runtime
set PROJETO=%~dp0

echo.
echo  =============================================
echo   Barbearia PRO - Iniciando servidor local
echo   Site:   http://localhost:8080
echo   Painel: http://localhost:8080/sistema
echo  =============================================
echo.

if not exist "%RUNTIME%\php\php.exe" (
    echo  ERRO: runtime portatil nao encontrado em:
    echo  %RUNTIME%
    pause
    exit /b 1
)

echo  [1/2] Iniciando MariaDB (porta 3306)...
start "barbearia-mariadb" /min "%RUNTIME%\mariadb\mariadb-11.4.8-winx64\bin\mysqld.exe" --datadir="%RUNTIME%\mariadb-data" --port=3306 --sql-mode=NO_ENGINE_SUBSTITUTION --console

timeout /t 8 /nobreak >nul

echo  [2/2] Iniciando PHP (porta 8080)...
set APP_BASE_PATH=
set DB_HOST=127.0.0.1
set DB_PORT=3306
set DB_NAME=barbearia
set DB_USER=root
set DB_PASSWORD=
set SEED_TEST_USERS=1
start "barbearia-php" /min "%RUNTIME%\php\php.exe" -S 127.0.0.1:8080 -t "%PROJETO%." "%RUNTIME%\router.php"

timeout /t 3 /nobreak >nul
start http://localhost:8080/

echo.
echo  Pronto! Acesse:
echo    Site ......... http://localhost:8080
echo    Painel admin . http://localhost:8080/sistema   (login: admin@admin / 123)
echo.
echo  Para parar, rode parar-local.bat
echo.
pause

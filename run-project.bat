@echo off
setlocal EnableDelayedExpansion
title LEAN ENTERPRISE - Project Runner
cd /d "%~dp0"

set "PROJECT_DIR=%~dp0"
set "FRONTEND_DIR=%~dp0lems-frontend"
set "PHP_PORT=8000"
set "NEXT_PORT=3000"

echo ================================================================
echo    LEAN ENTERPRISE - Project Runner
echo    Root: %PROJECT_DIR%
echo ================================================================
echo.
echo    [1] FULL STACK   MySQL + Laravel API :%PHP_PORT% + Next.js :%NEXT_PORT%
echo    [2] LARAVEL ONLY MySQL + Laravel API :%PHP_PORT%
echo    [3] NEXT.JS ONLY Next.js app :%NEXT_PORT%
echo    [0] Exit
echo.
set "CHOICE=1"
set /p "CHOICE=Choose option [default 1]: "
if "%CHOICE%"=="0" exit /b 0
if "%CHOICE%"=="1" goto :main
if "%CHOICE%"=="2" goto :main
if "%CHOICE%"=="3" goto :main
echo [ERROR] Unknown option "%CHOICE%"
pause
exit /b 1

:main
echo.
echo ---------------------------------------------------------------
echo [1/5] Checking PHP ...
REM Prefer XAMPP PHP (matches the MySQL extension set), then PATH.
set "PHP_EXE="
if exist "D:\xampp\php\php.exe" set "PHP_EXE=D:\xampp\php\php.exe"
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE (
    where php >nul 2>nul
    if not errorlevel 1 set "PHP_EXE=php"
)
if not defined PHP_EXE (
    echo [ERROR] PHP not found. Install XAMPP or add php.exe to PATH.
    pause
    exit /b 1
)
echo [OK] PHP = %PHP_EXE%

echo ---------------------------------------------------------------
echo [2/5] Checking Node.js ...
if "%CHOICE%"=="2" goto :node_done
where npm >nul 2>nul
if errorlevel 1 (
    echo [ERROR] npm not found on PATH. Install Node.js 18+ first.
    pause
    exit /b 1
)
echo [OK] npm found.
:node_done

echo ---------------------------------------------------------------
echo [3/5] Checking MySQL on port 3306 ...
if "%CHOICE%"=="3" goto :mysql_done
netstat -an | findstr ":3306" >nul 2>nul
if not errorlevel 1 (
    echo [OK] MySQL already running on 3306.
    goto :mysql_done
)
echo [MySQL] Not running - starting MySQL ...
if exist "D:\xampp\mysql\bin\mysqld.exe" (
    start "MySQL" /min "D:\xampp\mysql\bin\mysqld.exe"
) else if exist "C:\xampp\mysql\bin\mysqld.exe" (
    start "MySQL" /min "C:\xampp\mysql\bin\mysqld.exe"
) else (
    echo [ERROR] mysqld.exe not found. Start MySQL from the XAMPP Control Panel, then rerun.
    pause
    exit /b 1
)
set /a MYSQL_TRIES=0
:mysql_wait
timeout /t 2 /nobreak >nul
netstat -an | findstr ":3306" >nul 2>nul
if not errorlevel 1 (
    echo [OK] MySQL is up on 3306.
    goto :mysql_done
)
set /a MYSQL_TRIES+=1
if !MYSQL_TRIES! lss 20 goto :mysql_wait
echo [WARN] MySQL not detected on 3306 yet - continuing anyway.
:mysql_done

echo ---------------------------------------------------------------
echo [4/5] Checking Laravel app ...
if "%CHOICE%"=="3" goto :laravel_done
if not exist "%PROJECT_DIR%vendor\autoload.php" (
    echo [WARN] Composer dependencies missing. Run "composer install" in the project root.
)
if not exist "%PROJECT_DIR%.env" (
    echo [WARN] .env missing. Copy .env.example to .env and configure it.
)
if not exist "%PROJECT_DIR%public\storage" (
    echo [Laravel] Creating storage symlink ...
    "%PHP_EXE%" artisan storage:link
)
:laravel_done

echo ---------------------------------------------------------------
echo [5/5] Checking Next.js app ...
if "%CHOICE%"=="2" goto :fe_done
if not exist "%FRONTEND_DIR%\package.json" (
    echo [ERROR] lems-frontend\package.json not found.
    pause
    exit /b 1
)
if not exist "%FRONTEND_DIR%\node_modules" (
    echo [Frontend] First run - installing npm dependencies, please wait ...
    pushd "%FRONTEND_DIR%"
    call npm install
    popd
)
:fe_done

echo ---------------------------------------------------------------
echo [START] Launching services ...
if "%CHOICE%"=="3" goto :launch_next
start "LEMS - Laravel API :%PHP_PORT%" /D "%PROJECT_DIR%" cmd /k "%PHP_EXE%" artisan serve --host=0.0.0.0 --port=%PHP_PORT%
:launch_next
if "%CHOICE%"=="2" goto :launch_done
if not exist "%FRONTEND_DIR%\.next\BUILD_ID" (
    echo [Frontend] No production build yet - building once, please wait ...
    pushd "%FRONTEND_DIR%"
    call npm run build
    popd
)
netstat -an | findstr ":%NEXT_PORT% " | findstr "LISTENING" >nul 2>nul
if not errorlevel 1 (
    echo [ERROR] Port %NEXT_PORT% is already in use.
    echo         Close the other Next.js server window first, then rerun.
    pause
    exit /b 1
)
start "LEMS - Next.js :%NEXT_PORT%" /D "%FRONTEND_DIR%" cmd /k "npm run start"
:launch_done

echo ================================================================
echo    Laravel API : http://localhost:%PHP_PORT%
echo    Next.js app : http://localhost:%NEXT_PORT%
echo    Close a service window to stop that server.
echo ================================================================
timeout /t 3 >nul
if not "%CHOICE%"=="2" start "" http://localhost:%NEXT_PORT%
if not "%CHOICE%"=="3" start "" http://localhost:%PHP_PORT%
exit /b 0

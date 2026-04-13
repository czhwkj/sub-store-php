@echo off
chcp 65001 >nul
echo ========================================
echo   Sub-Store PHP 版本 - 快速启动脚本
echo ========================================
echo.

REM 检查 PHP 是否安装
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo [错误] 未检测到 PHP,请先安装 PHP 8.0+ 并添加到环境变量
    echo.
    echo 下载地址: https://windows.php.net/download/
    echo.
    pause
    exit /b 1
)

REM 显示 PHP 版本
echo [信息] 当前 PHP 版本:
php -v | findstr "PHP"
echo.

REM 检查必要扩展
echo [信息] 检查必要扩展...
php -m | findstr /i "pdo_sqlite" >nul
if %errorlevel% neq 0 (
    echo [警告] 缺少 pdo_sqlite 扩展
) else (
    echo [成功] pdo_sqlite 扩展已加载
)

php -m | findstr /i "curl" >nul
if %errorlevel% neq 0 (
    echo [警告] 缺少 curl 扩展
) else (
    echo [成功] curl 扩展已加载
)

php -m | findstr /i "mbstring" >nul
if %errorlevel% neq 0 (
    echo [警告] 缺少 mbstring 扩展
) else (
    echo [成功] mbstring 扩展已加载
)

echo.
echo [信息] 创建必要目录...
if not exist "storage" mkdir storage
if not exist "storage\logs" mkdir storage\logs
if not exist "storage\cache" mkdir storage\cache
if not exist "cron" mkdir cron

echo.
echo [信息] 启动开发服务器...
echo [信息] 访问地址: http://localhost:3000
echo [信息] 测试 API: http://localhost:3000/api/test
echo [信息] 按 Ctrl+C 停止服务器
echo.
echo ========================================
echo.

php -S localhost:3000 -t public

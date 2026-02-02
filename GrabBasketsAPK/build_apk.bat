@echo off
set "JAVA_HOME=C:\Program Files\Java\jdk-17"
set "ANDROID_HOME=C:\Users\krboo\AppData\Local\Android\Sdk"
set "PATH=%PATH%;%ANDROID_HOME%\platform-tools;%ANDROID_HOME%\tools"

echo ===================================================
echo GrabBaskets APK Builder
echo ===================================================
echo.
echo Setting up environment...
set "LOCAL_GRADLE=%~dp0gradle\gradle-8.4\bin"
set "PATH=%PATH%;%ANDROID_HOME%\platform-tools;%ANDROID_HOME%\tools;%LOCAL_GRADLE%"
echo JAVA_HOME: %JAVA_HOME%
echo ANDROID_HOME: %ANDROID_HOME%
echo Gradle Path: %LOCAL_GRADLE%
echo.

echo Checking requirements...
call npx cordova requirements android
if %errorlevel% neq 0 (
    echo.
    echo [WARNING] Requirements check failed likely due to missing avdmanager or updated tools.
    echo Attempting to build anyway as Gradle should now be available...
    echo.
)

echo.
echo Building Debug APK (Installable)...
call npx cordova build android --debug -- --packageType=apk
if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Build failed.
    echo.
    pause
    exit /b %errorlevel%
)

echo.
echo [SUCCESS] Build complete!
echo Check platforms\android\app\build\outputs\apk\debug
echo.
pause

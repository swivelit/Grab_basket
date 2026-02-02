@echo off
set "JAVA_HOME=C:\Program Files\Java\jdk-17"
set "ANDROID_HOME=C:\Users\krboo\AppData\Local\Android\Sdk"
set "PATH=%PATH%;%ANDROID_HOME%\platform-tools;%ANDROID_HOME%\tools"

echo Setting up environment...
set "LOCAL_GRADLE=d:\grab_baskets_new\grabbaskets\GrabBasketsAPK\gradle\gradle-8.4\bin"
set "PATH=%PATH%;%ANDROID_HOME%\platform-tools;%ANDROID_HOME%\tools;%LOCAL_GRADLE%"

echo Building Vendor APK...
call npx cordova build android --debug -- --packageType=apk
if %errorlevel% neq 0 (
    echo [ERROR] Build failed.
    exit /b %errorlevel%
)

echo [SUCCESS] Build complete!

#!/bin/bash

echo "=================================="
echo "🚀 JewelSync ERP - Auto Fix & Build"
echo "=================================="
echo ""

# Step 1: Check if XAMPP is running
echo "📋 Step 1: Checking XAMPP Status..."
if curl -s http://localhost/ > /dev/null 2>&1; then
    echo "   ✅ XAMPP is already running"
else
    echo "   ⚠️  XAMPP is not running"
    echo ""
    echo "   Please start XAMPP manually:"
    echo "   1. Open: /Applications/XAMPP/XAMPP Control.app"
    echo "   2. Start Apache"
    echo "   3. Start MySQL"
    echo ""
    echo "   Or run: sudo /Applications/XAMPP/xamppfiles/xampp start"
    echo ""
    read -p "Press Enter after XAMPP is started..."
fi

echo ""
echo "📋 Step 2: Testing API Connection..."
response=$(curl -s -X POST http://localhost/jewellery/api/login.php -d "username=admin&password=admin123" 2>&1)
if echo "$response" | grep -q "success"; then
    echo "   ✅ API is working correctly"
    echo "   Response: $(echo $response | head -c 80)..."
else
    echo "   ❌ API is not responding"
    echo "   Response: $response"
    echo ""
    echo "   Please check:"
    echo "   1. XAMPP Apache is running"
    echo "   2. Jewellery folder exists in /Applications/XAMPP/xamppfiles/htdocs/"
    echo "   3. API files exist in /Users/omsoni/Desktop/jewellery/api/"
    exit 1
fi

echo ""
echo "📋 Step 3: Rebuilding Android App..."
cd /Users/omsoni/Desktop/jewellery/android

if [ -f "./gradlew" ]; then
    echo "   Building APK..."
    ./gradlew clean assembleDebug --quiet 2>&1 | tail -20
    
    if [ $? -eq 0 ]; then
        echo "   ✅ Build successful!"
    else
        echo "   ❌ Build failed"
        echo "   Trying with --no-daemon flag..."
        ./gradlew clean assembleDebug --no-daemon 2>&1 | tail -20
    fi
else
    echo "   ❌ Gradle wrapper not found"
    exit 1
fi

echo ""
echo "📋 Step 4: Checking APK..."
apk_path="/Users/omsoni/Desktop/jewellery/android/app/build/outputs/apk/debug/app-debug.apk"
if [ -f "$apk_path" ]; then
    apk_size=$(ls -lh "$apk_path" | awk '{print $5}')
    echo "   ✅ APK created successfully"
    echo "   Size: $apk_size"
    echo "   Location: $apk_path"
else
    echo "   ❌ APK not found"
    exit 1
fi

echo ""
echo "=================================="
echo "✅ All Done!"
echo "=================================="
echo ""
echo "📱 Next Steps:"
echo ""
echo "Option 1: Install to connected device"
echo "   adb install $apk_path"
echo ""
echo "Option 2: Install to emulator"
echo "   - Start Android Emulator"
echo "   - Run: adb install $apk_path"
echo ""
echo "Option 3: Manual install"
echo "   - Copy APK to your phone"
echo "   - Tap to install"
echo ""
echo "🔐 Login Credentials:"
echo "   Username: admin"
echo "   Password: admin123"
echo ""
echo "⚠️  Important:"
echo "   - Keep XAMPP running while using the app"
echo "   - Phone and computer must be on same WiFi (for physical phone)"
echo ""

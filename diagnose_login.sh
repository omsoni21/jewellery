#!/bin/bash

echo "=================================="
echo "JewelSync ERP - Login Diagnostic Tool"
echo "=================================="
echo ""

# Check 1: XAMPP Status
echo "✅ Check 1: XAMPP Status"
if curl -s http://localhost/ > /dev/null 2>&1; then
    echo "   ✅ Apache is RUNNING"
else
    echo "   ❌ Apache is NOT RUNNING"
    echo "   👉 Start XAMPP first!"
    echo "   Command: sudo /Applications/XAMPP/xamppfiles/xampp start"
fi
echo ""

# Check 2: Jewellery App
echo "✅ Check 2: Jewellery Web App"
if curl -s http://localhost/jewellery/ > /dev/null 2>&1; then
    echo "   ✅ Web app is ACCESSIBLE"
else
    echo "   ❌ Web app is NOT ACCESSIBLE"
    echo "   👉 Check if jewellery folder exists in htdocs"
fi
echo ""

# Check 3: Login API
echo "✅ Check 3: Login API Endpoint"
response=$(curl -s -X POST http://localhost/jewellery/api/login.php -d "username=admin&password=admin123" 2>&1)
if echo "$response" | grep -q "success"; then
    echo "   ✅ Login API is WORKING"
    echo "   Response: $response" | head -c 100
    echo ""
else
    echo "   ❌ Login API is NOT WORKING"
    echo "   Response: $response"
    echo "   👉 Check if api/login.php file exists"
fi
echo ""

# Check 4: API Files
echo "✅ Check 4: API Files Check"
api_dir="/Users/omsoni/Desktop/jewellery/api"
if [ -d "$api_dir" ]; then
    echo "   ✅ API directory exists"
    files=("login.php" "dashboard.php" "customers.php" "invoices.php")
    for file in "${files[@]}"; do
        if [ -f "$api_dir/$file" ]; then
            echo "   ✅ $file exists"
        else
            echo "   ❌ $file MISSING"
        fi
    done
else
    echo "   ❌ API directory NOT FOUND"
fi
echo ""

# Check 5: Database Connection
echo "✅ Check 5: Database Check"
if curl -s http://localhost/jewellery/check_db.php 2>/dev/null | grep -q "success\|ok\|connected"; then
    echo "   ✅ Database is CONNECTED"
else
    echo "   ⚠️  Cannot verify database status"
    echo "   👉 Make sure MySQL is running in XAMPP"
fi
echo ""

# Check 6: Network (Get Computer IP)
echo "✅ Check 6: Network Configuration"
computer_ip=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -1)
if [ -n "$computer_ip" ]; then
    echo "   Your Computer IP: $computer_ip"
    echo "   👉 For physical phone, use BASE_URL: http://$computer_ip/jewellery/"
else
    echo "   ⚠️  Could not detect IP address"
fi
echo ""

# Check 7: Android APK
echo "✅ Check 7: Android App"
apk_path="/Users/omsoni/Desktop/jewellery/android/app/build/outputs/apk/debug/app-debug.apk"
if [ -f "$apk_path" ]; then
    apk_size=$(ls -lh "$apk_path" | awk '{print $5}')
    echo "   ✅ APK exists ($apk_size)"
    echo "   Location: $apk_path"
else
    echo "   ❌ APK not found"
    echo "   👉 Build the app first: ./gradlew assembleDebug"
fi
echo ""

echo "=================================="
echo "Diagnostic Complete!"
echo "=================================="
echo ""
echo "📝 Summary:"
echo "If all ✅ marks are present, your app should login successfully."
echo "If any ❌ marks, fix those issues first."
echo ""
echo "🔐 Default Login:"
echo "   Username: admin"
echo "   Password: admin123"
echo ""

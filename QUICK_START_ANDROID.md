# 🚀 Quick Start Guide - JewelSync ERP Android App

## Step-by-Step Instructions to Run the App

### Step 1: Install Android Studio

1. Download Android Studio from: https://developer.android.com/studio
2. Install it on your computer
3. Launch Android Studio and complete initial setup

### Step 2: Open the Project

1. Open Android Studio
2. Click **File** → **Open**
3. Navigate to: `/Users/omsoni/Desktop/jewellery/android`
4. Click **Open**
5. Wait for Gradle sync to complete (this may take a few minutes)

### Step 3: Start Your PHP Backend

**Important:** The Android app needs your PHP backend to be running!

1. Start XAMPP
2. Make sure **Apache** and **MySQL** are running
3. Test that your web app works at: http://localhost/jewellery/

### Step 4: Run on Emulator (Easy Way)

1. In Android Studio, click the **Run** button (▶️ green play icon)
2. Select **Create New Virtual Device**
3. Choose a phone (e.g., Pixel 5)
4. Download and select a system image (API 34 recommended)
5. Click **Finish**
6. Select the emulator and click **Run**

### Step 5: Run on Your Physical Phone

#### Option A: Using USB Cable (Recommended)

**On your phone:**

1. Go to **Settings** → **About Phone**
2. Tap **Build Number** 7 times to enable Developer Options
3. Go to **Settings** → **Developer Options**
4. Enable **USB Debugging**

**On your computer:**

1. Connect your phone via USB
2. On your phone, tap **Allow** when prompted for USB debugging
3. In Android Studio, select your phone from the device list
4. Click **Run**

#### Option B: Install APK Manually

**Build the APK:**

```bash
cd /Users/omsoni/Desktop/jewellery/android
./gradlew assembleDebug
```

**Find the APK:**

- Location: `app/build/outputs/apk/debug/app-debug.apk`

**Install on phone:**

1. Copy the APK file to your phone
2. On your phone, open the file manager
3. Navigate to the APK file
4. Tap on it and select **Install**
5. If prompted, allow installation from **Unknown Sources**

### Step 6: Configure Network (For Physical Phone Only)

If you're running on a **physical phone** (not emulator), you need to update the backend URL:

1. Find your computer's IP address:
   - **Mac:** Open Terminal and run: `ifconfig | grep "inet "`
   - Look for an IP like: `192.168.1.100`

2. In Android Studio, open:
   `app/src/main/java/com/jewellery/erp/network/RetrofitClient.kt`

3. Change the BASE_URL:

```kotlin
// Change from:
private const val BASE_URL = "http://10.0.2.2/jewellery/"

// To your computer's IP:
private const val BASE_URL = "http://192.168.1.100/jewellery/"
```

4. Save the file and rebuild the app

### Step 7: Login to the App

Use your existing credentials:

- **Username:** admin
- **Password:** admin123

⚠️ **Change the default password after first login!**

## Troubleshooting

### ❌ App shows "Connection Error"

**Check:**

1. ✅ Is XAMPP running?
2. ✅ Can you open http://localhost/jewellery/ in browser?
3. ✅ Is the BASE_URL correct?
   - Emulator: `http://10.0.2.2/jewellery/`
   - Phone: `http://YOUR_COMPUTER_IP/jewellery/`

**Fix:**

- Make sure your computer and phone are on the **same WiFi network**
- Check firewall settings - port 80 must be allowed

### ❌ Gradle Sync Failed

**Try:**

1. Click **File** → **Invalidate Caches / Restart**
2. Click **Build** → **Clean Project**
3. Click **Build** → **Rebuild Project**

### ❌ APK Won't Install on Phone

**Fix:**

1. Uninstall any previous version of the app
2. Enable **Unknown Sources** in phone settings
3. Check phone storage space
4. Try using USB debugging method instead

## 📱 Testing the API

Before running the app, test that the API endpoints work:

Open these URLs in your browser (replace localhost with your IP for phone):

1. **Login API:**
   - Use Postman or similar tool to test POST request

2. **Dashboard API:**

   ```
   http://localhost/jewellery/api/dashboard.php
   ```

3. **Customers API:**

   ```
   http://localhost/jewellery/api/customers.php
   ```

4. **Metal Rates API:**
   ```
   http://localhost/jewellery/api/metal-rates.php
   ```

If these return JSON data, the API is working correctly!

## 🎯 What Works Now

✅ **Login Screen** - Authenticate with your credentials  
✅ **Dashboard** - View sales stats, customers, outstanding  
✅ **Navigation** - Access all modules (placeholder screens)  
✅ **Metal Rates** - Today's rates displayed  
✅ **Recent Invoices** - Last 5 invoices shown

## 🚧 What Needs Implementation

🔄 Customer list with search  
🔄 Create/Edit customers  
🔄 Create invoices  
🔄 Stock management  
🔄 Payment entry  
🔄 Reports

## 💡 Tips

1. **For Development:** Use Android Emulator (easier setup)
2. **For Testing:** Use physical phone with USB debugging
3. **For Demo:** Build APK and install on phone
4. **Always:** Keep XAMPP running when testing the app

## 📞 Need Help?

1. Check Android Studio's **Logcat** for error messages
2. Test API endpoints in browser
3. Check XAMPP Apache error logs
4. Review this guide again

---

**Happy Coding! 💎📱**

# 🚀 How to Run Your Android App

## ✅ Android Studio is Opening Now!

### Step-by-Step Instructions:

#### 1️⃣ **Wait for Gradle Sync**

- Android Studio will open and start syncing Gradle
- This may take **2-5 minutes** on first run
- You'll see a progress bar at the bottom
- **Wait until it completes**

#### 2️⃣ **Start XAMPP (IMPORTANT!)**

The app needs your PHP backend to work:

- Open XAMPP
- Start **Apache**
- Start **MySQL**
- Verify: Open browser and go to `http://localhost/jewellery/`

#### 3️⃣ **Run the App**

**Option A: Using Emulator (Recommended for first time)**

1. In Android Studio, look for the device dropdown (top toolbar)
2. Click **"Create New Virtual Device"**
3. Select a phone (e.g., **Pixel 5**)
4. Click **Next**
5. Download a system image (choose **API 34** or highest available)
6. Click **Next** → **Finish**
7. Select the emulator from device dropdown
8. Click the **green Play button (▶️)** or **Run** button

**Option B: Using Your Physical Phone**

1. **Enable Developer Options on your phone:**
   - Go to Settings → About Phone
   - Tap "Build Number" 7 times
2. **Enable USB Debugging:**
   - Go to Settings → Developer Options
   - Enable "USB Debugging"
3. **Connect phone via USB cable**

4. **On your phone:** Tap "Allow" when prompted

5. **In Android Studio:**
   - Select your phone from device dropdown
   - Click the **green Play button (▶️)**

#### 4️⃣ **First Time Setup for Physical Phone**

If running on a **real phone** (not emulator), you need to update the backend URL:

1. Find your Mac's IP address:

   ```bash
   ifconfig | grep "inet "
   ```

   Look for an IP like: `192.168.1.100`

2. In Android Studio, open this file:

   ```
   app/src/main/java/com/jewellery/erp/network/RetrofitClient.kt
   ```

3. Change line 10 from:

   ```kotlin
   private const val BASE_URL = "http://10.0.2.2/jewellery/"
   ```

   To:

   ```kotlin
   private const val BASE_URL = "http://YOUR_IP/jewellery/"
   // Example: http://192.168.1.100/jewellery/
   ```

4. Click **Run** again

#### 5️⃣ **Login to the App**

- **Username:** `admin`
- **Password:** `admin123`

---

## 🐛 Troubleshooting

### ❌ "Gradle Sync Failed"

**Try these:**

1. Check your internet connection
2. Click **File → Invalidate Caches / Restart**
3. Click **Build → Clean Project**
4. Click **Build → Rebuild Project**

### ❌ "App can't connect to backend"

**Check:**

1. ✅ Is XAMPP running?
2. ✅ Can you open http://localhost/jewellery/ in browser?
3. ✅ Is BASE_URL correct?
   - Emulator: `http://10.0.2.2/jewellery/` ✅
   - Phone: `http://YOUR_MAC_IP/jewellery/`

### ❌ "No devices found"

**For Emulator:**

1. Click **Tools → Device Manager**
2. Click **+ Create Device**
3. Follow the setup wizard

**For Physical Phone:**

1. Make sure USB Debugging is enabled
2. Try different USB cable
3. Check if phone is recognized:
   ```bash
   adb devices
   ```

---

## 📱 Quick Commands

### Build APK manually:

```bash
cd /Users/omsoni/Desktop/jewellery/android
./gradlew assembleDebug
```

APK location: `app/build/outputs/apk/debug/app-debug.apk`

### Install APK to connected phone:

```bash
adb install app/build/outputs/apk/debug/app-debug.apk
```

### Check connected devices:

```bash
adb devices
```

---

## 🎯 What You'll See

1. **Login Screen** - Beautiful gold-themed login
2. **Dashboard** - After login, you'll see:
   - Today's sales
   - Monthly sales
   - Total customers
   - Outstanding amount
   - Metal rates
   - Recent invoices
   - Quick action buttons

---

## 💡 Tips

- **First build takes longer** - subsequent builds are faster
- **Keep XAMPP running** while testing the app
- **Use emulator for development** - easier to test
- **Use physical phone for final testing** - real-world experience

---

## 📞 Need Help?

If you encounter any errors:

1. Check the **Logcat** tab in Android Studio (bottom)
2. Look for error messages in red
3. Test your API: Open `http://localhost/jewellery/api/dashboard.php` in browser

---

**Your app is ready! Just wait for Gradle sync and click Run! 🎉**

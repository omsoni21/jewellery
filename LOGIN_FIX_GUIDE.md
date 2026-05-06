# 🔧 Login Issue Fix - Step by Step

## ❌ Problem: Mobile App Mai Login Nahi Ho Raha

### 🔍 Main Reasons:

1. **XAMPP/Server running nahi hai** - Most common issue
2. **Wrong BASE_URL** - App backend se connect nahi ho pa raha
3. **Network issue** - Phone/Emulator computer se connect nahi ho pa raha
4. **API endpoint issue** - login.php file missing ya error hai

---

## ✅ Solution - Step by Step

### **Step 1: XAMPP Start Karein** (MOST IMPORTANT!)

#### Mac par:

1. **XAMPP Control Panel kholein:**
   ```bash
   open /Applications/XAMPP/XAMPP\ Control.app
   ```
2. **Ya terminal se start karein:**

   ```bash
   sudo /Applications/XAMPP/xamppfiles/xampp start
   ```

3. **Check karein ki ye services running hain:**
   - ✅ **Apache** - START karein
   - ✅ **MySQL** - START karein

4. **Browser mein test karein:**
   - Open: `http://localhost/jewellery/`
   - Agar page dikha to XAMPP is running! ✅

#### Windows par:

1. XAMPP Control Panel kholein
2. Apache ko START karein
3. MySQL ko START karein
4. Browser mein: `http://localhost/jewellery/`

---

### **Step 2: API Test Karein**

Terminal/Command Prompt mein:

```bash
# Login API test
curl -X POST http://localhost/jewellery/api/login.php \
  -d "username=admin&password=admin123"
```

**Expected Response:**

```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "...",
    "full_name": "...",
    "role": "admin"
  }
}
```

Agar ye response aaya to API is working! ✅

---

### **Step 3: App Configuration Check Karein**

#### **A. Agar Android EMULATOR use kar rahe hain:**

File kholein: `app/src/main/java/com/jewellery/erp/network/RetrofitClient.kt`

BASE_URL should be:

```kotlin
private const val BASE_URL = "http://10.0.2.2/jewellery/"
```

**Note:** `10.0.2.2` emulator ke liye special IP hai jo localhost ko access karta hai

#### **B. Agar PHYSICAL PHONE use kar rahe hain:**

1. **Computer ka IP address pata karein:**

   **Mac:**

   ```bash
   ifconfig | grep "inet "
   ```

   Look for: `192.168.1.xxx` or `10.0.0.xxx`

   **Windows:**

   ```bash
   ipconfig
   ```

   Look for: IPv4 Address

2. **BASE_URL update karein:**

   ```kotlin
   private const val BASE_URL = "http://YOUR_IP/jewellery/"
   // Example:
   private const val BASE_URL = "http://192.168.1.100/jewellery/"
   ```

3. **Important:** Phone aur Computer **SAME WiFi network** par hone chahiye!

---

### **Step 4: App Rebuild Karein**

Configuration change karne ke baad app ko rebuild karein:

```bash
cd /Users/omsoni/Desktop/jewellery/android
./gradlew clean assembleDebug
```

Ya Android Studio mein:

1. Build → Clean Project
2. Build → Rebuild Project
3. Run → Run 'app'

---

### **Step 5: App Install aur Test Karein**

#### Emulator mein:

```bash
./gradlew installDebug
```

#### Physical Phone mein:

```bash
# USB debugging enabled hona chahiye
adb install app/build/outputs/apk/debug/app-debug.apk
```

---

## 🐛 Common Errors aur Solutions

### **Error 1: "Connection Refused" ya "Unable to connect"**

**Reason:** XAMPP running nahi hai ya wrong URL

**Solution:**

1. XAMPP start karein (Step 1 follow karein)
2. Browser mein test karein: `http://localhost/jewellery/api/login.php`
3. BASE_URL check karein

---

### **Error 2: "404 Not Found"**

**Reason:** API files missing hain

**Solution:**

```bash
# Check karein ki API files hain:
ls -la /Users/omsoni/Desktop/jewellery/api/
```

Ye files honi chahiye:

- ✅ login.php
- ✅ dashboard.php
- ✅ customers.php
- ✅ invoices.php
- etc.

Agar missing hain to recreate karein.

---

### **Error 3: "Invalid username or password"**

**Reason:** Database mein user nahi hai ya wrong credentials

**Solution:**

1. **Default credentials:**
   - Username: `admin`
   - Password: `admin123`

2. **Database check karein:**

   ```bash
   # Browser mein:
   http://localhost/jewellery/check_db.php
   ```

3. **Reset password:**

   ```bash
   # Terminal mein:
   mysql -u root -p jewellery_billing
   ```

   ```sql
   UPDATE users SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
   ```

---

### **Error 4: "Network request failed"**

**Reason:** Phone computer se connect nahi ho pa raha

**Solution:**

1. ✅ Phone aur Computer same WiFi par hain?
2. ✅ Firewall toh block nahi kar raha?
3. ✅ IP address correct hai?

**Test:**
Phone ke browser mein try karein:

```
http://YOUR_COMPUTER_IP/jewellery/api/login.php
```

Agar response aaya to network is working! ✅

---

### **Error 5: App crash ho rahi hai**

**Reason:** Code mein error

**Solution:**

1. Android Studio mein Logcat kholein
2. Error message check karein
3. Common fixes:
   ```bash
   # Clean and rebuild
   ./gradlew clean assembleDebug
   ```

---

## 📝 Quick Checklist

Login se pehle ye sab check karein:

- [ ] ✅ XAMPP installed hai
- [ ] ✅ Apache running hai
- [ ] ✅ MySQL running hai
- [ ] ] ✅ `http://localhost/jewellery/` browser mein open ho raha hai
- [ ] ✅ API files `api/` folder mein hain
- [ ] ✅ BASE_URL correct hai
  - Emulator: `http://10.0.2.2/jewellery/`
  - Phone: `http://YOUR_IP/jewellery/`
- [ ] ✅ Phone aur Computer same WiFi par hain (for physical phone)
- [ ] ✅ App rebuild kiya hai configuration change ke baad
- [ ] ✅ Correct credentials use kar rahe hain

---

## 🔐 Default Login Credentials

```
Username: admin
Password: admin123
```

⚠️ **First login ke baad password change kar lein!**

---

## 🧪 Complete Testing Process

1. **XAMPP Start:**

   ```bash
   sudo /Applications/XAMPP/xamppfiles/xampp start
   ```

2. **Test in Browser:**
   - Open: `http://localhost/jewellery/`
   - Should see login page

3. **Test API:**

   ```bash
   curl -X POST http://localhost/jewellery/api/login.php \
     -d "username=admin&password=admin123"
   ```

   Should return JSON with user data

4. **Test from Phone Browser:**

   ```
   http://YOUR_IP/jewellery/api/login.php
   ```

   Should return JSON response

5. **Now try Login in App**

---

## 📞 Still Not Working?

Agar abhi bhi issue hai to ye information check karein:

1. **Error message kya aa raha hai?**
   - App mein kya error dikha raha hai?
2. **Logcat mein kya error hai?**
   - Android Studio → Logcat tab → Red text check karein

3. **API response kya hai?**

   ```bash
   curl -v -X POST http://localhost/jewellery/api/login.php \
     -d "username=admin&password=admin123"
   ```

4. **XAMPP logs check karein:**
   ```bash
   tail -f /Applications/XAMPP/xamppfiles/logs/error_log
   ```

---

## ✨ Success Indicators

Aapka login successful hoga agar:

✅ Browser mein `http://localhost/jewellery/` open ho raha hai  
✅ API test se JSON response aa raha hai  
✅ BASE_URL correct hai  
✅ XAMPP running hai  
✅ Phone/Emulator computer se connect ho pa raha hai

**In cases mein app login karega!** 🎉

---

**Try these steps and let me know which error you're getting!**

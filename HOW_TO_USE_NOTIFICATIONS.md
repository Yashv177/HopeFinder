# How Notification System Works 🚀

## 🎯 **Step-by-Step Setup & Test**

### 1. **Database Setup** (2min)
```
phpMyAdmin → hopefinder DB → SQL tab → Paste & Run:
```
```sql
-- Database/create_notifications_table.sql content
```

### 2. **Admin Account** (30s)
```
cd php
php setup_admin.php
```
*Email*: admin@hopefinder.com  
*Password*: 123456789  
*Role*: Admin

### 3. **Test Live Demo** (Instant)
```
Open: http://localhost/HopeFinder/notifications-demo.html
```
- Click test buttons → **Live bell + sound + badge!**
- Mark read → Badge disappears
- Auto-refresh every 5s

### 4. **Dashboard Integration**
Dashboard2.php already has:
```html
<button class="notification-btn">
  <i class="bi bi-bell"></i>
  <span class="notification-badge">6</span>
</button>
<script src="../JavaScript/notifications.js"></script>
```

**Login** → Real-time notifications work automatically!

## ⚙️ **How It Works (Technical)**

```
Frontend (5s polling) → api/notifications.php → MySQL notifications table
Send → api/send_notification.php → DB insert → Live update
```

**Flow**:
```
1. New event (report/match/user signup) → send_notification()
2. Row added to notifications table
3. JS polls every 5s → sees unread → sound + badge + dropdown
4. User clicks → mark_read() → status='read' 
```

## 📱 **Usage Examples**

**Send Notification** (Admin Panel):
```javascript
fetch('./api/send_notification.php', {
  method: 'POST',
  body: 'target=police&message=New report R123&priority=high&link=view-report.php?id=R123'
});
```

**Types** (see notifications_types.txt):
- `new_report` → Police (high)
- `report_approved` → User (medium)
- `user_registered` → Admin (low)

## 🎵 **Sound Alert**
Base64 beep sound plays on new unread notification.

## 🔒 **Security**
- Session-based auth
- Prepared statements
- Role filtering
- XSS protection

## 📊 **Dashboard Ready**
Dashboard2.php bell icon works out-of-box after login.

**Test**: Login Dashboard2.php → notifications appear live!

**Done** - professional system deployed.

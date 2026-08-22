# 🎵 MelodyLogs - The Vocalist & Singer Community Blog

**MelodyLogs** is a modern, secure, and responsive full-stack blog application designed specifically for vocalists, singers, and vocal coaches to share warmup routines, acoustic tips, studio logs, and vocal health discoveries.

Built with **Core PHP (PDO)**, **MySQL**, **Bootstrap 5.3**, and custom dark-mode glassmorphic CSS.

---

## 🌟 Key Features

- 🔒 **Security First**:
  - Secure PDO prepared statements for all database operations (zero SQL injection).
  - Environment variable loader (`config/env.php`) reading `.env` credentials with zero external dependencies.
  - Safe password hashing with `password_hash()` and `password_verify()`.
  - Session fixation protection with `session_regenerate_id(true)` upon authentication.
  - Full Cross-Site Request Forgery (CSRF) token generation and verification.
  - Robust output escaping with `htmlspecialchars()` (`e()`) for XSS protection.
  - **Strict Ownership Checks**: Edit and Delete actions are strictly restricted to the author both at UI level and database query level.

- 🎨 **Modern Dark Music Aesthetic**:
  - Custom dark theme styling in `css/style.css` featuring glassmorphism, animated soundwave visualizers, and glowing accent badges.
  - Responsive Bootstrap 5.3 layout optimized for mobile, tablet, and desktop screens.
  - Interactive Delete Confirmation Modal.
  - Flash notification system for alerts and feedback toasts.

- 📝 **Full CRUD Workflow**:
  - **Feed (`index.php`)**: Explore all logs with category filter pills, search bar, author profiles, and reading time.
  - **Post View (`post.php`)**: Read in-depth guides with formatted headings, bold text, lists, and author bio cards.
  - **Editor (`editor.php`)**: Dual-mode unified form for publishing new entries or editing existing posts with preset image pickers.
  - **Delete Handler (`delete.php`)**: Secure POST endpoint with CSRF verification and author-only authorization.
  - **Auth System (`register.php`, `login.php`, `logout.php`)**: Register with custom vocal classifications (Soprano, Tenor, Vocal Coach, etc.).

---

## 📂 Project Structure

```text
Blog app/
├── .env                  # Local environment configuration (ignored by git)
├── .env.example          # Environment template
├── .gitignore            # Git exclusion rules
├── schema.sql            # MySQL table schema + starter demo seeds
├── README.md             # Project documentation
│
├── config/
│   ├── env.php           # Custom zero-dependency .env reader (getenv / $_ENV)
│   └── db.php            # Secure PDO connection using dynamic env variables
│
├── includes/
│   ├── functions.php     # Global helpers: auth, CSRF, flash, formatting, XSS
│   ├── header.php        # Dark-mode navbar with dynamic auth state & flash alerts
│   └── footer.php        # Responsive footer with category links and credits
│
├── css/
│   └── style.css         # Dark music theme, glassmorphism & visual effects
│
├── index.php             # Home feed: category filters, search, post grid
├── post.php              # Single post view with strict author action buttons
├── editor.php            # Unified Create & Update post form with auth checks
├── delete.php            # Secure post deletion handler
├── register.php          # User registration with vocal classification
├── login.php             # User sign-in with password_verify
└── logout.php            # Safe session destruction
```

---



## 🛡️ Security Audit Highlights

- **SQL Injection**: Prevented using PDO prepared statements with parameter binding and disabled emulation (`PDO::ATTR_EMULATE_PREPARES => false`).
- **Cross-Site Scripting (XSS)**: All user-supplied data is passed through `e()` (`htmlspecialchars`) before outputting to HTML.
- **Cross-Site Request Forgery (CSRF)**: All state-changing forms (Create, Edit, Delete, Login, Register) validate session tokens.
- **Session Hijacking / Fixation**: Session IDs are regenerated upon successful authentication (`session_regenerate_id(true)`).
- **Authorization Bypass**: The server verifies that `$_SESSION['user_id'] === $post['user_id']` on both `editor.php` and `delete.php` before modifying data.

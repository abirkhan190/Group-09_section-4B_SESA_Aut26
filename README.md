## Team Members
| Name | Student ID |
| Golam Irfan Khan | 04325105101074 |
| Saifur Rahman | 04325105101099 |
| Shahed Hossain Ratin | 04325105101079 |

## Features

### Student
- Register, log in and build a profile (skills, preferred areas, availability, class schedule)
- Job search with filters
- **Smart Match Score** for every job
- **Skill Gap** analysis (which skills are missing for a job)
- Apply to jobs and track application status
- Profile completeness indicator
- CV Builder
- In-app notifications (SMS is simulated)

### Employer
- Register and log in
- Post jobs (go to the admin for approval first)
- View applicants and update their status

### Admin
- Approve or reject pending jobs
- Verify students and employers
- Remove jobs
- Analytics dashboard with charts

## Smart Match Score

Each job gets a score from 0 to 100 for the logged-in student:

| Factor | Weight |
| Skills | 55% |
| Location | 20% |
| Preferred working hours | 15% |
| Class schedule (no conflict) | 10% |


## Tech Stack

| Layer | Technology |
| Front end | HTML, CSS, JavaScript |
| Back end | PHP (`api.php`, PDO) |
| Database | MySQL |
| Local server | XAMPP (Apache + MySQL) |

---

## Project Structure

```
campuscareerbd/
├── index.html                # Home page
├── login.html                # Student login
├── register.html             # Student registration
├── profile.html              # Student profile
├── student-dashboard.html    # Student dashboard
├── jobs.html                 # Find jobs
├── job-details.html          # Single job page
├── cv-builder.html           # CV builder
├── employer-register.html    # Employer registration
├── employer-login.html       # Employer login
├── employer-dashboard.html   # Employer dashboard
├── post-job.html             # Post a job
├── admin-login.html          # Admin login
├── admin-dashboard.html      # Admin dashboard
├── api.php                   # PHP backend (load / save / login)
├── database.sql              # MySQL schema
├── css/
│   └── style.css             # Styling
└── js/
    └── script.js             # Front-end logic (calls api.php)
```

---

## Database

Database name: `campuscareerbd`

Tables: `students`, `employers`, `jobs`, `applications`, `notifications`, `meta`

The database and all tables are **created automatically** on the first page load. `database.sql` is only for viewing or importing the schema by hand in phpMyAdmin. Passwords are stored hashed with `password_hash`.

---

## How to Run (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org) and open the Control Panel.
2. Start **Apache** and **MySQL** (both should turn green).
3. Clone or download this repository and copy the project folder to:
   ```
   C:\xampp\htdocs\campuscareerbd
   ```
   `api.php` must be directly inside this folder, next to `index.html`.
4. Open in your browser:
   ```
   http://localhost/campuscareerbd/
   ```
5. (Optional) View the data at `http://localhost/phpmyadmin`, database `campuscareerbd`.

> Do **not** open the HTML files by double-clicking. They must be opened through `localhost`, otherwise you will see "Cannot reach the server".

### Use from another device (same WiFi)
1. On the XAMPP computer, run `ipconfig` and note the IPv4 address (e.g. `192.168.0.105`).
2. On the other device open `http://192.168.0.105/campuscareerbd/`.
3. If it does not open, allow Apache through Windows Firewall.

### Troubleshooting
- **MySQL will not start:** another program is using port 3306. Stop it or restart the computer.
- **"Access denied":** your MySQL root user has a password. Set `DB_PASS` at the top of `api.php`.

---

## Demo Flow

1. **Employer:** `employer-register.html` → register → log in → **Post a Job** (status: Pending)
2. **Admin:** `admin-login.html` → **Pending Job Approvals** → Approve
3. **Student:** `register.html` → log in → **Find Jobs** → apply to the approved job
4. In phpMyAdmin, the job's `status` in the `jobs` table changes from `Pending` to `Approved`.

**Default admin login**

| Email | Password |
|-------|----------|
| `admin@campuscareer.bd` | `Admin@123` |

---

## Limitations

- This is a **prototype**, not a hardened production system. The logged-in user session is kept in the browser.
- SMS notifications are simulated (in-app alert only). No real SMS gateway is connected.
- "Verification" is a manual admin toggle.
- Requires PHP, so it **cannot be hosted on static hosting** such as Netlify or GitHub Pages.

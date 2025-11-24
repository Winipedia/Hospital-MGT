# 🏥 Queen's Medical Centre (QMC) Hospital Management System

**COMP4039 Databases, Interfaces and Software Design Principles - Coursework**
**Student ID:** psxws9-20749564
**Submission Date:** December 12, 2025

---

## 📋 Table of Contents

1. [Project Overview](#project-overview)
2. [System Requirements](#system-requirements)
3. [Installation & Setup](#installation--setup)
4. [Database Schema](#database-schema)
5. [Features Implemented](#features-implemented)
6. [User Guide](#user-guide)
7. [Admin Guide](#admin-guide)
8. [Security Features](#security-features)
9. [Testing](#testing)
10. [Project Structure](#project-structure)
11. [Technologies Used](#technologies-used)
12. [Known Issues & Limitations](#known-issues--limitations)

---

## 🎯 Project Overview

This is a comprehensive web-based Hospital Management System developed for Queen's Medical Centre (QMC) using a Docker-based LAMP stack. The system provides functionality for doctors to manage patient information, tests, parking permits, and for administrators to manage doctor accounts and monitor system activity through an audit trail.

### Key Objectives

- ✅ Implement a fully normalized database (BCNF) with proper relationships
- ✅ Create a user-friendly web interface for hospital staff
- ✅ Provide comprehensive patient and test management
- ✅ Enable parking permit request and approval workflow
- ✅ Implement role-based access control (Doctor vs Admin)
- ✅ Maintain complete audit trail for regulatory compliance
- ✅ Handle missing data gracefully with user-friendly messages

---

## 💻 System Requirements

### Required Software

- **Docker Desktop** (version 20.10 or higher)
- **Docker Compose** (version 1.29 or higher)
- **Web Browser** (Chrome, Firefox, Edge, or Safari)
- **Operating System:** Windows 10/11, macOS, or Linux

### Hardware Requirements

- **RAM:** Minimum 4GB (8GB recommended)
- **Disk Space:** Minimum 2GB free space
- **Processor:** Any modern CPU (Intel/AMD)

---

## 🚀 Installation & Setup

### Step 1: Clone or Extract the Project

```bash
cd /path/to/project
# Ensure you're in the directory containing docker-compose.yml
```

### Step 2: Start Docker Services

```bash
docker-compose up -d
```

This command will:
- Build and start the PHP-Apache container (port 80)
- Build and start the MariaDB container (port 3306)
- Start PHPMyAdmin container (port 8081)
- Create the `hospital` database with all tables and sample data

### Step 3: Verify Services are Running

```bash
docker-compose ps
```

You should see three services running:
- `php-apache` (port 80)
- `mariadb` (port 3306)
- `phpmyadmin` (port 8081)

### Step 4: Access the Application

Open your web browser and navigate to:

- **Main Application:** http://localhost/cw/
- **PHPMyAdmin:** http://localhost:8081/
  - Server: `mariadb`
  - Username: `root`
  - Password: `rootpwd`

### Step 5: Login Credentials

#### Administrator Account
- **Username:** `jelina`
- **Password:** `iron99`
- **Staff No:** `ADMIN001`

#### Doctor Accounts (Sample)
- **Username:** `mceards` | **Password:** `lord456` | **Staff No:** `CH007`
- **Username:** `moorland` | **Password:** `buzz48` | **Staff No:** `GT067`

---

## 🗄️ Database Schema

### Database Normalization

The database is fully normalized to **Boyce-Codd Normal Form (BCNF)** with:
- No partial dependencies
- No transitive dependencies
- Proper foreign key constraints
- Appropriate indexes for performance

### Core Tables (13 Total)

1. **`doctor`** - Doctor information and credentials
2. **`patient`** - Patient personal information
3. **`specialisation`** - Medical specialisations
4. **`gender`** - Gender reference table
5. **`address`** - Address information (shared by doctors and patients)
6. **`ward`** - Hospital ward information
7. **`department`** - Hospital departments
8. **`test`** - Available medical tests
9. **`patient_test`** - Tests prescribed to patients
10. **`patientexamination`** - Patient examination records
11. **`wardpatientaddmission`** - Ward admission history
12. **`parking_permit`** - Parking permit requests and approvals
13. **`audit_log`** - Complete audit trail of all database operations

### Entity Relationship Diagram

```
doctor ──┬── specialisation
         ├── gender
         ├── address
         ├── ward
         └── parking_permit

patient ──┬── gender
          ├── address
          ├── patient_test ── test
          ├── patientexamination
          └── wardpatientaddmission ── ward

audit_log ── doctor
```

---

## ✨ Features Implemented

### 1️⃣ Patient Information Management ✅

**Location:** `patient_search.php`, `patient_info.php`

- **Search Patients:** Search by NHS number, name, or phone
- **View Patient Details:** Complete patient information including:
  - Personal details (name, age, gender, contact)
  - Address information
  - Emergency contact
  - Ward admission history with dates and status
  - Tests performed with dates and reports
  - Summary statistics
- **Missing Data Handling:** Graceful handling of:
  - Patients with no admissions
  - Patients with no tests
  - Missing optional fields (shows "N/A")
  - Pending test reports (shows "Pending")
- **Pagination:** 10 patients per page

### 2️⃣ Add Test & Prescribe to Patient ✅

**Location:** `add_test.php`

**Three Main Functions:**

#### A. Create New Test
- Add new test types to the system
- Duplicate test name prevention
- Shows list of existing tests with IDs
- Auto-increment test ID

#### B. Add New Patient
- Add patient if not in database
- Required fields: NHS Number, First Name, Age, Phone
- Optional fields: Last Name, Gender, Emergency Phone, Address
- Duplicate NHS number prevention
- Auto-creates address record

#### C. Prescribe Test to Patient
- Select patient by NHS number
- Select test from dropdown (all existing tests)
- Set test date (defaults to today)
- Validates patient exists
- Records prescribing doctor
- Link to patient search for easy lookup

### 3️⃣ Doctor Profile Management ✅

**Location:** `profile.php`

- **View Profile:** Display account information
- **Update Profile:** Change first name and last name
- **Change Password:** Secure password update
- **Audit Logging:** All changes logged

### 4️⃣ Parking Permit System ✅

**Location:** `parking_permit.php`, `admin/parking_approvals.php`

#### Doctor Features:
- **Request Permit:** Submit parking permit request
  - Enter car registration
  - Choose permit type (Monthly £50 / Yearly £500)
  - Fee displayed based on selection
- **View Status:** See all permit requests with status badges
- **Cancel Request:** Cancel pending requests
- **View Details:** For approved permits, see:
  - Permit number
  - Activation date
  - End date
  - Approved by

#### Admin Features:
- **Approve Requests:**
  - Modal dialog to enter permit number
  - Auto-suggests format: `PP-YEAR-ID`
  - Validates permit number uniqueness
  - Calculates activation and end dates
  - Records approving admin
- **Reject Requests:**
  - Modal dialog to enter rejection reason
  - Mandatory reason field
  - Records rejecting admin
- **View History:** Recently processed permits (last 10)

### 5️⃣ Create Doctor Accounts (Admin) ✅

**Location:** `admin/create_doctor.php`

- **Complete Form:**
  - Staff Number (required, unique)
  - Username & Password (optional, for login access)
  - First Name (required) & Last Name (optional)
  - Gender (optional dropdown)
  - Specialisation (optional dropdown)
  - Qualification (optional text)
  - Ward assignment (optional dropdown)
  - Annual Pay (required)
  - Consultant Status (checkbox)
  - Administrator Access (checkbox)
  - Address fields (optional: street, city, postcode)
- **Validation:**
  - Duplicate staff number check
  - Duplicate username check
  - Required field validation
- **View All Doctors:** Table showing all doctors with status badges
- **Audit Logging:** Account creation logged

### 6️⃣ Audit Trail (Admin) ✅

**Location:** `admin/audit_log.php`

#### Features:
- **Complete Activity Log:** All database operations tracked
- **Statistics Dashboard:**
  - Total audit logs
  - Active users
  - Days logged
  - Last activity timestamp
- **Advanced Filtering:**
  - Filter by User (dropdown of all doctors)
  - Filter by Action (LOGIN, LOGOUT, INSERT, UPDATE, DELETE, SELECT)
  - Filter by Table (all database tables)
  - Filter by Date Range (from/to dates)
- **Pagination:** 50 records per page
- **Detailed Information:**
  - Audit ID
  - Timestamp (date and time)
  - User (name, staff number, admin badge)
  - Action (color-coded badges)
  - Table name
  - Record ID
  - Old and new values
  - IP address
- **Action Summary:** Count of each action type
- **Per-User Basis:** Filter to see all actions by specific user
- **Regulatory Compliance:** Complete trail for auditing purposes

---

## 📖 User Guide

### For Doctors

#### 1. Login
1. Navigate to http://localhost/cw/
2. Enter your username and password
3. Click "Login"

#### 2. View Dashboard
- See your account information
- Access all available features via menu cards

#### 3. Search for Patients
1. Click "Patient Search" from dashboard
2. Use search bar to find patients by NHS number, name, or phone
3. Click "View Details" to see complete patient information

#### 4. Add Test & Prescribe
1. Click "Add Test & Prescribe" from dashboard
2. **To create a new test:** Enter test name and click "Create Test"
3. **To add a new patient:** Fill in patient details and click "Add Patient"
4. **To prescribe a test:** Select patient NHS number, test, date, and click "Prescribe Test"

#### 5. Request Parking Permit
1. Click "Parking Permit" from dashboard
2. Enter car registration
3. Select permit type (Monthly or Yearly)
4. Review the fee
5. Click "Submit Request"
6. Wait for admin approval

#### 6. Update Profile
1. Click "My Profile" from dashboard
2. Update your first name or last name
3. Or change your password
4. Click "Update Profile" or "Change Password"

---

## 👨‍💼 Admin Guide

### For Administrators

#### 1. Login as Admin
- Username: `jelina`
- Password: `iron99`
- You'll see "ADMIN" badge in the navbar

#### 2. Approve/Reject Parking Permits
1. Click "Parking Approvals" from dashboard
2. View all pending requests
3. **To Approve:**
   - Click "✓ Approve"
   - Enter or edit permit number (auto-suggested)
   - Click "Approve Permit"
4. **To Reject:**
   - Click "✗ Reject"
   - Enter rejection reason
   - Click "Reject Permit"
5. View recently processed permits

#### 3. Create Doctor Accounts
1. Click "Create Doctor Account" from dashboard
2. Fill in required fields:
   - Staff Number (unique)
   - First Name
   - Annual Pay
3. Fill in optional fields as needed
4. Check "Consultant Status" if applicable
5. Check "Administrator Access" to grant admin rights
6. Click "Create Doctor Account"
7. View all doctors in the table below

#### 4. View Audit Trail
1. Click "Audit Trail" from dashboard
2. View statistics at the top
3. Use filters to narrow down results:
   - Select specific user
   - Select action type
   - Select table
   - Set date range
4. Click "Apply Filters"
5. Review audit logs with complete details
6. Use pagination to navigate through records
7. View action summary at the bottom

---

## 🔒 Security Features

### Authentication & Authorization
- ✅ Session-based authentication
- ✅ Password-protected accounts
- ✅ Role-based access control (Doctor vs Admin)
- ✅ Admin-only pages with access checks
- ✅ Automatic logout functionality

### Data Security
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (htmlspecialchars on all output)
- ✅ Input validation and sanitization
- ✅ CSRF protection (session validation)

### Audit & Compliance
- ✅ Complete audit trail of all operations
- ✅ IP address logging
- ✅ Timestamp on all actions
- ✅ User identification for all changes
- ✅ Old and new value tracking for updates

---

## 🧪 Testing

### Test Scenarios

#### 1. Patient with No Data
- Search for patient with no admissions or tests
- Verify user-friendly messages displayed

#### 2. Add New Test
- Create test "MRI Scan"
- Verify duplicate prevention
- Prescribe to existing patient

#### 3. Add New Patient
- Add patient with NHS: W99999
- Verify duplicate NHS number prevention
- Prescribe test to new patient

#### 4. Parking Permit Workflow
- Login as doctor
- Request monthly permit
- Login as admin
- Approve with permit number
- Login as doctor again
- Verify approved status

#### 5. Create Doctor Account
- Login as admin
- Create new doctor with all fields
- Verify in doctors list
- Test login with new account

#### 6. Audit Trail
- Perform various actions
- Login as admin
- View audit trail
- Filter by user, action, table
- Verify all actions logged

---

## 📁 Project Structure

```
psxws9-20749564_Docker/
├── README.md                          # This file
├── docker-compose.yml                 # Docker services configuration
├── html/
│   └── cw/                           # Main application directory
│       ├── index.php                 # Login page
│       ├── dashboard.php             # Main dashboard
│       ├── logout.php                # Logout handler
│       ├── db.inc.php                # Database connection
│       ├── profile.php               # User profile management
│       ├── patient_search.php        # Patient search & list
│       ├── patient_info.php          # Patient details page
│       ├── add_test.php              # Add test & prescribe
│       ├── parking_permit.php        # Parking permit requests
│       ├── admin/                    # Admin-only pages
│       │   ├── create_doctor.php     # Create doctor accounts
│       │   ├── parking_approvals.php # Approve/reject permits
│       │   └── audit_log.php         # Audit trail viewer
│       ├── includes/                 # Reusable components
│       │   ├── header.php            # HTML head & CSS
│       │   ├── navbar.php            # Navigation bar
│       │   └── footer.php            # Closing HTML tags
│       └── assets/
│           └── css/                  # Stylesheets
│               ├── style.css         # Common styles
│               ├── login.css         # Login page styles
│               └── dashboard.css     # Dashboard styles
├── mariadb/
│   ├── Dockerfile                    # MariaDB container config
│   └── cw-database.sql              # Database schema & data
├── php-apache/
│   └── Dockerfile                    # PHP-Apache container config
└── mariadb-data/                     # Database persistent storage
```

---

## 🛠️ Technologies Used

### Backend
- LAMP Stack (Linux, Apache, MySQL/MariaDB, PHP)

### Frontend
- **HTML5** - Structure
- **CSS3** - Styling (custom, no frameworks)
- **JavaScript** - Client-side interactions (modals, form validation)

### Database Design
- **BCNF Normalization** - Fully normalized schema
- **Foreign Key Constraints** - Referential integrity
- **Indexes** - Performance optimization
- **Prepared Statements** - SQL injection prevention

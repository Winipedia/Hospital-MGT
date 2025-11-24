# Queen's Medical Centre - Hospital Management System

A web-based hospital management system built with PHP, MariaDB, and Docker for COMP4039 coursework.

## Quick Start

1. **Start the system:**
   ```bash
   docker-compose up -d
   ```

2. **Access the application:**
   - Web Interface: http://localhost/cw/
   - phpMyAdmin: http://localhost:8081/

## Features

### For Doctors:
- **Profile Management** - Update personal details and change password
- **Parking Permits** - Request monthly (£50) or yearly (£500) parking permits
- **Patient Search** - Search and view patient information
- **Patient Details** - View patient records, ward history, and test results
- **Add Tests** - Create new test types and prescribe tests to patients

### For Administrators:
- **Create Doctor Accounts** - Add new doctors to the system
- **Parking Approvals** - Approve/reject parking permit requests
- **Audit Trail** - View complete database activity logs with filtering

## Database
- **Schema:** Fully normalized to BCNF with 13 tables
- **Audit Logging:** All database operations are logged for compliance

## Technology Stack

- **Backend:** PHP 8.2
- **Database:** MariaDB 10.4
- **Web Server:** Apache 2.4
- **Container:** Docker & Docker Compose
- **Admin Tool:** phpMyAdmin

## Project Structure

```
html/cw/
├── index.php              # Login page
├── dashboard.php          # Main dashboard
├── profile.php            # User profile management
├── parking_permit.php     # Parking permit requests
├── patient_search.php     # Patient search
├── patient_info.php       # Patient details
├── add_test.php           # Add tests and prescribe
├── admin/
│   ├── create_doctor.php      # Create doctor accounts
│   ├── parking_approvals.php  # Approve parking permits
│   └── audit_log.php          # Audit trail viewer
├── includes/
│   ├── header.php         # HTML header
│   ├── navbar.php         # Navigation bar
│   └── footer.php         # HTML footer
└── assets/css/            # Stylesheets
```

## Stopping the System

```bash
docker-compose down
```

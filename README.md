# QMC Hospital Management System

COMP4039 Coursework - Dockerized LAMP stack hospital management application.

## Quick Start

Install Docker Desktop, then run:
```
docker compose up
```

Access points:
- Application: http://localhost/cw/index.php
- phpMyAdmin: http://localhost:8081

Default login: username `jelina`, password `iron99` (admin account)

## Project Structure

- html/cw/ - Hospital management application (PHP, CSS, JS)
- mariadb/ - Database schema and initialization SQL
- mariadb-data/ - Database storage (delete contents to rebuild from scratch)
- php-apache/ - Apache/PHP Docker configuration

## Features

Doctor Portal:
- Patient search and management
- Test prescription and tracking
- Ward admission management
- Parking permit requests
- Profile management

Admin Portal:
- Doctor account creation
- Parking permit approvals
- Audit log viewing

## Database

MariaDB 10 with hospital schema including patients, doctors, wards, tests, admissions, and parking permits. Root password: `rootpwd`

## Tech Stack

Apache 2, PHP 8, MariaDB 10, phpMyAdmin

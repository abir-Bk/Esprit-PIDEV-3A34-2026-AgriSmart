# AgriSmart

## Overview

This project was developed as part of the PIDEV – 3rd Year Engineering Program at **Esprit School of Engineering** (Academic Year 2025–2026).

AgriSmart is a smart agricultural management system designed to digitalize farm operations and improve administrative efficiency. It integrates modern web technologies with AI-based face recognition for secure and intelligent authentication.

## Features

- Secure authentication with JWT
- AI-based face recognition login
- Farmer and user management
- Admin dashboard with statistics
- Full CRUD operations
- Role-based access control

## Tech Stack

### Frontend
- Twig
- Bootstrap

### Backend
- Symfony (PHP)
- MySQL

### AI Module
- Python (Face Recognition)

## Architecture

AgriSmart follows a 3-tier architecture:

- **Presentation Layer:** Twig + JavaScript
- **Business Logic Layer:** Symfony Controllers & Services
- **Data Layer:** MySQL Database
- **AI Module:** Python integrated with Symfony

## Contributors

PIDEV Project Team:
- Abir Benkhlifa
- Soumaya Drydy
- Aya Fdhyla
- Amine Arfaoui
- Akrem Zaied

## Academic Context

Developed at **Esprit School of Engineering – Tunisia**  
PIDEV – 3A34 | 2025–2026

## Getting Started

1. Clone the repository:
```bash
git clone https://github.com/Abir-BK/agrismart.git
```

2. Go to the project directory:
```bash
cd agrismart
```

3. Install dependencies:
```bash
composer install
```

4. Configure environment:  
   Update the `.env` file with your database credentials.

5. Run migrations:
```bash
php bin/console doctrine:migrations:migrate
```

6. Start the server:
```bash
symfony server:start
```

## Acknowledgments

Special thanks to our professors and mentors at **Esprit School of Engineering** for their support and guidance.

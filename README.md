# AgriSmart

## Overview
AgriSmart is a smart agricultural management system designed to digitalize farm operations and improve administrative efficiency. It integrates modern web technologies with AI-based face recognition for secure and intelligent authentication.

## Features
- Secure authentication with JWT
- AI-based face recognition login
- Farmer and user management
- Admin dashboard with statistics
- Full CRUD operations
- Role-based access control

## Tech Stack
- Twig
- Bootstrap
- Symfony (PHP)
- MySQL
- Python

## Architecture
AgriSmart follows a 3-tier architecture:

- **Presentation Layer:** Twig + JS  
- **Business Logic Layer:** Symfony Controllers & Services  
- **Data Layer:** MySQL Database  
- **AI Module:** Python integrated with Symfony

## Getting Started
1. Clone the repository:  
   ```bash
   git clone https://github.com/Abir-BK/agrismart.git

Go to the project directory:

cd agrismart

Install dependencies:

composer install

Configure environment: Update the .env file with your database credentials.

Run migrations:

php bin/console doctrine:migrations:migrate

Start the server:

symfony server:start
Contributors

PIDEV Project Team: Abir Benkhlifa, Soumaya Drydy, Aya Fdhyla, Amine Arfaoui, Akrem Zaied

Academic Context

This project was developed as part of the PIDEV – 3rd Year Engineering Program at Esprit School of Engineering (Academic Year 2025–2026).

Acknowledgments

Special thanks to our professors and mentors at Esprit School of Engineering for their support and guidance.


You can now **directly save it as `README.md`** and it’s ready for GitHub or any repo.  

If you want, I can also **add small badges for Symfony, MySQL, and PHP** to make it look more professional. Do you want me to do that?

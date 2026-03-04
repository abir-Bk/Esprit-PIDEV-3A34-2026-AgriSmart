<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriSmart Project</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f7f9fc;
      color: #333;
      margin: 0;
      padding: 0;
      line-height: 1.6;
    }

    header {
      background-color: #2e7d32;
      color: #fff;
      padding: 40px 20px;
      text-align: center;
    }

    header h1 {
      margin: 0;
      font-size: 2.5rem;
    }

    header p {
      font-size: 1.2rem;
      margin-top: 10px;
    }

    main {
      max-width: 900px;
      margin: 20px auto;
      padding: 20px;
      background: #fff;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      border-radius: 10px;
    }

    section {
      margin-bottom: 30px;
    }

    section h2 {
      color: #2e7d32;
      margin-bottom: 15px;
      border-bottom: 2px solid #2e7d32;
      display: inline-block;
      padding-bottom: 5px;
    }

    ul {
      list-style-type: none;
      padding: 0;
    }

    ul li {
      margin-bottom: 10px;
      padding-left: 25px;
      position: relative;
    }

    ul li::before {
      content: '🌱';
      position: absolute;
      left: 0;
    }

    code, pre {
      background: #eee;
      padding: 5px 10px;
      border-radius: 5px;
      display: block;
      overflow-x: auto;
    }

    footer {
      text-align: center;
      padding: 20px;
      background: #e8f5e9;
      color: #2e7d32;
      border-top: 1px solid #c8e6c9;
    }
  </style>
</head>
<body>
  <header>
    <h1>AgriSmart</h1>
    <p>Smart agricultural management system with AI-based authentication</p>
  </header>

  <main>
    <section>
      <h2>Overview</h2>
      <p>AgriSmart is a smart agricultural management system designed to digitalize farm operations and improve administrative efficiency. It integrates modern web technologies with AI-based face recognition for secure and intelligent authentication.</p>
    </section>

    <section>
      <h2>Features</h2>
      <ul>
        <li>Secure authentication with JWT 🔐</li>
        <li>AI-based face recognition login 🤖</li>
        <li>Farmer and user management 👨‍🌾</li>
        <li>Admin dashboard with statistics 📊</li>
        <li>Full CRUD operations 📁</li>
        <li>Role-based access control 🔎</li>
      </ul>
    </section>

    <section>
      <h2>Tech Stack</h2>
      <p>Twig - Bootstrap - Symfony (PHP) - MySQL - Python</p>
    </section>

    <section>
      <h2>Architecture</h2>
      <ul>
        <li>Presentation Layer: Twig + JS</li>
        <li>Business Logic Layer: Symfony Controllers & Services</li>
        <li>Data Layer: MySQL Database</li>
        <li>AI Module: Python integrated with Symfony</li>
      </ul>
    </section>

    <section>
      <h2>Getting Started</h2>
      <pre>
git clone https://github.com/Abir-BK/agrismart.git
cd agrismart
composer install
# Configure .env with your database credentials
php bin/console doctrine:migrations:migrate
symfony server:start
      </pre>
    </section>

    <section>
      <h2>Contributors</h2>
      <p>PIDEV Project Team: Abir Benkhlifa, Soumaya Drydy, Aya Fdhyla, Amine Arfaoui, Akrem Zaied</p>
    </section>

    <section>
      <h2>Academic Context</h2>
      <p>This project was developed as part of the PIDEV – 3rd Year Engineering Program at Esprit School of Engineering (Academic Year 2025–2026).</p>
    </section>

    <section>
      <h2>Acknowledgments</h2>
      <p>Special thanks to our professors and mentors at Esprit School of Engineering for their support and guidance.</p>
    </section>
  </main>

  <footer>
    &copy; 2026 AgriSmart Project Team
  </footer>
</body>
</html>
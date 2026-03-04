<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriSmart - README</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      color: #333;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 900px;
      margin: 40px auto;
      padding: 20px 30px;
      background: #fff;
      border-radius: 6px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    h1, h2 {
      color: #2c3e50;
    }

    h1 {
      border-bottom: 2px solid #2c3e50;
      padding-bottom: 10px;
    }

    h2 {
      margin-top: 30px;
      margin-bottom: 10px;
      font-size: 1.4rem;
    }

    p {
      margin: 10px 0;
    }

    ul {
      padding-left: 20px;
    }

    ul li {
      margin-bottom: 6px;
    }

    pre {
      background: #f0f0f0;
      padding: 10px;
      border-radius: 4px;
      overflow-x: auto;
    }

    footer {
      text-align: center;
      margin-top: 40px;
      font-size: 0.9rem;
      color: #666;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>AgriSmart</h1>

    <h2>Overview</h2>
    <p>AgriSmart is a smart agricultural management system designed to digitalize farm operations and improve administrative efficiency. It integrates modern web technologies with AI-based face recognition for secure and intelligent authentication.</p>

    <h2>Features</h2>
    <ul>
      <li>Secure authentication with JWT</li>
      <li>AI-based face recognition login</li>
      <li>Farmer and user management</li>
      <li>Admin dashboard with statistics</li>
      <li>Full CRUD operations</li>
      <li>Role-based access control</li>
    </ul>

    <h2>Tech Stack</h2>
    <p>Twig, Bootstrap, Symfony (PHP), MySQL, Python</p>

    <h2>Architecture</h2>
    <ul>
      <li>Presentation Layer: Twig + JS</li>
      <li>Business Logic Layer: Symfony Controllers & Services</li>
      <li>Data Layer: MySQL Database</li>
      <li>AI Module: Python integrated with Symfony</li>
    </ul>

    <h2>Getting Started</h2>
    <pre>
git clone https://github.com/Abir-BK/agrismart.git
cd agrismart
composer install
# Configure .env with your database credentials
php bin/console doctrine:migrations:migrate
symfony server:start
    </pre>

    <h2>Contributors</h2>
    <p>PIDEV Project Team: Abir Benkhlifa, Soumaya Drydy, Aya Fdhyla, Amine Arfaoui, Akrem Zaied</p>

    <h2>Academic Context</h2>
    <p>This project was developed as part of the PIDEV – 3rd Year Engineering Program at Esprit School of Engineering (Academic Year 2025–2026).</p>

    <h2>Acknowledgments</h2>
    <p>Special thanks to our professors and mentors at Esprit School of Engineering for their support and guidance.</p>

  </div>

  <footer>
    &copy; 2026 AgriSmart Project Team
  </footer>
</body>
</html>

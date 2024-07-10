# Product Management
## Overview

Product Management CRUD - Managing products with basic operations: Create, Read, Update, and Delete. This involves creating forms to add new products, displaying product information, updating existing products, and deleting them. Symfony components like Doctrine ORM for database interactions, Twig for templating, and Symfony Forms for form handling are crucial for efficiently implementing these CRUD functionalities.

# Technical Details
  Symfony 7.1\
  PHP v8.2.13\
  JQuery 3.7.1\
  Bootstrap 5.3.3\
  MySQL 5.7.43
  
# Installing the Project
1. Clone the master branch from git repo to your local machine.
2. Open git bash or terminal and navigate to the project folder
3. Create MySQL Database name it "productsdb" then run:
    * php bin/console doctrine:migrations:migrate
4. On your terminal, install dependencies: 
    * composer install
    * npm install
5. Running the project open two terminal and type:
    * npm run dev - to compile webpack
    * php bin/console server:start or symfony server:start
6. Open the project to your browser: http://127.0.0.1:8000 or http://localhost:8000

# Git Repository
https://github.com/raymondgeee/prod-mgnt.git

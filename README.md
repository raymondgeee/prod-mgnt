# Product Management
## Overview

Managing products with basic operations: Create, Read, Update, and Delete. This involves creating forms to add new products, displaying product information, updating existing products, and deleting them. Symfony components like Doctrine ORM for database interactions, Twig for templating, and Symfony Forms for form handling are crucial for efficiently implementing these CRUD functionalities.

# Technical Details
  Symfony v7.1\
  PHP v8.3.6\
  JQuery v3.7.1\
  Bootstrap v5.3.3\
  MariaDB v10.5.22
  
# Installing the Project
1. Clone the master branch from git repo to your local machine.
    * `git clone https://github.com/raymondgeee/prod-mgnt.git`
2. Open git bash or terminal and navigate to the project folder
3. On your terminal, install dependencies: 
    ```
    * composer install
    * npm install
    ```
4. Create Database run:
    ```
    * php bin/console doctrine:database:create
    * php bin/console make:migration
    * php bin/console doctrine:migrations:migrate
    ```
5. Running the project open terminal and type:
    ```
    * npm run dev - to compile webpack
    * php bin/console server:start or symfony server:start
    ```
6. Open the project to your browser: http://127.0.0.1:8000 or http://localhost:8000

# Git Repository
https://github.com/raymondgeee/prod-mgnt.git

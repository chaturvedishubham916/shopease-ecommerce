# ShopEase - Setup
1. Install XAMPP and start **Apache** and **MySQL**.
2. Copy the `shopease` folder to `C:\xampp\htdocs\`.
3. Open http://localhost/phpmyadmin -> Import -> choose `database/shopease.sql` -> Go.
4. Open http://localhost/shopease/

Admin login: http://localhost/shopease/admin/login.php  ->  `admin` / `admin123`

## Folder structure
- config/    database connection
- database/  SQL file
- includes/  shared header, footer, helper functions
- admin/     admin panel pages
- assets/    css, js, images
- uploads/products/  uploaded product images

These files are the login and registration scripts, that were build in our video:

Feel free to use them, change, etc.. They are meant for studying purposes, but we don't mind if you use them comercially in your website. 

System requirements:
====================

* A web hosting account with cPanel, DirectAdmin or any other control panel
* PHP 5 or newer (we have developed in PHP 7)
* MySQL

You can support us and get a web hosting account at Copahost: https://www.copahost.com/


Files in this package:
======================

database.php - Performs the connection to the MySQL server. You must edit this file,
changing your MySQL login, password and host details.

login.php - The login page. Queries the database and validates the login. Creates a cookie.

registration.php - The registration page. Adds a new customer to the databse.

restricted.php - This file must be included in all the pages where you require 
authentication. It checks the cookie and compared with the database.

private.php - The private area example. This page includes the restricted.php file , to 
ensure only authenticated users can enter.

logoff.php - The logoff page. It erases the login cookies.

users.sql - The Users table creation script, if necessary. You can also create it manually.

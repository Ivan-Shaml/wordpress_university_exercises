# Wordpress Site - University Exercises

## Config

Database Name: wordpress_upr
Database User: wordpress_upr
Database User Password: wordpress_upr1

### To create mysql user:

CREATE USER 'wordpress_upr'@'localhost' IDENTIFIED BY 'wordpress_upr1';
GRANT ALL PRIVILEGES ON * . * TO 'wordpress_upr'@'localhost';
FLUSH PRIVILEGES;

### Database Migration Files in wordpress_upr.sql file !
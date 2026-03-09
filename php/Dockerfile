FROM php:8.2-apache

# Installa l'estensione PDO per MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Abilita mod_rewrite di Apache (utile per future rotte)
RUN a2enmod rewrite

# Imposta la document root
WORKDIR /var/www/html
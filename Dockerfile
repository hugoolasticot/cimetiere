FROM php:8.2-apache

# Copie les fichiers dans le conteneur
COPY . /var/www/html/

# Active mod_rewrite si nécessaire
RUN a2enmod rewrite

FROM php:8.1-apache

# Copier les fichiers PHP
COPY . /var/www/html/

# Exposer le port
EXPOSE 80

# Commande par défaut
CMD ["apache2-foreground"]
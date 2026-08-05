
FROM nginx:alpine
# Copie les fichiers de l'application dans le répertoire par défaut de Nginx
COPY . /usr/share/nginx/html

# Copie le fichier de configuration Nginx personnalisé
COPY nginx.conf /etc/nginx/conf.d/default.conf
# Expose le port 80 pour accéder à l'application
EXPOSE 80
# Démarre Nginx en mode non détaché
CMD ["nginx", "-g", "daemon off;"]
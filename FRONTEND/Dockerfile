FROM nginx:alpine

# Copie tous vos fichiers statiques dans le dossier web par défaut de Nginx
COPY . /usr/share/nginx/html

# Nginx écoute sur le port 80 par défaut
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
FROM nginx:alpine

# Copie tous vos fichiers statiques dans le dossier web par défaut de Nginx
COPY . /usr/share/nginx/html

# Copie le fichier de configuration Nginx personnalisé
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
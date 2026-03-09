# On utilise Apache, le serveur web que tu connais
FROM httpd:2.4-alpine

# On copie tous tes fichiers (index.html, ton dossier css, js, images)
# vers le dossier que Apache utilise pour afficher les sites
COPY . /usr/local/apache2/htdocs/

EXPOSE 80
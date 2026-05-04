
FROM httpd:2.4-alpine
RUN sed -i 's/#LoadModule rewrite_module/LoadModule rewrite_module/' /usr/local/apache2/conf/httpd.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/' /usr/local/apache2/conf/httpd.conf
COPY . /usr/local/apache2/htdocs/

EXPOSE 80
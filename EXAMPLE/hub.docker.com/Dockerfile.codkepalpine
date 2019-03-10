#
# Dockerfile of CodKep
# Automatic builded by hub.docker.com from
#  https://github.com/hyper-prog/codkep
#
#

FROM alpine
LABEL maintainer="hyper80@gmail.com" Description="Codkep web framework on Apline linux with apache webserver"

RUN apk update && apk add php7-apache2 php7-pdo_mysql php7-pdo_pgsql php7-apcu php7-json php7-gd php-sockets
RUN mkdir -p /run/apache2 \
    && cp /etc/apache2/httpd.conf /etc/apache2/httpd.conf_orig \
    && cat /etc/apache2/httpd.conf_orig | sed 's/#LoadModule rewrite_module modules\/mod_rewrite.so/LoadModule rewrite_module modules\/mod_rewrite.so/' > /etc/apache2/httpd.conf \
    && ln -sf /proc/self/fd/1 /var/log/apache2/access.log \
    && ln -sf /proc/self/fd/1 /var/log/apache2/error.log \
    && rm -rf /var/www/localhost/htdocs/* \
    && mkdir /var/www/localhost/htdocs/sys
COPY ./EXAMPLE/hub.docker.com/myalpine.conf /etc/apache2/conf.d/my.conf
COPY . /var/www/localhost/htdocs/sys/
RUN cd /var/www/localhost/htdocs/sys \
    && rm -rf EXAMPLE \
    && cd /var/www/localhost/htdocs \
    && ln -s sys/index.php index.php \
    && mkdir site \
    && mkdir data \
    && chown apache:apache data \
    && chmod 775 data
VOLUME ["/var/www/localhost/htdocs/site","/var/www/localhost/htdocs/data"]

EXPOSE 80

CMD ["-D","FOREGROUND"]
# Srart httpd when container runs
ENTRYPOINT ["/usr/sbin/httpd"]

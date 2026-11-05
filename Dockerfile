# syntax=docker/dockerfile:1.7
FROM php:8.3-apache

ENV MPI_ROOT=/var/www/mpi \
    TZ=America/Guayaquil

# Extensiones requeridas por las tres aplicaciones.
# fileinfo, json, openssl y session ya vienen compiladas en la imagen oficial.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        gettext-base \
        libcurl4-openssl-dev \
        libonig-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring curl opcache \
    && a2enmod rewrite headers deflate expires remoteip setenvif \
    && rm -rf /var/lib/apt/lists/*

# Configuración PHP de producción.
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-mpi.ini

# Configuración común de Apache (HTTPS detrás del proxy, IP real, denegaciones).
COPY docker/apache/mpi-proxy.conf /etc/apache2/conf-available/mpi-proxy.conf
RUN a2enconf mpi-proxy \
    && rm -f /etc/apache2/sites-enabled/000-default.conf

# Plantillas de vhost; se renderizan al arrancar con los dominios reales.
COPY docker/apache/tienda.conf.template /opt/mpi/apache/tienda.conf.template
COPY docker/apache/acceso.conf.template /opt/mpi/apache/acceso.conf.template
COPY docker/apache/liquidaciones.conf.template /opt/mpi/apache/liquidaciones.conf.template

# Código de la plataforma.
WORKDIR /var/www/mpi
COPY . /var/www/mpi

# Copia semilla de las imágenes DEMO para el primer arranque de los volúmenes.
RUN mkdir -p /opt/mpi-seed \
    && cp -a /var/www/mpi/tienda/public/uploads /opt/mpi-seed/tienda-uploads \
    && cp -a /var/www/mpi/liquidaciones/public/uploads /opt/mpi-seed/liquidaciones-uploads \
    && mkdir -p /var/lib/php/sessions /data \
    && chown -R www-data:www-data \
        /var/www/mpi/tienda/public/uploads \
        /var/www/mpi/liquidaciones/public/uploads \
        /var/lib/php/sessions \
    && chmod 1733 /var/lib/php/sessions

# Punto de entrada: renderiza vhosts, prepara MariaDB y arranca Apache.
COPY docker/entrypoint.sh /usr/local/bin/mpi-entrypoint.sh
COPY docker/bootstrap.php /usr/local/bin/mpi-bootstrap.php
RUN sed -i 's/\r$//' /usr/local/bin/mpi-entrypoint.sh \
    && chmod 0755 /usr/local/bin/mpi-entrypoint.sh \
    && chmod 0644 /usr/local/bin/mpi-bootstrap.php

# Logs de Apache al stdout/stderr del contenedor (Coolify los captura).
RUN ln -sf /proc/self/fd/1 /var/log/apache2/access.log \
    && ln -sf /proc/self/fd/2 /var/log/apache2/error.log

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=5 \
    CMD curl -fsS http://127.0.0.1/ >/dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/mpi-entrypoint.sh"]
CMD ["apache2-foreground"]

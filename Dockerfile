# ----------------------------------------------------
# FASE 1: Builder para instalar dependências PHP (Composer)
# Usa uma imagem base maior, mas mais completa para a fase de construção
# ----------------------------------------------------
FROM composer:2.7 AS builder

# Define o diretório de trabalho dentro do container
WORKDIR /app

# Copia os arquivos de configuração do Composer
COPY composer.json composer.lock ./

# Instala as dependências do Composer. 

RUN composer install --no-dev --optimize-autoloader

# ----------------------------------------------------
# ----------------------------------------------------
FROM php:8.2-apache-bullseye

# Instala extensões PHP necessárias para Dompdf e PHPMailer
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    && docker-php-ext-install pdo_mysql zip mbstring gd

# Configura o Apache para usar o arquivo 'index.html' ou 'index.php'
RUN a2enmod rewrite

# Copia o código do projeto (incluindo a logo)
COPY --from=builder /app/vendor /var/www/html/vendor
COPY . /var/www/html


# A porta padrão do Apache é a 80
EXPOSE 80
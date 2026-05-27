FROM php:8.2-cli

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    sqlite3 \
    libsqlite3-dev

# Instala extensões necessárias do PHP
RUN docker-php-ext-install \
    pdo \
    pdo_sqlite \
    mbstring \
    bcmath \
    exif \
    pcntl \
    gd

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define pasta da aplicação
WORKDIR /app

# Copia arquivos do projeto
COPY . .

# Instala dependências do Laravel
RUN composer install --no-dev --optimize-autoloader

# Permissões do Laravel
RUN chmod -R 775 storage bootstrap/cache

# Expõe porta usada pelo Render
EXPOSE 10000

# Comando de inicialização
CMD php artisan serve --host=0.0.0.0 --port=10000
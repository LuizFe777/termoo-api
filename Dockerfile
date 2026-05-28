FROM php:8.4-cli

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    sqlite3 \
    libsqlite3-dev

# Configura GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Instala extensões PHP necessárias
RUN docker-php-ext-install \
    pdo \
    pdo_sqlite \
    mbstring \
    bcmath \
    exif \
    pcntl \
    gd \
    zip

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Diretório da aplicação
WORKDIR /app

# Copia arquivos
COPY . .

# Instala dependências do Laravel
RUN composer install --no-dev --optimize-autoloader

# Permissões
RUN chmod -R 775 storage bootstrap/cache

# Gera .env e APP_KEY se não existir
RUN cp .env.example .env && php artisan key:generate

# Porta usada pelo Render
EXPOSE 10000

# Inicializa Laravel
CMD php artisan serve --host=0.0.0.0 --port=10000
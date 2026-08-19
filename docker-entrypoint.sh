#!/bin/sh
set -e

# Aguarda o banco PostgreSQL ficar pronto
echo "Aguardando o banco de dados PostgreSQL iniciar..."
while ! nc -z ${DB_HOST:-timesheet-db} ${DB_PORT:-5432}; do
  sleep 1
done
echo "PostgreSQL está pronto!"

# Executa migrações
echo "Executando migrações do banco de dados..."
php artisan migrate --force

# Otimizações de cache para produção
if [ "$APP_ENV" = "production" ]; then
    echo "Otimizando cache do Laravel..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
else
    echo "Limpando cache do Laravel..."
    php artisan optimize:clear
fi

echo "Iniciando o Supervisor (Nginx, PHP-FPM, e Fila)..."
exec /usr/bin/supervisord -n -c /etc/supervisord.conf

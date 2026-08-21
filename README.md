# Vendeya - Sistema de Punto de Venta

Vendeya es un sistema de punto de venta (POS) desarrollado con Laravel, diseñado para gestionar ventas, inventario y operaciones de comercio minorista.

## Requisitos

- PHP 8.0+
- Composer
- Laravel 9.x
- Servidor web (Apache/Nginx)

## Instalación

1. Clonar el repositorio:
```bash
git clone https://gitlab.com/JordanFernandez/vendeya.git
```

2. Instalar dependencias:
```bash
composer install
```

3. Copiar archivo de configuración:
```bash
cp .env.example .env
```

4. Generar clave de aplicación:
```bash
php artisan key:generate
```

5. Ejecutar migraciones:
```bash
php artisan migrate
```

6. Iniciar el servidor:
```bash
php artisan serve
```

## Configuración

### Variables de Entorno

- `APP_NAME` - Nombre de la aplicación
- `APP_ENV` - Entorno (local, production)
- `APP_DEBUG` - Modo debug
- `APP_URL` - URL de la aplicación

### API Vendeya

- `VENDEYA_API_URL` - URL de la API
- `VENDEYA_API_TOKEN` - Token de autenticación
- `VENDEYA_COMPANY` - Identificador de empresa
- `VENDEYA_TIMEOUT` - Timeout de conexión

### APIPERU

- APIPERU_TOKEN - Token de apiperu.dev para consulta de DNI/RUC

## Licencia

MIT License

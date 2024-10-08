# prov Adapter
Сервис, для взаимодействия с аптечными системами большого магнита. 
Изначально разрабатывался как адаптер для УАС и Евразии, но обмен с евразией так и не запущен.

Карточка проекта [SRV-025+prov+Adapter](https://eportal.test1.ru/display/ECOM/SRV-025+prov+Adapter)

# Сборка контейнера для деплоя в кубер:
```
docker build -f docker/web/Dockerfile --target=deploy .
```


# Установка

1. Склонируйте репозиторий prov Connector, где prov-connector.localhost - название папки, в которую будет скачан проект:
```
git clone git@git.m.mcs.im:partner/prov_connector.git prov-connector.localhost
```
2. Перейдите в папку проекта:
```
cd prov-connector.localhost/
```
3. Скопируйте ваши ssh-ключи в ssh-agent
```
ssh-add <path-to-key>
```

5. Создайте файл .env.local и укажите следующие параметры:
```
APP_LOCALE=ru
APP_NAME=prov_adapter
APP_ENV=dev

MESSENGER_AMQP_DSN=amqp://guest:guest@rabbitmq:5672/%2f
CACHE_DSN=redis://redis:6379

PROVIDER_API_URL=http://127.0.0.1
MONOLITH_API_URL=http://dev114.test1.ru/api
```
5. Соберите все контейнеры, выполнив команду:
```
docker-compose build
```
6. Установите composer зависимости
```
docker-compose run fpm composer install
```
7. Запустите контейнеры командой:
```
docker-compose up
```
8. Откройте приложение по адресу http://localhost/health

# Команды

Запуск контейнеров в фоне
```shell
docker-compose up -d
```

Для мака запуск контейнеров
```shell
SSH_AUTH_SOCK="/run/host-services/ssh-auth.sock" docker-compose up -d
```

Удаление контейнеров
```shell
docker-compose down
```

Прогрев symfony кеша
```shell
docker-compose exec fpm bin/console cache:warmup
```

Откатить все миграции
```shell
docker-compose exec fpm bin/console doctrine:migrations:migrate first --no-interaction
```

Накатить все миграции
```shell
docker-compose exec fpm bin/console doctrine:migrations:migrate --no-interaction
```

Запуск тестов при запущенных контейнерах
```shell
docker-compose exec fpm bin/phpunit
```

Запуск тестов в режиме дебага
```shell
docker-compose exec fpm bin/phpunit --debug
```

# Технологии:

PostgreSQL: 13.0 (взят из Catalog Adapter, скорее всего нужно взять 14.0)

Symfony 5.4 (взят из-за того, что поддержка его будет осуществляться еще 2,5 года https://symfony.com/releases )

PHP 8.1.12 (взята как текущая стабильная https://www.php.net/manual/ru/doc.changelog.php )

XDebug 3.0.4 (уточняйте порт в docker-compose.yml:PHP_XDEBUG_CLIENT_PORT)

# Дополнительный софт
## PHPStan - PHP Static Analysis Tool
https://github.com/phpstan/phpstan

```
vendor/bin/phpstan analyse src
```
Возможный негативный вывод:
```
/var/www # vendor/bin/phpstan analyse src
Note: Using configuration file /var/www/phpstan.neon.
2/2 [################] 100%

------ -----------------------------------------------------------------------------------------------------------
Line   Controller/DefaultController.php
------ -----------------------------------------------------------------------------------------------------------
9      Method App\Controller\DefaultController::index() has no return type specified.
16     Binary operation "/" between 1 and 0 results in an error.
17     Parameter #1 $content of class Symfony\Component\HttpFoundation\Response constructor expects string|null,
string|false given.
------ -----------------------------------------------------------------------------------------------------------

[ERROR] Found 3 errors
```
Возможный позитивный вывод:
```
/var/www # vendor/bin/phpstan analyse src
Note: Using configuration file /var/www/phpstan.neon.
 2/2 [################] 100%

 [OK] No errors
```

## PHP Coding Standards Fixer
https://github.com/FriendsOfPHP/PHP-CS-Fixer
```
vendor/bin/php-cs-fixer fix src
```
# Мониторинг
Для развернутой в Grafana [prov-adapter dashboard](https://grafana.dev.test_corp/d/pApr5wkVk/provadapter), необходимы данные из Prometheus, который развернут в VKCS. Для этого к нему предоставлен доступ из сетей ЯО, а в Grafana подключен соотвествующий DatsSource с префиксом VK.

## Тестирование

Создадим тестовую БД (пока не используется)
```
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test
```

Тесты аффектят БД, но сообения Кафки генерируют в фейковую очередь.

Так же, доступны тестовые команды:
 - ```php bin/console app:test:import:remains %storeId% %article% %quantity% %price% %codeMf% %vital% %barcode%``` - отправка в очередь Кафки сообщения с указанными параметрами.
Отсутствующие параметры, как и строка null будут заменены на null. Пример вызова: ```php bin/console app:test:import:remains 1 2 3 4 ААА null AABBCC```

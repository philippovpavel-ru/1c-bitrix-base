# Мероприятия — сайт на 1С-Битрикс (локальное окружение)

Проект для локальной разработки раздела «Мероприятия» на **1С-Битрикс: Управление сайтом** (редакция «Бизнес»).
Код сайта лежит в этом репозитории, а сам дистрибутив Битрикса, база данных и кеш — в Docker-томе.
Окружение собрано на образах `quay.io/bitrix24/*` из официального шаблона `env-docker`.

## Содержание

- [Требования](#требования)
- [Быстрый старт](#быстрый-старт)
- [Адреса и доступы](#адреса-и-доступы)
- [Структура проекта](#структура-проекта)
- [Как устроены монтирования](#как-устроены-монтирования)
- [Состав контейнеров](#состав-контейнеров)
- [Разработка](#разработка)
- [Шпаргалка по командам](#шпаргалка-по-командам)
- [Текущее состояние настроек](#текущее-состояние-настроек)
- [Важно: чего нельзя делать](#важно-чего-нельзя-делать)

## Требования

- Docker Desktop (проверено на Docker 29.9 / Compose v5.6, Windows + Git Bash)
- Свободные порты: `8588` (HTTP), `8589` (HTTPS), `80` (lego)
- Файлы окружения: `confs/`, `sources/`, `.env*` — **не хранятся в git**, должны лежать локально рядом с `docker-compose.yml`
  (берутся из релиза `env-docker`; без `confs/` контейнеры nginx/php/redis/mysql не запустятся)

## Быстрый старт

```bash
docker compose up -d
docker compose ps
```

Проверка:

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8588/events/
```

Ожидаемо `200`. Сайт уже установлен в томе `dev_www_data` — установщик (`bitrixsetup.php`) запускать не нужно.

Сборка образов не требуется: используются готовые образы с тегами из `docker-compose.yml`.
Если нужно пересобрать локально — см. `docs/README.env-docker.md`, раздел про `sources/*/Dockerfile`.

## Адреса и доступы

| Что | Значение |
|---|---|
| Сайт (HTTP) | http://localhost:8588/ |
| Сайт (HTTPS) | https://localhost:8589/ — **демо-сертификат**, браузер будет ругаться |
| Админка | http://localhost:8588/bitrix/admin/ |
| Проверка системы | http://localhost:8588/bitrix/admin/site_checker.php?lang=ru |
| Логин администратора | `adminadmin` |
| E-mail администратора | `philippov.pavel@mail.ru` |
| MySQL | хост `mysql`, база `sitemanager`, пользователь `root`, пароль `bitrix` |
| PostgreSQL | хост `postgres`, пользователь `postgres`, пароль `bitrix` (Битриксом не используется) |
| Часовой пояс | `Europe/Moscow` (контейнеры + PHP) |
| Версия PHP | 8.2.35 (fpm), расширения из образа `bitrix24/php` |

Число таблиц в БД: 957, редакция «Бизнес».

## Структура проекта

```
.
├── docker-compose.yml        # 12 сервисов окружения
├── index.php                 # главная страница (шаблон-заготовка)
├── .top.menu.php             # верхнее меню сайта
├── events/                   # раздел «Мероприятия» (публичная часть)
│   ├── index.php             #   /events/ — афиша
│   ├── .section.php          #   свойства раздела
│   ├── about/index.php       #   /events/about/
│   └── personal/             #   /events/personal/ — личный кабинет
├── local/                    # кастомный код, перекрывающий /bitrix
│   ├── php_interface/init.php
│   ├── components/academy/event.host/          # шаблоны компонента
│   ├── modules/academy.events/                 # модуль + компонент registration.list
│   └── templates/events/                       # шаблон сайта «Мероприятия»
│       ├── header.php, footer.php, description.php
│       ├── styles.css, template_styles.css
│       └── components/bitrix/{menu,news.list,news.detail}/...
├── confs/                    # конфиги nginx / php82..85 / mysql / redis / sphinx (локально, вне git)
├── sources/                  # Dockerfile'ы образов окружения (локально, вне git)
└── docs/
    └── README.env-docker.md  # официальная документация шаблона env-docker
```

Всё, что находится в `/opt/www`, но не перечислено выше (`bitrix/`, `upload/`, `urlrewrite.php`, `favicon.ico` и т.д.),
живёт **только в Docker-томе** и в репозиторий не попадает.

## Как устроены монтирования

Базовый слой — именованный том `www_data` (~2 ГБ), в нём лежит весь установленный Битрикс.
Поверх него точечно смонтированы файлы и каталоги из репозитория:

| Хост | В контейнере | Сервисы |
|---|---|---|
| `www_data` (том) | `/opt/www/` | php, nginx, cron |
| `./index.php` | `/opt/www/index.php` | php, nginx, cron |
| `./.top.menu.php` | `/opt/www/.top.menu.php` | php, nginx, cron |
| `./events/` | `/opt/www/events/` | php, nginx, cron |
| `./local/` | `/opt/www/local/` | php, nginx, cron |
| `./confs/php82/etc/` | `/usr/local/etc/` | php, cron |
| `./confs/nginx/` | `/etc/nginx/` | nginx |
| `./confs/redis/redis.conf` | `/usr/local/etc/redis/redis.conf` | redis |
| `./confs/sphinx/sphinx.conf` | `/opt/sphinx/conf/sphinx.conf` | sphinx |

Схема наложения:

```
/opt/www                 ← том www_data: bitrix/, upload/, urlrewrite.php, favicon.ico ...
/opt/www/index.php       ← файл из репозитория
/opt/www/.top.menu.php   ← файл из репозитория
/opt/www/events          ← каталог из репозитория
/opt/www/local           ← каталог из репозитория
```

Docker накладывает вложенные mount'ы по длине пути, поэтому остальные файлы тома остаются доступны.
**Монтирование каталога `./что-то:/opt/www` целиком скрыло бы весь сайт** — так делать нельзя.

Практические следствия:

- новые страницы и шаблоны кладём в `events/` и `local/` — они сразу видны в контейнерах;
- файлы, созданные самим Битриксом в корне (`404.php`, `robots.txt`, `sitemap.php`), остаются в томе;
- при монтировании **отдельного файла** (`index.php`, `.top.menu.php`) действует ограничение Windows:
  если редактор сохраняет через «temp + rename», контейнер может продолжать видеть старую версию.
  Лечится `docker compose up -d --force-recreate php nginx cron`. Для каталогов (`events/`, `local/`) проблемы нет.

## Состав контейнеров

| Сервис | Контейнер | Образ | Порт |
|---|---|---|---|
| nginx | `dev_nginx` | `bitrix24/nginx:1.30.6-v1-alpine` | 8588, 8589 |
| php | `dev_php` | `bitrix24/php:8.2.35-fpm-v1-alpine` | — |
| cron | `dev_cron` | `bitrix24/php:8.2.35-fpm-v1-alpine` | — |
| mysql | `dev_mysql` | `bitrix24/percona-server:8.0.46-v1-rhel` | — |
| postgres | `dev_postgres` | `postgres:16.15-trixie` | — |
| redis | `dev_redis` | `redis:8.2.11-alpine` | — |
| memcached | `dev_memcached` | `memcached:1.6.45-alpine` | — |
| sphinx | `dev_sphinx` | `bitrix24/sphinx:2.2.11-v3-alpine` | — |
| push-pub / push-sub | `dev_push_pub`, `dev_push_sub` | `bitrix24/push:3.3-v1-alpine` | — |
| ssl | `dev_ssl` | `bitrix24/ssl:1.2-v1-alpine` | — |
| lego | `dev_lego` | `bitrix24/lego:4.35.2-v1-alpine` | 80 |

Сеть `dev` — `10.10.10.0/24`, контейнеры доступны по DNS-именам (`mysql`, `redis`, `php`, ...).
Пользователь внутри php/nginx/cron — `bitrix` (uid/gid `979`).

## Разработка

### Добавить страницу

Создать каталог/файл в `events/`, например `events/contacts/index.php`:

```php
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Контакты');
?>
<p>Текст страницы</p>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
```

URL: `http://localhost:8588/events/contacts/`. Перезапуск контейнеров не нужен.

### Шаблон сайта

Шаблон `events` (`local/templates/events/`) уже применён к сайту `s1`.
Кеш шаблона/меню включён — при правках `styles.css`, `header.php` и меню очищайте кеш:
**Админка → Настройки → Очистить кеш**, либо

```bash
docker compose exec --user=bitrix php sh -c 'rm -rf /opt/www/bitrix/cache/* /opt/www/bitrix/managed_cache/*'
```

### Меню

- `.top.menu.php` (корень) — верхнее меню, компонент `bitrix:menu` с шаблоном `events_top`;
- `events/personal/.left.menu.php` — левое меню личного кабинета.

Ссылки в меню — абсолютные, от корня сайта (`/events/`, `/events/personal/`).

### Компоненты и модули

- `local/components/academy/event.host` — шаблоны `.default` и `compact`;
- `local/modules/academy.events` — модуль, внутри `install/components/academy/registration.list`;
- перекрытия шаблонов компонентов — `local/templates/events/components/bitrix/...`.

Правило: ничего не правим в `/opt/www/bitrix/`, всё кастомизируем в `local/`.

### Права

На Windows/Docker Desktop файлы из репозитория видны как `root:root 0777` — php-fpm (uid 979) их читает.
Каталоги, в которые пишет Битрикс (`bitrix/cache`, `bitrix/managed_cache`, `upload`), находятся в томе и уже имеют нужные права.

## Шпаргалка по командам

```bash
# запуск / состояние / остановка
docker compose up -d
docker compose ps
docker compose stop
docker compose restart nginx php

# логи
docker compose logs -f nginx php

# консоль внутри контейнера
docker compose exec --user=bitrix php sh
docker compose exec nginx sh
docker compose exec --user=root cron sh

# проверка конфигов
docker compose config
docker compose exec nginx nginx -t

# MySQL
docker compose exec mysql sh -c 'mysql -uroot -pbitrix sitemanager'

# PHP-конфиг / версия
docker compose exec php sh -c 'php -v; php --ini'

# кеш Битрикса
docker compose exec --user=bitrix php sh -c 'rm -rf /opt/www/bitrix/cache/* /opt/www/bitrix/managed_cache/*'
```

В Git Bash на Windows пути внутри контейнера искажаются (`/opt/www` → `C:/Program Files/Git/opt/www`).
Перед такими командами добавляйте `MSYS_NO_PATHCONV=1`:

```bash
MSYS_NO_PATHCONV=1 docker exec dev_php ls -la /opt/www
```

## Текущее состояние настроек

Работает:

- все 12 контейнеров запущены, `nginx -t` проходит;
- `/` и `/events/*` → 200, `/bitrix/admin/` → 200;
- PHP 8.2.35, OPCache в dev-режиме (`validate_timestamps=1`, `revalidate_freq=0`) — правки подхватываются сразу;
- база подключена (`bitrix/.settings.php` → `mysql` / `sitemanager`), 957 таблиц;
- шаблон `events` применён, кастомные меню и стили подключаются.

Не настроено (для локальной разработки обычно не мешает):

- **Название сайта и URL** — в `b_lang` пустые `SITE_NAME`, `SERVER_NAME`, `EMAIL`. Задаётся в
  «Настройки → Настройки продукта → Сайты → Сайт по умолчанию» (или `/bitrix/admin/settings.php?lang=ru&mid=main`).
- **Почта** — `/opt/msmtp/.msmtprc` всё ещё шаблон (`host localhost`, `from xxx@yyy.zzz`), два теста почты в
  «Проверке системы» будут красными. Для отладки можно включить логирование писем в файл (`LOG_FILENAME`).
- **Агенты на cron** — `install.config` удалён, но в `bitrix/php_interface/dbconn.php` нет `BX_CRONTAB_SUPPORT`;
  агенты работают на хитах. Чтобы перевести на cron в контейнере `dev_cron`, добавьте `define("BX_CRONTAB_SUPPORT", true);`.
- **Роутинг** — в nginx `@bitrix` направлен на `urlrewrite.php`, `routing_index.php` закомментирован.
- **HTTPS** — демо-сертификаты `confs/nginx/certs/example.com`; для доверенного локального домена нужно
  сгенерировать CA и сертификат через контейнер `ssl`.
- **Redis / Memcache / Push&Pull / Sphinx** — сервисы запущены, но в настройках Битрикса не включены.
- **Структура БД** — «Проверка системы» находила 10 расхождений (7 чинятся автоматически) — можно исправить
  штатной кнопкой на странице `site_checker.php`.
- **Xdebug** — отключён (`confs/php82/etc/php/conf.d/docker-php-ext-xdebug.ini`).

Демо-контент: главная (`index.php`) — стандартная заготовка Битрикса («Text here....»), её можно заменить на реальную.
В `events/personal/.left.menu.php` остался пункт «Профиль» со ссылкой на `profile.php`, которого ещё нет (404) —
файл нужно создать или убрать пункт.

## Важно: чего нельзя делать

- **Не выполнять `docker compose down -v`** — удалит тома, включая `dev_www_data` (сайт) и `dev_mysql_lib_data` (БД).
  Восстановление = только переустановка Битрикса с нуля.
- **Не монтировать `./что-то:/opt/www`** — это скроет весь сайт из тома. Монтировать можно только вложенные пути.
- **Не удалять контейнеры с `--volumes`** и не чистить тома `dev_*` вручную.
- **Не править файлы в `/opt/www/bitrix/`** внутри контейнера — изменения пропадут при обновлении продукта и не попадут в git.
- При добавлении нового файлового mount'а сначала убедитесь, что файл существует на хосте, иначе Docker создаст
  на его месте каталог.

## Ссылки

- Официальная документация шаблона окружения — [`docs/README.env-docker.md`](docs/README.env-docker.md)
  (установка, cron, почта, SSL, роутинг, кеширование, сборка образов).
- Документация продукта: https://dev.1c-bitrix.ru/learning/
- Курс по разработке: https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=34

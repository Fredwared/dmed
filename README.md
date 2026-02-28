# API управления медиа

REST API для загрузки, управления и раздачи изображений. Построен на Laravel 12, рассчитан на высокую нагрузку (100K+ загрузок в день).

## Стек

- **PHP 8.4** с OPcache + JIT
- **Laravel 12** + Sanctum (токен-авторизация) + Spatie Data (DTO)
- **PostgreSQL 17** — основная БД
- **Redis 7** — кеш, сессии, очереди
- **RustFS (MinIO)** — S3-совместимое объектное хранилище
- **Nginx 1.27** — обратный прокси
- **Intervention Image 3** — конвертация в WebP через GD

## Архитектура

```
Запрос → DTO (валидация) → Controller → Action (бизнес-логика) → Response DTO → JSON
```

- **DTO** (`app/Data/`) — Spatie Data классы для валидации запросов и формирования ответов
- **Actions** (`app/Actions/`) — классы с единственной ответственностью для бизнес-логики
- **Controllers** (`app/Http/Controllers/`) — тонкие, делегируют логику в Actions
- **Models** (`app/Models/`) — Eloquent с типизированными свойствами

## API эндпоинты

### Аутентификация

| Метод | Эндпоинт | Авторизация | Описание |
|-------|----------|-------------|----------|
| POST | `/api/auth/register` | Нет | Регистрация, получение токена |
| POST | `/api/auth/login` | Нет | Вход, получение токена |
| POST | `/api/auth/logout` | Да | Отзыв текущего токена |
| POST | `/api/auth/forgot-password` | Нет | Отправка ссылки для сброса |
| POST | `/api/auth/reset-password` | Нет | Сброс пароля по токену |

### Изображения

| Метод | Эндпоинт | Авторизация | Описание |
|-------|----------|-------------|----------|
| POST | `/api/images/upload-url` | Да | Получить pre-signed URL для загрузки |
| POST | `/api/images/confirm` | Да | Подтвердить загрузку, запустить обработку |
| GET | `/api/images` | Да | Список ваших изображений (с пагинацией) |
| GET | `/api/images/{id}` | Да | Детали изображения + подписанный URL |
| DELETE | `/api/images/{id}` | Да | Удаление изображения |

Все защищённые эндпоинты требуют заголовок `Authorization: Bearer {token}`.

### Лимиты запросов

- Авторизация (сброс пароля): **5 запросов/мин** на IP
- Загрузка изображений: **100 запросов/мин** на пользователя

## Загрузка изображений

Загрузка происходит в 3 шага — клиент загружает файл напрямую в S3, PHP не пропускает байты через себя.

### Флоу

```
1. POST /api/images/upload-url  →  получить pre-signed PUT URL + file_key
2. PUT {upload_url}             →  клиент загружает файл напрямую в S3
3. POST /api/images/confirm     →  PHP проверяет файл, создаёт запись, запускает обработку
```

### Шаг 1 — Получить URL для загрузки

```bash
curl -s -X POST http://localhost/api/images/upload-url \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"filename": "photo.jpg", "mime_type": "image/jpeg"}'
```

Ответ:

```json
{
  "upload_url": "https://s3.example.com/uploads/1/uuid.jpg?X-Amz-Signature=...",
  "file_key": "uploads/1/uuid.jpg"
}
```

Допустимые `mime_type`: `image/jpeg`, `image/png`.

### Шаг 2 — Загрузить файл в S3

```bash
curl -X PUT "${upload_url}" \
  -H "Content-Type: image/jpeg" \
  --data-binary @photo.jpg
```

URL действителен 5 минут.

### Шаг 3 — Подтвердить загрузку

```bash
curl -s -X POST http://localhost/api/images/confirm \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"file_key": "uploads/1/uuid.jpg"}'
```

Ответ (201):

```json
{
  "id": 1,
  "original_filename": "uuid.jpg",
  "mime_type": "image/jpeg",
  "file_size": 204800,
  "width": null,
  "height": null,
  "url": null,
  "status": "pending",
  "created_at": "2026-02-28T10:00:00.000000Z"
}
```

После подтверждения файл обрабатывается в фоне (конвертация в WebP). Когда `status` станет `"ready"`, в поле `url` появится подписанная ссылка для скачивания.

### Обработка

- **Принимаемые форматы**: PNG, JPEG (макс. 5МБ)
- **На выходе**: WebP (quality 80) — лучшее соотношение размер/качество
- **Фоновая обработка**: оригинал из `uploads/` → WebP в `images/{user_id}/{hash}.webp` → оригинал удаляется
- **Статусы**: `pending` → `ready` (или `failed`)
- **Дедупликация**: SHA-256 хеш, уникален в рамках пользователя. Повторная загрузка того же файла вернёт существующую запись
- **Раздача**: подписанные временные URL из S3 (60 мин)
- **Race conditions**: обработка через `UniqueConstraintViolationException`

## Быстрый старт

### Требования

- Docker и Docker Compose

### Установка

```bash
cp .env.example .env
make setup
```

Это выполнит:
1. Сборку всех контейнеров
2. Запуск сервисов (PostgreSQL, Redis, RustFS, PHP-FPM, Nginx, Queue Worker)
3. Генерацию ключа приложения
4. Запуск миграций БД
5. Создание бакета в S3

API доступен по адресу `http://localhost` (порт настраивается через `APP_PORT` в `.env`).

### Быстрая проверка

```bash
# Регистрация
curl -s -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@example.com","password":"password123","password_confirmation":"password123"}'

# Получить URL для загрузки (замените TOKEN)
curl -s -X POST http://localhost/api/images/upload-url \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"filename":"photo.jpg","mime_type":"image/jpeg"}'

# Загрузить файл напрямую в S3 (замените UPLOAD_URL)
curl -X PUT "UPLOAD_URL" \
  -H "Content-Type: image/jpeg" \
  --data-binary @photo.jpg

# Подтвердить загрузку (замените TOKEN и FILE_KEY)
curl -s -X POST http://localhost/api/images/confirm \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"file_key":"FILE_KEY"}'
```

## Разработка

### Основные команды

```bash
make up              # Запустить все сервисы
make down            # Остановить все сервисы
make restart         # Перезапустить сервисы
make logs s=app      # Просмотр логов приложения
make shell           # Зайти в контейнер приложения
make test            # Запустить тесты
make migrate         # Выполнить миграции
make fresh           # Чистые миграции + сид
make cache           # Очистить и пересобрать кеши
```

## Документация API

Сгенерирована с помощью [Scribe](https://scribe.knuckles.wtf). Доступна по адресу `/docs` при запущенном приложении.

Перегенерация после изменений:

```bash
make artisan c="scribe:generate"
```

Также генерируются:
- Postman-коллекция по адресу `/docs.postman`
- OpenAPI 3.0.3 спецификация по адресу `/docs.openapi`

## Docker-сервисы

| Сервис | Контейнер | Порт | Назначение |
|--------|-----------|------|------------|
| app | dmed-app | 9000 (внутренний) | PHP-FPM |
| nginx | dmed-nginx | 80 | Обратный прокси |
| postgres | dmed-postgres | 5432 | База данных |
| redis | dmed-redis | 6379 | Кеш/Очереди/Сессии |
| rustfs | dmed-rustfs | 9000, 9001 | S3-хранилище + консоль |
| queue | dmed-queue | — | Фоновые задачи |

Консоль RustFS (MinIO UI): `http://localhost:9001` — креды в `.env`.

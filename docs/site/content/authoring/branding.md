# Брендирование сайта

Брендирование задаёт название продукта, подпись раздела, логотипы для светлой
и тёмной темы и favicon. Эти файлы принадлежат проекту: обновление Docara не
должно заменять их.

## 1. Подготовьте файлы

Положите изображения в корневой каталог `assets`:

```text
assets/
  logo.svg
  logo-dark.svg
  favicon.svg
```

Используйте относительные пути без `..`, обратных слешей и начального `/`.
Символический link вместо файла не допускается. Для SVG задайте `viewBox`,
чтобы изображение масштабировалось на узких экранах.

## 2. Настройте `docara.json`

Ниже приведён полный минимальный файл сайта с брендингом:

```json
{
  "schema": "docara.site.v1",
  "title": "Acme Docs",
  "preset": "docs",
  "content_root": "content",
  "framework_lock": "simai-framework.lock.json",
  "default_locale": "ru",
  "base_url": "/",
  "branding": {
    "title": "Acme",
    "label": "Документация",
    "logo": "assets/logo.svg",
    "logo_dark": "assets/logo-dark.svg",
    "favicon": "assets/favicon.svg"
  }
}
```

`branding.title` — имя продукта и ссылка на главную. `branding.label` — короткая
подпись текущего сайта, например «Документация» или «API». Если отдельного
тёмного логотипа нет, укажите тот же файл в `logo` и `logo_dark`.

Header является системной областью с зарегистрированным Smart-компонентом
`docara.header`. Он получает уже проверенный объект `branding`; вставлять HTML
логотипа или путь к template в авторский JSON не нужно.

## 3. Переопределите бренд только для ветки

В `content/api/section.json` можно изменить подпись для каталога и потомков:

```json
{
  "schema": "docara.section.v1",
  "branding": {
    "label": "API Reference"
  }
}
```

Остальные поля наследуются из `docara.json`. Если нужен полностью другой
набор, начните объект с `"$reset": true` и явно задайте все необходимые поля.

## 4. Проверьте результат

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Проверьте header, favicon, светлую и тёмную темы, затем остановите preview через
`Ctrl+C`. Если asset отсутствует или выходит за пределы проекта, сборка должна
остановиться до замены прежнего output.

Далее: [области и их содержимое](/authoring/regions/) и
[наследование настроек](/authoring/inheritance/).

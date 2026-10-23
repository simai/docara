---
description: "Создайте страницу по шаблону, соберите сайт и откройте страницу в браузере."
profile: tutorial
---

# Создайте первую страницу

На этом уроке вы добавите страницу на сайт, соберёте сайт и откроете страницу.
Замените этот урок первым уроком, который нужен вашим читателям.

## Что понадобится

- Проект создан командой `docara init`.
- Терминал открыт в папке проекта.

## Шаги

1. Создайте страницу по шаблону инструкции:

   ```bash
   php vendor/bin/docara scaffold page how-to/my-task --type how_to --title "Моя задача"
   ```

2. Откройте `content/ru/how-to/my-task.md`. Замените каждое предложение-подсказку
   и удалите строку `draft: true`.
3. Проверьте страницу и соберите сайт:

   ```bash
   php vendor/bin/docara validate page /how-to/my-task/
   php vendor/bin/docara build production
   ```

4. Откройте сайт командой `php vendor/bin/docara serve production --no-build`
   и найдите страницу в меню.

## Результат

Страница «Моя задача» появилась в разделе «Инструкции».

## См. также

- [Опубликуйте сайт](/how-to/publish-the-site/)
- [Типы страниц](/reference/page-types/)

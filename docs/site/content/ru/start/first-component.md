---
description: Урок на 20 минут — создать свой компонент проекта командой scaffold, оформить его классами SIMAI Framework и вставить на страницу.
tags: [урок, компонент-проекта, smart, scaffold]
profile: tutorial
---

# Урок: первый компонент проекта

Когда готовых блоков не хватает, проект может завести свой компонент. За
этот урок вы создадите карточку объявления с заголовком и текстом, оформите
её классами SIMAI Framework и поставите на главную страницу. Урок занимает
около 20 минут.

Вы сделаете шесть шагов:

1. Объявите пространство имён компонентов проекта.
2. Составите план заготовки.
3. Примените план.
4. Оформите шаблон.
5. Вставите компонент на страницу.
6. Соберёте сайт и проверите результат.

Понадобится проект из [быстрого старта](/ru/start/quick-start/), например
созданный командой `php vendor/bin/docara init . --lang=ru`.

## 1. Объявите пространство имён

Все компоненты проекта начинаются с общего имени, чтобы не путаться со
встроенными. Добавьте в `docara.json` поле `smart`:

```json {title="docara.json"}
{
  "smart": { "namespace": "project" }
}
```

Остальные поля файла оставьте как есть. Имена `ui` и `docara` заняты
встроенными компонентами.

## 2. Составьте план заготовки

```bash
php vendor/bin/docara scaffold smart project.notice-card --dry-run
```

Команда ничего не записывает. Она показывает, какие файлы создаст, и
возвращает номер плана в поле `plan_id`:

```text
"plan_id": "3fe97af5…"
```

## 3. Примените план

Передайте номер плана целиком:

```bash
php vendor/bin/docara scaffold --apply=<plan_id>
```

Появятся три файла:

```text
smart/project.notice-card/
  manifest.json          описание: параметры, назначение, доступность
  view/default.json      представление по умолчанию
  template/default.php   шаблон, который выводит HTML
```

В `manifest.json` уже объявлены два обязательных параметра — `title` и
`text`. Проверьте заготовку:

```bash
php vendor/bin/docara validate smart project.notice-card
```

Все проверки в ответе должны быть отмечены `PASS`.

## 4. Оформите шаблон

Шаблон получает параметры в `$props` и печатает HTML. Замените
`smart/project.notice-card/template/default.php`:

```php {title="smart/project.notice-card/template/default.php"}
<?php

declare(strict_types=1);

$title = htmlspecialchars((string) ($props['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$text = htmlspecialchars((string) ($props['text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
echo '<aside data-docara-smart="project.notice-card" class="flex flex-col gap-1/2 p-2 radius-2 border bg-surface-0 m-bottom-1">'
    . '<strong>' . $title . '</strong>'
    . '<p class="m-0">' . $text . '</p>'
    . '</aside>';
```

- `htmlspecialchars` экранирует текст: так в параметры нельзя подложить
  чужую разметку. Не убирайте его.
- Классы `flex`, `p-2`, `radius-2`, `border`, `bg-surface-0` — утилиты
  SIMAI Framework. Docara сама подключит их стили и подстроит цвета под
  светлую и тёмную тему.

## 5. Вставьте компонент на страницу

Допишите в конец `content/ru/index.md`:

```markdown
:::project.notice-card
{"title":"Новая версия","text":"Сервис обновлён: поиск стал быстрее."}
:::
```

Параметры пишутся в JSON между строками `:::`.

## 6. Соберите и проверьте

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Откройте `http://127.0.0.1:8000/` и прокрутите главную вниз: там рамка с
заголовком «Новая версия» и текстом под ним. Остановите просмотр клавишами
`Ctrl+C`.

Теперь проверьте, что сборка следит за параметрами. Уберите из вызова
`"text"` и соберите сайт ещё раз — сборка остановится:

```text
[PORTABLE_SMART_PROPS_INVALID] Portable Smart [project.notice-card] has invalid [props.text.required]. Source [content/ru/index.md:38:1].
```

Сообщение называет параметр и строку страницы. Верните `"text"`.

## Если что-то пошло не так

| Сообщение | Что сделать |
| --- | --- |
| `SCAFFOLD_NAMESPACE_FORBIDDEN` | Имя компонента не начинается с пространства имён из `docara.json`. Проверьте шаг 1 и пишите `project.<имя>`. |
| `SCAFFOLD_PLAN_ID_INVALID` | В `--apply` передан не весь номер плана. Скопируйте `plan_id` целиком. |
| `SCAFFOLD_TARGET_EXISTS` | Такой компонент уже есть. Выберите другое имя. |
| `SMART_REGISTRY_COMPONENT_NOT_FOUND` | На странице опечатка в имени компонента: такого компонента нет в `smart/`. |

Остальные коды — в справочнике
[«Ошибки инструментов разработчика»](/ru/reference/errors/developer/) и
[«Ошибки оформления и расширения»](/ru/reference/errors/extend/#компоненты-проекта).

## Что дальше

- Как устроены компоненты проекта: стили и скрипты в `assets/`,
  представления, пресеты — на странице [«Smart-компоненты»](/ru/development/smart-components/).
- Весь путь от плана до проверки для разработчика и AI-агента — на странице
  [«SDK для разработчика и AI-агента»](/ru/development/developer-sdk/).
- Компонент можно поставить внутрь [оформленной полосы](/ru/components/surface/),
  если объявить его встраиваемым.

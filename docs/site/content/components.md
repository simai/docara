# Компоненты

Docara собирает один эффективный каталог из трёх исполняемых семейств:

- **Native Markdown** — заголовки, текст, ссылки, изображения, списки, цитаты,
  код и таблицы из ограниченного переносимого профиля;
- **typed-компоненты Docara** — безопасные семантические блоки `card`, `steps`,
  `cta` и `features` с собственными проверяемыми renderer;
- **Smart-компоненты Simai Framework** — только компоненты, допущенные точным
  `simai-framework.lock.json`, с manifest, проверкой props и зафиксированными
  ассетами.

Сборка сохраняет эту производную проекцию в
`_docara/component-catalog.json` (публичный URL учитывает `base_url`) как
`docara.effective_component_catalog.v1`. Это не второй реестр Simai Framework:
каталог не может сам разрешить Smart-компонент и не заявляет готовность всех
компонентов или production readiness.

У каждой записи есть lifecycle: `supported`, `admission_pending`,
`framework_gap` или `deferred`. Неисполняемые записи потребностей объясняют,
чего не хватает, кто владеет изменением, какой есть fallback и что должно
произойти для допуска. Наличие такой записи не делает синтаксис доступным.

## Справочник

- [Синтаксис и границы](/components/syntax/)
- [Alert](/components/alert/)
- [Button](/components/button/)
- [Card](/components/card/)
- [Steps](/components/steps/)
- [CTA-ссылка](/components/cta/)
- [Список возможностей](/components/features/)
- [Code](/components/code/)
- [Table](/components/table/)
- [Tabs](/components/tabs/)

Страницы поддерживаемых возможностей объясняют авторский синтаксис и показывают
примеры. Страница недоступной потребности объясняет gap, fallback и условие
будущего допуска. Машинным источником фактического статуса остаётся каталог
конкретной сборки. Не используйте произвольный HTML вместо отсутствующего
компонента: raw HTML в переносимом Markdown удаляется.

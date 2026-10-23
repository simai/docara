# Разработка Docara

[Путь разработчика и AI-агента](/ru/development/developer-sdk/#путь-разработчика-и-ai-агента)
ведёт через discover → plan → preview → dry-run → hash-bound apply → validate
на одних application services.

Этот раздел предназначен для maintainers движка. Пользователю переносимого
сайта не нужно изменять тему; для сборки ему достаточно PHP, Composer и
Node.js 20+ из [быстрого старта](/ru/start/quick-start/).

## Руководства

- [Начало разработки](/ru/development/getting-started/)
- [Архитектура](/ru/development/architecture/)
- [Композиция интерфейса](/ru/development/composition/) — в том числе свои макеты и блоки проекта
- [Smart-компоненты](/ru/development/smart-components/)
- [SDK для разработчика и AI-агента](/ru/development/developer-sdk/)
- [Тестирование](/ru/development/testing/)
- [Примеры для разработчиков](/ru/development/examples/)

## Как развивать возможности

Начинайте с самого простого уровня расширения: native Markdown, затем
typed-компонент Docara, затем Smart-компонент из exact Framework lock. Если
исполняемого контракта ещё нет, добавьте requirement с явной безопасной
заменой. Изменения не должны обходить schema, manifest, exact revision,
negative tests и browser acceptance.

Каждое расширение начинайте с самого простого строительного блока, который
решает задачу. Не начинайте со Smart-компонента только потому, что он существует
во Framework.

### 1. Проверьте native Markdown

Выберите native Markdown, если задачу решают заголовок, текст, ссылка, список,
цитата, code fence, изображение или небольшая таблица.

Изменение native profile допустимо только вместе с parser/render tests,
документацией и exact fixture. Не разрешайте raw HTML как обход отсутствующей
возможности.

### 2. Добавьте typed-компонент Docara

Typed-компонент подходит для повторяемого семантического блока с
детерминированным PHP renderer, например `card`, `steps`, `cta`, `features` или
`columns`.

1. Добавьте единственную definition в `resources/component-catalog/typed/`.
2. Зафиксируйте новый versioned renderer id.
3. Опишите узкий авторский синтаксис и ограничения.
4. Добавьте exact fixture, positive и negative renderer tests.
5. Добавьте один физический Markdown-owner страницы компонента и проверьте его
   через тот же PageBuilder.

Typed renderer может использовать только проверенные утилиты и компоненты
SIMAI Framework; он не создаёт независимую дизайн-систему.

### 3. Допустите Smart-компонент

Smart admission нужен, когда требуется точный компонент SIMAI Framework и его
runtime-поведение.

1. Получите owner-backed manifest с props, состояниями и immutable revision.
2. Закрепите provider revision, upstream revision, assets и SHA-256 в exact
   Framework lock.
3. Добавьте только сужающие consumer metadata.
4. Проверьте props, constraints, dependency closure, темы, клавиатуру,
   адаптивность и accessibility.
5. Добавьте физическую Markdown-страницу и отдельный exact fixture. Публичный
   текст не переносите в manifest, config или `lang.json`.

Consumer metadata не может расширить manifest или самостоятельно повысить
readiness. Подробный admission contract описан в разделе
[«Компонент SIMAI Framework»](/ru/development/smart-components/#компонент-simai-framework).

### 4. Зафиксируйте requirement

Если renderer, manifest, assets или acceptance ещё не готовы, создайте
неисполняемую запись в `resources/component-catalog/requirements/`.

Она должна назвать:

- owner;
- причину недоступности;
- безопасную замену;
- проверяемое условие допуска;
- честный lifecycle `admission_pending`, `framework_gap` или `deferred`.

Requirement делает дефицит видимым, но не разрешает вызов.

### Продвиньте возможность без overclaim

Requirement нельзя переключить в `supported` на месте.

1. Удалите requirement-запись.
2. Добавьте executable owner record в native profile, typed definitions либо
   Smart metadata с exact-lock admission.
3. Добавьте renderer, tests, docs и exact fixture.
4. Проверьте Markdown-owner, machine catalog, static verifier и browser matrix.
5. Заявляйте только lifecycle конкретной возможности.

Готовность одной записи не означает production readiness Docara, public
release или готовность остальных компонентов.

# Развитие возможностей

Каждое расширение начинайте с самого простого строительного блока, который
решает задачу. Не начинайте со Smart-компонента только потому, что он существует
во Framework.

## 1. Проверьте native Markdown

Выберите native Markdown, если задачу решают заголовок, текст, ссылка, список,
цитата, code fence, изображение или небольшая таблица.

Изменение native profile допустимо только вместе с parser/render tests,
документацией и exact fixture. Не разрешайте raw HTML как обход отсутствующей
возможности.

## 2. Добавьте typed-компонент Docara

Typed-компонент подходит для повторяемого семантического блока с
детерминированным PHP renderer, например `card`, `steps`, `cta`, `features` или
`columns`.

1. Добавьте единственную definition в `resources/component-catalog/typed/`.
2. Зафиксируйте новый versioned renderer id.
3. Опишите узкий авторский синтаксис и ограничения.
4. Добавьте exact fixture, positive и negative renderer tests.
5. Убедитесь, что generated catalog создаёт одну detail-страницу без ручного
   Markdown-дубля.

Typed renderer может использовать только проверенные утилиты и компоненты
Simai Framework; он не создаёт независимую дизайн-систему.

## 3. Допустите Smart-компонент

Smart admission нужен, когда требуется точный компонент Simai Framework и его
runtime-поведение.

1. Получите owner-backed manifest с props, состояниями и immutable revision.
2. Закрепите provider revision, upstream revision, assets и SHA-256 в exact
   Framework lock.
3. Добавьте только сужающие consumer metadata.
4. Проверьте props, constraints, dependency closure, темы, клавиатуру,
   адаптивность и accessibility.
5. Добавьте presentation в language packs и отдельный exact fixture; generated
   catalog объединит переводимый текст с параметрами и ограничениями
   эффективной записи.

Consumer metadata не может расширить manifest или самостоятельно повысить
readiness. Подробный admission contract находится в
[руководстве по Framework-компонентам](/development/framework-components/).

## 4. Зафиксируйте requirement

Если renderer, manifest, assets или acceptance ещё не готовы, создайте
неисполняемую запись в `resources/component-catalog/requirements/`.

Она должна назвать:

- owner;
- причину недоступности;
- безопасную замену;
- проверяемое условие допуска;
- честный lifecycle `admission_pending`, `framework_gap` или `deferred`.

Requirement делает дефицит видимым, но не разрешает вызов.

## Продвиньте возможность без overclaim

Requirement нельзя переключить в `supported` на месте.

1. Удалите requirement-запись.
2. Добавьте executable owner record в native profile, typed definitions либо
   Smart metadata с exact-lock admission.
3. Добавьте renderer, tests, docs и exact fixture.
4. Проверьте generated catalog, static verifier и browser matrix.
5. Заявляйте только lifecycle конкретной возможности.

Готовность одной записи не означает production readiness Docara, public
release или готовность остальных компонентов.

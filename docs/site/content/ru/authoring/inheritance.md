# Наследование настроек

Для `content/guides/install.md` слои применяются в одном порядке:

:::steps
1. Встроенные значения Docara.
2. `docara.json`.
3. Необязательный корневой `section.json`.
4. `content/section.json`.
5. `content/guides/section.json`.
6. `content/guides/install.page.json`.
7. Markdown как содержание.

:::

Поздний слой заменяет ранний только там, где явно содержит значение.

## Правила merge

| Вход | Результат |
| --- | --- |
| Поле отсутствует | Унаследованное значение сохраняется |
| Объект с частью ключей | Объекты объединяются рекурсивно |
| Массив | Предыдущий массив заменяется целиком |
| Скаляр | Предыдущее значение заменяется |
| `{}` или `[]` в presentation-ветке | Schema error |
| `{"$reset": true}` | Ветка очищается до пустого объекта |
| Reset + соседние ключи | Ветка очищается и заполняется заново |

Исключение — структурная часть `layout`: после очистки Docara возвращает
зарегистрированные `layout.key` и полную карту `layout.regions`, иначе страница
не имела бы проверяемого каркаса. Авторские значения layout при этом удаляются.

## Частичное переопределение

Корневой бренд может содержать title, label и logo. Раздел меняет только label:

```json
{
  "schema": "docara.section.v1",
  "branding": { "label": "API" }
}
```

Title и logo продолжают наследоваться.

## Reset с новыми значениями

Чтобы удалить унаследованные brand assets и оставить текстовый title:

```json
{
  "schema": "docara.page.v1",
  "branding": {
    "$reset": true,
    "title": "Печатная версия"
  }
}
```

У итоговой страницы больше нет унаследованных `logo`, `logo_dark`, `favicon`
и `label`.

## Reset без значений

```json
{
  "schema": "docara.page.v1",
  "navigation": { "$reset": true }
}
```

В resolved plan ветка `navigation` станет пустым JSON-объектом, а provenance
самой ветки укажет page sidecar. Leaf keys из ранних слоёв исчезнут.

При рендере отсутствие конкретного leaf использует безопасное поведение
renderer: страница не скрыта, явный order отсутствует. Это отличается от
обычного отсутствия всей ветки в sidecar, при котором ранние значения
продолжают наследоваться.

## Поиск

Раздел исключает страницы из индекса:

```json
{
  "schema": "docara.section.v1",
  "search": { "indexed": false }
}
```

Одна страница очищает эту ветку и задаёт полный новый результат:

```json
{
  "schema": "docara.page.v1",
  "search": {
    "$reset": true,
    "enabled": true,
    "indexed": true
  }
}
```

Если написать только `{"indexed": true}` без reset, `enabled` продолжит
наследоваться.

## Контекст чтения

```json
{
  "schema": "docara.page.v1",
  "reading": {
    "$reset": true,
    "breadcrumbs": true,
    "toc": true,
    "toc_depth": 2,
    "previous_next": false
  }
}
```

Reset затрагивает только `reading`; branding, layout, search и navigation
наследуются независимо.

## Области макета

Раздел отключает правое оглавление для всех потомков:

```json
{
  "schema": "docara.section.v1",
  "layout": {
    "regions": {
      "outline": { "enabled": false }
    }
  }
}
```

Одна страница может снова включить его:

```json
{
  "schema": "docara.page.v1",
  "layout": {
    "regions": {
      "outline": { "enabled": true }
    }
  }
}
```

При этом унаследованный список `sections` сохраняется. Если `sections` указан
явно, новый массив заменяет предыдущую композицию целиком.

[Полный контракт областей](/authoring/regions/).

[Живой пример композиции сайта, раздела и страницы](/examples/composition-inheritance/)
показывает наследованный бренд, заменённый sidebar, отключённый разделом aside,
его page-level reset и footer, включённый только для одной страницы.

## Проверка provenance

Соберите сайт:

```bash
php vendor/bin/docara build production
```

Откройте `.docara/resolved-page-plans.json` внутри output. Для нужной страницы
сравните:

- `resolved_page_plan.configuration` — итог;
- `provenance` — source каждого JSON Pointer;
- `trace` — входные файлы, schemas и hashes.

Если pointer отсутствует после reset, значение действительно удалено. Pointer
самой очищенной ветки остаётся и показывает файл, выполнивший reset.

[Подробнее о resolved plan](/reference/resolved-plan/).

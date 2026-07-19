# Markdown

Начинайте страницу с одного H1. Дальше используйте обычные H2–H6, абзацы,
списки, ссылки, изображения, цитаты, fenced code и таблицы.

```markdown
# Установка

Коротко объясните результат страницы.

## Шаги

1. Установите пакет.
2. Инициализируйте сайт.

| Команда | Результат |
| --- | --- |
| `docara build production` | Статический production-каталог |
```

Заголовки получают детерминированные Unicode IDs. При переименовании заголовка
меняется fragment URL, поэтому после правки deep links повторяйте
`verify-static`.

## Ссылки и изображения

Используйте относительные source assets и root routes сайта. `base_url`
добавляется генератором там, где это предусмотрено контрактом. Небезопасные
протоколы отклоняются.

## Rich content

Raw HTML удаляется. Если обычного Markdown недостаточно:

1. откройте [generated catalog](/components/catalog/);
2. выберите запись lifecycle `supported`;
3. скопируйте exact call со страницы записи;
4. пересоберите и проверьте сайт.

[Общие правила директив](/components/syntax/).

Не копируйте параметры компонента из старой страницы или другого проекта:
фактический contract принадлежит каталогу текущей сборки.

## Примеры директив

Внутри обычного fenced code строки директив остаются текстом и безопасно
показываются читателю:

````markdown
```markdown
:::ui.<component>
{"<prop>":"<value>"}
:::
```
````

## Проверка

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

Verifier обнаружит отсутствующий local route/fragment, duplicate HTML ID и
небезопасную fragment encoding.

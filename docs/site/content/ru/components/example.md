# Интерактивный пример

Блок помещает рядом готовый результат и его исходник. Читатель может
переключить вкладку, проверить поведение и скопировать точный вызов.

## Общий пример

:::example {label="Пример"}
```markdown
:badge[Готово]{type=main scheme=success size=1/2}
```
:::

Параметр `label` задаёт понятное название вкладки с результатом.

## Общий пример из проекта

Если один и тот же пример нужен на нескольких страницах или во всех локалях,
храните его один раз в корневой папке `examples/`:

```text
examples/
  utilities/
    animation-duration/
      index.html
      index.css
      index.js
      assets/
```

`index.html` обязателен. CSS, JavaScript и `assets/` добавляются только при
необходимости. Путь каталога становится стабильным идентификатором:

```markdown
:::example {id="utilities/animation-duration" label="Результат"}
:::
```

Docara автоматически показывает только существующие вкладки, публикует
разрешённые assets с учётом `base_url` и записывает зависимости в
`.docara/examples.json`. У блока должен быть либо `id`, либо встроенный код —
смешивать оба способа нельзя. После изменения общего примера выполните полную
сборку: точечная сборка специально остановится, чтобы не оставить другие
страницы со старой версией примера.

Sandbox получает тот же заранее собранный Framework CSS, что и страница
документации. Если JavaScript добавляет классы, которых нет в начальном HTML,
перечислите их семейства на корневом элементе через штатный
`data-sf-require`. Планировщик учитывает это требование только у исполняемого
примера; кодовые фрагменты в тексте страницы не расширяют набор ресурсов.

## HTML, CSS и JavaScript

Для небольшого автономного интерфейса объедините HTML с необязательными CSS и
JavaScript. Результат выполняется в изолированном iframe и не получает доступ к
странице документации.

:::example {label="Интерактивный пример"}
```html
<button class="example-button" type="button">Нажмите</button>
<output class="example-result">Готово</output>
```
```css
:root { color-scheme: light dark; }
body { color: CanvasText; background: Canvas; }
.example-button { padding: .5rem 1rem; }
.example-result { margin-inline-start: 1rem; }
```
```javascript
document.querySelector('.example-button').addEventListener('click', () => {
  document.querySelector('.example-result').textContent = 'Выполнено';
});
```
:::

## Вызов

~~~markdown
:::example {label="Пример"}
```markdown
:badge[Готово]{type=main scheme=success size=1/2}
```
:::
~~~

Markdown-пример использует ровно один исходник. Для интерфейсного примера
обязателен HTML; CSS и JavaScript добавляйте только когда они нужны результату.
Высота результата и исходников подстраивается при переключении вкладок, поэтому
короткий пример не резервирует место под самую длинную вкладку.

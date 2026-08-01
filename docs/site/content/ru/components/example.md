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

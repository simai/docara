<!-- docara-variant:base -->
:::example {label=Пример}
```markdown
:badge[Готово]{type=main scheme=success size=1/2}
```
:::

## HTML, CSS и JavaScript

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

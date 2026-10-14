<!-- docara-variant:base -->
:::example {label=Example}
```markdown
:badge[Ready]{type=main scheme=success size=1/2}
```
:::

## HTML, CSS and JavaScript

:::example {label="Interactive example"}
```html
<button class="example-button" type="button">Run</button>
<output class="example-result">Ready</output>
```
```css
:root { color-scheme: light dark; }
body { color: CanvasText; background: Canvas; }
.example-button { padding: .5rem 1rem; }
.example-result { margin-inline-start: 1rem; }
```
```javascript
document.querySelector('.example-button').addEventListener('click', () => {
  document.querySelector('.example-result').textContent = 'Done';
});
```
:::

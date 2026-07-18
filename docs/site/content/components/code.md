# Code

Для кода используется стандартный fenced code Markdown. Portable Docara
добавляет безопасную прокрутку, поверхность, границу и отступы Simai Framework.

```php
$site = 'Docara';
echo $site;
```

Укажите язык после открывающего fence, чтобы в HTML появился соответствующий
класс `language-*`:

````markdown
```php
echo 'Docara';
```
````

Текущий переносимый renderer не заявляет подсветку или кнопку копирования.
Это обычный семантический `<pre><code>`, а не выдуманный Smart-компонент.

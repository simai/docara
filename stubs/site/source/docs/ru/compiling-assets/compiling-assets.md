---
extends: _core._layouts.documentation
section: content
title: 'Компиляция активов'
description: 'Компиляция активов'
---

# Компиляция активов

Сайты-пазлы настроены с поддержкой Vite из коробки. Если вы когда-либо использовали Vite в проекте Laravel, вы
уже знают, как использовать Vite с Jigsaw.

## Настройка

Чтобы начать работу, сначала убедитесь, что у вас установлены Node.js и NPM. Затем подтяните зависимости, необходимые для компиляции
Ваши активы:

Установка npm
Для получения более подробных инструкций по установке ознакомьтесь с полной документацией Laravel Vite.

## Быстрая настройка

В корне вашего проекта вы найдете конфигурационный файл Vite с настроенным плагином Jigsaw:

`input` устанавливает две точки входа, `main.js` для JavaScript и `main.css` для CSS.

refresh указывает, следует ли прислушиваться к изменениям или нет.

```js
import jigsaw from '@tighten/jigsaw-vite-plugin';
import {defineConfig} from 'vite';

export default defineConfig({
    plugins: [
        jigsaw({
            input: [
                'source/_core/_assets/js/main.js',
                'source/_core/_assets/css/main.css'
            ],
            refresh: true,
        }),
    ],
});
```

## Ссылки на активы

В основном файле макета ссылки на ваши ресурсы выполняются с помощью метода vite. Директива @viteRefresh() нужна для
Включение автоматического обновления страниц при внесении изменений во время локальной разработки.

```html
@viteRefresh()
<link rel="stylesheet" href="{{ vite('source/_core/_assets/css/main.css') }}">
<script defer type="module" src="{{ vite('source/_core/_assets/js/main.js') }}"></script>
```

## Импорт модулей

Из этих точек входа вы можете легко импортировать дополнительные модули. Vite обрабатывает эти модули при предварительном просмотре
Ваш сайт с помощью npm run dev, и он эффективно объединяет их при подготовке к рабочей среде с помощью npm run build.

В JavaScript вы можете импортировать библиотеки и другие локальные файлы:

```js
import {get} from 'lodash';
import helpers from './helpers';
```

Точно так же в CSS вы можете импортировать другие файлы стилей, будь то из пакетов или локальные для вашего проекта:

```js
@import 'prismjs/themes/prism.css';

@import './more-styles.css';
```

## Более жирный и меньший

Vite предлагает встроенную поддержку файлов предварительного процессора, таких как .scss, .sass, .less, .styl и .stylus. Вам не нужно
установите для них любые плагины, специфичные для Vite. Однако вам необходимо установить соответствующий пакет препроцессора самостоятельно:

```
# For .scss and .sass files
npm add -D sass-embedded # or sass

# For .less files
npm add -D less

# For .styl and .stylus files
npm add -D stylus
```

После установки вы можете импортировать эти типы файлов непосредственно в основной файл CSS (например, `main.css`):

```css
@import './my-styles.sass';
```

## Изменение местоположения активов

Мы рекомендуем держать эти точки входа в `/source/_core/_assets`. Структура по умолчанию выглядит следующим образом:

<div class="files">
    <div class="folder folder--open">source
 <div class="folder folder--open focus">_core
        <div class="folder folder--open">_assets
            <div class="folder folder--open">js
                <div class="file">main.js</div>
            </div>
            <div class="folder folder--open">css
                <div class="file">main.css</div>
            </div>
        </div>
        <div class="folder folder--open">_layouts
            <div class="file">master.blade.php</div>
        </div>
</div>
        <div class="folder folder--open">assets
            <div class="folder folder--open">images
                <div class="file">jigsaw.png</div>
            </div>
        </div>
        <div class="file">index.blade.php</div>
    </div>
    <div class="folder">vendor</div>
    <div class="ellipsis">...</div>
</div>

Тем не менее, вы можете хранить свои активы в другом месте и соответствующим образом обновлять конфигурацию и макет Vite. Для
, если вы решите хранить их в каталоге с именем `foobar` в корне вашего проекта, вы обновите свой Blade
Макет такой:
```html
@viteRefresh()
<link rel="stylesheet" href="{{ vite('foobar/css/main.css') }}">
<script defer type="module" src="{{ vite('foobar/js/main.js') }}"></script>
```
И ваш `jigsaw` Конфигурация в `vite.config.js` Выглядел бы так:
```js
jigsaw({
input: ['foobar/js/main.js', 'foobar/css/main.css'],
refresh: true,
}),
```
Имейте в виду, что если вы разместите этот каталог в `source`, его название **должен** начните с символа подчеркивания (например, `_foobar`) на
запретить Jigsaw напрямую копировать его содержимое в выходные данные сборки.

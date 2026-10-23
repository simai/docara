---
description: Surface — оформленная полоса вокруг Markdown: внешняя ширина content или full, внутренний контейнер или полная ширина, фон и overlay.
---

# Оформленная полоса

`Surface` создаёт оформленную полосу вокруг обычного Markdown. Внешняя ширина
может оставаться в потоке (`content`) или достигать границы активного region
(`full`); внутренний контент независимо остаётся в `.container` либо занимает
полную ширину.

Surface выбирают для общей полосы содержимого: нескольких абзацев, сетки с
карточками или допущенного Smart-компонента проекта. Для первого экрана с H1, описанием и
действиями используйте [Hero](/ru/components/hero/). Hero, Showcase и Promo уже
оформлены так же, как Surface, и **не требуют** внешней обёртки `:::surface`.
Такое двойное оборачивание запрещено правилами контейнера.

:::surface {width=full content_width=container padding=lg tone=muted}
## Surface в границе main

Эта полоса достигает краёв только основной области `main`: в документации она
не перекрывает боковое меню и правую колонку, а внутренний текст остаётся в
контейнере.
:::

:::example {label="Полоса с выровненным контентом"}
```markdown
:::surface {width=full content_width=container padding=lg tone=muted}
## Один контентный ритм

Текст внутри полной полосы выровнен с соседним контентом страницы.
:::
```
:::

## Безопасный декоративный фон

Фон берётся только из локального `/assets/` проекта, скрыт от accessibility tree
и не получает фокус или события указателя. Позиция, overlay, отступ и tone —
ограниченные значения, а не произвольные CSS-классы.

:::example {label="Локальный декоративный фон"}
```markdown
:::surface {width=full content_width=container background_image=/assets/docara-screen.png background_fit=cover background_x=right background_y=center overlay=dark overlay_strength=medium padding=xl tone=contrast}
## Документация как продукт

Читаемый контент остаётся поверх декоративного слоя.
:::
```
:::

## Контейнерный контракт

Surface принимает обычный Markdown и только зарегистрированные content-embeddable
компоненты. Вложенный Surface, Hero, Promo, Showcase, shell-компоненты,
неизвестные children и небезопасные пути отклоняются до публикации. Допустимые
children, slot `content`, порядок, количество и глубина публикуются в Design
Atlas из реального registry descriptor. `max_depth=3` считается относительно
Surface как уровня 1; вложенный Grid отдельно считает себя уровнем 1, поэтому
каноническая цепочка Surface → Grid → Card допустима без глобального depth.

Проектный Smart тоже проходит тот же Gateway, если его собственный manifest
явно объявляет capability `content.embeddable`:

:::::surface {width=content content_width=container padding=md tone=default}
:::project.product-configurator
{"title":"Локальный конфигуратор","base_price":2500,"team_price":4500,"business_price":8000,"currency":"₽"}
:::
:::::

:::example {label="Project Smart внутри Surface"}
``````markdown
:::::surface {width=content content_width=container padding=md tone=default}
:::project.product-configurator
{"title":"Локальный конфигуратор","base_price":2500,"team_price":4500,"business_price":8000,"currency":"₽"}
:::
:::::
``````
:::

Параметры: `width=content|full`, `content_width=container|full`,
`background_fit=cover|contain|auto`, `background_x=left|center|right`,
`background_y=top|center|bottom`, `overlay=none|light|dark`,
`overlay_strength=soft|medium|strong`, `padding=none|sm|md|lg|xl` и
`tone=default|muted|accent|contrast`.

## Доступность и адаптивность

- `full` достигает только границы своей области макета: на лендинге — края
  страницы, в документации — края `main`, не бокового меню и не правой
  колонки;
- внутренний `container` сохраняет одну линию выравнивания с соседним текстом:
  при `content_width=container` параметр `padding` задаёт только отступы
  сверху и снизу, а отступ слева и справа остаётся отступом `container`, тем же,
  что у основного текста страницы. При `content_width=full` `padding` задаёт
  отступы со всех сторон;
- декоративное изображение выводится один раз с `alt=""` и
  `aria-hidden="true"`, overlay не получает фокус и не перехватывает указатель;
- `cover`, `contain` и `auto` не создают отдельный мобильный источник или
  произвольный breakpoint; responsive geometry принадлежит общей presentation;
- авторский текст и интерактивные children остаются поверх overlay в обычном
  DOM-порядке и доступны с клавиатуры.

## Fail-closed граница

| Ошибка автора | Результат |
| --- | --- |
| Surface внутри Surface или вокруг Hero/Showcase/Promo | вложение отклоняется до render |
| remote, `data:`, protocol-relative, traversal, symlink или hardlink media | локальная asset policy отклоняет путь |
| `background_x/y/fit` без `background_image` | `MARKDOWN_SURFACE_BACKGROUND_REQUIRED` |
| `overlay_strength` без overlay | `MARKDOWN_SURFACE_OVERLAY_REQUIRED` |
| неизвестный prop, CSS class, style, callback или template path | schema/admission error |

Глобальной настройки Surface нет: каждое значение выше принадлежит конкретной
типизированной директиве и попадает в Atlas из допущенного описания.

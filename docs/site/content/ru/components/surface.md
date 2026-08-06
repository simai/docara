# Surface

`Surface` создаёт оформленную полосу вокруг обычного Markdown. Внешняя ширина
может оставаться в потоке (`content`) или достигать границы активного region
(`full`); внутренний контент независимо остаётся в `.container` либо занимает
полную ширину.

:::surface {width=full content_width=container padding=lg tone=muted}
## Surface в границе main

Эта полоса достигает краёв только текущего `main` region: в документации она
не перекрывает sidebar и outline, а внутренний текст остаётся в контейнере.
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

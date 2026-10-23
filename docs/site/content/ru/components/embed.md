---
description: "Как встроить видео, карту или другой внешний материал: загрузка только по действию читателя и адаптивные пропорции."
---

# Встраиваемый материал

Блок показывает видео, карту или другой доверенный HTTPS-материал в
адаптивной области. По умолчанию внешний сайт загружается только после явного
действия читателя.

## Общий пример

:::example {label="Общий пример"}
```markdown
:::embed {provider=video ratio=16/9 title="Внешняя демонстрация"}
[Открыть встроенный материал](https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ)
:::
```
:::

## Параметры

| Параметр | Значения | Назначение |
| --- | --- | --- |
| `provider` | `generic`, `video`, `map`, `external` | Тип внешнего материала |
| `ratio` | `1/1`, `4/3`, `16/9`, `21/9` | Пропорции адаптивной области |
| `title` | текст | Доступное название iframe |
| `consent` | `required`, `none` | Требовать действие перед загрузкой; по умолчанию `required` |
| `id` | короткий идентификатор | Необязательный стабильный якорь блока |

## Допустимые адреса

Адрес проверяется при сборке; неподходящий останавливает её с ошибкой
`MARKDOWN_EMBED_ORIGIN_FORBIDDEN`.

| `provider` | Какой адрес принимается |
| --- | --- |
| `generic` | Только адрес этого же сайта: путь от корня, например `/demo/widget/` (не `//host/...`) |
| `video` | Только `https`: `youtube.com`, `www.youtube.com`, `youtube-nocookie.com`, `www.youtube-nocookie.com`, `player.vimeo.com` |
| `map` | Только `https`: `openstreetmap.org`, `www.openstreetmap.org` |
| `external` | Любой `https`-адрес, но только с `consent=required` |

`consent=none` допустим только для адреса этого же сайта, то есть на практике
для `generic`. Видео, карта и внешний виджет всегда загружаются после действия
читателя.

## Наглядные варианты

:::embed {provider=video ratio=16/9 title="Внешняя демонстрация"}
[Открыть встроенный материал](https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ)
:::

:::embed {provider=map ratio=4/3 title="Интерактивная карта"}
[Открыть карту](https://www.openstreetmap.org/export/embed.html)
:::

:::embed {provider=external ratio=1/1 title="Внешний виджет"}
[Открыть внешний виджет](https://example.com/)
:::

## Вызов

```markdown
:::embed {provider=map ratio=4/3 title="Интерактивная карта"}
[Открыть карту](https://www.openstreetmap.org/export/embed.html)
:::
```

Используйте только доверенные адреса и всегда задавайте осмысленный `title`.
`consent=none` загружает материал сразу при открытии страницы и поэтому
разрешён только для адреса этого же сайта.

## См. также

- [Иллюстрация](/ru/components/figure/)
- [Изолированный HTML](/ru/components/html/)
- [Безопасность](/ru/reference/security-and-errors/)

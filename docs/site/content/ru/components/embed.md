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

Используйте только доверенные HTTPS-адреса и всегда задавайте осмысленный
`title`. Отключайте подтверждение через `consent=none` лишь для источника,
который допустимо загружать сразу при открытии страницы.

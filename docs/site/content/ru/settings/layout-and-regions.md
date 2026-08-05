# Layout и regions

`layout.key` выбирает admitted Layout descriptor. Container max, scrollbar, content gap и region recipes проверяются schema + DesignRegistry; config не указывает template/file/class.

```json
{"layout":{"key":"docara.docs","container":{"max":7},"scrollbar":{"preset":"overlay"},"content":{"gap":0}}}
```

Regions принадлежат layout descriptor, а compatible Sections/slots/Blocks видны в [Design Atlas](/ru/design/composition/). Unknown region, incompatible section/block, invalid View Tree utility или unsafe path отклоняется до render.


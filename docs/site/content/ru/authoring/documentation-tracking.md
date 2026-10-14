---
title: "Актуальность документации по исходному коду"
description: "Необязательный контроль страниц и примеров относительно публичного контракта проекта."
profile: reference
---

# Актуальность документации по исходному коду

Docara может показывать, какие справочные страницы и примеры соответствуют
текущему публичному API проекта. Механизм необязателен: без
`documentation_tracking` сайт работает как прежде.

```json
{
  "documentation_tracking": {
    "enabled": true,
    "source_locale": "ru",
    "mode": "report",
    "lock_file": "documentation.lock.json",
    "sources": [
      {
        "id": "simai-framework",
        "provider": "simai_framework",
        "framework_lock": "simai-framework.lock.json"
      }
    ]
  }
}
```

## Источники

Встроенный provider `simai_framework` получает Core, utilities, обычные и Smart
Components из точного hash-bound контракта закреплённого Framework runtime.
Старый runtime без такого указателя читается через совместимый `rule.json`
adapter и получает предупреждение об ограниченной точности. Для другого проекта используйте
`provider: "contract_json"` и файл схемы `docara.documentation_source.v1`.
В контракт входят только стабильные ключи и публичное поведение; внутреннее
форматирование исходников не влияет на отпечаток.

## Статусы

- `current` — исходник, Markdown и обязательные примеры приняты;
- `new` и `changed` — сущность появилась или её публичный контракт изменился;
- `missing` и `missing_example` — исчезла страница или обязательный пример;
- `unverified` — принятый Markdown или пример отредактирован;
- `orphan` — исходная сущность исчезла;
- `excluded` — документация намеренно не требуется и указана причина.

Технические конфликты ключей и неоднозначные связи остаются diagnostics. Они не
маскируются редакционным статусом.

## Безопасная работа агента

Агент сначала читает `documentation status` и точный `inspect source`, затем
редактирует Markdown и общие примеры, запускает `validate project`, build и
`verify-static`. Принятие выполняется отдельным `documentation accept
--dry-run`; любое изменение входов делает подготовленный план недействительным.
Docara не вызывает ИИ, сеть и не редактирует документацию самостоятельно.

# Контроль документации по исходному коду

## Решение

Docara предоставляет необязательный универсальный status engine для
документации публичного API. Исходный проект предоставляет нейтральный контракт
`docara.documentation_source.v1`; Docara сопоставляет его сущности со страницами
и общими примерами базовой локали.

Новая база знаний не создаётся. Source contract является производной проекцией
авторитетных реестров и исходников проекта. `documentation.lock.json` хранит
только принятые связи и отпечатки, а `.docara/documentation-status.json` —
удаляемый производный отчёт.

## Границы ответственности

- source provider владеет стабильными ключами, публичными классами,
  параметрами, вариантами, состояниями, зависимостями и provenance;
- Docara владеет schemas, matching, status, inspect/validate, scaffold и
  hash-bound accept;
- Markdown и `examples/` остаются пользовательскими исходниками;
- перевод страниц контролируется отдельным `translation_tracking`;
- build, status и validate не вызывают сеть или ИИ и ничего не принимают
  автоматически.

## Статусы

`current`, `new`, `changed`, `missing`, `missing_example`, `unverified`,
`orphan` и `excluded` являются редакционными состояниями. Дубли stable key,
неоднозначные route и невалидная схема являются diagnostics.

Отпечаток сущности считается только по `public_contract`. Форматирование,
комментарии и внутренний рефакторинг source-проекта не меняют состояние.
Utilities группируются в семейства, поэтому responsive и state-варианты одной
утилиты не создают сотни независимых страниц.

## Принятие

План acceptance связан SHA-256 с `docara.json`, предыдущим lock, source
contract, Markdown и указанными Example ID. Apply повторно проверяет все входы
и атомарно заменяет только lock-файл. Уровни проверки — `ai_verified` и
`human_reviewed`; повышение уровня не меняет исходники.

## Scaffold

Source-aware `scaffold page` создаёт только отсутствующий Markdown. Он может
создать `index.html`, `index.css` и `index.js` общего примера, только если эти
файлы полностью предоставлены source-owned проверенным шаблоном. Без шаблона
Docara не сочиняет API или демонстрацию.

## Framework provider

SIMAI Framework генерирует нейтральный контракт из существующего Framework
Contract Registry. Точный release lock публикует SHA-256 и путь файла в
существующем `framework_registry.documentation_source`; commit и tree не
дублируются. Docara сначала использует этот hash-bound контракт, а для
закреплённых старых runtime без указателя — read-only адаптер `rule.json` с
явной отметкой ограниченной точности. Семантика радиусов
также входит в public contract: `--sf-radius--ui` принадлежит компактным
контролам, `--sf-radius-default` — крупным поверхностям; `square` и `rounded`
являются явными overrides.

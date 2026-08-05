# Диагностика и provenance

Каждая ошибка application service имеет stable code/severity, safe source path/pointer, line/column для file-backed данных, owner, provenance и actionable suggestion. Human, `--json` и optional MCP — проекции одного `OperationResult`.

```bash
docara doctor --json
docara atlas --json
docara inspect smart ui.dropdown --json
docara inspect page /ru/components/framework/ --json
docara schema smart --json
```

Build receipt связывает route с source/config chain, engine revision, Framework tuple, manifests/templates/assets hashes, Document IR hash и PageBuilder provenance без private absolute paths. Schema-derived tables на этих страницах содержат точный `resources/schemas/<file>#<pointer>` для каждого поля.

При file-backed ошибке сначала исправьте названный path/pointer/location, повторите doctor/validate, затем preview/build. Не редактируйте generated receipt как способ «исправить» provenance.


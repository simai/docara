# Сборка и публикация

Обычному автору не нужен frontend toolchain. PHP-команда читает Markdown и
JSON, проверяет Framework lock и создаёт готовый статический каталог.

## Безопасный путь

:::steps
1. Соберите `production` output.
2. Проверьте его командой `verify-static`.
3. Откройте тот же output по HTTP через `serve --no-build`.
4. Перед публикацией перенесите проверенный каталог в staging.
5. Сравните digest, выполните smoke и только затем переключите traffic.
6. При ошибке верните сохранённый предыдущий каталог.

:::

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Preview блокирует терминал до `Ctrl+C`. Не используйте `file://`.

## Руководства

- [Сборка сайта](/ru/build/php-only/) — команды, готовый каталог и воспроизводимость
- [Локальный просмотр](/ru/build/local-preview/)
- [Проверка сборки](/ru/build/verify/)
- [Размещение на хостинге](/ru/build/hosting/)
- [Публикация и откат](/ru/build/publish/)
- [Сборка для портала](/ru/build/portal/)
- [Обновление Docara](/ru/build/update/) — в том числе переход с 2.x на 3.0

Vite нужен только maintainer-разработчику исходных ассетов темы. Он не входит в
portable author/build path.

Успешный local verify не является автоматическим разрешением публичного
release или production deployment.

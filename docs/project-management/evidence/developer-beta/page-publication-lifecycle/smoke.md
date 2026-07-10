# Browser smoke

Target: disposable `http://127.0.0.1:8766`, installed through the Developer
Beta installer with isolated SQLite and the bounded current Docara tree overlaid
for pre-commit acceptance.

Observed:

1. draft edit showed current status, Preview and Publish;
2. protected preview showed saved title/body and no live link;
3. anonymous draft URL returned 404;
4. Publish showed success, Published, View live and Unpublish;
5. anonymous public URL rendered the page;
6. editing live content kept it published and updated the public result;
7. Unpublish returned the page to Draft and removed public availability;
8. persisted audit sequence contained exactly four expected events.

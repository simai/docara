(function () {
    'use strict';

    var trigger = document.querySelector('[data-docara-search-trigger]');
    var dialog = document.querySelector('[data-docara-search-dialog]');
    if (!trigger || !dialog) return;

    var input = dialog.querySelector('[data-docara-search-input]');
    var status = dialog.querySelector('[data-docara-search-status]');
    var results = dialog.querySelector('[data-docara-search-results]');
    var closeButton = dialog.querySelector('[data-docara-search-close]');
    var shortcut = trigger.querySelector('[data-docara-search-shortcut]');
    var locale = document.documentElement.lang || 'en';
    var copy = locale.toLowerCase().startsWith('ru') ? {
        idle: 'Введите минимум 2 символа',
        loading: 'Загрузка поиска…',
        found: function (count) { return 'Найдено: ' + count; },
        empty: 'Ничего не найдено',
        error: 'Поиск временно недоступен. Обновите страницу и попробуйте снова.'
    } : {
        idle: 'Enter at least 2 characters',
        loading: 'Loading search…',
        found: function (count) { return 'Found: ' + count; },
        empty: 'No results found',
        error: 'Search is temporarily unavailable. Reload the page and try again.'
    };
    var indexPromise = null;
    var preparedDocuments = [];
    var visibleResults = [];
    var deploymentBase = null;
    var expectedContentHash = null;

    if (shortcut && !/Mac|iPhone|iPad/.test(navigator.platform || '')) {
        shortcut.textContent = 'Ctrl K';
    }

    function normalize(value) {
        return String(value || '')
            .normalize('NFKC')
            .toLocaleLowerCase(locale)
            .replace(/ё/g, 'е')
            .normalize('NFD')
            .replace(/\p{M}/gu, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function tokens(value) {
        return normalize(value).match(/[\p{L}\p{N}_-]+/gu) || [];
    }

    function setStatus(message, state) {
        status.textContent = message;
        status.dataset.state = state;
    }

    function clearResults() {
        results.replaceChildren();
        visibleResults = [];
    }

    function isPlainRecord(value) {
        return value !== null && typeof value === 'object' && !Array.isArray(value);
    }

    function hasExactKeys(value, expected) {
        var keys = Object.keys(value).sort();
        var wanted = expected.slice().sort();
        return keys.length === wanted.length && keys.every(function (key, index) {
            return key === wanted[index];
        });
    }

    function isSafeLocalPageUrl(value) {
        if (typeof value !== 'string'
            || !/^\/(?:(?!\.{1,2}\/)[A-Za-z0-9._~-]+\/)*$/u.test(value)
            || typeof deploymentBase !== 'string'
            || !value.startsWith(deploymentBase)) return false;
        try {
            var parsed = new URL(value, window.location.origin);
            return parsed.origin === window.location.origin
                && parsed.pathname === value
                && !parsed.search
                && !parsed.hash;
        } catch (error) {
            return false;
        }
    }

    function validateDocument(document) {
        var headingsValid = Array.isArray(document.headings) && document.headings.every(function (heading) {
            return isPlainRecord(heading)
                && hasExactKeys(heading, ['level', 'text'])
                && Number.isInteger(heading.level)
                && heading.level >= 1
                && heading.level <= 6
                && typeof heading.text === 'string'
                && heading.text.length > 0;
        });
        var trailValid = Array.isArray(document.trail) && document.trail.every(function (part) {
            return typeof part === 'string' && part.length > 0;
        });
        return isPlainRecord(document)
            && hasExactKeys(document, ['id', 'url', 'locale', 'title', 'description', 'trail', 'headings', 'text'])
            && /^[a-f0-9]{64}$/.test(document.id || '')
            && isSafeLocalPageUrl(document.url)
            && /^[a-z]{2}(?:-[A-Z]{2})?$/.test(document.locale || '')
            && typeof document.title === 'string'
            && document.title.length > 0
            && typeof document.description === 'string'
            && trailValid
            && headingsValid
            && typeof document.text === 'string';
    }

    function validateIndex(payload) {
        if (!isPlainRecord(payload)
            || !hasExactKeys(payload, ['schema', 'version', 'algorithm', 'content_sha256', 'documents'])
            || payload.schema !== 'docara.search_index.v1'
            || payload.version !== 1
            || payload.algorithm !== 'docara-prefix-v1'
            || !/^[a-f0-9]{64}$/.test(payload.content_sha256 || '')
            || !Array.isArray(payload.documents)
            || !payload.documents.length
            || payload.content_sha256 !== expectedContentHash
            || !payload.documents.every(validateDocument)) {
            throw new Error('SEARCH_INDEX_INVALID');
        }
        var ids = new Set();
        var localeUrls = new Set();
        payload.documents.forEach(function (document) {
            var localeUrl = document.locale + '\0' + document.url;
            if (ids.has(document.id) || localeUrls.has(localeUrl)) throw new Error('SEARCH_INDEX_INVALID');
            ids.add(document.id);
            localeUrls.add(localeUrl);
        });
        var localized = payload.documents.filter(function (document) {
            return document.locale === locale;
        });
        if (!localized.length) throw new Error('SEARCH_INDEX_LOCALE_EMPTY');
        return localized;
    }

    function prepare(document) {
        var headings = document.headings.map(function (heading) { return heading.text || ''; }).join(' ');
        var fields = {
            title: normalize(document.title),
            headings: normalize(headings),
            description: normalize(document.description || ''),
            body: normalize(document.text)
        };
        fields.all = [fields.title, fields.headings, fields.description, fields.body].join(' ');
        return { document: document, fields: fields };
    }

    function loadIndex() {
        if (indexPromise) return indexPromise;
        var indexUrl = new URL(dialog.dataset.docaraSearchIndex, window.location.href);
        var indexSuffix = '_docara/search-index.json';
        if (indexUrl.origin !== window.location.origin
            || !indexUrl.pathname.endsWith('/' + indexSuffix)
            || indexUrl.hash
            || Array.from(indexUrl.searchParams.keys()).length !== 1
            || !/^[a-f0-9]{64}$/.test(indexUrl.searchParams.get('docara_v') || '')) {
            indexPromise = Promise.reject(new Error('SEARCH_INDEX_ORIGIN_INVALID'));
            return indexPromise;
        }
        deploymentBase = indexUrl.pathname.slice(0, -indexSuffix.length);
        expectedContentHash = indexUrl.searchParams.get('docara_v');
        setStatus(copy.loading, 'loading');
        indexPromise = fetch(indexUrl.toString(), {
            mode: 'same-origin',
            redirect: 'error',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        }).then(function (response) {
            if (!response.ok) throw new Error('SEARCH_INDEX_FETCH_FAILED');
            return response.json();
        }).then(validateIndex).then(function (documents) {
            preparedDocuments = documents.map(prepare);
            setStatus(copy.idle, 'idle');
            return preparedDocuments;
        }).catch(function (error) {
            setStatus(copy.error, 'error');
            throw error;
        });
        return indexPromise;
    }

    function score(prepared, phrase, queryTokens) {
        var fields = prepared.fields;
        if (!queryTokens.every(function (token) { return fields.all.includes(token); })) return -1;
        var total = 0;
        if (fields.title === phrase) total += 1200;
        else if (fields.title.includes(phrase)) total += 600;
        if (fields.headings.includes(phrase)) total += 320;
        if (fields.description.includes(phrase)) total += 180;
        if (fields.body.includes(phrase)) total += 60;
        queryTokens.forEach(function (token) {
            if (fields.title.split(' ').some(function (word) { return word.startsWith(token); })) total += 140;
            if (fields.headings.split(' ').some(function (word) { return word.startsWith(token); })) total += 80;
            if (fields.description.includes(token)) total += 35;
            if (fields.body.includes(token)) total += 10;
        });
        return total;
    }

    function snippet(document) {
        var description = String(document.description || '').replace(/\s+/g, ' ').trim();
        var text = String(description || document.text || '').replace(/\s+/g, ' ').trim();
        if (!description && document.title) {
            var escapedTitle = String(document.title).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            text = text.replace(new RegExp('^(?:\\s*' + escapedTitle + '[\\s:—–-]*)+', 'iu'), '').trim();
        }
        if (text.length <= 180) return text;
        return text.slice(0, 177).trimEnd() + '…';
    }

    function createResult(item) {
        var record = item.document;
        var listItem = window.document.createElement('li');
        var link = window.document.createElement('a');
        var title = window.document.createElement('span');
        var context = window.document.createElement('span');
        var summary = window.document.createElement('span');
        listItem.className = 'docara-search-result-item';
        link.className = 'docara-search-result bg-surface-container border border-outline-variant radius-2 p-2 flex flex-col gap-1 decoration-none color-on-surface';
        link.href = record.url;
        link.dataset.docaraSearchResult = 'true';
        title.className = 'weight-7';
        title.textContent = record.title;
        link.append(title);
        if (record.trail.length) {
            context.className = 'docara-search-result-context color-on-surface-variant';
            context.textContent = record.trail.join(' / ');
            link.append(context);
        }
        var excerpt = snippet(record);
        if (excerpt) {
            summary.className = 'docara-search-result-summary color-on-surface-variant';
            summary.textContent = excerpt;
            link.append(summary);
        }
        link.setAttribute('aria-label', [record.title, record.trail.join(' / '), excerpt]
            .filter(Boolean).join('. '));
        listItem.append(link);
        return { listItem: listItem, link: link };
    }

    function render() {
        var phrase = normalize(input.value);
        clearResults();
        if (Array.from(phrase).length < 2) {
            setStatus(copy.idle, 'idle');
            return;
        }
        var queryTokens = tokens(phrase);
        if (!queryTokens.length) {
            setStatus(copy.idle, 'idle');
            return;
        }
        var ranked = preparedDocuments.map(function (prepared) {
            return { prepared: prepared, score: score(prepared, phrase, queryTokens) };
        }).filter(function (item) {
            return item.score >= 0;
        }).sort(function (left, right) {
            return right.score - left.score || left.prepared.document.url.localeCompare(right.prepared.document.url);
        }).slice(0, 20);
        if (!ranked.length) {
            setStatus(copy.empty, 'empty');
            return;
        }
        ranked.forEach(function (item) {
            var rendered = createResult(item.prepared);
            results.append(rendered.listItem);
            visibleResults.push(rendered.link);
        });
        setStatus(copy.found(ranked.length), 'results');
    }

    function openSearch() {
        if (!dialog.open) {
            if (typeof dialog.showModal === 'function') dialog.showModal();
            else dialog.setAttribute('open', '');
        }
        window.requestAnimationFrame(function () { input.focus(); });
        loadIndex().then(render).catch(function () {});
    }

    function closeSearch() {
        if (typeof dialog.close === 'function' && dialog.open) dialog.close();
        else {
            dialog.removeAttribute('open');
            trigger.focus();
        }
    }

    trigger.addEventListener('click', openSearch);
    closeButton.addEventListener('click', closeSearch);
    input.addEventListener('input', function () {
        if (preparedDocuments.length) render();
        else loadIndex().then(render).catch(function () {});
    });
    input.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowDown' && visibleResults[0]) {
            event.preventDefault();
            visibleResults[0].focus();
        }
        if (event.key === 'Enter' && visibleResults[0]) {
            event.preventDefault();
            visibleResults[0].click();
        }
    });
    results.addEventListener('keydown', function (event) {
        var index = visibleResults.indexOf(event.target);
        if (index < 0 || !['ArrowDown', 'ArrowUp'].includes(event.key)) return;
        event.preventDefault();
        if (event.key === 'ArrowUp' && index === 0) input.focus();
        else visibleResults[Math.max(0, Math.min(visibleResults.length - 1, index + (event.key === 'ArrowDown' ? 1 : -1)))].focus();
    });
    dialog.addEventListener('click', function (event) {
        if (event.target === dialog) closeSearch();
    });
    dialog.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            closeSearch();
            return;
        }
        if (event.key !== 'Tab') return;
        var order = [input].concat(visibleResults, [closeButton]);
        var current = order.indexOf(document.activeElement);
        var next = current < 0
            ? 0
            : (current + (event.shiftKey ? -1 : 1) + order.length) % order.length;
        event.preventDefault();
        order[next].focus();
    }, true);
    dialog.addEventListener('cancel', function (event) {
        event.preventDefault();
        closeSearch();
    });
    dialog.addEventListener('close', function () { trigger.focus(); });
    document.addEventListener('keydown', function (event) {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            openSearch();
        }
    });
}());

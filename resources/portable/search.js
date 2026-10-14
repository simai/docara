(function () {
    'use strict';

    var trigger = document.querySelector('[data-docara-search-trigger]');
    var dialog = document.querySelector('[data-docara-search-dialog]');
    if (!trigger || !dialog) return;

    var input = null;
    var status = null;
    var results = null;
    var closeButton = null;
    var shortcut = trigger.querySelector('[data-docara-search-shortcut]');
    var locale = document.documentElement.lang || 'und';
    var copyNode = document.getElementById('docara-runtime-copy');
    var messages = {};
    try { messages = JSON.parse(copyNode ? copyNode.textContent : '{}'); } catch (error) { messages = {}; }
    function message(id, parameters) {
        var value = typeof messages[id] === 'string' ? messages[id] : id;
        Object.keys(parameters || {}).forEach(function (name) {
            value = value.split('{' + name + '}').join(String(parameters[name]));
        });
        return value;
    }
    var copy = {
        idle: message('search.idle'),
        loading: message('search.loading'),
        found: function (count) { return message('search.found', {count: count}); },
        empty: message('search.empty'),
        error: message('search.error')
    };
    var indexPromise = null;
    var preparedDocuments = [];
    var visibleResults = [];
    var deploymentBase = null;
    var expectedContentHash = null;
    var initialized = false;

    if (shortcut && !/Mac|iPhone|iPad/.test(navigator.platform || '')) {
        shortcut.textContent = 'Ctrl K';
    }

    function normalize(value) {
        return String(value || '')
            .normalize('NFKC')
            .toLocaleLowerCase(locale)
            .normalize('NFD')
            .replace(/\p{M}/gu, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function tokens(value) {
        return normalize(value).match(/[\p{L}\p{N}_-]+/gu) || [];
    }

    function highlightTokens(value) {
        return String(value || '').normalize('NFKC').match(/[\p{L}\p{N}_-]+/gu) || [];
    }

    function escapeExpression(value) {
        return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function appendHighlighted(target, value, terms) {
        var text = String(value || '');
        var uniqueTerms = Array.from(new Set((terms || []).filter(Boolean)))
            .sort(function (left, right) { return right.length - left.length; });
        if (!uniqueTerms.length) {
            target.textContent = text;
            return;
        }
        var expression = new RegExp(uniqueTerms.map(escapeExpression).join('|'), 'giu');
        var cursor = 0;
        Array.from(text.matchAll(expression)).forEach(function (match) {
            var offset = match.index || 0;
            if (offset > cursor) target.append(document.createTextNode(text.slice(cursor, offset)));
            var mark = document.createElement('mark');
            mark.className = 'docara-search-mark';
            mark.textContent = match[0];
            target.append(mark);
            cursor = offset + match[0].length;
        });
        if (cursor < text.length) target.append(document.createTextNode(text.slice(cursor)));
    }

    function setStatus(value, state) {
        status.textContent = value;
        status.dataset.state = state;
        results.dataset.state = state;
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

    function validateDocument(record) {
        var headingsValid = Array.isArray(record.headings) && record.headings.every(function (heading) {
            return isPlainRecord(heading)
                && hasExactKeys(heading, ['level', 'text'])
                && Number.isInteger(heading.level)
                && heading.level >= 1
                && heading.level <= 6
                && typeof heading.text === 'string'
                && heading.text.length > 0;
        });
        var trailValid = Array.isArray(record.trail) && record.trail.every(function (part) {
            return typeof part === 'string' && part.length > 0;
        });
        return isPlainRecord(record)
            && hasExactKeys(record, ['id', 'url', 'locale', 'title', 'description', 'trail', 'headings', 'text'])
            && /^[a-f0-9]{64}$/.test(record.id || '')
            && isSafeLocalPageUrl(record.url)
            && /^[a-z]{2}(?:-[A-Z]{2})?$/.test(record.locale || '')
            && typeof record.title === 'string'
            && record.title.length > 0
            && typeof record.description === 'string'
            && trailValid
            && headingsValid
            && typeof record.text === 'string';
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
        payload.documents.forEach(function (record) {
            var localeUrl = record.locale + '\0' + record.url;
            if (ids.has(record.id) || localeUrls.has(localeUrl)) throw new Error('SEARCH_INDEX_INVALID');
            ids.add(record.id);
            localeUrls.add(localeUrl);
        });
        var localized = payload.documents.filter(function (record) {
            return record.locale === locale;
        });
        if (!localized.length) throw new Error('SEARCH_INDEX_LOCALE_EMPTY');
        return localized;
    }

    function prepare(record) {
        var headings = record.headings.map(function (heading) { return heading.text || ''; }).join(' ');
        var fields = {
            title: normalize(record.title),
            headings: normalize(headings),
            description: normalize(record.description || ''),
            body: normalize(record.text)
        };
        fields.all = [fields.title, fields.headings, fields.description, fields.body].join(' ');
        return { document: record, fields: fields };
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
            setStatus(copy.error, 'error');
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

    function compactText(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function matchPositions(value, terms) {
        var lowered = normalize(value);
        return (terms || []).map(function (term) {
            return lowered.indexOf(normalize(term));
        }).filter(function (position) {
            return position >= 0;
        });
    }

    function contextualSnippet(record, terms) {
        var candidates = [
            compactText(record.description),
            compactText(record.headings.map(function (heading) { return heading.text || ''; }).join(' ')),
            compactText(record.text)
        ].filter(Boolean).map(function (text, priority) {
            var positions = matchPositions(text, terms);
            return {
                text: text,
                positions: positions,
                matchedTerms: positions.length,
                firstMatch: positions.length ? Math.min.apply(null, positions) : -1,
                priority: priority
            };
        });
        var matched = candidates.filter(function (candidate) {
            return candidate.matchedTerms > 0;
        }).sort(function (left, right) {
            return right.matchedTerms - left.matchedTerms
                || left.priority - right.priority
                || left.firstMatch - right.firstMatch;
        })[0];
        var candidate = matched || candidates[0];
        if (!candidate) return '';

        var text = candidate.text;
        if (!matched && !record.description && record.title) {
            var escapedTitle = escapeExpression(record.title);
            text = text.replace(new RegExp('^(?:\\s*' + escapedTitle + '[\\s:—–-]*)+', 'iu'), '').trim();
        }
        if (text.length <= 180) return text;
        if (!matched) return text.slice(0, 177).trimEnd() + '…';

        var start = Math.max(0, matched.firstMatch - 62);
        var end = Math.min(text.length, start + 180);
        if (end === text.length) start = Math.max(0, end - 180);
        if (start > 0) {
            var nextBoundary = text.indexOf(' ', start);
            if (nextBoundary >= 0 && nextBoundary < matched.firstMatch) start = nextBoundary + 1;
        }
        if (end < text.length) {
            var previousBoundary = text.lastIndexOf(' ', end);
            if (previousBoundary > matched.firstMatch) end = previousBoundary;
        }
        return (start > 0 ? '…' : '') + text.slice(start, end).trim() + (end < text.length ? '…' : '');
    }

    function createResult(item, terms) {
        var record = item.document;
        var listItem = document.createElement('li');
        var link = document.createElement('a');
        var icon = document.createElement('sf-icon');
        var content = document.createElement('span');
        var title = document.createElement('span');
        var context = document.createElement('span');
        var summary = document.createElement('span');
        listItem.className = 'docara-search-result-item min-w-0';
        link.className = 'docara-search-result min-w-0 p-2 flex items-cross-start gap-1 decoration-none color-on-surface transition';
        link.href = record.url;
        link.dataset.docaraSearchResult = 'true';
        icon.setAttribute('icon', 'description');
        icon.setAttribute('aria-hidden', 'true');
        icon.className = 'docara-search-result-icon color-on-surface-variant flex-none';
        content.className = 'flex flex-col gap-1/4 min-w-0';
        title.className = 'docara-search-result-title weight-7';
        appendHighlighted(title, record.title, terms);
        if (record.trail.length) {
            context.className = 'docara-search-result-context color-link';
            appendHighlighted(context, record.trail.join(' › '), terms);
            content.append(context);
        }
        content.append(title);
        var excerpt = contextualSnippet(record, terms);
        if (excerpt) {
            summary.className = 'docara-search-result-summary color-on-surface-variant';
            summary.dataset.docaraSearchResultSummary = 'true';
            appendHighlighted(summary, excerpt, terms);
            content.append(summary);
        }
        link.append(icon, content);
        link.setAttribute('aria-label', [record.title, record.trail.join(' / '), excerpt]
            .filter(Boolean).join('. '));
        listItem.append(link);
        return { listItem: listItem, link: link };
    }

    function render() {
        var phrase = normalize(input.value);
        var terms = highlightTokens(input.value);
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
            var rendered = createResult(item.prepared, terms);
            results.append(rendered.listItem);
            visibleResults.push(rendered.link);
        });
        setStatus(copy.found(ranked.length), 'results');
    }

    function openSearch() {
        document.dispatchEvent(new CustomEvent('docara:open-transient', {
            detail: { id: dialog.id }
        }));
        dialog.open();
        trigger.setAttribute('aria-expanded', 'true');
        window.requestAnimationFrame(function () { input.focus(); });
        loadIndex().then(render).catch(function () {});
    }

    function closeSearch() {
        dialog.close();
    }

    function bind(root) {
        if (!root) return;
        input = root.querySelector('[data-docara-search-input]');
        status = root.querySelector('[data-docara-search-status]');
        results = root.querySelector('[data-docara-search-results]');
        closeButton = root.querySelector('[data-docara-search-close]');
        if (!input || !status || !results || !closeButton) {
            window.requestAnimationFrame(function () { bind(root); });
            return;
        }
        if (input.dataset.docaraSearchBound === 'true') return;
        input.dataset.docaraSearchBound = 'true';
        root.setAttribute('aria-labelledby', 'docara-search-title');
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
        if (dialog.openState) window.requestAnimationFrame(function () { input.focus(); });
        if (!initialized) {
            initialized = true;
            trigger.addEventListener('click', openSearch);
            dialog.onAfterClose(function () {
                trigger.setAttribute('aria-expanded', 'false');
            });
            document.addEventListener('keydown', function (event) {
                if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                    event.preventDefault();
                    openSearch();
                }
            });
        }
    }

    customElements.whenDefined('sf-modal').then(function () {
        function bindModal(event) {
            bind(event.detail && event.detail.root ? event.detail.root : dialog.getModalRoot());
        }
        dialog.onModalReady(bindModal);
        dialog.onModalUpdate(bindModal);
    });
}());

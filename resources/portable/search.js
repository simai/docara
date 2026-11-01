(function () {
    'use strict';

    // The site search window is the Framework sf-search. Docara answers it
    // through its port (simai.search-port 1.0.0) with its own index and
    // ranking: the page's locale only, weighted fields, accents folded, and
    // the words around the match as the row's preview.
    var search = document.querySelector('sf-search[data-docara-search-dialog]');
    if (!search) return;

    var locale = document.documentElement.lang || 'und';
    var indexPromise = null;
    var preparedDocuments = [];
    var deploymentBase = null;
    var expectedContentHash = null;
    var recentKey = 'docara.search.recent.' + locale;
    var recentLimit = 5;

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

    function escapeExpression(value) {
        return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
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
        var keys = ['id', 'url', 'locale', 'title', 'description', 'trail', 'headings', 'text'];
        // Front matter tags are optional: a page without tags has no field.
        var tagsValid = !isPlainRecord(record) || !Object.prototype.hasOwnProperty.call(record, 'tags')
            || (Array.isArray(record.tags) && record.tags.length > 0 && record.tags.every(function (tag) {
                return typeof tag === 'string' && tag.length > 0;
            }));
        return isPlainRecord(record)
            && (hasExactKeys(record, keys) || hasExactKeys(record, keys.concat(['tags'])))
            && tagsValid
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
            body: normalize(record.text),
            tags: (record.tags || []).map(normalize)
        };
        fields.all = [fields.title, fields.headings, fields.description, fields.body].concat(fields.tags).join(' ');
        return { document: record, fields: fields };
    }

    function loadIndex() {
        if (indexPromise) return indexPromise;
        var indexUrl = new URL(search.dataset.docaraSearchIndex, window.location.href);
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
            return preparedDocuments;
        });
        // A failed read is tried again on the next query.
        indexPromise.catch(function () { indexPromise = null; });
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
        // A tag is a word the author chose for the page, so it ranks between
        // the title and the headings.
        if (fields.tags.indexOf(phrase) !== -1) total += 500;
        queryTokens.forEach(function (token) {
            if (fields.tags.some(function (tag) { return tag.startsWith(token); })) total += 120;
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

    function rank(query) {
        var phrase = normalize(query);
        var queryTokens = tokens(phrase);
        return preparedDocuments.map(function (prepared) {
            return { prepared: prepared, score: score(prepared, phrase, queryTokens) };
        }).filter(function (item) {
            return item.score >= 0;
        }).sort(function (left, right) {
            return right.score - left.score || left.prepared.document.url.localeCompare(right.prepared.document.url);
        }).slice(0, 20).map(function (item) {
            return item.prepared.document;
        });
    }

    // Recent queries are the host's: kept per locale in this browser, and
    // only for as long as the page lives where storage is refused.
    var keptRecent = [];
    function readRecent() {
        try {
            var saved = JSON.parse(window.localStorage.getItem(recentKey) || '[]');
            keptRecent = Array.isArray(saved) ? saved.filter(function (entry) { return typeof entry === 'string' && entry; }).slice(0, recentLimit) : [];
        } catch (error) {}
        return keptRecent;
    }
    function writeRecent(list) {
        keptRecent = list.slice(0, recentLimit);
        try {
            if (keptRecent.length) window.localStorage.setItem(recentKey, JSON.stringify(keptRecent));
            else window.localStorage.removeItem(recentKey);
        } catch (error) {}
        return keptRecent;
    }
    function recordRecent(query) {
        var value = String(query || '').trim();
        if (!value) return readRecent();
        return writeRecent([value].concat(readRecent().filter(function (entry) { return entry !== value; })));
    }

    var byId = {};
    var lastTerms = [];

    function applied(data, sequence) {
        return sequence === undefined ? { answer: 'applied', data: data } : { answer: 'applied', sequence: sequence, data: data };
    }

    var host = {
        id: 'simai.search-port',
        port: 'simai.search-port',
        version: '1.0.0',
        capabilities: ['recent', 'preview'],
        raise: function (intent, payload) {
            payload = payload || {};
            var sequence = payload.sequence;
            if (intent === 'query.change') {
                var query = String(payload.query || '');
                var echo = { query: query, scope: payload.scope || null };
                if (!normalize(query)) return Promise.resolve(applied({ results: [], echo: echo, recent: readRecent() }, sequence));
                if (Array.from(normalize(query)).length < 2) {
                    return Promise.resolve({ answer: 'refused', reason: 'query-too-short', sequence: sequence });
                }
                return loadIndex().then(function () {
                    lastTerms = tokens(query);
                    var results = rank(query).map(function (record) {
                        byId[record.id] = record;
                        return {
                            id: record.id,
                            group: '',
                            title: record.title,
                            path: record.trail.length ? record.trail.join(' › ') : null,
                            snippet: null,
                            icon: 'description',
                            shortcut: null,
                            kind: 'page',
                            url: record.url
                        };
                    });
                    return applied({ results: results, echo: echo, recent: readRecent() }, sequence);
                }, function () {
                    return { answer: 'unavailable', reason: 'index-unavailable', sequence: sequence };
                });
            }
            if (intent === 'result.preview') {
                var record = byId[payload.id];
                if (!record) return Promise.resolve({ answer: 'unavailable', reason: 'not-found', sequence: sequence });
                return Promise.resolve(applied({ preview: {
                    id: record.id,
                    title: record.title,
                    path: record.trail.join(' › '),
                    text: contextualSnippet(record, lastTerms)
                } }, sequence));
            }
            if (intent === 'result.open' || intent === 'action.run') {
                return Promise.resolve(applied({ recent: recordRecent(payload.query) }));
            }
            if (intent === 'recent.forget') {
                var forgotten = String(payload.query || '');
                return Promise.resolve(applied({ recent: writeRecent(readRecent().filter(function (entry) { return entry !== forgotten; })) }));
            }
            if (intent === 'recent.clear') return Promise.resolve(applied({ recent: writeRecent([]) }));
            return Promise.resolve({ answer: 'unavailable', reason: 'intent-unknown' });
        }
    };

    function connect(element) {
        if (!element || typeof element.connectPort !== 'function' || element.sfPort === host) return;
        element.connectPort(host);
    }
    // The element asks for a host once per connection; the script may come
    // before or after that, so it answers the request and also connects when
    // the element is defined.
    document.addEventListener('sf-host-port-request', function (event) {
        var component = event.detail && event.detail.component;
        if (component === search) connect(search);
    });
    if (window.customElements) {
        window.customElements.whenDefined('sf-search').then(function () { connect(search); });
    }

    // A link that opens search (the not-found page has one); without the
    // script it leads to its href.
    document.addEventListener('click', function (event) {
        var opener = event.target instanceof Element
            ? event.target.closest('a[data-docara-search-open]')
            : null;
        if (!opener || event.defaultPrevented || event.button !== 0
            || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
            || typeof search.open !== 'function') return;
        event.preventDefault();
        search.open();
    });
}());

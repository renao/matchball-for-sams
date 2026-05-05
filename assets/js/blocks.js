(function (wp) {
    var el = wp.element.createElement;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    var InspectorControls = (wp.blockEditor || wp.editor).InspectorControls;
    var useBlockProps = (wp.blockEditor || wp.editor).useBlockProps;
    var SelectControl = wp.components.SelectControl;
    var PanelBody = wp.components.PanelBody;

    function findOptionLabel(options, value, fallback) {
        if (!value) {
            return fallback || 'Not selected';
        }
        var list = Array.isArray(options) ? options : [];
        for (var i = 0; i < list.length; i++) {
            if (list[i] && list[i].value === value) {
                return list[i].label;
            }
        }
        return value;
    }

    function mapOptions(items, labelKey, firstLabel) {
        var list = [{ label: firstLabel || 'Select...', value: '' }];
        var mapped = (items || []).map(function (item) {
            return {
                label: item[labelKey] || item.name || item.uuid,
                value: item.uuid || item.id,
            };
        });
        // Sort alphabetically by label (case-insensitive, German locale)
        mapped.sort(function (a, b) {
            return a.label.localeCompare(b.label, 'de', { sensitivity: 'base' });
        });
        return list.concat(mapped);
    }

    function mapSeasonOptions(items, firstLabel) {
        var list = [{ label: firstLabel || 'Select...', value: '' }];
        var mapped = (items || []).map(function (item) {
            return {
                label: item.name,
                value: item.uuid,
            };
        });
        // Sort seasons in descending order (newest first, e.g. 2026/2027 before 2025/2026)
        mapped.sort(function (a, b) {
            return b.label.localeCompare(a.label, 'de', { sensitivity: 'base' });
        });
        return list.concat(mapped);
    }

    // Shared cache so all block instances on the same page reuse one request per endpoint+params.
    var _apiCache = {};   // cacheKey → resolved data
    var _apiPending = {}; // cacheKey → in-flight Promise

    function fetchApi(endpoint, params) {
        var url = new URL(sikConfig.root + endpoint, window.location.origin);
        if (params && typeof params === 'object') {
            Object.keys(params).forEach(function (key) {
                var value = params[key];
                if (value !== undefined && value !== null && value !== '') {
                    url.searchParams.set(key, value);
                }
            });
        }

        var cacheKey = url.toString();

        if (_apiCache[cacheKey] !== undefined) {
            return Promise.resolve(_apiCache[cacheKey]);
        }

        if (_apiPending[cacheKey]) {
            return _apiPending[cacheKey];
        }

        var promise = wp.apiFetch({
            url: cacheKey,
            headers: { 'X-WP-Nonce': sikConfig.nonce },
        }).then(function (data) {
            _apiCache[cacheKey] = data;
            delete _apiPending[cacheKey];
            return data;
        }, function (err) {
            delete _apiPending[cacheKey];
            return Promise.reject(err);
        });

        _apiPending[cacheKey] = promise;
        return promise;
    }

    function BlockEdit(kind) {
        return function (props) {
            var attrs = props.attributes;
            var setAttrs = props.setAttributes;
            var blockProps = useBlockProps({ className: 'sik-editor-block' });

            var baseSourceOptions = [{ label: 'Select source', value: '' }];
            var baseSeasonOptions = [{ label: 'Select season', value: '' }];
            var baseLeagueOptions = [{ label: 'Select league', value: '' }];
            var baseTeamOptions = [{ label: 'Optional team', value: '' }];
            var loadingOption = [{ label: 'Loading...', value: '__loading__' }];

            var _useState = useState(baseSourceOptions), sourceOptions = _useState[0], setSourceOptions = _useState[1];
            var _useState2 = useState(attrs.sourceId ? loadingOption : baseSeasonOptions), seasonOptions = _useState2[0], setSeasonOptions = _useState2[1];
            var _useState3 = useState(attrs.seasonUuid ? loadingOption : baseLeagueOptions), leagueOptions = _useState3[0], setLeagueOptions = _useState3[1];
            var _useState4 = useState(attrs.leagueUuid ? loadingOption : baseTeamOptions), teamOptions = _useState4[0], setTeamOptions = _useState4[1];

            useEffect(function () {
                var active = true;
                fetchApi('sources')
                    .then(function (res) {
                        if (!active) {
                            return;
                        }
                        var items = (res.items || []).map(function (item) {
                            return { id: item.id, name: item.name };
                        });
                        setSourceOptions(mapOptions(items, 'name', 'Select source'));
                        if (!attrs.sourceId && res.default_source) {
                            setAttrs({ sourceId: res.default_source });
                        }
                    })
                    .catch(function () {
                        if (active) {
                            setSourceOptions([{ label: 'No sources available', value: '' }]);
                        }
                    });

                return function () {
                    active = false;
                };
            }, []);

            useEffect(function () {
                var active = true;
                if (!attrs.sourceId) {
                    setSeasonOptions(baseSeasonOptions);
                    setLeagueOptions(baseLeagueOptions);
                    setTeamOptions(baseTeamOptions);
                    return function () { active = false; };
                }

                setSeasonOptions(loadingOption);
                fetchApi('seasons', { source: attrs.sourceId })
                    .then(function (res) {
                        if (!active) {
                            return;
                        }
                        setSeasonOptions(mapSeasonOptions(res.items || [], 'Select season'));
                    })
                    .catch(function () {
                        if (active) {
                            setSeasonOptions(baseSeasonOptions);
                        }
                    });

                return function () {
                    active = false;
                };
            }, [attrs.sourceId]);

            useEffect(function () {
                var active = true;
                if (!attrs.sourceId || !attrs.seasonUuid) {
                    setLeagueOptions(baseLeagueOptions);
                    setTeamOptions(baseTeamOptions);
                    return function () { active = false; };
                }

                setLeagueOptions(loadingOption);
                fetchApi('leagues', { source: attrs.sourceId, season: attrs.seasonUuid })
                    .then(function (res) {
                        if (!active) {
                            return;
                        }
                        setLeagueOptions(mapOptions(res.items || [], 'name', 'Select league'));
                    })
                    .catch(function () {
                        if (active) {
                            setLeagueOptions(baseLeagueOptions);
                        }
                    });

                return function () {
                    active = false;
                };
            }, [attrs.sourceId, attrs.seasonUuid]);

            useEffect(function () {
                var active = true;
                if (!attrs.sourceId || !attrs.leagueUuid) {
                    setTeamOptions(baseTeamOptions);
                    return function () { active = false; };
                }

                setTeamOptions(loadingOption);
                fetchApi('teams', { source: attrs.sourceId, league: attrs.leagueUuid })
                    .then(function (res) {
                        if (!active) {
                            return;
                        }
                        setTeamOptions(mapOptions(res.items || [], 'name', 'Optional team'));
                    })
                    .catch(function () {
                        if (active) {
                            setTeamOptions(baseTeamOptions);
                        }
                    });

                return function () {
                    active = false;
                };
            }, [attrs.sourceId, attrs.leagueUuid]);

            function createSelectField(label, value, options, onChange) {
                return el(SelectControl, {
                    label: label,
                    value: value || '',
                    options: options,
                    onChange: onChange,
                });
            }

            var controls = [
                createSelectField(
                    'Data source',
                    attrs.sourceId || '',
                    sourceOptions,
                    function (value) {
                        setAttrs({ sourceId: value, seasonUuid: '', leagueUuid: '', teamUuid: '' });
                    }
                ),
                createSelectField(
                    'Season',
                    attrs.seasonUuid || '',
                    seasonOptions,
                    function (value) {
                        setAttrs({ seasonUuid: value, leagueUuid: '', teamUuid: '' });
                    }
                ),
                createSelectField(
                    'League',
                    attrs.leagueUuid || '',
                    leagueOptions,
                    function (value) {
                        setAttrs({ leagueUuid: value, teamUuid: '' });
                    }
                ),
                createSelectField(
                    'Team (optional)',
                    attrs.teamUuid || '',
                    teamOptions,
                    function (value) {
                        setAttrs({ teamUuid: value });
                    }
                ),
            ];

            if (kind === 'results') {
                controls.push(el(wp.components.ToggleControl, {
                    label: 'Include future matches',
                    checked: !!attrs.includeFuture,
                    onChange: function (value) {
                        setAttrs({ includeFuture: !!value });
                    },
                }));
            }

            var blockTypeLabel = kind === 'results' ? 'SAMS Results' : 'SAMS Standings';
            var configItems = [
                'Source: ' + findOptionLabel(sourceOptions, attrs.sourceId, 'Not selected'),
                'Season: ' + findOptionLabel(seasonOptions, attrs.seasonUuid, 'Not selected'),
                'League: ' + findOptionLabel(leagueOptions, attrs.leagueUuid, 'Not selected'),
                'Team: ' + findOptionLabel(teamOptions, attrs.teamUuid, 'All teams'),
            ];

            if (kind === 'results') {
                configItems.push('Include future matches: ' + (attrs.includeFuture ? 'Yes' : 'No'));
            }

            var previewLines = configItems.map(function (line, index) {
                return el('li', { key: 'cfg-' + index }, line);
            });

            return el(
                'div',
                blockProps,
                el(
                    InspectorControls,
                    null,
                    el(PanelBody, { title: 'SAMS settings', initialOpen: true }, controls)
                ),
                el('div', { className: 'sik-editor-preview' },
                    el('strong', { className: 'sik-editor-preview__title' }, blockTypeLabel),
                    el('ul', { className: 'sik-editor-preview__list' }, previewLines)
                )
            );
        };
    }

    function register(name, title, icon, kind, extraAttributes) {
        wp.blocks.registerBlockType(name, {
            title: title,
            icon: icon,
            category: 'widgets',
            attributes: Object.assign({
                sourceId: { type: 'string', default: '' },
                seasonUuid: { type: 'string', default: '' },
                leagueUuid: { type: 'string', default: '' },
                teamUuid: { type: 'string', default: '' },
            }, extraAttributes || {}),
            edit: BlockEdit(kind),
            save: function () {
                return null;
            },
        });
    }

    register('sik/standings', 'SAMS Standings', 'list-view', 'standings');
    register('sik/results', 'SAMS Results', 'chart-bar', 'results', {
        includeFuture: { type: 'boolean', default: false },
    });
})(window.wp);

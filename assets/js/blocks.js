(function (wp) {
    var el = wp.element.createElement;
    var __ = wp.i18n.__;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    var InspectorControls = (wp.blockEditor || wp.editor).InspectorControls;
    var useBlockProps = (wp.blockEditor || wp.editor).useBlockProps;
    var SelectControl = wp.components.SelectControl;
    var PanelBody = wp.components.PanelBody;

    function findOptionLabel(options, value, fallback) {
        if (!value) {
            return fallback || __('Not selected', 'matchball');
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
        var list = [{ label: firstLabel || __('Select...', 'matchball'), value: '' }];
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
        var list = [{ label: firstLabel || __('Select...', 'matchball'), value: '' }];
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
        var url = new URL(matchballConfig.root + endpoint, window.location.origin);
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
            headers: { 'X-WP-Nonce': matchballConfig.nonce },
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
            var blockProps = useBlockProps({ className: 'matchball-editor-block' });

            var baseSourceOptions = [{ label: __('Select source', 'matchball'), value: '' }];
            var baseSeasonOptions = [{ label: __('Select season', 'matchball'), value: '' }];
            var baseAssocOptions = [{ label: __('All state associations', 'matchball'), value: '' }];
            var baseLeagueOptions = [{ label: __('Select league', 'matchball'), value: '' }];
            var baseTeamOptions = [{ label: __('Optional team', 'matchball'), value: '' }];
            var loadingOption = [{ label: __('Loading...', 'matchball'), value: '__loading__' }];

            var _useState = useState(baseSourceOptions), sourceOptions = _useState[0], setSourceOptions = _useState[1];
            var _useState2 = useState(attrs.sourceId ? loadingOption : baseSeasonOptions), seasonOptions = _useState2[0], setSeasonOptions = _useState2[1];
            var _useState5 = useState(attrs.sourceId ? loadingOption : baseAssocOptions), assocOptions = _useState5[0], setAssocOptions = _useState5[1];
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
                        setSourceOptions(mapOptions(items, 'name', __('Select source', 'matchball')));
                        if (!attrs.sourceId && res.default_source) {
                            setAttrs({ sourceId: res.default_source });
                        }
                    })
                    .catch(function () {
                        if (active) {
                            setSourceOptions([{ label: __('No sources available', 'matchball'), value: '' }]);
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
                    setAssocOptions(baseAssocOptions);
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
                        setSeasonOptions(mapSeasonOptions(res.items || [], __('Select season', 'matchball')));
                    })
                    .catch(function () {
                        if (active) {
                            setSeasonOptions(baseSeasonOptions);
                        }
                    });

                setAssocOptions(loadingOption);
                fetchApi('associations', { source: attrs.sourceId })
                    .then(function (res) {
                        if (!active) {
                            return;
                        }
                        var items = res.items || [];
                        if (items.length === 0) {
                            setAssocOptions(baseAssocOptions);
                            return;
                        }
                        var mapped = items.map(function (item) {
                            return { label: item.name || item.uuid, value: item.uuid };
                        });
                        mapped.sort(function (a, b) {
                            return a.label.localeCompare(b.label, 'de', { sensitivity: 'base' });
                        });
                        setAssocOptions(baseAssocOptions.concat(mapped));
                    })
                    .catch(function () {
                        if (active) {
                            setAssocOptions(baseAssocOptions);
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
                var leagueParams = { source: attrs.sourceId, season: attrs.seasonUuid };
                if (attrs.associationUuid) {
                    leagueParams.association = attrs.associationUuid;
                }
                fetchApi('leagues', leagueParams)
                    .then(function (res) {
                        if (!active) {
                            return;
                        }
                        setLeagueOptions(mapOptions(res.items || [], 'name', __('Select league', 'matchball')));
                    })
                    .catch(function () {
                        if (active) {
                            setLeagueOptions(baseLeagueOptions);
                        }
                    });

                return function () {
                    active = false;
                };
            }, [attrs.sourceId, attrs.seasonUuid, attrs.associationUuid]);

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
                        setTeamOptions(mapOptions(res.items || [], 'name', __('Optional team', 'matchball')));
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

            function createSelectField(label, value, options, onChange, help) {
                var props = {
                    label: label,
                    value: value || '',
                    options: options,
                    onChange: onChange,
                };
                if (help) {
                    props.help = help;
                }
                return el(SelectControl, props);
            }

            var controls = [
                createSelectField(
                    __('Data source', 'matchball'),
                    attrs.sourceId || '',
                    sourceOptions,
                    function (value) {
                        setAttrs({ sourceId: value, seasonUuid: '', associationUuid: '', leagueUuid: '', teamUuid: '' });
                    }
                ),
                createSelectField(
                    __('Season', 'matchball'),
                    attrs.seasonUuid || '',
                    seasonOptions,
                    function (value) {
                        setAttrs({ seasonUuid: value, leagueUuid: '', teamUuid: '' });
                    }
                ),
                createSelectField(
                    __('State association (optional)', 'matchball'),
                    attrs.associationUuid || '',
                    assocOptions,
                    function (value) {
                        setAttrs({ associationUuid: value, leagueUuid: '', teamUuid: '' });
                    },
                    __('Filters the league list to a single state association (Landesverband). The available options are determined by the top-level association configured on the data source.', 'matchball')
                ),
                createSelectField(
                    __('League', 'matchball'),
                    attrs.leagueUuid || '',
                    leagueOptions,
                    function (value) {
                        setAttrs({ leagueUuid: value, teamUuid: '' });
                    }
                ),
                createSelectField(
                    __('Team (optional)', 'matchball'),
                    attrs.teamUuid || '',
                    teamOptions,
                    function (value) {
                        setAttrs({ teamUuid: value });
                    }
                ),
            ];

            if (kind === 'results') {
                controls.push(el(wp.components.ToggleControl, {
                    label: __('Include future matches', 'matchball'),
                    checked: !!attrs.includeFuture,
                    onChange: function (value) {
                        setAttrs({ includeFuture: !!value });
                    },
                }));
            }

            var blockTypeLabel = kind === 'results' ? __('Matchball Results', 'matchball') : __('Matchball Standings', 'matchball');
            var configItems = [
                __('Source:', 'matchball') + ' ' + findOptionLabel(sourceOptions, attrs.sourceId, __('Not selected', 'matchball')),
                __('Season:', 'matchball') + ' ' + findOptionLabel(seasonOptions, attrs.seasonUuid, __('Not selected', 'matchball')),
                __('Association:', 'matchball') + ' ' + findOptionLabel(assocOptions, attrs.associationUuid, __('All', 'matchball')),
                __('League:', 'matchball') + ' ' + findOptionLabel(leagueOptions, attrs.leagueUuid, __('Not selected', 'matchball')),
                __('Team:', 'matchball') + ' ' + findOptionLabel(teamOptions, attrs.teamUuid, __('All teams', 'matchball')),
            ];

            if (kind === 'results') {
                configItems.push(__('Include future matches:', 'matchball') + ' ' + (attrs.includeFuture ? __('Yes', 'matchball') : __('No', 'matchball')));
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
                    el(PanelBody, { title: __('SAMS settings', 'matchball'), initialOpen: true }, controls)
                ),
                el('div', { className: 'matchball-editor-preview' },
                    el('strong', { className: 'matchball-editor-preview__title' }, blockTypeLabel),
                    el('ul', { className: 'matchball-editor-preview__list' }, previewLines)
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
                associationUuid: { type: 'string', default: '' },
                leagueUuid: { type: 'string', default: '' },
                teamUuid: { type: 'string', default: '' },
            }, extraAttributes || {}),
            edit: BlockEdit(kind),
            save: function () {
                return null;
            },
        });
    }

    register('matchball/standings', __('Matchball Standings', 'matchball'), 'list-view', 'standings');
    register('matchball/results', __('Matchball Results', 'matchball'), 'chart-bar', 'results', {
        includeFuture: { type: 'boolean', default: false },
    });
})(window.wp);


Vue.component('datatable', {
    data: function () {
        return {
            rows: [],
            isMobile: false,
            currentMassiveAction: null,
            rowsChecked: [],
            pagination: null,
            limit: 10
        }
    },
    props: {
        config: Object,
        responsiveBreakpoint: Number,
        lastMobileColumnIndex: {
            type: Number,
            default: 1
        }  
    },
    template: '#datatable-template',
    methods: {
        isColumnSortable: function(column) {
            return this.config.columns[column] && 
                (this.config.columns[column].sortable !== undefined ? this.config.columns[column].sortable : true);
        },
        isColumnFilterable: function(column) {
            return this.config.columns[column] && 
                (this.config.columns[column].filterable !== undefined ? this.config.columns[column].sortable : true);
        },
        isCurrentSortColumn: function(column) {
            return this.sort_column && this.sort_column == column;
        },
        getColumnIndex: function(column) {
            return this.columnIndex[column];
        },
        loadRows: function() {
            var that = this;
            
            $(this.$el).addClass('loading');
            
            //get full action url;
            let anchor = document.createElement('a');
            anchor.href = this.config.backend_action;
            let url = new URL(anchor.href);
            
            //get pagination params
            let q = {
                'page': this.current_page,
                'limit': this.limit
            };
            
            if(this.sort_column) {
                q['sort'] = this.sort_column;
            }
            if(this.sort_direction) {
                q['direction'] = this.sort_direction;
            }
            
            if(Object.keys(this.current_filters).length) {
                q['filter'] = this.current_filters;
            }
            
            if(url.search.length) {
                url.search = url.search + '&' + $.param(q);
            }
            else {
                url.search = '?' + $.param(q);
            }
            
            $.getJSON(url.toString())
                .done(function(data) {
                    that.rows = data.data;
                    that.pagination = data.pagination;
                })
                .fail(function() {
                    alert('errore caricamento dati');
                })
                .always(function() {
                    $(that.$el).removeClass('loading');
                    const updateEvent = new CustomEvent('update', {
                        bubbles: true,
                        detail: { datatable: that }
                    });
                    that.$el.dispatchEvent(updateEvent);
                });
                
        },
        doPostAction: function(url, confirm, data) {
            let _doPostAction = function() {
                let id = Math.random().toString(36).substr(2,9); //random id
                let form = $('<form method="post" id="'+id+'"></form>');
                form.attr('action', url);
                if(_CSRF_TOKEN != undefined) { //_CSRF_TOKEN is a global variable
                    var _csrfToken = $('<input type="hidden" name="_csrfToken"/>');
                    _csrfToken.attr('value', _CSRF_TOKEN);
                    form.append(_csrfToken);
                }
                if(data) {
                    for(let rowId of data) {
                        var _data = $('<input type="hidden" name="ids[]"/>');
                        _data.attr('value', rowId);
                        form.append(_data);
                    }
                }
                $('body').append(form);
                form.submit();
            }
            if(confirm) {
                if(window.confirm(confirm)) {
                    _doPostAction();
                }
            }
            else {
                _doPostAction();
            }
        },
        actionAttributes: function(actionData) {
            let additionalAttributes = {}
            if(actionData.dataset) {
                for(dataAttribute in actionData.dataset) {
                    additionalAttributes['data-'+dataAttribute] = actionData.dataset[dataAttribute];
                }
            }
            return additionalAttributes;
        },
        sortBy: function(column) {
            let direction = this.sort_column == column ? (this.sort_direction == 'asc' ? 'desc': 'asc') : 'asc';
            this.sort_column = column;
            this.sort_direction = direction;
            
            this.loadRows();
        },
        goToPage: function(page) {
            if(page != this.current_page && page >= 1 && page <= this.pagination.pageCount) {
                this.current_page = page;
                this.loadRows();
            }
        },
        changeLimit: function() {
            this.loadRows();
        },
        filter: function() {
            this.current_filters = {};
            this.current_page = 1;
            if(this.$refs.filter) {
                for(filter of this.$refs.filter) {
                    this.current_filters[filter.column] = filter.getFilterValue();
                }
            }
            this.loadRows();
        },
        autoFilter: function() {
            if(this.autoFilterTimeout != null) {
                clearTimeout(this.autoFilterTimeout);
            }
            this.autoFilterTimeout = setTimeout(this.filter, 300);
        },
        resetFilter: function() {
            this.current_filters = {};
            if(this.$refs.filter) {
                for(filter of this.$refs.filter) {
                    filter.reset();
                }
            }
            this.loadRows();
        },
        showOnMobile(column) {
            return !this.isMobile || this.getColumnIndex(column) <= this.lastMobileColumnIndex;
        },
        showRowDetail: function(e) {
            if(this.isMobile) {
                var target = $(e.target);
                if(target.is('td')) {
                    target.parent().next('tr.detail-row').toggle();
                }
            }
        },
        showMoreFilters: function() {
            $(this.$el).find('.more-filters').toggle();
        },
        selectVisible: function() {
            var _cheked = [];
            for(let row of this.rows) {
                _cheked.push(row.id);
            }
            this.rowsChecked = _cheked;
        },
        unselectVisible: function() {
            this.rowsChecked = [];
        },
        doMassiveAction: function() {
            var action = this.currentMassiveAction;
            if(action) {
                if(this.config.massive_actions[action].callback) {
                    window[this.config.massive_actions[action].callback](this.rowsChecked);
                }
                else {
                    var url = this.config.massive_actions[action].url;
                    var confirm = this.config.massive_actions[action].confirm ? this.config.massive_actions[action].confirm : false;
                    this.doPostAction(url, confirm, this.rowsChecked);
                }
            }
        },
        getRowClasses: function(row) {
            if(row['options'] != null && "class" in row['options']) {
                return row['options']['class'];
            }
            return [];
        }
    },
    created: function() {
        this.pagination = null;
        this.sort_column = null;
        this.sort_direction = null;
        this.current_page = 1;
        this.current_filters = {};

        this.columnIndex = {};
        var index = 0;
        for(columnName in this.config.columns) {
            this.columnIndex[columnName] = index++;
        }
        
        this.needAdditionalInfo = index > this.lastMobileColumnIndex;
    },
    mounted: function() {
        var that = this;
        this.autoFilterTimeout = null;
        if(this.config.default_order) {
            this.sort_column = this.config.default_order.column;
            this.sort_direction = this.config.default_order.direction;
        }
        
        var breakpoint = this.responsiveBreakpoint || 767;
        $(window).resize(function() {
            that.isMobile = $(window).width() <= breakpoint;
        }).resize();
        
        this.loadRows();
    },
    computed: {
        tableColumnCount: function() {
            var columnCount = this.isMobile ? Math.min(Object.keys(this.config.columns).length, this.lastMobileColumnIndex + 1) : Object.keys(this.config.columns).length;
            if(this.config.has_actions) {
                columnCount += 1;
            }
            if(this.hasMassiveActions) {
                columnCount += 1;
            }
            return columnCount;
        },
        hasMassiveActions: function() {
            return Object.keys(this.config.massive_actions).length > 0;
        },
        displayPages: function() {
            let pages = [];
            let start = Math.max(1, this.current_page - 3);
            if(start + 6 > this.pagination.pageCount) {
                start = Math.max(1, start - (start + 6 - this.pagination.pageCount));
            }
            let end = Math.min(start + 6, this.pagination.pageCount);
            for(i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        },
        lastPage: function() {
            return 
        }
    },
    watch: {
        limit: function() {
            this.changeLimit();
        }
    }
});


var filterMixin = {
    props: {
        config: Object,
        column: String
    },
    methods: {
        getColumnName: function() {
            return this.config.columns[this.column].header ? this.config.columns[this.column].header : this.column;
        },
        getFieldType: function() {
            let type = this.config.columns[this.column]['type'] ? 
                (
                    typeof(this.config.columns[this.column]['type']) == 'object' ? (this.config.columns[this.column]['type']['type'] ? this.config.columns[this.column]['type']['type'] : 'text') : 
                    this.config.columns[this.column]['type']
                ) : 
                'text'; 
            
            switch(type) {
                case 'date':
                    return 'date';
                case 'datetime':
                    return 'datetime-local';
                case 'number':
                case 'currency':
                    return 'number';
                case 'text':
                    return 'text';
                default:
                    return 'text';
            }
        },
        formatValue: function(value, type) {
            if(value == null || value == "") {
                return value;
            }
            switch(type) {
                case 'date':
                    return moment(value).format('YYYY-MM-DD');
                case 'datetime-local':
                    return moment(value).format('YYYY-MM-DD HH:mm');
                default:
                    return value;
            }
        },
        getOptions: function() {
            return this.config.columns[this.column]['options'] ? this.config.columns[this.column]['options'] : (this.config.columns[this.column]['type'] == 'bool' ? {'1': 'Sì', '0' : 'No'} : {});
        },
        getFilterValue: function() {
            return null;
        },
        reset: function() {
            return false;
        },
        onChange: function() {
            this.$emit('filter')
        }
    }
}

Vue.component('textFilter', {
    mixins: [filterMixin],
    data: function () {
        return {
        }
    },
   
    template: '#text-filter-template',
    methods: {
        getFilterValue: function() {
            return this.formatValue($(this.$refs.value).val(), this.getFieldType());
        },
        reset: function() {
            $(this.$refs.value).val('');
        }
    }
});


Vue.component('rangeFilter', {
    mixins: [filterMixin],
    data: function () {
        return {
        }
    },
    template: '#range-filter-template',
    methods: {
        getFilterValue: function() {
            let fieldType = this.getFieldType();
            return {
                from: this.formatValue($(this.$refs.from).val(), fieldType),
                to: this.formatValue($(this.$refs.to).val(), fieldType)
            };
        },
        reset: function() {
            $(this.$refs.from).val('');
            $(this.$refs.to).val('');
        }
    }
});

Vue.component('selectFilter', {
    mixins: [filterMixin],
    data: function () {
        return {
        }
    },
    template: '#select-filter-template',
    methods: {
        getFilterValue: function() {
            return $(this.$refs.value).val();
        },
        reset: function() {
            $(this.$refs.value).val('').change();
        }
    },
    mounted: function() {
        var that = this;
        setTimeout(function() {
            $(that.$refs.value).select2().on("select2:select", (e) => {
                that.$refs.value.dispatchEvent(new Event('change', { target: e.target }));
            });
        }, 0);
        
    },
    updated: function() {
        var that = this;
        setTimeout(function() {
            $(that.$refs.value).select2().on("select2:select", (e) => {
                that.$refs.value.dispatchEvent(new Event('change', { target: e.target }));
            });
        }, 0);
    }
});


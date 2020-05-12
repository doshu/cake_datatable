<?php

    namespace Datatable\Model;

    abstract class TableSchema
    {
    
        protected $_defaultPages = [5, 10, 20, 50, 100];
        protected $_columns = [];
        protected $_entityId = 'id';
        protected $_options = [];
        protected $_hasActions = true;
        protected $_defaultOrder = null;
        
        protected $_backendAction = null;
        protected $_controller = null;
        
        protected $_enumFlattenOptions = [];
        
        protected $_defaultFilters = [];
        
        public function __construct($backendAction, $controller) {
            $this->_backendAction = $backendAction;
            $this->_controller = $controller;
            $this->_prepareColumns();
        }
        
        /**
         * _prepareColumns
         * 
         * aggiunge colonne alla tabella
         * Possibili opzioni:
         * 
         * header => nome della colonna
         * index => property dell'entity da visualizzare
         * type => tipo di valore, utilizzato per il render
         * options => opzioni per un filtro a select
         * sortable => indica se una colonna deve essere ordinabile
         * filterable => indica se una colonna deve essere filtrabile
         * sort_index => proprietà su cui effetuare il sort
         * filter_index => proprietà su cui effetuare il filtro
         * filter_type => tipo di filtro (text, range, select)
         * renderer => element da utilizzare per il render
         *
         * @return void
         */
        protected abstract function  _prepareColumns();
        
        /**
         * _prepareRowsAction
         * 
         * aggiunge azioni alle righe
         * Possibili opzioni
         * title => nome dell'azione
         * url => url dell'azione
         * type => get o post
         * confirm => richiesta di conferma
         *
         * @return void
         */
        protected abstract function _prepareRowsAction($row);
        
        
        protected function _addRowOptions($row) {
            return [];
        }
        
        
        protected function _getMassiveActions() {
            return [];
        }
        
        public function getConfig() {
            //TODO
            //rimuovere campi che non devono esser visti su frontend
            return [
                'columns' => $this->getColumns(),
                'filters' => $this->getColumnsFilter(),
                'has_actions' => $this->getHasActions(),
                'massive_actions' => $this->_getMassiveActions(),
                'backend_action' => \Cake\Routing\Router::url($this->_backendAction),
                'default_order' => $this->getDefaultOrder(),
            ];
        }
        
        public function setHasActions($hasActions) {
            $this->_hasActions = $hasActions;
        }
        
        public function getHasActions() {
            return $this->_hasActions;
        }
        
        protected function _addColumn($name, $options) {
            $this->_columns[$name] = $options;
        } 
        
        public function getColumns() {
            return $this->_columns;
        }
        
        public function getColumnHeader($column) {
            return $this->_columns[$column]['header'];
        }
        
        public function getColumnIndex($column) {
            return isset($this->_columns[$column]['index'])?$this->_columns[$column]['index']:$column;
        }
        
        public function getColumnSortIndex($column) {
            return isset($this->_columns[$column]['sort_index'])?$this->_columns[$column]['sort_index']:$this->getColumnIndex($column);
        }
        
        public function getColumnFilterIndex($column) {
            return isset($this->_columns[$column]['filter_index'])?$this->_columns[$column]['filter_index']:$this->getColumnIndex($column);
        }
        
        public function getColumnType($column) {
            if(!isset($this->_columns[$column]['type']) || empty($this->_columns[$column]['type'])) {
                return 'text';
            }
            if(is_array($this->_columns[$column]['type'])) {
                return $this->_columns[$column]['type']['type'] ?? 'text';
            }
            return $this->_columns[$column]['type'];
        }
        
        public function getColumnTypeOptions($column) {
            if(isset($this->_columns[$column]['type']) && is_array($this->_columns[$column]['type'])) {
                return $this->_columns[$column]['type']['options'] ?? [];
            }
            return [];
        }
        
        public function getColumnSortable($column) {
            return isset($this->_columns[$column]['sortable'])?$this->_columns[$column]['sortable']:true;
        }
        
        public function getColumnFilterable($column) {
            return isset($this->_columns[$column]['filterable'])?$this->_columns[$column]['filterable']:true;
        }
        
        public function getColumnOptions($column) {
            if($this->getColumnType($column) == 'bool') {
                return [1 => __('Sì'), 0 => __('No')];
            }
            return isset($this->_columns[$column]['options'])?$this->_columns[$column]['options']:[];
        }
        
        public function getColumnRenderer($column) {
            return isset($this->_columns[$column]['renderer'])?$this->_columns[$column]['renderer']:false;
        }
        
        public function getColumnsFilter() {
            $columns = $this->getColumns();
            $filters = [];
            foreach($columns as $column => $data) {
                $filters[$column] = $this->getColumnFilterType($column);
            }
            return $filters;
        }
        
        public function getColumnFilterType($column) {
            if(isset($this->_columns[$column]['filter_type']) && !empty($this->_columns[$column]['filter_type'])) {
                return $this->_columns[$column]['filter_type'];
            }
            
            $type = $this->getColumnType($column);
            switch($type) {
                case 'date':
                case 'datetime':
                case 'number':
                case 'currency':
                    return 'range';
                break;
                case 'enum':
                case 'bool':
                    return 'select';
                break;
                default:
                    return 'text';
            }
        }
        
        public function getColumnFormatter($column) {
            $type = $this->getColumnType($column);
            $options = $this->getColumnTypeOptions($column);
            $renderer = $this->getColumnRenderer($column);
            //$value = \Cake\Utility\Hash::get($row, $this->getColumnIndex($column));
            
            if($renderer) {
                $formatter = "_formatElement";
            }
            else {
                $method = '_format'.ucfirst($type);
                if(method_exists($this, $method)) {
                    $formatter =  $method;
                } 
                else {
                    $formatter = '_formatText';
                }
            }
            
            $clojure = function($value, $row) use ($formatter, $column, $options) {
                return $this->$formatter($value, $column, $row, $options);
            };
            
            return $clojure;
        }   
        
        public function setDefaultFilters($filters) {
            $this->_defaultFilters = $filters;
        }
        
        public function getDefaultFilters() {
            return $this->_defaultFilters;
        }
        
        protected function _setEntityId($id) {
            $this->_entityId = $id;
        }
        
        public function getEntityId() {
            return $this->_entityId;
        }
        
        public function prepareCollection($collection) {
        
            $queryParams = $this->_controller->request->getQueryParams();
            
            $filters = ($queryParams['filter'] ?? []) + $this->getDefaultFilters();
            
            if($filters) {
                foreach($filters as $column => $value) {
                    
                    $filterApplier = $this->_getFilterApplier($column);
                    
                    if($filterApplier) {
                        $index = $this->getColumnFilterIndex($column);
                        $filterApplier($index, $value, $collection, $this);
                    }
                    
                }
            }
            
            //enable sort fields
            $options = [
                'sortWhitelist' => []
            ];
            foreach($this->getColumns() as $column => $columnData) {
                if($this->getColumnSortable($column)) {
                    $options['sortWhitelist'][] = $this->getColumnSortIndex($column);
                }
            }
            
            //check sort column and change with real sort index
            if(isset($queryParams['sort'])) {
                $queryParams['sort'] = $this->getColumnSortIndex($queryParams['sort']);
                $this->_controller->request = $this->_controller->request->withQueryParams($queryParams);
            }
            
            //pagination
            $this->_controller->paginate($collection, $options);
            $paging = $this->_controller->request->getParam('paging');
            $paginationParams = $paging && $paging[$collection->getRepository()->getAlias()] ? $paging[$collection->getRepository()->getAlias()] : null; 
            
            $data = [];
            $hasActions = $this->getHasActions();
            foreach($collection as $row) {
                $_row = [
                    'original' => $row,
                    'id' => $row[$this->getEntityId()],
                    'options' => $this->_addRowOptions($row)
                ];
                
                if($hasActions) {
                    $actions = $this->_prepareRowsAction($row);
                    $_row['actions'] = [];
                    foreach($actions as $action => $actionData) {
                        $url = $actionData['url'];
                        $_row['actions'][] = [
                            'code' => $action,
                            'url' => \Cake\Routing\Router::url($url),
                            'type' => $actionData['type'] ?? 'get',
                            'title' => $actionData['title'],
                            'confirm' => $actionData['confirm'] ?? null
                        ];
                    }
                    
                }
                $data[] = $_row;
            }
                
            foreach($this->getColumns() as $column => $columnData) {
                $formatter = $this->getColumnFormatter($column);
                $index = $this->getColumnIndex($column);
                foreach($data as &$row) {
                    $row['columns'][$column] = $formatter(\Cake\Utility\Hash::get($row['original'], $index), $row);
                }
            }
            
            $this->_controller->set('data', $data);
            $this->_controller->set('pagination', $paginationParams);
            $this->_controller->set('_serialize', ['data', 'pagination']);
            
            return $collection;
        }
        
        public function _formatDatetime($value, $column, $row, $options = []) {
            //use i18n format
            $format = $options['format'] ?? null;
            if($format) {
                return $value->format($format);
            }
            return (string) $value;
        }
        
        public function _formatDate($value, $column, $row, $options = []) {
            $format = $options['format'] ?? null;
            if($format) {
                return $value->format($format);
            }
            return (string) $value;
        }
        
        public function _formatBool($value, $column, $row, $options = []) {
            return $value?__('Sì'):__('No');
        }   
        
        public function _formatEnum($value, $column, $row, $options = []) {
            if(!isset($this->_enumFlattenOptions[$column])) {
                $enum = $this->getColumnOptions($column);
                $this->_enumFlattenOptions[$column] = iterator_to_array(
                    new \RecursiveIteratorIterator(new \RecursiveArrayIterator($enum))
                );
            }
            $enum = $this->_enumFlattenOptions[$column];
            
            if($enum) {
                if(isset($enum[$value])) {
                    return $enum[$value];
                }
                if(isset($enum[(string)$value])) {
                    return $enum[(string)$value];
                }
            }
            return $value;
        }
        
        public function _formatCurrency($value, $column, $row, $options = []) {
            return \Cake\I18n\Number::currency($value, 'EUR');
        }
        
        public function _formatText($value, $column, $options = []) {
            return $value;
        }
        
        public function _formatElement($value, $column, $row, $options = []) {
            $renderer = $this->getColumnRenderer($column);
            $viewBuilder = new \Cake\View\ViewBuilder();
            $view = $viewBuilder->build();
            $html = $view->element($renderer, compact('value', 'column', 'row', 'options') + ['table' => $this]);
            return $html;
        }
        
        public function setDefaultOrder($column, $direction) {
            $this->_defaultOrder = ['column' => $column, 'direction' => $direction];
        }
        
        public function getDefaultOrder() {
            return $this->_defaultOrder;
        }
        
        protected function _getFilterApplier($column) {
            $filterIndex = $this->getColumnFilterIndex($column);
            if(!is_string($filterIndex) && is_callable($filterIndex)) {
                return $filterIndex;
            }
            $filterType = $this->getColumnFilterType($column);
            $filterClass = ucfirst($filterType).'Filter';
            $filterClass = "\\Datatable\\Model\\Filter\\".$filterClass;
            return new $filterClass;
        }
        
    }


?>

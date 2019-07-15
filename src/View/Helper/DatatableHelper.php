<?php

    namespace Datatable\View\Helper;

    use Cake\View\Helper;

    class DatatableHelper extends Helper
    {
        protected static $_templateLoaded = false;
        
        public $helpers = ['Html'];
        
        public function display(\Datatable\Model\TableSchema $table) {
            if(!static::$_templateLoaded) {
                $this->_loadTemplates();
            }
            echo $this->getView()->element('Datatable.display', ['table' => $table]);
        }
        
        protected function _loadTemplates() {
            echo $this->getView()->element('Datatable.templates');
            static::$_templateLoaded = true;
        }
        
        public function loadAssets() {
            return $this->Html->css('Datatable.datatable.css');
        }
    }

?>

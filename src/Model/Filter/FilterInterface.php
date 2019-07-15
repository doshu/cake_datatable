<?php

    namespace Datatable\Model\Filter;
    
    
    interface FilterInterface {
    
        public function __invoke($index, $value, $collection, \Datatable\Model\TableSchema $table);
    
    }

?>

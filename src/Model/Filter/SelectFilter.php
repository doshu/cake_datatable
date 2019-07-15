<?php

    namespace Datatable\Model\Filter;
    
    class SelectFilter implements FilterInterface {
    
        public function __invoke($index, $value, $collection, \Datatable\Model\TableSchema $table) {
            if($value !== null && $value !== "") {
                $collection->andWhere([$index => $value]);
            }
        }
        
    }
    
    
?>

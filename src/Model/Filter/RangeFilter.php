<?php

    namespace Datatable\Model\Filter;
    
    class RangeFilter implements FilterInterface {
    
        public function __invoke($index, $value, $collection, \Datatable\Model\TableSchema $table) {
            if(isset($value['from']) && trim($value['from']) !== "") {
                $collection->andWhere([$index.' >=' => $value['from']]);
            }
            if(isset($value['to']) && trim($value['to']) !== "") {
                $collection->andWhere([$index.' <=' => $value['to']]);
            }
        }
        
    }
    
    
?>

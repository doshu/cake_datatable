<?php

    namespace Datatable\Model\Filter;
    
    class TextFilter implements FilterInterface {
    
        public function __invoke($index, $value, $collection, \Datatable\Model\TableSchema $table) {
            if($value !== null && $value !== "") {
                $collection->andWhere([$index.' LIKE' => '%'.$value.'%']);
            }
        }
        
    }
    
    
?>

<?php
    $uuid = 'datatable-'.\Cake\Utility\Text::uuid();
    $name = !empty($name) ? $name : $uuid;
?>
<datatable id="<?= $uuid ?>" :config="config" last-mobile-column-index="50"></datatable>
<?php 
    if(isset($scriptBlock) && !empty($scriptBlock)) {
        $this->append($scriptBlock);
    }
?>
    
<script>
    $(function() {
        window["<?= json_encode($name) ?>"] = new Vue({
            el: '#<?= $uuid ?>',
            data: {
                config: <?= json_encode($table->getConfig()) ?>
            }
        });
    });
</script>

<?php 
    if(isset($scriptBlock) && !empty($scriptBlock)) {
        $this->end();
    }
?>

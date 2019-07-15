<?php
    $uuid = 'datatable-'.\Cake\Utility\Text::uuid();
?>
<datatable id="<?= $uuid ?>" :config="config"></datatable>
<script>
    $(function() {
        new Vue({
            el: '#<?= $uuid ?>',
            data: {
                config: <?= json_encode($table->getConfig()) ?>
            }
        });
    });
</script>

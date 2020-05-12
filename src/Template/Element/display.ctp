<?php
    $uuid = 'datatable-'.\Cake\Utility\Text::uuid();
?>
<datatable id="<?= $uuid ?>" :config="config" last-mobile-column-index="50"></datatable>
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

<?php echo $this->Form_Render($form);?>
<div class="sabai-csv-results" style="display:none;">
    <h2 class="sabai-form-header"><?php Sabai::_h($results_header);?></h2>
    <table class="sabai-table sabai-table-hover">
        <thead>
            <tr>
                <th style="width:15%;">#</th>
                <th style="width:15%;"><?php echo __('ID', 'sabai');?></th>
                <th style="width:35%;"><?php echo __('Title', 'sabai');?></th>
                <th style="width:35%;"><?php echo __('Status', 'sabai');?></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    <div class="sabai-csv-results-footer"></div>
</div>
<?php if (isset($download_url)):?>
<a style="display:none;" class="sabai-csv-download sabai-btn sabai-btn-primary sabai-btn-lg" href="<?php echo $download_url;?>"><?php Sabai::_h(__('Download', 'sabai'));?></a>
<?php endif;?>
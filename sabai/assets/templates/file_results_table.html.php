<?php echo $this->Form_Render($form);?>
<div class="sabai-file-results" style="display:none;">
    <table class="sabai-table sabai-table-hover">
        <thead>
            <tr>
                <th style="width:15%;">#</th>
                <th style="width:15%;"><?php Sabai::_h(__('File ID', 'sabai'));?></th>
                <th style="width:35%;"><?php Sabai::_h(__('File Name', 'sabai'));?></th>
                <th style="width:35%;"><?php Sabai::_h(__('Status', 'sabai'));?></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    <div class="sabai-file-results-footer"></div>
</div>
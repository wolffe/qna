<div id="<?php echo $id;?>" class="<?php echo $class;?> sabai-clearfix">
    <div class="sabai-questions-status">
        <?php echo $this->Entity_RenderLabels($entity);?>
    </div>
    <div class="sabai-questions-main">
        <div class="sabai-questions-body">
            <?php echo $this->Entity_RenderField($entity, 'content_body');?>
        </div>
        <div class="sabai-questions-taxonomy">
<?php if ($entity->questions_categories):?>
            <?php echo $this->Entity_RenderField($entity, 'questions_categories');?>
<?php endif;?>
<?php if ($entity->questions_tags):?>
            <?php echo $this->Entity_RenderField($entity, 'questions_tags');?>
<?php endif;?>
        </div>
        <div class="sabai-row">
            <div class="sabai-col-xs-offset-7 sabai-col-xs-5 sabai-questions-activity">
                <?php echo $this->Entity_RenderActivity($entity, array('action_label' => __('%s asked %s', 'sabai-discuss'), 'show_last_edited' => true, 'show_last_active' => false, 'permalink' => false));?>
            </div>
        </div>
    </div>
</div>
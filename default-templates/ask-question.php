<?php
wp_enqueue_style('qa-section', QA_PLUGIN_URL . QA_DEFAULT_TEMPLATE_DIR . '/css/general.css', [], QA_VERSION);
?>

<div id="qa-page-wrapper">
    <div id="qa-content-wrapper">
        <?php do_action('qa_before_content', 'ask-question'); ?>

        <div id="ask-question">
            <?php the_question_form(); ?>
        </div>

        <?php do_action('qa_after_content', 'ask-question'); ?>
    </div>
</div>

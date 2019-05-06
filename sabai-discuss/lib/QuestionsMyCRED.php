<?php
class Sabai_Addon_QuestionsMyCRED extends Sabai_Addon
{
    const VERSION = '1.4.6', PACKAGE = 'sabai-discuss';
    
    public function isUninstallable($currentVersion)
    {
        return true;
    }
    
    public function isInstallable()
    {
        return $this->_application->isAddonLoaded('Questions') && $this->_application->isAddonLoaded('MyCRED');
    }
    
    public function onMyCREDHooksFilter(&$hooks)
    {
        if (!isset($hooks['Discuss'])) {
            $hooks['Discuss'] = array(
                'actions' => array(),
                'references' => array(), 
            );
        }
        $hooks['Discuss']['actions'] += array(
            'question_created' => array(
                'hook' => 'entity_create_content_questions_entity_success',
                'num_args' => 3,
            ),
            'question_published' => array(
                'hook' => 'content_post_published',
                'num_args' => 1,
            ),
            'answer_created' => array(
                'hook' => 'entity_create_content_questions_answers_entity_success',
                'num_args' => 3,
            ),
            'answer_published' => array(
                'hook' => 'content_post_published',
                'num_args' => 1,
            ),
            'question_voted_updown' => array(
                'hook' => 'voting_content_questions_entity_voted_updown',
                'num_args' => 2,
            ),
            'answer_voted_updown' => array(
                'hook' => 'voting_content_questions_answers_entity_voted_updown',
                'num_args' => 2,
            ),
            'answer_accepted' => array(
                'hook' => 'questions_answer_accepted',
                'num_args' => 3,
            ),
        );
        $hooks['Discuss']['references'] += array(
            'submit_question' => array(
                'label' => _x('Submit question', 'MyCRED log', 'sabai-discuss'),
            ),
            'submit_answer' => array(
                'label' => _x('Submit answer', 'MyCRED log', 'sabai-discuss'),
            ),
            'accept_answer' => array(
                'label' => _x('Accepting answer', 'MyCRED log', 'sabai-discuss'),
            ),
            'unaccept_answer' => array(
                'label' => _x('Unaccepting answer', 'MyCRED log', 'sabai-discuss'),
                'default_credits' => -1,
            ),
            'answer_accepted' => array(
                'label' => _x('Answer accepted', 'MyCRED log', 'sabai-discuss'),
            ),
            'vote_question' => array(
                'label' => _x('Voting up question', 'MyCRED log', 'sabai-discuss'),
            ),
            'vote_down_question' => array(
                'label' => _x('Voting down question', 'MyCRED log', 'sabai-discuss'),
                'default_credits' => -1,
            ),
            'unvote_question' => array(
                'label' => _x('Unvoting question', 'MyCRED log', 'sabai-discuss'),
                'default_credits' => -1,
            ),
            'question_voted' => array(
                'label' => _x('Question voted up', 'MyCRED log', 'sabai-discuss'),
            ),
            'question_voted_down' => array(
                'label' => _x('Question voted down', 'MyCRED log', 'sabai-discuss'),
                'default_credits' => -1,
            ),
            'vote_answer' => array(
                'label' => _x('Voting up answer', 'MyCRED log', 'sabai-discuss'),
            ),
            'vote_down_answer' => array(
                'label' => _x('Voting down answer', 'MyCRED log', 'sabai-discuss'),
                'default_credits' => -1,
            ),
            'unvote_answer' => array(
                'label' => _x('Unvoting answer', 'MyCRED log', 'sabai-discuss'),
                'default_credits' => -1,
            ),
            'answer_voted' => array(
                'label' => _x('Answer voted up', 'MyCRED log', 'sabai-discuss'),
            ),
            'answer_voted_down' => array(
                'label' => _x('Answer voted down', 'MyCRED log', 'sabai-discuss'),
                'default_credits' => -1,
            ),
        );
    }
    
    public function onMyCREDHookDiscuss($hook, $action, $args)
    {        
        $this->_application->QuestionsMyCRED_Hook($hook, $action, $args);
    }
}

<?php
class Sabai_Addon_QuestionsMyCRED_Helper_Hook extends Sabai_Helper
{
    public function help(Sabai $application, $hook, $action, $args)
    {
        switch ($action) {
            case 'question_created':
                $question = $args[1];
                if (!$author_id = $question->getAuthorId()) return;
                
                if ($question->isPublished()) {
                    // Add credits to author
                    $hook->addCredits('Discuss', 'submit_question', $question->getId(), $author_id);
                }
                break;
            case 'question_published':
                $question = $args[0];
                if ($question->getBundleType() !== 'questions'
                    || (!$author_id = $question->getAuthorId())
                ) return;
                
                $hook->addCredits('Discuss', 'submit_question', $question->getId(), $author_id);
                break;
            case 'answer_created':
                $answer = $args[1];
                if (!$author_id = $answer->getAuthorId()) return;
                
                if ($answer->isPublished()) {
                    // Add credits to author
                    $hook->addCredits('Discuss', 'submit_answer', $answer->getId(), $author_id);
                }
                break;
            case 'answer_published':
                $answer = $args[0];
                if ($answer->getBundleType() !== 'questions_answers'
                    || (!$author_id = $answer->getAuthorId())
                ) return;
                
                $hook->addCredits('Discuss', 'submit_answer', $answer->getId(), $author_id);
                break;
            case 'question_voted_updown':
                $user_id = $application->getUser()->id;
                $question = $args[0];
                $results = $args[1];
                if ($question->getAuthorId() === $user_id) return; // no points for voting own content
                
                if (isset($results[''])) $results = $results[''];
                
                // Undoing vote?
                if ($results['prev_value'] !== false) {
                    $hook->addCredits( // Add credits to voter
                        'Discuss',
                        'unvote_question',
                        $question->getId(),
                        $user_id
                    )->deductCredits( // Deduct credits from author
                        'Discuss',
                        $results['prev_value'] == 1 ? 'question_voted' : 'question_voted_down',
                        $question->getId(),
                        $question->getAuthorId()
                    );
                }
                // Reflect current vote
                if ($results['value'] !== false) {
                    $hook->addCredits( // Add credits to author
                        'Discuss',
                        $results['value'] == 1 ? 'question_voted' : 'question_voted_down', 
                        $question->getId(),
                        $question->getAuthorId()
                    )->addCredits( // Add credits to voter
                        'Discuss',
                        $results['value'] == 1 ? 'vote_question' : 'vote_down_question',
                        $question->getId(),
                        $user_id
                    );
                }
                break;
                
            case 'answer_voted_updown':
                $user_id = $application->getUser()->id;
                $answer = $args[0];
                $results = $args[1];
                if ($answer->getAuthorId() === $user_id) return;
                
                if (isset($results[''])) $results = $results[''];
  
                // Undoing vote?
                if ($results['prev_value'] !== false) {
                    $hook->addCredits( // Add credits to voter
                        'Discuss',
                        'unvote_answer',
                        $answer->getId(),
                        $user_id
                    )->deductCredits( // Deduct credits from author
                        'Discuss',
                        $results['prev_value'] == 1 ? 'answer_voted' : 'answer_voted_down',
                        $answer->getId(),
                        $answer->getAuthorId()
                    );
                }
        
                // Reflect current vote
                if ($results['value'] !== false) {
                    $hook->addCredits( // Add credits to author
                        'Discuss',
                        $results['value'] == 1 ? 'answer_voted' : 'answer_voted_down', 
                        $answer->getId(),
                        $answer->getAuthorId()
                    )->addCredits( // Add credits to voter
                        'Discuss',
                        $results['value'] == 1 ? 'vote_answer' : 'vote_down_answer',
                        $answer->getId(),
                        $user_id
                    );
                }
                break;

            case 'answer_accepted':
                $user_id = $application->getUser()->id;
                $answer = $args[0];
                $score = $args[1];
                //$accepted_answer_count = $args[2];
                
                if ($answer->getAuthorId() === $user_id) return;

                if ($score) {
                    $hook->addCredits( // Add credits to user accepted
                        'Discuss',
                        'accept_answer',
                        $answer->getId(),
                        $user_id
                    )->addCredits( // Add credits to author
                        'Discuss',
                        'answer_accepted',
                        $answer->getId(),
                        $answer->getAuthorId()
                    );
                } else {
                    $hook->addCredits( // Add credits to user accepted
                        'Discuss',
                        'unaccept_answer',
                        $answer->getId(),
                        $user_id
                    )->deductCredits( // Deduct credits from author
                        'Discuss',
                        'answer_accepted',
                        $answer->getId(),
                        $answer->getAuthorId()
                    );
                }
                break;
        }
    }
}


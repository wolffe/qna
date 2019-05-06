<?php
class Sabai_Addon_Form_Field_Slider extends Sabai_Addon_Form_Field_AbstractField
{
    static private $_elements = array();

    public function formFieldGetFormElement($name, array &$data, Sabai_Addon_Form_Form $form)
    {
        if (!isset(self::$_elements[$form->settings['#id']])) {
            self::$_elements[$form->settings['#id']] = array();
        }
        
        if (!isset($data['#id'])) {
            $data['#id'] = $form->getFieldId($name);
        }
        if (empty($data['#integer'])) {
            $data['#min_value'] = isset($data['#min_value']) && is_numeric($data['#min_value']) ? $data['#min_value'] : 0;
            $data['#max_value'] = isset($data['#max_value']) && is_numeric($data['#max_value']) ? $data['#max_value'] : 100;
            if (!isset($data['#step'])) {
                $data['#step'] = 1;
            }
        } else {
            $data['#min_value'] = isset($data['#min_value']) ? intval($data['#min_value']) : 0;
            $data['#max_value'] = isset($data['#max_value']) ? intval($data['#max_value']) : 100;
            $data['#step'] = isset($data['#step']) ? intval($data['#step']) : 1;
        }
        if (!isset($data['#size'])) {
            $data['#size'] = strlen($data['#max_value']) + 2;
        }
        $markup = sprintf(
            '%8$s<input type="number" class="sabai-form-slider-value" name="%1$s" step="%2$s" min="%3$s" max="%4$s" size="%5$d" value="%6$s" placeholder="%3$s" />%9$s
<div class="sabai-form-slider%7$s" style="display:none; margin:8px 0 16px;" data-slider-step="%2$s" data-slider-min="%3$s" data-slider-max="%4$s" data-slider-value="%6$s"></div>',
            Sabai::h($name),
            $data['#step'],
            $data['#min_value'],
            $data['#max_value'],
            $data['#size'],
            @$data['#default_value'],
            !strlen(@$data['#default_value']) && !strlen(@$data['#default_value']) ? ' sabai-form-inactive' : '',
            isset($data['#field_prefix']) ? '<span class="sabai-form-field-prefix">' . $data['#field_prefix'] . '</span>' : '',
            isset($data['#field_suffix']) ? '<span class="sabai-form-field-suffix">' . $data['#field_suffix'] . '</span>' : ''
        );
        unset($data['#field_prefix'], $data['#field_suffix']);

        // Register pre render callback if this is the first date element
        if (empty(self::$_elements[$form->settings['#id']])) {
            $form->settings['#pre_render'][] = array($this, 'preRenderCallback');
        }

        self::$_elements[$form->settings['#id']][$name] = $data['#id'];

        unset($data['#default_value'], $data['#value']);

        return $form->createHTMLQuickformElement('static', $name, $data['#label'], $markup);
    }

    public function formFieldOnSubmitForm($name, &$value, array &$data, Sabai_Addon_Form_Form $form)
    {        
        if (!strlen($value)) {
            if ($form->isFieldRequired($data)) {
                $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Please fill out this field.', 'sabai'), $name);
            }
            $value = null;
            return;
        }

        if ($value > $data['#max_value']
            || $value < $data['#min_value']
        ) {
            $form->setError(sprintf(__('The input range must be between %s and %s.'), $data['#min_value'], $data['#max_value']));
        }
    }

    public function formFieldOnCleanupForm($name, array &$data, Sabai_Addon_Form_Form $form)
    {

    }

    public function formFieldOnRenderForm($name, array &$data, Sabai_Addon_Form_Form $form)
    {
        $form->renderElement($data);
    }

    public function preRenderCallback($form)
    {
        if (empty(self::$_elements[$form->settings['#id']])) return;
        
        $application = $this->_addon->getApplication();
        $application->LoadJqueryUi(array('slider'));
        $application->LoadJs('sabai-form-slider.min.js', 'sabai-form-slider', array('sabai'));
        
        $js = array();
        foreach (self::$_elements[$form->settings['#id']] as $id) {
            $js[] = 'SABAI.Form.slider("#'. $id .'");';
        }
        // Add js
        $form->addJs(sprintf(
            'jQuery(document).ready(function ($) {
    %s
});',
            implode(PHP_EOL, $js)
        ));
    }
}
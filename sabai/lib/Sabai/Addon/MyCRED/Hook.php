<?php
class Sabai_Addon_MyCRED_Hook extends myCRED_Hook
{
    protected $_actions = array();
    
    public function __construct($hook_prefs, $type = 'mycred_default')
    {
        $sabai = get_sabai();
        $defaults = array();
        if ($sabai->isAddonLoaded('MyCRED')) { // needs this since MyCRED will autoload this class file even when MyCRED add-on is inactive
            foreach ($sabai->MyCRED_Hooks() as $hook_category => $hook) {
                $hook_category_lc = strtolower($hook_category);
                foreach ($hook['references'] as $ref_name => $ref) {
                    $defaults['sabai_' . $hook_category_lc . '_' . $ref_name] = array(
                        'creds' => isset($ref['default_credits']) ? $ref['default_credits'] : 1,
                        'limit' => isset($ref['default_limit']) ? $ref['default_limit'] : '0/x',
                        'log' => $hook_category . ' - ' . $ref['label'],
                    );
                }
                $this->_actions[$hook_category] = $hook['actions'];
            }
        }
        parent::__construct(
            array(
                'id' => 'hook_sabai',
                'defaults' => $defaults,
            ),
            $hook_prefs,
            $type
        );
    }

    public function run()
    {
        foreach ($this->_actions as $hook_category => $actions) {
            foreach ($actions as $action_name => $action) {
                foreach ((array)$action['hook'] as $hook) {
                    add_action('sabai_' . $hook, array($this, 'hook__' . $hook_category . '__' . $action_name), 10, $action['num_args']);
                }
            }
        }
    }
    
    public function addCredits($category, $name, $refId, $userId = null, $data = '')
    {
        if (isset($userId) && empty($userId)) return $this;
        
        $hook = 'sabai_' . strtolower($category) . '_' . $name;
        if ($this->over_hook_limit($hook, $hook)) return $this;
        
        $this->core->add_creds(
            $hook,
            isset($userId) ? $userId : get_current_user_id(),
            $this->prefs[$hook]['creds'],
            $this->prefs[$hook]['log'],
            $refId,
            $data,
            $this->mycred_type
        );
        
        return $this;
    }
    
    public function deductCredits($category, $name, $refId, $userId = null, $data = '')
    {
        if (isset($userId) && empty($userId)) return $this;
        
        $hook = 'sabai_' . strtolower($category) . '_' . $name;
        $this->core->add_creds(
            $hook,
            isset($userId) ? $userId : get_current_user_id(),
            -1 * $this->prefs[$hook]['creds'],
            sprintf(__('%s (reversal)', 'sabai'), $this->prefs[$hook]['log']),
            $refId,
            $data,
            $this->mycred_type
        );
        
        return $this;
    }
    
    public function hasEntry($category, $name, $refId, $userId = null, $data = '')
    {
        $hook = 'sabai_' . strtolower($category) . '_' . $name;
        return $this->core->has_entry($hook, $refId, $userId, $data, $this->mycred_type);
    }
    
    public function __call($name, $args)
    {
        if (strpos($name, 'hook__') !== 0) return;
        
        $names = explode('__', $name);
        
        // Call general action hook
        get_sabai()->Action('mycred_hook', array(
            $this, // this hook object, for other add-ons to call MyCRED hook methods
            $names[1], // hook category name
            $names[2], // action name
            $args // action args
        ));
        
        // Call category specific action hook
        get_sabai()->Action('mycred_hook_' . strtolower($names[1]), array(
            $this, // this hook object, for other add-ons to call MyCRED hook methods
            $names[2], // action name
            $args // action args
        ));
    }
    
    public function preferences()
    {
        foreach ($this->prefs as $id => $prefs) {
            $this->_printPreferences($id, $prefs);
        }
    }
    
    protected function _printPreferences($id, $prefs)
    {
?>
<label for="<?php echo $this->field_id(array($id, 'creds'));?>" class="subheader"><?php echo $this->core->template_tags_general($prefs['log']);?></label>
<ol>
    <li>
        <div class="h2">
            <input type="text" name="<?php echo $this->field_name(array($id, 'creds'));?>" id="<?php echo $this->field_id(array($id, 'creds'));?>" value="<?php echo $this->core->number($prefs['creds']);?>" size="8" />
        </div>
    </li>
    <li class="empty">&nbsp;</li>
    <li>
        <label for="<?php echo $this->field_id(array($id, 'limit'));?>"><?php echo __('Limit', 'sabai');?></label>
        <?php echo $this->hook_limit_setting($this->field_name(array($id, 'limit')), $this->field_id(array($id, 'limit')), $prefs['limit']);?>
    </li>
    <li class="empty">&nbsp;</li>
    <li>
        <label for="<?php echo $this->field_id(array($id, 'log'));?>"><?php echo __('Log template', 'sabai');?></label>
        <div class="h2">
            <input type="text" name="<?php echo $this->field_name(array($id, 'log'));?>" id="<?php echo $this->field_id(array($id, 'log'));?>" value="<?php echo esc_attr($prefs['log']);?>" class="long" />
        </div>
        <span class="description"><?php echo $this->available_template_tags(array('general'));?></span>
    </li>
<?php if (isset($prefs['_log'])):?>
    <li class="empty">&nbsp;</li>
    <li>
        <label for="<?php echo $this->field_id(array($id, '_log'));?>"><?php echo __('Log template (reversal)', 'sabai');?></label>
        <div class="h2">
            <input type="text" name="<?php echo $this->field_name(array($id, '_log'));?>" id="<?php echo $this->field_id(array($id, '_log'));?>" value="<?php echo esc_attr($prefs['_log']);?>" class="long" />
        </div>
        <span class="description"><?php echo $this->available_template_tags(array('general'));?></span>
    </li>
<?php endif;?>
</ol>
<?php
    }
    
    public function sanitise_preferences($data)
    {
        foreach (array_keys($this->prefs) as $id) {
            if (isset($this->prefs[$id]['limit'])
                && isset($data[$id]['limit'])
                && isset($data[$id]['limit_by'])
            ){
                $limit = sanitize_text_field($data[$id]['limit']);
                if ($limit == '') $limit = 0;
                $data[$id]['limit'] = $limit . '/' . $data[$id]['limit_by'];
                unset($data[$id]['limit_by']);    
            }
        }
        return $data;                
    }
}

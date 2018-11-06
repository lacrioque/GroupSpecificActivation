<?php
/**
 * ####   Plugin for LimeSurvey v3
 * Keeps anyone not in the specified usergroup from actuivating a survey.
 *
 * @author Markus Flür <markus.fluer@limesurvey.org>
 * @license GPL3.0
 * @copyright 2018 LimeSurvey GmbH
 * @package LimeSurvey/Plugin
 */
class GroupSpecificActivation extends \LimeSurvey\PluginManager\PluginBase
{
    
    protected $storage = 'DbStorage';
       
    static protected $description = 'Core: Allow the activation of surveys only for a specific usergroup';
    static protected $name = 'Group specific survey activation';
    
    protected $settings = array (
        'usergroup' => array(
            'type' => 'select',
            'options' => [
                '' => 'Everyone'
            ],
            'label' => 'Usergroup with activation right',
            'help' => 'Users in this group will have the right to activate a survey. All others won\'t',
        ),
        'erroractivatemessage' => array(
            'type' => 'text',
            'label' => 'Errormessage when activation fails',
            'help' => 'When the activation fails, this message will be shown, leave empty for a pre-generated message',
        ),
        'errordeactivatemessage' => array(
            'type' => 'text',
            'label' => 'Errormessage when deactivation fails',
            'help' => 'When the deactivation fails, this message will be shown, leave empty for a pre-generated message',
        )
    );

    public function init()
    {
        
        /**
         * Here you should handle subscribing to the events your plugin will handle
         */
        $this->subscribe('beforeSurveyActivate');
        $this->subscribe('beforeSurveyDeactivate');
    }

    public function beforeSurveyActivate(){
        $event = $this->getEvent();
        if (!$this->isInAllowedUserGroup())
        {
            $event->set('success', false);
            $message = $this->get('erroractivatemessage', null, null, '');
            $message = $message === '' ? gT('You do not have the right to activate/deactivate this survey. Please contact an administrator.') : $message;
            $event->set('message', $message);
        }
    }

    public function beforeSurveyDeactivate(){
        $event = $this->getEvent();
        if (!$this->isInAllowedUserGroup())
        {
            $event->set('success', false);
            $message = $this->get('errordeactivatemessage', null, null, '');
            $message = $message === '' ? gT('You do not have the right to activate/deactivate this survey. Please contact an administrator.') : $message;
            $event->set('message', $message);
        }
    }

    private function isInAllowedUserGroup() {
        //Superadmin are always alloed for everything
        if (Permission::model()->hasGlobalPermission('superadmin', 'read')) {
            return true;
        }

        $iUserId = Yii::app()->user->id;
        $iUserGroupId = $this->get('usergroup', null, null, '');

        //If no group is set everyone has the right to activate
        if($iUserGroupId === null) { return true; }

        //Pull the connector table values, if no entry found in the table, the call will return null
        $userInGroup = UserInGroup::model()->findByAttributes(['ugid'=>$iUserGroupId, 'uid'=>$iUserId]);

        if ($userInGroup !== null) {
            return true;
        }
        
        return false;
    }

     /**
     * Modified getPluginSettings
     *
     * @param boolean $getValues
     * @return array
     */
    public function getPluginSettings($getValues = true) {
        $aPluginSettings = parent::getPluginSettings($getValues);
        $oGetUserGroups = UserGroup::model()->searchMine(0);
        $oGetUserGroups->setPagination(false);
        $aUserGroups = $oGetUserGroups->getData();
        array_walk($aUserGroups, function($oUserGroup) use (&$aPluginSettings) {
            $aPluginSettings['usergroup']['options'][$oUserGroup->ugid] = $oUserGroup->name; 
        });
        return $aPluginSettings;
    }

}
    
<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

abstract class NotificationTargetCommonITILObject extends NotificationTarget {

   /**
    * @param $entity          (default '')
    * @param $event           (default '')
    * @param $object          (default null)
    * @param $options   array
   **/
   function __construct($entity='', $event='', $object=null, $options=array()) {

      parent::__construct($entity, $event, $object, $options);

      // For compatibility
      $this->options['sendprivate'] = true;
   }

   /**
    * Get events related to Itil Object
    *
    * @since 9.2
    *
    * @return array of events (event key => event label)
   **/
   function getEvents() {

      $events = array('requester_user'    => __('New user in requesters'),
                      'requester_group'   => __('New group in requesters'),
                      'observer_user'     => __('New user in observers'),
                      'observer_group'    => __('New group in observers'),
                      'assign_user'       => __('New user in assignees'),
                      'assign_group'      => __('New group in assignees'),
                      'assign_supplier'   => __('New supplier in assignees'));

      asort($events);
      return $events;
   }


   /**
    * Add linked users to the notified users list
    *
    * @param integer $type type of linked users
    *
    * @return void
    */
   function addLinkedUserByType($type) {
      global $DB, $CFG_GLPI;

      $userlinktable = getTableForItemType($this->obj->userlinkclass);
      $fkfield       = $this->obj->getForeignKeyField();

      //Look for the user by his id
      $query =        $this->getDistinctUserSql().",
                      `$userlinktable`.`use_notification` AS notif,
                      `$userlinktable`.`alternative_email` AS altemail
               FROM `$userlinktable`
               LEFT JOIN `glpi_users` ON (`$userlinktable`.`users_id` = `glpi_users`.`id`)".
               $this->getProfileJoinSql()."
               WHERE `$userlinktable`.`$fkfield` = '".$this->obj->fields["id"]."'
                     AND `$userlinktable`.`type` = '$type'";

      foreach ($DB->request($query) as $data) {
         //Add the user email and language in the notified users list
         if ($data['notif']) {
            $author_email = UserEmail::getDefaultForUser($data['users_id']);
            $author_lang  = $data["language"];
            $author_id    = $data['users_id'];

            if (!empty($data['altemail'])
                && ($data['altemail'] != $author_email)
                && NotificationMail::isUserAddressValid($data['altemail'])) {
               $author_email = $data['altemail'];
            }
            if (empty($author_lang)) {
               $author_lang = $CFG_GLPI["language"];
            }
            if (empty($author_id)) {
               $author_id = -1;
            }

            if ($this->isMailMode()) {
               $this->addToAddressesList(array('email'    => $author_email,
                                             'language' => $author_lang,
                                             'users_id' => $author_id));
            } else {
               $this->addToIdList(array('language' => $author_lang,
                                        'users_id' => $author_id));
            }
         }
      }

      // Anonymous user
      $query = "SELECT `alternative_email`
                FROM `$userlinktable`
                WHERE `$userlinktable`.`$fkfield` = '".$this->obj->fields["id"]."'
                      AND `$userlinktable`.`users_id` = 0
                      AND `$userlinktable`.`use_notification` = 1
                      AND `$userlinktable`.`type` = '$type'";
      foreach ($DB->request($query) as $data) {
         if ($this->isMailMode()) {
            if (NotificationMail::isUserAddressValid($data['alternative_email'])) {
               $this->addToAddressesList(array('email'    => $data['alternative_email'],
                                               'language' => $CFG_GLPI["language"],
                                               'users_id' => -1));
            }
         }
      }
   }


   /**
    * Add linked group to the notified user list
    *
    * @param integer $type type of linked groups
    *
    * @return void
    */
   function addLinkedGroupByType($type) {
      global $DB;

      $grouplinktable = getTableForItemType($this->obj->grouplinkclass);
      $fkfield        = $this->obj->getForeignKeyField();

      //Look for the user by his id
      $query = "SELECT `groups_id`
                FROM `$grouplinktable`
                WHERE `$grouplinktable`.`$fkfield` = '".$this->obj->fields["id"]."'
                      AND `$grouplinktable`.`type` = '$type'";

      foreach ($DB->request($query) as $data) {
         //Add the group in the notified users list
         $this->addForGroup(0, $data['groups_id']);
      }
   }



   /**
    * Add linked group without supervisor to the notified user list
    *
    * @since version 0.84.1
    *
    * @param integer $type type of linked groups
    *
    * @return void
    */
   function addLinkedGroupWithoutSupervisorByType($type) {
      global $DB;

      $grouplinktable = getTableForItemType($this->obj->grouplinkclass);
      $fkfield        = $this->obj->getForeignKeyField();

      $query = "SELECT `groups_id`
                FROM `$grouplinktable`
                WHERE `$grouplinktable`.`$fkfield` = '".$this->obj->fields["id"]."'
                      AND `$grouplinktable`.`type` = '$type'";

      foreach ($DB->request($query) as $data) {
         //Add the group in the notified users list
         $this->addForGroup(2, $data['groups_id']);
      }
   }


   /**
    * Add linked group supervisor to the notified user list
    *
    * @param integer $type type of linked groups
    *
    * @return void
    */
   function addLinkedGroupSupervisorByType($type) {
      global $DB;

      $grouplinktable = getTableForItemType($this->obj->grouplinkclass);
      $fkfield        = $this->obj->getForeignKeyField();

      $query = "SELECT `groups_id`
                FROM `$grouplinktable`
                WHERE `$grouplinktable`.`$fkfield` = '".$this->obj->fields["id"]."'
                      AND `$grouplinktable`.`type` = '$type'";

      foreach ($DB->request($query) as $data) {
         //Add the group in the notified users list
         $this->addForGroup(1, $data['groups_id']);
      }
   }


   /**
    * Get the email of the item's user : Overloaded manual address used
   **/
   function addItemAuthor() {
      $this->addLinkedUserByType(CommonITILActor::REQUESTER);
   }


   /**
    * Add previous technician in charge (before reassign)
    *
    * @return void
    */
   function addOldAssignTechnician() {
      global $CFG_GLPI;

      if (isset($this->options['_old_user'])
           && ($this->options['_old_user']['type'] == CommonITILActor::ASSIGN)
           && $this->options['_old_user']['use_notification']) {

         $user = new User();
         $user->getFromDB($this->options['_old_user']['users_id']);

         $author_email = UserEmail::getDefaultForUser($user->fields['id']);
         $author_lang  = $user->fields["language"];
         $author_id    = $user->fields['id'];

         if (!empty($this->options['_old_user']['alternative_email'])
             && ($this->options['_old_user']['alternative_email'] != $author_email)
             && NotificationMail::isUserAddressValid($this->options['_old_user']['alternative_email'])) {

            $author_email = $this->options['_old_user']['alternative_email'];
         }
         if (empty($author_lang)) {
            $author_lang = $CFG_GLPI["language"];
         }
         if (empty($author_id)) {
            $author_id = -1;
         }
         if ($this->isMailMode()) {
            $this->addToAddressesList(array('email'    => $author_email,
                                            'language' => $author_lang,
                                            'users_id' => $author_id));
         } else if ($author_id != -1) {
            $this->addToIdList(array('language' => $author_lang,
                                     'users_id' => $author_id));
         }
      }
   }


   /**
    * Add recipient
    *
    * @return void
    */
   function addRecipientAddress() {
      return $this->addUserByField("users_id_recipient");
   }


   /**
    * Get supplier related to the ITIL object
    *
    * @param boolean $sendprivate (false by default)
    *
    * @return void
    */
   function addSupplier($sendprivate=false) {
      global $DB;

      if (!$sendprivate
         && $this->obj->countSuppliers(CommonITILActor::ASSIGN)
         && $this->isMailMode()) {

         $supplierlinktable = getTableForItemType($this->obj->supplierlinkclass);
         $fkfield           = $this->obj->getForeignKeyField();

         $query = "SELECT DISTINCT `glpi_suppliers`.`email` AS email,
                                   `glpi_suppliers`.`name` AS name
                   FROM `$supplierlinktable`
                   LEFT JOIN `glpi_suppliers`
                     ON (`$supplierlinktable`.`suppliers_id` = `glpi_suppliers`.`id`)
                   WHERE `$supplierlinktable`.`$fkfield` = '".$this->obj->getID()."'";

         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data);
         }
      }
   }


   /**
    * Get Add approver related to the ITIL object validation
    *
    * @param $options array
    *
    * @return void
    */
   function addValidationApprover($options=array()) {
      global $DB;

      if (isset($options['validation_id'])) {
         $validationtable = getTableForItemType($this->obj->getType().'Validation');

         $query = $this->getDistinctUserSql()."
                  FROM `$validationtable`
                  LEFT JOIN `glpi_users`
                        ON (`glpi_users`.`id` = `$validationtable`.`users_id_validate`)".
                  $this->getProfileJoinSql()."
                  WHERE `$validationtable`.`id` = '".$options['validation_id']."'";

         foreach ($DB->request($query) as $data) {
            if ($this->isMailMode()) {
               $this->addToAddressesList($data);
            } else {
               $this->addToIdList($data);
            }
         }
      }
   }

   /**
    * Add requester related to the ITIL object validation
    *
    * @param array $options Options
    *
    * @return void
   **/
   function addValidationRequester($options=array()) {
      global $DB;

      if (isset($options['validation_id'])) {
         $validationtable = getTableForItemType($this->obj->getType().'Validation');

         $query = $this->getDistinctUserSql()."
                  FROM `$validationtable`
                  LEFT JOIN `glpi_users`
                        ON (`glpi_users`.`id` = `$validationtable`.`users_id`)".
                  $this->getProfileJoinSql()."
                  WHERE `$validationtable`.`id` = '".$options['validation_id']."'";

         foreach ($DB->request($query) as $data) {
            if ($this->isMailMode()) {
               $this->addToAddressesList($data);
            } else {
               $this->addToIdList($data);
            }
         }
      }
   }


   /**
    * Add author related to the followup
    *
    * @param array $options Options
    *
    * @return void
    */
   function addFollowupAuthor($options=array()) {
      global $DB;

      if (isset($options['followup_id'])) {
         $followuptable = getTableForItemType($this->obj->getType().'Followup');

         $query = $this->getDistinctUserSql()."
                  FROM `$followuptable`
                  INNER JOIN `glpi_users`
                        ON (`glpi_users`.`id` = `$followuptable`.`users_id`)".
                  $this->getProfileJoinSql()."
                  WHERE `$followuptable`.`id` = '".$options['followup_id']."'";

         foreach ($DB->request($query) as $data) {
            if ($this->isMailMode()) {
               $this->addToAddressesList($data);
            } else {
               $this->addToIdList($data);
            }
         }
      }
   }


   /**
    * Add task author
    *
    * @param array $options Options
    *
    * @return void
    */
   function addTaskAuthor($options=array()) {
      global $DB;

            // In case of delete task pass user id
      if (isset($options['task_users_id'])) {
         $query = $this->getDistinctUserSql()."
                  FROM `glpi_users` ".
                  $this->getProfileJoinSql()."
                  WHERE `glpi_users`.`id` = '".$options['task_users_id']."'";

         foreach ($DB->request($query) as $data) {
            if ($this->isMailMode()) {
               $this->addToAddressesList($data);
            } else {
               $this->addToIdList($data);
            }
         }
      } else if (isset($options['task_id'])) {
         $tasktable = getTableForItemType($this->obj->getType().'Task');

         $query = $this->getDistinctUserSql()."
                  FROM `$tasktable`
                  INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `$tasktable`.`users_id`)".
                  $this->getProfileJoinSql()."
                  WHERE `$tasktable`.`id` = '".$options['task_id']."'";

         foreach ($DB->request($query) as $data) {
            if ($this->isMailMode()) {
               $this->addToAddressesList($data);
            } else {
               $this->addToIdList($data);
            }
         }
      }
   }


   /**
    * Add user assigned to task
    *
    * @param array $options Options
    *
    * @return void
    */
   function addTaskAssignUser($options=array()) {
      global $DB;

      // In case of delete task pass user id
      if (isset($options['task_users_id_tech'])) {
         $query = $this->getDistinctUserSql()."
                  FROM `glpi_users` ".
                  $this->getProfileJoinSql()."
                  WHERE `glpi_users`.`id` = '".$options['task_users_id_tech']."'";

         foreach ($DB->request($query) as $data) {
            if ($this->isMailMode()) {
               $this->addToAddressesList($data);
            } else {
               $this->addToIdList($data);
            }
         }
      } else if (isset($options['task_id'])) {
         $tasktable = getTableForItemType($this->obj->getType().'Task');

         $query = $this->getDistinctUserSql()."
                  FROM `$tasktable`
                  INNER JOIN `glpi_users`
                        ON (`glpi_users`.`id` = `$tasktable`.`users_id_tech`)".
                  $this->getProfileJoinSql()."
                  WHERE `$tasktable`.`id` = '".$options['task_id']."'";

         foreach ($DB->request($query) as $data) {
            if ($this->isMailMode()) {
               $this->addToAddressesList($data);
            } else {
               $this->addToIdList($data);
            }
         }
      }
   }


   /**
    * Add group assigned to the task
    *
    * @since version 9.1
    *
    * @param array $options Options
    *
    * @return void
    */
   function addTaskAssignGroup($options=array()) {
      global $DB;

      // In case of delete task pass user id
      if (isset($options['task_groups_id_tech'])) {
         $this->addForGroup(0, $options['task_groups_id_tech']);

      } else if (isset($options['task_id'])) {
         $tasktable = getTableForItemType($this->obj->getType().'Task');
         foreach ($DB->request(array($tasktable, 'glpi_groups'),
                               "`glpi_groups`.`id` = `$tasktable`.`groups_id_tech`
                                AND `$tasktable`.`id` = '".$options['task_id']."'") as $data) {
            $this->addForGroup(0, $data['groups_id_tech']);
         }
      }
   }


   /**
    * Add additionnals targets for ITIL objects
    *
    * @param string $event specif event to get additional targets (default '')
    *
    * @return void
   **/
   function addAdditionalTargets($event='') {

      if ($event=='update') {
         $this->addTarget(Notification::OLD_TECH_IN_CHARGE,
                          __('Former technician in charge of the ticket'));
      }

      if ($event=='satisfaction') {
         $this->addTarget(Notification::AUTHOR, __('Requester'));
         $this->addTarget(Notification::RECIPIENT, __('Writer'));
      } else if ($event!='alertnotclosed') {
         $this->addTarget(Notification::RECIPIENT, __('Writer'));
         $this->addTarget(Notification::SUPPLIER, __('Supplier'));
         $this->addTarget(Notification::SUPERVISOR_ASSIGN_GROUP,
                          __('Manager of the group in charge of the ticket'));
         $this->addTarget(Notification::ASSIGN_GROUP_WITHOUT_SUPERVISOR,
                          __("Group in charge of the ticket except manager users"));
         $this->addTarget(Notification::SUPERVISOR_REQUESTER_GROUP, __('Requester group manager'));
         $this->addTarget(Notification::REQUESTER_GROUP_WITHOUT_SUPERVISOR,
                          __("Requester group except manager users"));
         $this->addTarget(Notification::ITEM_TECH_IN_CHARGE,
                          __('Technician in charge of the hardware'));
         $this->addTarget(Notification::ITEM_TECH_GROUP_IN_CHARGE,
                          __('Group in charge of the hardware'));
         $this->addTarget(Notification::ASSIGN_TECH, __('Technician in charge of the ticket'));
         $this->addTarget(Notification::REQUESTER_GROUP, __('Requester group'));
         $this->addTarget(Notification::AUTHOR, __('Requester'));
         $this->addTarget(Notification::ITEM_USER, __('Hardware user'));
         $this->addTarget(Notification::ASSIGN_GROUP, __('Group in charge of the ticket'));
         $this->addTarget(Notification::OBSERVER_GROUP, __('Watcher group'));
         $this->addTarget(Notification::OBSERVER, __('Watcher'));
         $this->addTarget(Notification::SUPERVISOR_OBSERVER_GROUP, __('Watcher group manager'));
         $this->addTarget(Notification::OBSERVER_GROUP_WITHOUT_SUPERVISOR,
                          __("Watcher group except manager users"));
      }

      if (($event == 'validation') || ($event == 'validation_answer')) {
         $this->addTarget(Notification::VALIDATION_REQUESTER, __('Approval requester'));
         $this->addTarget(Notification::VALIDATION_APPROVER, __('Approver'));
      }

      if (($event == 'update_task') || ($event == 'add_task') || ($event == 'delete_task')) {
         $this->addTarget(Notification::TASK_ASSIGN_TECH, __('Technician in charge of the task'));
         $this->addTarget(Notification::TASK_ASSIGN_GROUP, __('Group in charge of the task'));
         $this->addTarget(Notification::TASK_AUTHOR, __('Task author'));
      }

      if (($event == 'update_followup')
          || ($event == 'add_followup')
          || ($event == 'delete_followup')) {
         $this->addTarget(Notification::FOLLOWUP_AUTHOR, __('Followup author'));
      }
   }


   /**
    * Get specifics targets for ITIL objects
    *
    * @param array $data    Data
    * @param array $options Options
    *
    * @return void
   **/
   function addSpecificTargets($data, $options) {

      //Look for all targets whose type is Notification::ITEM_USER
      switch ($data['type']) {
         case Notification::USER_TYPE :

            switch ($data['items_id']) {
               case Notification::ASSIGN_TECH :
                  $this->addLinkedUserByType(CommonITILActor::ASSIGN);
                  break;

               //Send to the supervisor of group in charge of the ITIL object
               case Notification::SUPERVISOR_ASSIGN_GROUP :
                  $this->addLinkedGroupSupervisorByType(CommonITILActor::ASSIGN);
                  break;

               //Notification to the group in charge of the ITIL object without supervisor
               case Notification::ASSIGN_GROUP_WITHOUT_SUPERVISOR :
                  $this->addLinkedGroupWithoutSupervisorByType(CommonITILActor::ASSIGN);
                  break;

               //Send to the user who's got the issue
               case Notification::RECIPIENT :
                  $this->addRecipientAddress();
                  break;

               //Send to the supervisor of the requester's group
               case Notification::SUPERVISOR_REQUESTER_GROUP :
                  $this->addLinkedGroupSupervisorByType(CommonITILActor::REQUESTER);
                  break;

               //Send to the technician previously in charge of the ITIL object (before reassignation)
               case Notification::OLD_TECH_IN_CHARGE :
                  $this->addOldAssignTechnician();
                  break;

               //Assign to a supplier
               case Notification::SUPPLIER :
                  $this->addSupplier($this->options['sendprivate']);
                  break;

               case Notification::REQUESTER_GROUP :
                  $this->addLinkedGroupByType(CommonITILActor::REQUESTER);
                  break;

               //Notification to the requester group without supervisor
               case Notification::REQUESTER_GROUP_WITHOUT_SUPERVISOR :
                  $this->addLinkedGroupWithoutSupervisorByType(CommonITILActor::REQUESTER);
                  break;

               case Notification::ASSIGN_GROUP :
                  $this->addLinkedGroupByType(CommonITILActor::ASSIGN);
                  break;

               //Send to the ITIL object validation approver
               case Notification::VALIDATION_APPROVER :
                  $this->addValidationApprover($options);
                  break;

               //Send to the ITIL object validation requester
               case Notification::VALIDATION_REQUESTER :
                  $this->addValidationRequester($options);
                  break;

               //Send to the ITIL object followup author
               case Notification::FOLLOWUP_AUTHOR :
                  $this->addFollowupAuthor($options);
                  break;

               //Send to the ITIL object followup author
               case Notification::TASK_AUTHOR :
                  $this->addTaskAuthor($options);
                  break;

               //Send to the ITIL object followup author
               case Notification::TASK_ASSIGN_TECH :
                  $this->addTaskAssignUser($options);
                  break;

               //Send to the ITIL object task group assigned
               case Notification::TASK_ASSIGN_GROUP :
                  $this->addTaskAssignGroup($options);
                  break;

               //Notification to the ITIL object's observer group
               case Notification::OBSERVER_GROUP :
                  $this->addLinkedGroupByType(CommonITILActor::OBSERVER);
                  break;

               //Notification to the ITIL object's observer user
               case Notification::OBSERVER :
                  $this->addLinkedUserByType(CommonITILActor::OBSERVER);
                  break;

               //Notification to the supervisor of the ITIL object's observer group
               case Notification::SUPERVISOR_OBSERVER_GROUP :
                  $this->addLinkedGroupSupervisorByType(CommonITILActor::OBSERVER);
                  break;

               //Notification to the observer group without supervisor
               case Notification::OBSERVER_GROUP_WITHOUT_SUPERVISOR :
                  $this->addLinkedGroupWithoutSupervisorByType(CommonITILActor::OBSERVER);
                  break;

            }
      }
   }


   function addDataForTemplate($event, $options=array()) {
      global $CFG_GLPI;

      $events    = $this->getAllEvents();
      $objettype = strtolower($this->obj->getType());

      // Get datas from ITIL objects
      if ($event != 'alertnotclosed') {
         $this->datas = $this->getDatasForObject($this->obj, $options);

      } else {
         if (isset($options['entities_id'])
             && isset($options['items'])) {
            $entity = new Entity();
            if ($entity->getFromDB($options['entities_id'])) {
               $this->datas["##$objettype.entity##"]      = $entity->getField('completename');
               $this->datas["##$objettype.shortentity##"] = $entity->getField('name');
            }
            if ($item = getItemForItemtype($objettype)) {
               $objettypes = Toolbox::strtolower(getPlural($objettype));
               $items      = array();
               foreach ($options['items'] as $object) {
                  $item->getFromDB($object['id']);
                  $tmp = $this->getDatasForObject($item, $options, true);
                  $this->datas[$objettypes][] = $tmp;
               }
            }
         }
      }

      if (($event == 'validation')
          && isset($options['validation_status'])) {
         $this->datas["##$objettype.action##"]
                     //TRANS: %s id of the approval's state
                     = sprintf(__('%1$s - %2$s'), __('Approval'),
                               TicketValidation::getStatus($options['validation_status']));
      } else {
         $this->datas["##$objettype.action##"] = $events[$event];
      }

      $this->getTags();

      foreach ($this->tag_descriptions[parent::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }

   }


   /**
    * @param $item            CommonDBTM object
    * @param $options   array
    * @param $simple          (false by default)
   **/
   function getDatasForObject(CommonDBTM $item, array $options, $simple=false) {
      global $CFG_GLPI, $DB;

      $objettype = strtolower($item->getType());

      $datas["##$objettype.title##"]        = $item->getField('name');
      $datas["##$objettype.content##"]      = $item->getField('content');
      $datas["##$objettype.description##"]  = $item->getField('content');
      $datas["##$objettype.id##"]           = sprintf("%07d", $item->getField("id"));

      $datas["##$objettype.url##"]
                        = $this->formatURL($options['additionnaloption']['usertype'],
                                           $objettype."_".$item->getField("id"));

      $tab = '$2';
      if ($_SESSION['glpiticket_timeline'] == 1) {
         $tab = '$1';
      }
      $datas["##$objettype.urlapprove##"]
                           = $this->formatURL($options['additionnaloption']['usertype'],
                                              $objettype."_".$item->getField("id")."_".
                                                        $item->getType().$tab);

      $entity = new Entity();
      if ($entity->getFromDB($this->getEntity())) {
         $datas["##$objettype.entity##"]          = $entity->getField('completename');
         $datas["##$objettype.shortentity##"]     = $entity->getField('name');
         $datas["##$objettype.entity.phone##"]    = $entity->getField('phonenumber');
         $datas["##$objettype.entity.fax##"]      = $entity->getField('fax');
         $datas["##$objettype.entity.website##"]  = $entity->getField('website');
         $datas["##$objettype.entity.email##"]    = $entity->getField('email');
         $datas["##$objettype.entity.address##"]  = $entity->getField('address');
         $datas["##$objettype.entity.postcode##"] = $entity->getField('postcode');
         $datas["##$objettype.entity.town##"]     = $entity->getField('town');
         $datas["##$objettype.entity.state##"]    = $entity->getField('state');
         $datas["##$objettype.entity.country##"]  = $entity->getField('country');
      }

      $datas["##$objettype.storestatus##"]  = $item->getField('status');
      $datas["##$objettype.status##"]       = $item->getStatus($item->getField('status'));

      $datas["##$objettype.urgency##"]      = $item->getUrgencyName($item->getField('urgency'));
      $datas["##$objettype.impact##"]       = $item->getImpactName($item->getField('impact'));
      $datas["##$objettype.priority##"]     = $item->getPriorityName($item->getField('priority'));
      $datas["##$objettype.time##"]         = $item->getActionTime($item->getField('actiontime'));

      $datas["##$objettype.creationdate##"] = Html::convDateTime($item->getField('date'));
      $datas["##$objettype.closedate##"]    = Html::convDateTime($item->getField('closedate'));
      $datas["##$objettype.solvedate##"]    = Html::convDateTime($item->getField('solvedate'));
      $datas["##$objettype.duedate##"]      = Html::convDateTime($item->getField('due_date'));

      $datas["##$objettype.category##"] = '';
      if ($item->getField('itilcategories_id')) {
         $datas["##$objettype.category##"]
                              = Dropdown::getDropdownName('glpi_itilcategories',
                                                          $item->getField('itilcategories_id'));
      }

      $datas["##$objettype.authors##"] = '';
      $datas['authors']                = array();
      if ($item->countUsers(CommonITILActor::REQUESTER)) {
         $users = array();
         foreach ($item->getUsers(CommonITILActor::REQUESTER) as $tmpusr) {
            $uid = $tmpusr['users_id'];
            $user_tmp = new User();
            if ($uid
                && $user_tmp->getFromDB($uid)) {
               $users[] = $user_tmp->getName();

               $tmp = array();
               $tmp['##author.id##']   = $uid;
               $tmp['##author.name##'] = $user_tmp->getName();

               if ($user_tmp->getField('locations_id')) {
                  $tmp['##author.location##']
                                    = Dropdown::getDropdownName('glpi_locations',
                                                                $user_tmp->getField('locations_id'));
               } else {
                  $tmp['##author.location##'] = '';
               }

               if ($user_tmp->getField('usertitles_id')) {
                  $tmp['##author.title##']
                                    = Dropdown::getDropdownName('glpi_usertitles',
                                                                $user_tmp->getField('usertitles_id'));
               } else {
                  $tmp['##author.title##'] = '';
               }

               if ($user_tmp->getField('usercategories_id')) {
                  $tmp['##author.category##']
                                    = Dropdown::getDropdownName('glpi_usercategories',
                                                                $user_tmp->getField('usercategories_id'));
               } else {
                  $tmp['##author.category##'] = '';
               }

               $tmp['##author.email##']  = $user_tmp->getDefaultEmail();
               $tmp['##author.mobile##'] = $user_tmp->getField('mobile');
               $tmp['##author.phone##']  = $user_tmp->getField('phone');
               $tmp['##author.phone2##'] = $user_tmp->getField('phone2');
               $datas['authors'][]       = $tmp;
            } else {
               // Anonymous users only in xxx.authors, not in authors
               $users[] = $tmpusr['alternative_email'];
            }
         }
         $datas["##$objettype.authors##"] = implode(', ', $users);
      }

      $datas["##$objettype.suppliers##"] = '';
      $datas['suppliers']              = [];
      if ($item->countSuppliers(CommonITILActor::ASSIGN)) {
         $suppliers = [];
         foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $tmpspplier) {
            $sid      = $tmpspplier['suppliers_id'];
            $supplier = new Supplier();
            if ($sid
                && $supplier->getFromDB($sid)) {
               $suppliers[] = $supplier->getName();

               $tmp = [];
               $tmp['##supplier.id##']       = $sid;
               $tmp['##supplier.name##']     = $supplier->getName();
               $tmp['##supplier.email##']    = $supplier->getField('email');
               $tmp['##supplier.phone##']    = $supplier->getField('phonenumber');
               $tmp['##supplier.fax##']      = $supplier->getField('fax');
               $tmp['##supplier.website##']  = $supplier->getField('website');
               $tmp['##supplier.email##']    = $supplier->getField('email');
               $tmp['##supplier.address##']  = $supplier->getField('address');
               $tmp['##supplier.postcode##'] = $supplier->getField('postcode');
               $tmp['##supplier.town##']     = $supplier->getField('town');
               $tmp['##supplier.state##']    = $supplier->getField('state');
               $tmp['##supplier.country##']  = $supplier->getField('country');
               $tmp['##supplier.comments##'] = $supplier->getField('comment');

               $tmp['##supplier.type##'] = '';
               if ($supplier->getField('suppliertypes_id')) {
                  $tmp['##supplier.type##']
                     = Dropdown::getDropdownName('glpi_suppliertypes',
                                                 $supplier->getField('suppliertypes_id'));
               }

               $datas['suppliers'][] = $tmp;
            }
         }
         $datas["##$objettype.suppliers##"] = implode(', ', $suppliers);
      }

      $datas["##$objettype.openbyuser##"] = '';
      if ($item->getField('users_id_recipient')) {
         $user_tmp = new User();
         $user_tmp->getFromDB($item->getField('users_id_recipient'));
         $datas["##$objettype.openbyuser##"] = $user_tmp->getName();
      }

      $datas["##$objettype.lastupdater##"] = '';
      if ($item->getField('users_id_lastupdater')) {
         $user_tmp = new User();
         $user_tmp->getFromDB($item->getField('users_id_lastupdater'));
         $datas["##$objettype.lastupdater##"] = $user_tmp->getName();
      }

      $datas["##$objettype.assigntousers##"] = '';
      if ($item->countUsers(CommonITILActor::ASSIGN)) {
         $users = array();
         foreach ($item->getUsers(CommonITILActor::ASSIGN) as $tmp) {
            $uid      = $tmp['users_id'];
            $user_tmp = new User();
            if ($user_tmp->getFromDB($uid)) {
               $users[$uid] = $user_tmp->getName();
            }
         }
         $datas["##$objettype.assigntousers##"] = implode(', ', $users);
      }

      $datas["##$objettype.assigntosupplier##"] = '';
      if ($item->countSuppliers(CommonITILActor::ASSIGN)) {
         $suppliers = array();
         foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $tmp) {
            $uid           = $tmp['suppliers_id'];
            $supplier_tmp  = new Supplier();
            if ($supplier_tmp->getFromDB($uid)) {
               $suppliers[$uid] = $supplier_tmp->getName();
            }
         }
         $datas["##$objettype.assigntosupplier##"] = implode(', ', $suppliers);
      }

      $datas["##$objettype.groups##"] = '';
      if ($item->countGroups(CommonITILActor::REQUESTER)) {
         $groups = array();
         foreach ($item->getGroups(CommonITILActor::REQUESTER) as $tmp) {
            $gid          = $tmp['groups_id'];
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $datas["##$objettype.groups##"] = implode(', ', $groups);
      }

      $datas["##$objettype.observergroups##"] = '';
      if ($item->countGroups(CommonITILActor::OBSERVER)) {
         $groups = array();
         foreach ($item->getGroups(CommonITILActor::OBSERVER) as $tmp) {
            $gid          = $tmp['groups_id'];
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $datas["##$objettype.observergroups##"] = implode(', ', $groups);
      }

      $datas["##$objettype.observerusers##"] = '';
      if ($item->countUsers(CommonITILActor::OBSERVER)) {
         $users = array();
         foreach ($item->getUsers(CommonITILActor::OBSERVER) as $tmp) {
            $uid      = $tmp['users_id'];
            $user_tmp = new User();
            if ($uid
                && $user_tmp->getFromDB($uid)) {
               $users[] = $user_tmp->getName();
            } else {
               $users[] = $tmp['alternative_email'];
            }
         }
         $datas["##$objettype.observerusers##"] = implode(', ', $users);
      }

      $datas["##$objettype.assigntogroups##"] = '';
      if ($item->countGroups(CommonITILActor::ASSIGN)) {
         $groups = array();
         foreach ($item->getGroups(CommonITILActor::ASSIGN) as $tmp) {
            $gid          = $tmp['groups_id'];
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $datas["##$objettype.assigntogroups##"] = implode(', ', $groups);
      }

      $datas["##$objettype.solution.type##"]='';
      if ($item->getField('solutiontypes_id')) {
         $datas["##$objettype.solution.type##"]
                              = Dropdown::getDropdownName('glpi_solutiontypes',
                                                          $item->getField('solutiontypes_id'));
      }

      $datas["##$objettype.solution.description##"]
                     = Toolbox::unclean_cross_side_scripting_deep($item->getField('solution'));

      // Complex mode
      if (!$simple) {
         $datas['log'] = array();
         // Use list_limit_max or load the full history ?
         foreach (Log::getHistoryData($item, 0, $CFG_GLPI['list_limit_max']) as $data) {
            $tmp                               = array();
            $tmp["##$objettype.log.date##"]    = $data['date_mod'];
            $tmp["##$objettype.log.user##"]    = $data['user_name'];
            $tmp["##$objettype.log.field##"]   = $data['field'];
            $tmp["##$objettype.log.content##"] = $data['change'];
            $datas['log'][]                    = $tmp;
         }

         $datas["##$objettype.numberoflogs##"] = count($datas['log']);

         // Get unresolved items
         $restrict = "`".$item->getTable()."`.`status`
                        NOT IN ('".implode("', '", array_merge($item->getSolvedStatusArray(),
                                                               $item->getClosedStatusArray())
                                          )."'
                               )";

         if ($item->maybeDeleted()) {
            $restrict .= " AND `".$item->getTable()."`.`is_deleted` = '0' ";
         }

         $datas["##$objettype.numberofunresolved##"]
               = countElementsInTableForEntity($item->getTable(), $this->getEntity(), $restrict);

         // Document
         $query = "SELECT `glpi_documents`.*
                   FROM `glpi_documents`
                   LEFT JOIN `glpi_documents_items`
                     ON (`glpi_documents`.`id` = `glpi_documents_items`.`documents_id`)
                   WHERE `glpi_documents_items`.`itemtype` =  '".$item->getType()."'
                         AND `glpi_documents_items`.`items_id` = '".$item->getField('id')."'";

         $datas["documents"] = array();
         $addtodownloadurl   = '';
         if ($item->getType() == 'Ticket') {
            $addtodownloadurl = "%2526tickets_id=".$item->fields['id'];
         }
         if ($result = $DB->query($query)) {
            while ($data = $DB->fetch_assoc($result)) {
               $tmp                      = array();
               $tmp['##document.id##']   = $data['id'];
               $tmp['##document.name##'] = $data['name'];
               $tmp['##document.weblink##']
                                         = $data['link'];

               $tmp['##document.url##']  = $this->formatURL($options['additionnaloption']['usertype'],
                                                            "document_".$data['id']);
               $downloadurl              = "/front/document.send.php?docid=".$data['id'];

               $tmp['##document.downloadurl##']
                                         = $this->formatURL($options['additionnaloption']['usertype'],
                                                            $downloadurl.$addtodownloadurl);
               $tmp['##document.heading##']
                                         = Dropdown::getDropdownName('glpi_documentcategories',
                                                                     $data['documentcategories_id']);

               $tmp['##document.filename##']
                                         = $data['filename'];

               $datas['documents'][]     = $tmp;
            }
         }

         $datas["##$objettype.urldocument##"]
                        = $this->formatURL($options['additionnaloption']['usertype'],
                                           $objettype."_".$item->getField("id").'_Document_Item$1');

         $datas["##$objettype.numberofdocuments##"]
                        = count($datas['documents']);

         //costs infos
         $costtype = $item->getType().'Cost';
         $costs    = $costtype::getCostsSummary($costtype, $item->getField("id"));

         $datas["##$objettype.costfixed##"]    = $costs['costfixed'];
         $datas["##$objettype.costmaterial##"] = $costs['costmaterial'];
         $datas["##$objettype.costtime##"]     = $costs['costtime'];
         $datas["##$objettype.totalcost##"]    = $costs['totalcost'];

         $restrict  = "`".$item->getForeignKeyField()."`='".$item->getField('id')."'";

         $restrict .= " ORDER BY `begin_date` DESC, `id` ASC";

         $costs          = getAllDatasFromTable(getTableForItemType($costtype), $restrict);
         $datas['costs'] = array();
         foreach ($costs as $cost) {
            $tmp = array();
            $tmp['##cost.name##']         = $cost['name'];
            $tmp['##cost.comment##']      = $cost['comment'];
            $tmp['##cost.datebegin##']    = Html::convDate($cost['begin_date']);
            $tmp['##cost.dateend##']      = Html::convDate($cost['end_date']);
            $tmp['##cost.time##']         = $item->getActionTime($cost['actiontime']);
            $tmp['##cost.costtime##']     = Html::formatNumber($cost['cost_time']);
            $tmp['##cost.costfixed##']    = Html::formatNumber($cost['cost_fixed']);
            $tmp['##cost.costmaterial##'] = Html::formatNumber($cost['cost_material']);
            $tmp['##cost.totalcost##']    = CommonITILCost::computeTotalCost($cost['actiontime'],
                                                                             $cost['cost_time'],
                                                                             $cost['cost_fixed'],
                                                                             $cost['cost_material']);
            $tmp['##cost.budget##']       = Dropdown::getDropdownName('glpi_budgets',
                                                                      $cost['budgets_id']);
            $datas['costs'][]             = $tmp;
         }
         $datas["##$objettype.numberofcosts##"] = count($datas['costs']);

         //Task infos
         $tasktype = $item->getType().'Task';
         $taskobj  = new $tasktype();
         $restrict = "`".$item->getForeignKeyField()."`='".$item->getField('id')."'";
         if ($taskobj->maybePrivate()
             && (!isset($options['additionnaloption']['show_private'])
                 || !$options['additionnaloption']['show_private'])) {
            $restrict .= " AND `is_private` = '0'";
         }
         $restrict .= " ORDER BY `date_mod` DESC, `id` ASC";

         $tasks          = getAllDatasFromTable($taskobj->getTable(), $restrict);
         $datas['tasks'] = array();
         foreach ($tasks as $task) {
            $tmp                          = array();
            $tmp['##task.id##']           = $task['id'];
            if ($taskobj->maybePrivate()) {
               $tmp['##task.isprivate##'] = Dropdown::getYesNo($task['is_private']);
            }
            $tmp['##task.author##']       = Html::clean(getUserName($task['users_id']));

            $tmp_taskcatinfo = Dropdown::getDropdownName('glpi_taskcategories',
                                                         $task['taskcategories_id'], true, true, false);
            $tmp['##task.categoryid##']      = $task['taskcategories_id'];
            $tmp['##task.category##']        = $tmp_taskcatinfo['name'];
            $tmp['##task.categorycomment##'] = $tmp_taskcatinfo['comment'];

            $tmp['##task.date##']         = Html::convDateTime($task['date']);
            $tmp['##task.description##']  = $task['content'];
            $tmp['##task.time##']         = Ticket::getActionTime($task['actiontime']);
            $tmp['##task.status##']       = Planning::getState($task['state']);

            $tmp['##task.user##']         = Html::clean(getUserName($task['users_id_tech']));
            $tmp['##task.group##']
               = Html::clean(Toolbox::clean_cross_side_scripting_deep(Dropdown::getDropdownName("glpi_groups",
                                                        $task['groups_id_tech'])), true, 2, false);
            $tmp['##task.begin##']        = "";
            $tmp['##task.end##']          = "";
            if (!is_null($task['begin'])) {
               $tmp['##task.begin##']     = Html::convDateTime($task['begin']);
               $tmp['##task.end##']       = Html::convDateTime($task['end']);
            }

            $datas['tasks'][]             = $tmp;
         }

         $datas["##$objettype.numberoftasks##"] = count($datas['tasks']);
      }
      return $datas;
   }

   function getTags() {

      $itemtype  = $this->obj->getType();
      $objettype = strtolower($itemtype);

      //Locales
      $tags = array($objettype.'.id'                    => __('ID'),
                    $objettype.'.title'                 => __('Title'),
                    $objettype.'.url'                   => __('URL'),
                    $objettype.'.category'              => __('Category'),
                    $objettype.'.content'               => __('Description'),
                    $objettype.'.description'           => sprintf(__('%1$s: %2$s'), __('Ticket'),
                                                                   __('Description')),
                    $objettype.'.status'                => __('Status'),
                    $objettype.'.urgency'               => __('Urgency'),
                    $objettype.'.impact'                => __('Impact'),
                    $objettype.'.priority'              => __('Priority'),
                    $objettype.'.time'                  => __('Total duration'),
                    $objettype.'.creationdate'          => __('Opening date'),
                    $objettype.'.closedate'             => __('Closing date'),
                    $objettype.'.solvedate'             => __('Date of solving'),
                    $objettype.'.duedate'               => __('Time to resolve'),
                    $objettype.'.authors'               => _n('Requester', 'Requesters', Session::getPluralNumber()),
                    'author.id'                         => __('Requester ID'),
                    'author.name'                       => __('Requester'),
                    'author.location'                   => __('Requester location'),
                    'author.mobile'                     => __('Mobile phone'),
                    'author.phone'                      => __('Phone'),
                    'author.phone2'                     => __('Phone 2'),
                    'author.email'                      => _n('Email', 'Emails', 1),
                    'author.title'                      => _x('person', 'Title'),
                    'author.category'                   => __('Category'),
                    $objettype.'.suppliers'             => _n('Supplier', 'Suppliers', Session::getPluralNumber()),
                    'supplier.id'                       => __('Supplier ID'),
                    'supplier.name'                     => __('Supplier'),
                    'supplier.phone'                    => __('Phone'),
                    'supplier.fax'                      => __('Fax'),
                    'supplier.website'                  => __('Website'),
                    'supplier.email'                    => __('Email'),
                    'supplier.address'                  => __('Address'),
                    'supplier.postcode'                 => __('Postal code'),
                    'supplier.town'                     => __('City'),
                    'supplier.state'                    => _x('location', 'State'),
                    'supplier.country'                  => __('Country'),
                    'supplier.comments'                 => _n('Comment', 'Comments', 2),
                    'supplier.type'                     => __('Third party type'),
                    $objettype.'.openbyuser'            => __('Writer'),
                    $objettype.'.lastupdater'           => __('Last updater'),
                    $objettype.'.assigntousers'         => __('Assigned to technicians'),
                    $objettype.'.assigntosupplier'      => __('Assigned to a supplier'),
                    $objettype.'.groups'                => _n('Requester group',
                                                              'Requester groups', Session::getPluralNumber()),
                    $objettype.'.observergroups'        => _n('Watcher group', 'Watcher groups', Session::getPluralNumber()),
                    $objettype.'.assigntogroups'        => __('Assigned to groups'),
                    $objettype.'.solution.type'         => __('Solution type'),
                    $objettype.'.solution.description'  => _n('Solution', 'Solutions', 1),
                    $objettype.'.observerusers'         => _n('Watcher', 'Watchers', Session::getPluralNumber()),
                    $objettype.'.action'                => _n('Event', 'Events', 1),
                    $objettype.'.numberofunresolved'    => __('Number of unresolved items'),
                    $objettype.'.numberofdocuments'     => _x('quantity', 'Number of documents'),
                    $objettype.'.costtime'              => __('Time cost'),
                    $objettype.'.costfixed'             => __('Fixed cost'),
                    $objettype.'.costmaterial'          => __('Material cost'),
                    $objettype.'.totalcost'             => __('Total cost'),
                    $objettype.'.numberofcosts'         => __('Number of costs'),
                    'cost.name'                         => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Name')),
                    'cost.comment'                      => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Comments')),
                    'cost.datebegin'                    => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Begin date')),
                    'cost.dateend'                      => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('End date')),
                    'cost.time'                         => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Duration')),
                    'cost.costtime'                     => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Time cost')),
                    'cost.costfixed'                    => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Fixed cost')),
                    'cost.costmaterial'                 => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Material cost')),
                    'cost.totalcost'                    => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Total cost')),
                    'cost.budget'                       => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Budget')),
                    'task.author'                       => __('Writer'),
                    'task.isprivate'                    => __('Private'),
                    'task.date'                         => __('Opening date'),
                    'task.description'                  => __('Description'),
                    'task.categoryid'                   => __('Category id'),
                    'task.category'                     => __('Category'),
                    'task.categorycomment'              => __('Category comment'),
                    'task.time'                         => __('Total duration'),
                    'task.user'                         => __('User assigned to task'),
                    'task.group'                        => __('Group assigned to task'),
                    'task.begin'                        => __('Start date'),
                    'task.end'                          => __('End date'),
                    'task.status'                       => __('Status'),
                    $objettype.'.numberoftasks'         => _x('quantity', 'Number of tasks'),
                    $objettype.'.entity.phone'          => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('Phone')),
                    $objettype.'.entity.fax'            => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('Fax')),
                    $objettype.'.entity.website'        => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('Website')),
                    $objettype.'.entity.email'          => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('Email')),
                    $objettype.'.entity.address'        => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('Address')),
                    $objettype.'.entity.postcode'       => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('Postal code')),
                    $objettype.'.entity.town'           => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('City')),
                    $objettype.'.entity.state'          => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), _x('location', 'State')),
                    $objettype.'.entity.country'        => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('Country')),
                   );

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => parent::TAG_FOR_ALL_EVENTS));
      }

      //Foreach global tags
      $tags = array('log'       => __('Historical'),
                    'tasks'     => _n('Task', 'Tasks', Session::getPluralNumber()),
                    'costs'     => _n('Cost', 'Costs', Session::getPluralNumber()),
                    'authors'   => _n('Requester', 'Requesters', Session::getPluralNumber()),
                    'suppliers' => _n('Supplier', 'Suppliers', Session::getPluralNumber()));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'     => $tag,
                                   'label'   => $label,
                                   'value'   => false,
                                   'foreach' => true));
      }

      //Tags with just lang
      $tags = array($objettype.'.days'               => _n('Day', 'Days', Session::getPluralNumber()),
                    $objettype.'.attribution'        => __('Assigned to'),
                    $objettype.'.entity'             => __('Entity'),
                    $objettype.'.nocategoryassigned' => __('No defined category'),
                    $objettype.'.log'                => __('Historical'),
                    $objettype.'.tasks'              => _n('Task', 'Tasks', Session::getPluralNumber()),
                    $objettype.'.costs'              => _n('Cost', 'Costs', Session::getPluralNumber()));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => false,
                                   'lang'  => true));
      }

      //Tags without lang
      $tags = array($objettype.'.urlapprove'     => __('Web link to approval the solution'),
                    $objettype.'.entity'         => sprintf(__('%1$s (%2$s)'),
                                                            __('Entity'), __('Complete name')),
                    $objettype.'.shortentity'    => sprintf(__('%1$s (%2$s)'),
                                                            __('Entity'), __('Name')),
                    $objettype.'.numberoflogs'   => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                            _x('quantity', 'Number of items')),
                    $objettype.'.log.date'       => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                            __('Date')),
                    $objettype.'.log.user'       => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                            __('User')),
                    $objettype.'.log.field'      => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                            __('Field')),
                    $objettype.'.log.content'    => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                            _x('name', 'Update')),
                    'document.url'               => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('URL')),
                    'document.downloadurl'       => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('Download URL')),
                    'document.heading'           => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('Heading')),
                    'document.id'                => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('ID')),
                    'document.filename'          => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('File')),
                    'document.weblink'           => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('Web Link')),
                    'document.name'              => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('Name')),
                     $objettype.'.urldocument'   => sprintf(__('%1$s: %2$s'),
                                                            _n('Document', 'Documents', Session::getPluralNumber()),
                                                            __('URL')));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false));
      }

      //Tickets with a fixed set of values
      $status         = $this->obj->getAllStatusArray(false);
      $allowed_ticket = array();
      foreach ($status as $key => $value) {
         $allowed_ticket[] = $key;
      }

      $tags = array($objettype.'.storestatus' => array('text'     => __('Status value in database'),
                                                       'allowed_values'
                                                                  => $allowed_ticket));
      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'            => $tag,
                                   'label'          => $label['text'],
                                   'value'          => true,
                                   'lang'           => false,
                                   'allowed_values' => $label['allowed_values']));
      }
   }

   /**
    * Add linked users to the notified users list
    *
    * @deprecated Use NotificationTargetCommonITILObject::addLinkedUserByType()
    *
    * @param integer $type type of linked users
    *
    * @return void
   **/
   function getLinkedUserByType($type) {
      Toolbox::logDebug('getLinkedUserByType() method is deprecated');
      $this->addLinkedUserByType($type);
   }

   /**
    * Add linked group to the notified user list
    *
    * @param integer $type type of linked groups
    *
    * @deprecated Use NotificationTargetCommonITILObject::addLinkedGroupByType()
    *
    * @return void
   **/
   function getLinkedGroupByType($type) {
      Toolbox::logDebug('getLinkedGroupByType() method is deprecated');
      $this->addLinkedGroupByType();
   }

   /**
    * Add linked group without supervisor to the notified user list
    *
    * @since version 0.84.1
    *
    * @param integer $type type of linked groups
    *
    * @deprecated Use NotificationTargetCommonITILObject::addLinkedGroupWithoutSupervisorByType()
    *
    * @return void
    */
   function getLinkedGroupWithoutSupervisorByType($type) {
      Toolbox::logDebug('getLinkedGroupWithoutSupervisorByType() method is deprecated');
      $this->addLinkedGroupWithoutSupervisorByType($type);
   }


   /**
    * Add linked group supervisor to the notified user list
    *
    * @param integer $type type of linked groups
    *
    * @deprecated Use NotificationTargetCommonITILObject::addLinkedGroupSupervisorByType()
    *
    * @return void
    */
   function getLinkedGroupSupervisorByType($type) {
      Toolbox::logDebug('getLinkedGroupSupervisorByType() method is deprecated');
      $this->addLinkedGroupSupervisorByType($type);
   }

   /**
    * Add recipient
    *
    * @deprecated Use NotificationTargetCommonITILObject::addRecipientAddress()
    *
    * @return void
    */
   function getRecipientAddress() {
      Toolbox::logDebug('getRecipientAddress() method is deprecated');
      return $this->addRecipientAddress();
   }

   /**
    * @deprecated Use NotificationTargetCommonITILObject::addOldAssignTechnician()
    *
    * @return void
    */
   function getOldAssignTechnicianAddress() {
      Toolbox::logDebug('getOldAssignTechnicianAddress() method is deprecated');
      $this->addOldAssignTechnician();
   }

   /**
    * Get supplier related to the ITIL object
    *
    * @param boolean $sendprivate (false by default)
    *
    * @deprecated Use NotificationTargetCommonITILObject::addSupplier()
    *
    * @return void
   **/
   function getSupplierAddress($sendprivate=false) {
      Toolbox::logDebug('getSupplierAddress() method is deprecated');
      $this->addSupplier($sendprivate);
   }

   /**
    * Get approuver related to the ITIL object validation
    *
    * @param array $options Options
    *
    * @deprecated Use NotificationTargetCommonITILObject::addValidationApprover()
    *
    * @return void
   **/
   function getValidationApproverAddress($options=array()) {
      Toolbox::logDebug('getValidationApproverAddress() method is deprecated');
      $this->addValidationApprover($options);
   }

   /**
    * Add requester related to the ITIL object validation
    *
    * @param array $options Options
    *
    * @return void
   **/
   function getValidationRequesterAddress($options=array()) {
      Toolbox::logDebug('getValidationRequesterAddress() method is deprecated');
      $this->addValidationRequester($options);
   }

   /**
    * Add author related to the followup
    *
    * @param array $options Options
    *
    * @return void
    */
   function getFollowupAuthor($options=array()) {
      Toolbox::logDebug('getFollowupAuthor() method is deprecated');
      $this->addFollowupAuthor($options);
   }

   /**
    * Add task author
    *
    * @param array $options Options
    *
    * @deprecated Use NotificationTargetCommonITILObject::addTaskAuthor()
    *
    * @return void
    */
   function getTaskAuthor($options=array()) {
      Toolbox::logDebug('getTaskAuthor() method is deprecated');
      $this->addTaskAuthor($options);
   }


   /**
    * Add user assigned to task
    *
    * @param array $options Options
    *
    * @deprecated Use NotificationTargetCommonITILObject::addTaskAssignUser()
    *
    * @return void
    */
   function getTaskAssignUser($options=array()) {
      Toolbox::logDebug('getTaskAssignUser() method is deprecated');
      $this->addTaskAssignUser($options);
   }


   /**
    * Add group assigned to the task
    *
    * @since version 9.1
    *
    * @param array $options Options
    *
    * @deprecated Use NotificationTargetCommonITILObject::addTaskAssignGroup()
    *
    * @return void
    */
   function getTaskAssignGroup($options=array()) {
      Toolbox::logDebug('getTaskAssignGroup() method is deprecated');
      $this->addTaskAssignGroup($options);
   }
}

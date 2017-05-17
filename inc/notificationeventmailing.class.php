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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class NotificationEventMailing implements NotificationEventInterface {

   /**
    * Raise a mail notification event
    *
    * @param string               $event              Event
    * @param CommonDBTM           $item               Item
    * @param array                $options            Options
    * @param string               $label              Label
    * @param array                $data               Notification data
    * @param NotificationTarget   $notificationtarget Target
    * @param NotificationTemplate $template           Template
    * @param boolean              $notify_me          Whether to notify current user
    *
    * @return void
    */
   static public function raise(
      $event,
      CommonDBTM $item,
      array $options,
      $label,
      array $data,
      NotificationTarget $notificationtarget,
      NotificationTemplate $template,
      $notify_me
   ) {
      global $CFG_GLPI;

      if ($CFG_GLPI['notifications_mailing']) {
         $entity = $notificationtarget->getEntity();
         $email_processed    = array();
         $email_notprocessed = array();

         $targets = getAllDatasFromTable(
            'glpi_notificationtargets',
            "notifications_id = {$data['id']}"
         );

         //Set notification's signature (the one which corresponds to the entity)
         $template->setSignature(Notification::getMailingSignature($entity));

         //Foreach notification targets
         foreach ($targets as $target) {
            //Get all users affected by this notification
            $notificationtarget->addForTarget($target, $options);

            foreach ($notificationtarget->getTargets() as $user_email => $users_infos) {
               if ($label
                     || $notificationtarget->validateSendTo($event, $users_infos, $notify_me)) {
                  //If the user have not yet been notified
                  if (!isset($email_processed[$users_infos['language']][$users_infos['email']])) {
                     //If ther user's language is the same as the template's one
                     if (isset($email_notprocessed[$users_infos['language']]
                                                   [$users_infos['email']])) {
                        unset($email_notprocessed[$users_infos['language']]
                                                   [$users_infos['email']]);
                     }
                     $options['item'] = $item;
                     if ($tid = $template->getTemplateByLanguage($notificationtarget,
                                                                  $users_infos, $event,
                                                                  $options)) {
                        //Send notification to the user
                        if ($label == '') {
                           $send_data = $template->getDataToSend(
                              $notificationtarget,
                              $tid,
                              $user_email,
                              $users_infos,
                              $options
                           );
                           $send_data['_notificationtemplates_id'] = $data['notificationtemplates_id'];
                           $send_data['_itemtype']                 = $item->getType();
                           $send_data['_items_id']                 = $item->getID();
                           $send_data['_entities_id']              = $entity;
                           $send_data['mode']                      = $data['mode'];

                           Notification::send($send_data);
                        } else {
                           $notificationtarget->getFromDB($target['id']);
                           echo "<tr class='tab_bg_2'><td>".$label."</td>";
                           echo "<td>".$notificationtarget->getNameID()."</td>";
                           echo "<td>".sprintf(__('%1$s (%2$s)'), $template->getName(),
                                                $users_infos['language'])."</td>";
                           echo "<td>".$options['mode']."</td>";
                           echo "<td>".$users_infos['email']."</td>";
                           echo "</tr>";
                        }
                        $email_processed[$users_infos['language']][$users_infos['email']]
                                                                  = $users_infos;

                     } else {
                        $email_notprocessed[$users_infos['language']][$users_infos['email']]
                                                                     = $users_infos;
                     }
                  }
               }
            }
         }

         unset($email_processed);
         unset($email_notprocessed);
      }
   }


   static public function getTargetField(&$data) {
      $field = 'email';

      if (!isset($data[$field])
         && isset($data['users_id'])) {
         // No email set : get default for user
         $data[$field] = UserEmail::getDefaultForUser($data['users_id']);
      }
      $data[$field] = trim(Toolbox::strtolower($data[$field]));

      if (empty($data[$field]) or !NotificationMailing::isUserAddressValid($data[$field])) {
         $data[$field] = null;
      }

      return $field;
   }


   static public function canCron() {
      return true;
   }


   static public function getAdminData() {
      global $CFG_GLPI;

      return [
         'email'     => $CFG_GLPI['admin_email'],
         'name'      => $CFG_GLPI['admin_email_name'],
         'language'  => $CFG_GLPI['language'],
         'usertype'  => self::getDefaultUserType()
      ];
   }


   static public function getEntityAdminsData($entity) {
      global $DB, $CFG_GLPI;

      $iterator = $DB->request([
         'FROM'   => 'glpi_entities',
         'WHERE'  => ['id' => $entity]
      ]);

      $admins = [];

      while ($row = $iterator->next()) {
         $admins[] = [
            'language'  => $CFG_GLPI['language'],
            'email'     => $row['admin_email'],
            'name'      => $row['admin_email_name']
         ];
      }

      return $admins;
   }
}

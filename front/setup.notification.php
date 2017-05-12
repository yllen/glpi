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

include ('../inc/includes.php');

Session::checkSeveralRightsOr(array('notification' => READ,
                                    'config'       => UPDATE));

Html::header(_n('Notification', 'Notifications', 2), $_SERVER['PHP_SELF'], "config", "notification");

if (!Session::haveRight("config", READ)
   && Session::haveRight("notification", READ)
   && ($CFG_GLPI['notifications_mailing'] || $CFG_GLPI['notifications_websockets'])) {
   Html::redirect($CFG_GLPI["root_doc"].'/front/notification.php');
}

$modes = NotificationTemplateTemplate::getModes();

if (isset($_POST['use_notifications'])) {
   $config             = new Config();
   $tmp = [
      'id'                 => 1,
      'use_notifications'  => $_POST['use_notifications']
   ];
   $config->update($tmp);
   //disable all notifications types if notifications has been disabled
   if ($tmp['use_notifications'] == 0) {
      foreach (array_keys($modes) as $mode) {
         $_POST['notifications_' . $mode] = 0;
      }
   }
}

if (count($_POST)) {
   $config = new Config();
   foreach ($_POST as $k => $v) {
      if (substr($k, 0, strlen('notifications_')) === 'notifications_') {
         $tmp = [
            'id'  => 1,
            $k    => $v
         ];
         $config->update($tmp);
      }
   }

   Html::back();
}

echo "<div class='center notifs_setup'>";

/** TODO:
 *    - get forms from settings classes
 *    - add hook for plugins to add their own settings
 */

if (Session::haveRight("config", UPDATE)) {
   echo "<form method='POST' action='{$CFG_GLPI['root_doc']}/front/setup.notification.php'>";

   echo "<table class='tab_cadre'>";
   echo "<tr><th colspan='3'>" . __('Notifications configuration') . "</th></tr>";

   echo "<tr>";
   echo "<td>" . __('Enable followup') . "</td>";
   echo "<td>";
   echo "<input type='radio' name='use_notifications' id='use_notifications_on' value='1'";
   if ($CFG_GLPI['use_notifications']) {
      echo " checked='checked'";
   }
   echo "/>";
   echo "<label class='radio' for='use_notifications_on'>" . __('Yes') . "</label>";
   echo "</td>";
   echo "<td>";
   echo "<input type='radio' name='use_notifications' id='use_notifications_off' value='0'";
   if (!$CFG_GLPI['use_notifications']) {
      echo " checked='checked'";
   }
   echo "/><label for='use_notifications_off'>" . __('No') . "</label>";
   echo "</td>";
   echo "</tr>";

   foreach ($modes as $mode => $label) {
      $settings_class = 'Notification' . ucfirst($mode) . 'Setting';
      $settings = new $settings_class();

      echo "<tr>";
      echo "<td>" . $settings->getEnableLabel() . "</td>";
      echo "<td>";
      echo "<input type='radio' name='notifications_$mode' id='notifications_{$mode}_on' value='1'";
      if ($CFG_GLPI['notifications_' . $mode]) {
         echo " checked='checked'";
      }
      if (!$CFG_GLPI['use_notifications']) {
         echo " disabled='disabled'";
      }
      echo "/>";
      echo "<label for='notifications_{$mode}_on'>" . __('Yes') . "</label>";
      echo "</td>";
      echo "<td>";
      echo "<input type='radio' name='notifications_$mode' id='notifications_{$mode}_off' value='0'";
      if (!$CFG_GLPI['notifications_' . $mode]) {
         echo " checked='checked'";
      }
      if (!$CFG_GLPI['use_notifications']) {
         echo " disabled='disabled'";
      }
      echo "/><label for='notifications_{$mode}_off'>" . __('No') . "</label>";
      echo "</td>";
      echo "</tr>";
   }

   echo "<tr><td colspan='3' class='center'><input class='submit' type='submit' value='" . __('Save')  . "'/></td></tr>";
   echo "</table>";
   echo "</form>";

   $js = "$(function(){
      $('input[name=use_notifications]').on('change', function() {
         if ($(this).attr('value') == '1') {
            $('input[type=radio][name!=use_notifications]').removeAttr('disabled');
         } else {
            $('input[type=radio][name!=use_notifications]').attr('disabled', 'disabled');
         }
      });
   })";
   echo Html::scriptBlock($js);
}

$notifs_on = false;
if ($CFG_GLPI['use_notifications']) {
   foreach (array_keys($modes) as $mode) {
      if ($CFG_GLPI['notifications_' . $mode]) {
         $notifs_on = true;
         break;
      }
   }
}

if ($notifs_on) {
   echo "<table class='tab_cadre'>";
   echo "<tr><th>" . _n('Notification', 'Notifications', 2)."</th></tr>";

   /* Glocal parameters */
   if (Session::haveRight("config", READ)) {
      echo "<tr class='tab_bg_1'><td class='center'><a href='notificationtemplate.php'>" .
            _n('Notification template', 'Notification templates', 2) ."</a></td> </tr>";
   }

   if (Session::haveRight("notification", READ) && $notifs_on) {
      echo "<tr class='tab_bg_1'><td class='center'>".
            "<a href='notification.php'>". _n('Notification', 'Notifications', 2)."</a></td></tr>";
   } else {
         echo "<tr class='tab_bg_1'><td class='center'>" .
         __('Unable to configure notifications: please configure at least one followup type using the above configuration.') .
               "</td></tr>";
   }

   /* Per notification parameters */
   foreach (array_keys($modes) as $mode) {
      if (Session::haveRight("config", UPDATE) && $CFG_GLPI['notifications_' . $mode]) {
         $settings_class = 'Notification' . ucfirst($mode) . 'Setting';
         $settings = new $settings_class();
         echo "<tr class='tab_bg_1'><td class='center'>".
            "<a href='" . $settings->getFormURL() ."'>". $settings->getTypeName() .
            "</a></td></tr>";
      }
   }

   echo "</table>";
}

echo "</div>";

Html::footer();

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

if (isset($_POST['use_notifications'])) {
   $config             = new Config();
   $tmp['id']          = 1;
   $tmp['use_notifications'] = $_POST['use_notifications'];
   $config->update($tmp);
   //disable all notifications types if notifications has been disabled
   if ($tmp['use_notifications'] == 0) {
      $_POST['notifications_mailing'] = 0;
      $_POST['notifications_ajax'] = 0;
      $_POST['notifications_websockets'] = 0;
   }
}

if (isset($_POST['notifications_mailing'])) {
   $config             = new Config();
   $tmp['id']          = 1;
   $tmp['notifications_mailing'] = $_POST['notifications_mailing'];
   $config->update($tmp);
}

if (isset($_POST['notifications_ajax'])) {
   $config             = new Config();
   $tmp['id']          = 1;
   $tmp['notifications_ajax'] = $_POST['notifications_ajax'];
   $config->update($tmp);
}


if (isset($_POST['notifications_websockets'])) {
   $config             = new Config();
   $tmp['id']          = 1;
   $tmp['notifications_websockets'] = $_POST['notifications_websockets'];
   $config->update($tmp);
}

if (count($_POST)) {
   $current_config = Config::getConfigurationValues('core');
   $CFG_GLPI = array_merge($CFG_GLPI, $current_config);
}

if (Session::haveRight("config", UPDATE)) {
   echo "<form method='POST' action='{$CFG_GLPI['root_doc']}/front/setup.notification.php'>";
   echo "<div class='center'>";

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

   echo "<tr>";
   echo "<td>" .__('Enable followup via email') . "</td>";
   echo "<td>";
   echo "<input type='radio' name='notifications_mailing' id='notifications_mailing_on' value='1'";
   if ($CFG_GLPI['notifications_mailing']) {
      echo " checked='checked'";
   }
   if (!$CFG_GLPI['use_notifications']) {
      echo " disabled='disabled'";
   }
   echo "/>";
   echo "<label for='notifications_mailing_on'>" . __('Yes') . "</label>";
   echo "</td>";
   echo "<td>";
   echo "<input type='radio' name='notifications_mailing' id='notifications_mailing_off' value='0'";
   if (!$CFG_GLPI['notifications_mailing']) {
      echo " checked='checked'";
   }
   if (!$CFG_GLPI['use_notifications']) {
      echo " disabled='disabled'";
   }
   echo "/><label for='notifications_mailing_off'>" . __('No') . "</label>";
   echo "</td>";
   echo "</tr>";

   echo "<tr>";
   echo "<td>" .__('Enable followup via ajax calls') . "</td>";
   echo "<td>";
   echo "<input type='radio' name='notifications_ajax' id='notifications_ajax_on' value='1'";
   if ($CFG_GLPI['notifications_ajax']) {
      echo " checked='checked'";
   }
   if (!$CFG_GLPI['use_notifications']) {
      echo " disabled='disabled'";
   }
   echo "/>";
   echo "<label for='notifications_ajax_on'>" . __('Yes') . "</label>";
   echo "</td>";
   echo "<td>";
   echo "<input type='radio' name='notifications_ajax' id='notifications_ajax_off' value='0'";
   if (!$CFG_GLPI['notifications_ajax']) {
      echo " checked='checked'";
   }
   if (!$CFG_GLPI['use_notifications']) {
      echo " disabled='disabled'";
   }
   echo "/><label for='notifications_ajax_off'>" . __('No') . "</label>";
   echo "</td>";
   echo "</tr>";

   echo "<tr>";
   echo "<td>" .__('Enable followup via websockets') . "</td>";
   echo "<td>";
   echo "<input type='radio' name='notifications_websockets' id='notifications_websockets_on' value='1'";
   if ($CFG_GLPI['notifications_websockets']) {
      echo " checked='checked'";
   }
   if (!$CFG_GLPI['use_notifications']) {
      echo " disabled='disabled'";
   }
   echo "/>";
   echo "<label for='notifications_websockets_on'>" . __('Yes') . "</label>";
   echo "</td>";
   echo "<td>";
   echo "<input type='radio' name='notifications_websockets' id='notifications_websockets_off' value='0'";
   if (!$CFG_GLPI['notifications_websockets']) {
      echo " checked='checked'";
   }
   if (!$CFG_GLPI['use_notifications']) {
      echo " disabled='disabled'";
   }
   echo "/><label for='notifications_websockets_off'>" . __('No') . "</label>";
   echo "</td>";
   echo "</tr>";

   echo "<tr><td colspan='3' class='center'><input class='submit' type='submit' value='" . __('Save')  . "'/></td></tr>";

   echo "</table>";
   echo "</div>";
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

$notifs_on = ($CFG_GLPI['notifications_mailing'] || $CFG_GLPI['notifications_ajax'] || $CFG_GLPI['notifications_websockets']);

if ($CFG_GLPI['use_notifications'] && $notifs_on) {
   echo "<table class='tab_cadre'>";
   echo "<tr><th>" . _n('Notification', 'Notifications', 2)."</th></tr>";
   if (Session::haveRight("config", UPDATE) && $CFG_GLPI['notifications_mailing']) {
      echo "<tr class='tab_bg_1'><td class='center'>".
            "<a href='notificationmailsetting.form.php'>". __('Email followups configuration') .
            "</a></td></tr>";
   }

   if (Session::haveRight("config", UPDATE) && $CFG_GLPI['notifications_ajax']) {
      echo "<tr class='tab_bg_1'><td class='center'>".
            "<a href='notificationajaxsetting.form.php'>". __('Ajax followups configuration') .
            "</a></td></tr>";
   }

   if (Session::haveRight("config", UPDATE) && $CFG_GLPI['notifications_websockets']) {
      echo "<tr class='tab_bg_1'><td class='center'>".
            "<a href='notificationwebsocketssetting.form.php'>". __('Websockets followups configuration') .
            "</a></td></tr>";
   }

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
   echo "</table>";
}

Html::footer();

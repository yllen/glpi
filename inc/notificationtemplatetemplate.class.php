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


/**
 * NotificationTemplateTemplate Class
 *
 * @since version 9.2
**/
class NotificationTemplateTemplate extends CommonDBChild {
   // From CommonDBChild
   static public $itemtype             = 'Notification';
   static public $items_id             = 'notifications_id';
   //WHY? Can edit but not create without that one
   static public $mustBeAttached       = false;

   const MODE_MAIL      = 'mailing';
   const MODE_AJAX      = 'ajax';
   const MODE_WEBSOCKET = 'websocket';
   const MODE_SMS       = 'sms';
   const MODE_XMPP      = 'xmpp';

   static function getTypeName($nb=0) {
      return _n('Template', 'Templates', $nb);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate && Notification::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Notification' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable($this->getTable(),
                                             ['notifications_id' => $item->getID()]);
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      self::showForNotification($item, $withtemplate);
      return true;
   }


   /**
    * Print the notification templates
    *
    * @param Notification $notif        Notification object
    * @param boolean      $withtemplate Template or basic item (default '')
    *
    * @return Nothing (call to classes members)
   **/
   static function showForNotification(Notification $notif, $withtemplate='') {
      global $DB;

      $ID = $notif->getID();

      if (!$notif->getFromDB($ID)
          || !$notif->can($ID, READ)) {
         return false;
      }
      $canedit = $notif->canEdit($ID);

      if ($canedit
          && !(!empty($withtemplate) && ($withtemplate == 2))) {
         echo "<div class='center firstbloc'>".
               "<a class='vsubmit' href='" . self::getFormUrl() ."?notifications_id=$ID&amp;withtemplate=".
                  $withtemplate."'>";
         echo __('Add a template');
         echo "</a></div>\n";
      }

      echo "<div class='center'>";

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => ['notifications_id' => $ID]
      ]);

      echo "<table class='tab_cadre_fixehov'>";
      $colspan = 2;

      if ($iterator->numrows()) {
         $header = "<tr>";
         $header .= "<th>" . __('ID') . "</th>";
         $header .= "<th>".__('Template')."</th>";
         $header .= "<th>".__('Mode')."</th>";
         $header .= "</tr>";
         echo $header;

         Session::initNavigateListItems(__CLASS__,
                           //TRANS : %1$s is the itemtype name,
                           //        %2$s is the name of the item (used for headings of a list)
                                          sprintf(__('%1$s = %2$s'),
                                          Notification::getTypeName(1), $notif->getName()));

         $notiftpl = new self();
         while ($data = $iterator->next()) {
            $notiftpl->getFromDB($data['id']);
            $tpl = new NotificationTemplate();
            $tpl->getFromDB($data['notificationtemplates_id']);

            echo "<tr class='tab_bg_2'>";
            echo "<td>".$notiftpl->getLink()."</td>";
            echo "<td>".$tpl->getLink()."</td>";
            echo "<td>" . self::getMode($data['mode']) . "</td>";
            echo "</tr>";
            Session::addToNavigateListItems(__CLASS__, $data['id']);
         }
         echo $header;
      } else {
         echo "<tr class='tab_bg_2'><th colspan='$colspan'>".__('No item found')."</th></tr>";
      }

      echo "</table>";
      echo "</div>";
   }


   function getName($options = []) {
      return $this->getID();
   }


   /**
    * Print the form
    *
    * @param integer $ID      ID of the item
    * @param array   $options array
    *     - target for the Form
    *     - computers_id ID of the computer for add process
    *
    * @return true if displayed  false if item not found or not right to display
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      if (!Session::haveRight("notification", UPDATE)) {
         return false;
      }

      $notif = new Notification();
      if ($ID > 0) {
         $this->check($ID, READ);
         $notif->getFromDB($this->fields['notifications_id']);
      } else {
         $this->check(-1, CREATE, $options);
         $notif->getFromDB($options['notifications_id']);
      }

      $this->showFormHeader($options);

      if ($this->isNewID($ID)) {
         echo "<input type='hidden' name='notifications_id' value='".$options['notifications_id']."'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Notification')."</td>";
      echo "<td>".$notif->getLink()."</td>";
      echo "<td colspan='2'>&nbsp;</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Mode') . "</td>";
      echo "<td>";
      Dropdown::showFromArray('mode', self::getModes(), ['value' => $this->getField('mode')]);
      echo "</td>";

      echo "<td>". NotificationTemplate::getTypeName(1)."</td>";
      echo "<td><span id='show_templates'>";
      NotificationTemplate::dropdownTemplates(
         'notificationtemplates_id',
         $notif->fields['itemtype'],
         $this->fields['notificationtemplates_id']
      );
      echo "</span></td></tr>";

      $this->showFormButtons($options);

      return true;

   }
   /**
    * Get notification method label
    *
    * @param string $mode the mode to use
    *
    * @return string
   **/
   static function getMode($mode) {

      $tab = self::getModes();
      if (isset($tab[$mode])) {
         return $tab[$mode];
      }
      return NOT_AVAILABLE;
   }

   /**
    * Register a new notification mode (for plugins)
    *
    * @param string $mode  Mode
    * @param string $label Mode's label
    *
    * @return void
    */
   static public function registerMode($mode, $label) {
      global $CFG_GLPI;

      self::getModes();
      $CFG_GLPI['notifications_modes'][$mode] = $label;
   }

   /**
    * Get notification method label
    *
    * @since version 0.84
    *
    * @return the mode's label
   **/
   static function getModes() {
      global $CFG_GLPI;

      $core_modes = [
         self::MODE_MAIL      => __('Email'),
         self::MODE_AJAX      => __('Ajax'),
         /*self::MODE_WEBSOCKET => __('Websocket'),
         self::MODE_SMS       => __('SMS')*/
      ];

      if (!isset($CFG_GLPI['notifications_modes'])) {
         $CFG_GLPI['notifications_modes'] = $core_modes;
      } else {
         //check that core modes are part of the config
         foreach ($core_modes as $mode => $label) {
            if (!isset($CFG_GLPI['notifications_modes'][$mode])) {
               $CFG_GLPI['notifications_modes'][$mode] = $label;
            }
         }
      }

      return $CFG_GLPI['notifications_modes'];
   }


   static function getSpecificValueToDisplay($field, $values, array $options=array()) {
      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'mode':
            return self::getMode($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;

      switch ($field) {
         case 'mode' :
            $options['value']    = $values[$field];
            $options['name']     = $name;
            $options['multiple'] = false;
            return self::dropdownMode($options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Display a dropdown with all the available notification modes
    *
    * @param array $options array of options
    *
    * @return void
    */
   static function dropdownMode($options) {
      $p['name']     = 'modes';
      $p['display']  = true;
      $p['value']    = '';
      $p['multiple'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      return Dropdown::showFromArray($p['name'], self::getModes(), $p);
   }
}

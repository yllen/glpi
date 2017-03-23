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
 *  This class manages the ajax notifications settings
 */
class NotificationAjaxSetting extends CommonDBTM {

   public $table           = 'glpi_configs';

   protected $displaylist  = false;

   static $rightname       = 'config';



   // Temproray hack for this class in 0.84
   static function getTable() {
      return 'glpi_configs';
   }


   static function getTypeName($nb=0) {
      return __('Ajax followups configuration');
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      switch ($item->getType()) {
         case __CLASS__ :
            $tabs[1] = __('Setup');
            return $tabs;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 :
               $item->showFormConfig();
               break;
         }
      }
      return true;
   }


   /**
    * Print the config form
    *
    * @param integer $ID      integer ID of the item
    * @param array   $options array
    *     - target filename : where to go when done.
    *     - tabs integer : ID of the tab to display
    *
    * @return void
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      if (!Config::canUpdate()) {
         return false;
      }
      if (!$CFG_GLPI['notifications_ajax']) {
         $options['colspan'] = 1;
      }

      $this->getFromDB($ID);
      return true;
   }


   function showFormConfig() {
      global $CFG_GLPI;

      echo "<form action='".Toolbox::getItemTypeFormURL(__CLASS__)."' method='post'>";
      echo "<div>";
      echo "<input type='hidden' name='id' value='1'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='4'>"._n('Ajax notification', 'Ajax notifications', Session::getPluralNumber())."</th></tr>";

      if ($CFG_GLPI['notifications_ajax']) {
         $sounds = [
            'sound_a' => __('Sound') . ' A',
            'sound_b' => __('Sound') . ' B',
            'sound_c' => __('Sound') . ' C',
            'sound_d' => __('Sound') . ' D',
         ];

         echo "<tr class='tab_bg_2'>";
         echo "<td> " . __('Default notification sound') . "</td><td>";
         $rand_sound = mt_rand();
         Dropdown::showFromArray("notifications_ajax_sound", $sounds, [
            'value'               => $CFG_GLPI["notifications_ajax_sound"],
            'display_emptychoice' => true,
            'emptylabel'          => __('Disabled'),
            'rand'                => $rand_sound,
         ]);
         echo "</td><td>" . __('Show an example notification') . "</td><td>";
         echo "<input type='button' onclick='browsernotification && browsernotification.showExample($(\"#dropdown_sound" . $rand_sound . "\").val())' class='submit' value=\"" . __('Show example') . "\">";
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td>" . __('Time to check for new notifications (in seconds)') . "</td>";
         echo "<td>";
         Dropdown::showInteger('notifications_ajax_check_interval', $CFG_GLPI["notifications_ajax_check_interval"], 5, 120, 5);
         echo "</td>";
         echo "<td>" . __('URL of the icon') . "</td>";
         echo "<td><input type='text' name='notifications_ajax_icon_url' value='" . $CFG_GLPI["notifications_ajax_icon_url"] . "' "
         . "placeholder='{$CFG_GLPI['root_doc']}/pics/glpi.png'/>";
         echo "</td></tr>";

      } else {
         echo "<tr><td colspan='4'>" . __('Notifications are disabled.')  . " <a href='{$CFG_GLPI['root_doc']}/front/setup.notification.php'>" . _('See configuration') .  "</td></tr>";
      }
      $options['candel']     = false;
      if ($CFG_GLPI['notifications_ajax']) {
         $options['addbuttons'] = array('test_ajax_send' => __('Send a test ajax call to the administrator'));
      }
      $this->showFormButtons($options);

   }

}

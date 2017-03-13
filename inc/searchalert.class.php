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
 * SearchAlert class
**/
class SearchAlert extends SavedSearch {

   //TODO: what to do?
   //static $rightname               = 'bookmark_public';

   public static function getTypeName($nb = 0) {
      _n('Search alert', 'Search alerts', $nb);
   }

   /**
    * Get personal order field name
    *
    * @return string
    */
   protected function getPersonalOrderField() {
      return 'privatesavedalertorder';
   }

   /**
    * Get current type.
    *
    * @return integer, either SavedSearch::SEARCH or SavedSearch::ALERT
    */
   protected function getCurrentType() {
      return self::ALERT;
   }

   /**
    * Get save button text
    *
    * @return string
    */
   static protected function getBtntext() {
      return __s('Save as search alert');
   }

   /**
    * Get user related class name
    *
    * @return string
    */
   static protected function getUserClass() {
      return 'SavedSearch_User';
   }


   /**
    * Display bookmark buttons
    *
    * @param $type      bookmark type to use
    * @param $itemtype  device type of item where is the bookmark (default 0)
   **/
   static function showSaveButton($type, $itemtype=0) {
      global $CFG_GLPI;

      echo " <a href='#' onClick=\"".Html::jsGetElementbyID('bookmarksave').".dialog('open'); return false;\">";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/bookmark_record.png'
             title=\"".__s('Save as bookmark')."\" alt=\"".__s('Save as bookmark')."\"
             class='calendrier pointer'>";
      echo "</a>";
      Ajax::createIframeModalWindow('bookmarksave',
                                    $CFG_GLPI["root_doc"]."/front/bookmark.php?type=$type".
                                          "&action=edit&itemtype=$itemtype&".
                                          "url=".rawurlencode($_SERVER["REQUEST_URI"]),
                                    array('title'         => __('Save as bookmark'),
                                          'reloadonclose' => true));
   }


}

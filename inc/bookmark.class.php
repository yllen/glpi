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

/**
 * Bookmark class
**/
class Bookmark extends SavedSearch {

   static $rightname               = 'bookmark_public';

   public static function getTypeName($nb = 0) {
      _n('Bookmark', 'Bookmarks', $nb);
   }

   /**
    * Get personal order field name
    *
    * @return string
    */
   protected function getPersonalOrderField() {
      return 'privatebookmarkorder';
   }

   /**
    * Get current type.
    *
    * @return integer, either SavedSearch::SEARCH or SavedSearch::ALERT
    */
   protected function getCurrentType() {
      return self::SEARCH;
   }

   /**
    * Get save button text
    *
    * @return string
    */
   static protected function getBtntext() {
      return __s('Save as bookmark');
   }

   /**
    * Get user related class name
    *
    * @return string
    */
   static protected function getUserClass() {
      return 'Bookmark_User';
   }

   /**
    * Print the bookmark form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target for the Form
    *     - type bookmark type when adding a new bookmark
    *     - url when adding a new bookmark
    *     - itemtype when adding a new bookmark
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      $ID = $this->fields['id'];

      // Only an edit form : always check w right
      if ($ID > 0) {
         $this->check($ID, UPDATE);
      } else {
         $this->check(-1, CREATE);
      }

      echo '<br>';
      echo "<form method='post' name='form_save_query' action='".$_SERVER['PHP_SELF']."'>";
      echo "<div class='center'>";
      if (isset($options['itemtype'])) {
         echo "<input type='hidden' name='itemtype' value='".$options['itemtype']."'>";
      }
      if (isset($options['type']) && ($options['type'] != 0)) {
         echo "<input type='hidden' name='type' value='".$options['type']."'>";
      }

      if (isset($options['url'])) {
         echo "<input type='hidden' name='url' value='" . rawurlencode($options['url']) . "'>";
      }

      echo "<table class='tab_cadre' width='".self::WIDTH."px'>";
      echo "<tr><th>&nbsp;</th><th>";
      if ($ID > 0) {
         //TRANS: %1$s is the Itemtype name and $2$d the ID of the item
         printf(__('%1$s - ID %2$d'), $this->getTypeName(1), $ID);
      } else {
         echo __('New item');
      }
      echo "</th></tr>";

      echo "<tr><td class='tab_bg_1'>".__('Name')."</td>";
      echo "<td class='tab_bg_1'>";
      Html::autocompletionTextField($this, "name", array('user' => $this->fields["users_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>".__('Type')."</td>";
      echo "<td>";

      if (static::canCreate()) {
         Dropdown::showPrivatePublicSwitch($this->fields["is_private"],
                                           $this->fields["entities_id"],
                                           $this->fields["is_recursive"]);
      } else {
         if ($this->fields["is_private"]) {
            echo __('Private');
         } else {
            echo __('Public');
         }
      }
      echo "</td></tr>";

      if ($ID <= 0) { // add
         echo "<tr>";
         echo "<td class='tab_bg_2 top' colspan='2'>";
         echo "<input type='hidden' name='users_id' value='".$this->fields['users_id']."'>";
         echo "<div class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</div></td></tr>";

      } else {
         echo "<tr>";
         echo "<td class='tab_bg_2 top' colspan='2'>";
         echo "<input type='hidden' name='id' value='$ID'>";
         echo "<input type='submit' name='update' value=\"".__s('Save')."\" class='submit'>";
         echo "</td></tr><tr><td class='tab_bg_2 right' colspan='2'>";
         echo "<input type='submit' name='purge' value=\""._sx('button', 'Delete permanently')."\"
                class='submit'>";
         echo "</td></tr>";
      }
      echo "</table></div>";
      Html::closeForm();
   }


   /**
    * Modify ranking and automatically reorder
    *
    * @since version 0.85
    *
    * @param integer $ID     The saved search ID whose ranking must be modified
    * @param string  $action Either 'up' or 'down'
    *
    * @deprecated since version 9.2; use changeOrder() method instead.
    *
    * @return void
    */
   function changeBookmarkOrder($ID, $action) {
      Toolbox::logDebug('changeBookmarkOrder() method is deprecated');
      return $this->changeOrder($ID, $action);
   }

   /**
    * Move a bookmark in an ordered collection
    *
    * @since version 0.85
    *
    * @param array   $items  ID to move
    * @param integer $ref_ID Position  (0 means all, so before all or after all)
    * @param string  $action Either after or before ( default 'after')
    *
    * @deprecated since version 9.2; use move() method instead.
    *
    * @return true if all ok
   **/
   function moveBookmark(array $items, $ref_ID, $action='after') {
      Toolbox::logDebug('moveBookmark() method is deprecated');
      return parent::move($items, $ref_ID, $action);
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

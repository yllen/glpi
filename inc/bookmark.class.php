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
      return _n('Bookmark', 'Bookmarks', $nb);
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
}

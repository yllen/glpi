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

abstract class SavedSearch_User extends CommonDBRelation {
   public $auto_message_on_action = false;

   static public $items_id_1          = 'savedsearches_id';

   static public $itemtype_2          = 'User';
   static public $items_id_2          = 'users_id';

   static public function getTable() {
      return parent::getTable(__CLASS__);
      /*if (empty($_SESSION['glpi_table_of'][__CLASS__])) {
         $_SESSION['glpi_table_of'][__CLASS__] = getTableForItemType(__CLASS__);
      }

      return $_SESSION['glpi_table_of'][__CLASS__];*/
   }
}
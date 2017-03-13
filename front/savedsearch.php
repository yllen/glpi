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
   include ('../inc/includes.php');
}


if (!isset($type)) {
   if (!isset($_GET["type"]) && !isset($_POST['type'])) {
      throw new \RuntimeException('Invalid SavedSearch type provided!');
   } else {
      $type = (isset($_POST['type']) ? (int)$_POST['type'] : (int)$_GET['type']);
      if ($type == SavedSearch::ALERT) {
         $savedsearch = new SearchAlert();
      } else {
         $savedsearch = new Bookmark();
      }
   }
   Html::popHeader(__('Saved searches'), $_SERVER['PHP_SELF'], true);
}

if (!isset($_GET["itemtype"]) || $_GET['itemtype'] == 'AllAssets') {
   $_GET["itemtype"] = -1;
} else {
   if (!is_subclass_of($_GET['itemtype'], 'CommonDBTM')) {
       throw new \RuntimeException('Invalid item type provided!');
   }
}

if (!isset($_GET["url"])) {
   $_GET["url"] = "";
}

if (!isset($_GET["action"])) {
   $_GET["action"] = "";
}

if (isset($_POST["add"])) {
   $savedsearch->check(-1, CREATE, $_POST);
   $savedsearch->add($_POST);
   //Html::back();
} else if (isset($_POST["update"])) {
   $savedsearch->check($_POST["id"], UPDATE);   // Right to update the saved search
   $savedsearch->check(-1, CREATE, $_POST);     // Right when entity change

   $savedsearch->update($_POST);
   $_GET["action"] = "";
   Html::back();
} else if ($_GET["action"] == "edit"
           && isset($_GET['mark_default'])
           && isset($_GET["id"])) {
   $savedsearch->check($_GET["id"], READ);

   if ($_GET["mark_default"] > 0) {
      $savedsearch->markDefault($_GET["id"]);
   } else if ($_GET["mark_default"] == 0) {
      $savedsearch->unmarkDefault($_GET["id"]);
   }
   $_GET["action"] = "";
   Html::back();
} else if (($_GET["action"] == "load")
           && isset($_GET["id"]) && ($_GET["id"] > 0)) {
   $savedsearch->check($_GET["id"], READ);
   $savedsearch->load($_GET["id"]);
   $_GET["action"] = "";

} else if (isset($_POST["action"])
           && (($_POST["action"] == "up") || ($_POST["action"] == "down"))) {
   Session::checkLoginUser();
   $savedsearch->changeOrder($_POST['id'], $_POST["action"]);
   $_GET["action"] = "";
   Html::back();
} else if (isset($_POST["purge"])) {
   $savedsearch->check($_POST["id"], PURGE);
   $savedsearch->delete($_POST, 1);
   $_GET["action"] = "";
   Html::back();
}

if ($_GET["action"] == "edit") {
   if (isset($_GET['id']) && ($_GET['id'] > 0)) {
      // Modify
      $savedsearch->check($_GET["id"], UPDATE);
      $savedsearch->showForm($_GET['id']);
   } else {
      // Create
      $savedsearch->check(-1, CREATE);
      $savedsearch->showForm(0, array('type'     => $type,
                                   'url'      => rawurldecode($_GET["url"]),
                                   'itemtype' => $_GET["itemtype"]));
   }
} else {
   $savedsearch->display();
}

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
 * Saved searches class
**/
abstract class SavedSearch extends CommonDBTM {

   // From CommonGLPI
   public $taborientation          = 'horizontal';
   public $auto_message_on_action = false;
   protected $displaylist          = false;

   const WIDTH  = 750;
   const SEARCH = 1; //SEARCH SYSTEM bookmark
   const URI    = 2;
   const ALERT  = 3; //SEARCH SYSTEM search alert

   /**
    * @since version 0.84
    *
    * @return array
   **/
   function getForbiddenStandardMassiveAction() {
      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   function getSpecificMassiveActions($checkitem=NULL) {
      $actions[get_called_class().MassiveAction::CLASS_ACTION_SEPARATOR.'move_savedsearch'] = __('Move');
      return $actions;
   }


   static function showMassiveActionsSubForm(MassiveAction $ma) {
      switch ($ma->getAction()) {
         case 'move_savedsearch' :
            $values             = array('after'  => __('After'),
                                        'before' => __('Before'));
            Dropdown::showFromArray('move_type', $values, array('width' => '20%'));

            $param              = array('name'  => "savedsearch_id_ref",
                                        'width' => '50%');

            $type = static::getCurrentType();
            $param['condition'] = "(`is_private`='1' AND `users_id`='" .
               Session::getLoginUserID() . "' AND type=$type) ";
            $param['entity']    = -1;
            static::dropdown($param);
            echo "<br><br>\n";
            echo Html::submit(_x('button', 'Move'), array('name' => 'massiveaction'))."</span>";
            return true;
      }
      return parent::showMassiveActionsSubForm($ma);
   }


   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      global $DB;

      switch ($ma->getAction()) {
         case 'move_savedsearch' :
            $input = $ma->getInput();
            if ($item->move($ids, $input['savedsearch_id_ref'],
                                    $input['move_type'])) {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_OK);
            } else {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   /**
    * Special case: a private saved search has entities_id==-1 => we cannot check it
    * @see CommonDBTM::canCreateItem()
    *
    * @since version 0.85
    *
    * @return boolean
   **/
   function canCreateItem() {
      if (($this->fields['is_private'] == 1)
          && ($this->fields['users_id'] == Session::getLoginUserID())) {
         return true;
      }
      return parent::canCreateItem();
   }


   /**
    *  Special case: a private saved search has entities_id==-1 => we cannot check it
    * @see CommonDBTM::canViewItem()
    *
    * @since version 0.85
    *
    * @return boolean
   **/
   function canViewItem() {
      if (($this->fields['is_private'] == 1)
          && ($this->fields['users_id'] == Session::getLoginUserID())) {
         return true;
      }
      return parent::canViewItem();
   }


   function isNewItem() {
      /// For tabs management : force isNewItem
      return false;
   }


   function defineTabs($options=array()) {

      $ong               = array();
      $this->addStandardTab(get_called_class(), $ong, $options);
      $ong['no_all_tab'] = true;
      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      switch ($item->getType()) {
         case get_called_class():
            $ong     = array();
            $ong[1]  = __('Personal');
            if (self::canView()) {
               $ong[2] = __('Public');
            }
            return $ong;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case get_called_class():
            $is_private = 1;
            if ($tabnum == 2) {
               $is_private = 0;
            }
            $item->showSavedSearchesList($_GET['_target'], $is_private);
            return true;
      }
      return false;
   }


   function prepareInputForAdd($input) {

      if (!isset($input['url']) || !isset($input['type'])) {
         return false;
      }

      $taburl = parse_url(rawurldecode($input['url']));

      $index  = strpos($taburl["path"], "plugins");
      if (!$index) {
         $index = strpos($taburl["path"], "front");
      }
      $input['path'] = Toolbox::substr(
         $taburl["path"],
         $index,
         Toolbox::strlen($taburl["path"]) - $index
      );

      $query_tab = array();

      if (isset($taburl["query"])) {
         parse_str($taburl["query"], $query_tab);
      }

      $input['query'] = Toolbox::append_params(
         $this->prepareQueryToStore($input['type'],
         $query_tab)
      );

      return $input;
   }


   function pre_updateInDB() {

      // Set new user if initial user have been deleted
      if (($this->fields['users_id'] == 0)
          && $uid=Session::getLoginUserID()) {
         $this->input['users_id']  = $uid;
         $this->fields['users_id'] = $uid;
         $this->updates[]          = "users_id";
      }
   }


   function post_getEmpty() {

      $this->fields["users_id"]     = Session::getLoginUserID();
      $this->fields["is_private"]   = 1;
      $this->fields["is_recursive"] = 0;
      $this->fields["entities_id"]  = $_SESSION["glpiactive_entity"];
   }


   function cleanDBonPurge() {
      global $DB;

      $query="DELETE
              FROM `glpi_savedsearches_users`
              WHERE `savedsearches_id` = '".$this->fields['id']."'";
      $DB->query($query);
   }


   /**
    * Print the saved search form
    *
    * @param integer $ID      ID of the item
    * @param array   $options Options:
    *     - target for the Form
    *     - type when adding
    *     - url when adding
    *     - itemtype when adding
    *
    * @return void
   **/
   function showForm($ID, $options=array()) {
      $ID = $this->fields['id'];

      if (!isset($options['type'])) {
         $options['type'] = static::getCurrentType();
      }

      // Only an edit form : always check w right
      if ($ID > 0) {
         $this->check($ID, UPDATE);
      } else {
         $this->check(-1, CREATE);
      }

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
    * Prepare query to store depending of the type
    *
    * @param integer $type      Saved search type (self::SEARCH, self::URI or self::ALERT)
    * @param array   $query_tab Parameters
    *
    * @return clean query array
   **/
   protected function prepareQueryToStore($type, $query_tab) {

      switch ($type) {
         case self::SEARCH:
         case self::ALERT:
            $fields_toclean = [
               'add_search_count',
               'add_search_count2',
               'delete_search_count',
               'delete_search_count2',
               'start',
               '_glpi_csrf_token'
            ];
            foreach ($fields_toclean as $field) {
               if (isset($query_tab[$field])) {
                  unset($query_tab[$field]);
               }
            }

            break;
      }
      return $query_tab;
   }


   /**
    * Prepare query to use depending of the type
    *
    * @param integer $type      Saved search type (see SavedSearch constants)
    * @param array   $query_tab Parameters array
    *
    * @return prepared query array
   **/
   function prepareQueryToUse($type, $query_tab) {

      switch ($type) {
         case self::SEARCH:
         case self::ALERT:
            // Check if all datas are valid
            $opt = Search::getCleanedOptions($this->fields['itemtype']);

            $query_tab_save = $query_tab;
            $partial_load   = false;
            // Standard search
            if (isset($query_tab_save['criteria']) && count($query_tab_save['criteria'])) {
               unset($query_tab['criteria']);
               $new_key = 0;
               foreach ($query_tab_save['criteria'] as $key => $val) {
                  if (($val['field'] != 'view') && ($val['field'] != 'all')
                      && (!isset($opt[$val['field']])
                          || (isset($opt[$val['field']]['nosearch'])
                              && $opt[$val['field']]['nosearch']))) {
                     $partial_load = true;
                  } else {
                     $query_tab['criteria'][$new_key] = $val;
                     $new_key++;
                  }
               }
            }

            // Meta search
            if (isset($query_tab_save['metacriteria']) && count($query_tab_save['metacriteria'])) {
               $meta_ok = Search::getMetaItemtypeAvailable($query_tab['itemtype']);

               unset($query_tab['metacriteria']);

               $new_key = 0;
               foreach ($query_tab_save['metacriteria'] as $key => $val) {
                  $opt = Search::getCleanedOptions($val['itemtype']);
                  // Use if meta type is valid and option available
                  if (!in_array($val['itemtype'], $meta_ok)
                      || !isset($opt[$val['field']])) {
                     $partial_load = true;
                  } else {
                     $query_tab['metacriteria'][$new_key] = $val;
                     $new_key++;
                  }
               }
            }

            // Display message
            if ($partial_load) {
               Session::addMessageAfterRedirect(__('Partial load of the saved search.'), false, ERROR);
            }
            // add reset value
            $query_tab['reset'] = 'reset';
            break;
      }
      return $query_tab;
   }


   /**
    * Load a saved search
    *
    * @param integer $ID     ID of the saved search
    * @param boolean $opener Whether to load saved search in opener window
    *                        false -> current window (true by default)
    *
    * @return nothing
   **/
   function load($ID, $opener=true) {
      global $CFG_GLPI;

      if ($params = $this->getParameters($ID)) {
         $url  = $CFG_GLPI['root_doc']."/".rawurldecode($this->fields["path"]);
         $url .= "?".Toolbox::append_params($params);

         if ($opener) {
            echo "<script type='text/javascript' >\n";
            echo "window.parent.location.href='$url';";
            echo "</script>";
            exit();
         } else {
            Html::redirect($url);
         }
      }
   }


   /**
    * Get saved search parameters
    *
    * @param integer $ID ID of the saved search
    *
    * @return array|false
   **/
   function getParameters($ID) {
      if ($this->getFromDB($ID)) {
         $query_tab = array();
         parse_str($this->fields["query"], $query_tab);
         return $this->prepareQueryToUse($this->fields["type"], $query_tab);
      }
      return false;
   }

   /**
    * Mark saved search as default view for the current user
    *
    * @param integer $ID ID of the saved search
    *
    * @deprecated since version 9.2; @see SavedSearch::markDefault()
    *
    * @return void
   **/
   function mark_default($ID) {
      Toolbox::logDebug('mark_default() method is deprecated');
      return $this->markDefault($ID);
   }

   /**
    * Mark saved search as default view for the currect user
    *
    * @param integer $ID ID of the saved search
    *
    * @return nothing
   **/
   function markDefault($ID) {
      global $DB;

      if ($this->getFromDB($ID)
         && ($this->fields['type'] != self::URI)) {
         $userclass = $this->getUserClass();
         $dd = new $userclass();
         // Is default view for this itemtype already exists ?
         $query = "SELECT `id`
                   FROM `glpi_savedsearches_users`
                   WHERE `users_id` = '".Session::getLoginUserID()."'
                         AND `savedsearches_id` = '$ID'
                         AND `itemtype` = '".$this->fields['itemtype']."'";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               // already exists update it
               $updateID = $DB->result($result, 0, 0);
               $dd->update([
                  'id'                 => $updateID,
                  'savedsearches_id'   => $ID
               ]);
            } else {
               $dd->add([
                  'savedsearches_id'   => $ID,
                  'users_id'           => Session::getLoginUserID(),
                  'itemtype'           => $this->fields['itemtype']
               ]);
            }
         }
      }
   }


   /**
    * Mark savedsearch as default view for the current user
    *
    * @param integer $ID ID of the saved search
    *
    * @deprecated since version 9.2; @see SavedSearch::unmarkDefault()
    *
    * @return void
   **/
   function unmark_default($ID) {
      Toolbox::logDebug('unmark_default() method is deprecated');
      return $this->unmarkDefault($ID);
   }


   /**
    * Mark savedsearch as default view for the current user
    *
    * @param integer $ID ID of the saved search
    *
    * @return void
   **/
   function unmarkDefault($ID) {
      global $DB;

      if ($this->getFromDB($ID)
         && ($this->fields['type'] != self::URI)) {
         $userclass = $this->getUserClass();
         $dd = new $userclass();
         // Is default view for this itemtype already exists ?
         $query = "SELECT `id`
                   FROM `glpi_savedsearches_users`
                   WHERE `users_id` = '".Session::getLoginUserID()."'
                         AND `savedsearches_id` = '$ID'
                         AND `itemtype` = '".$this->fields['itemtype']."'";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               // already exists delete it
               $deleteID = $DB->result($result, 0, 0);
               $dd->delete(['id' => $deleteID]);
            }
         }
      }
   }


   /**
    * Show saved searches list
    *
    * @param string  $target     Target to use for links
    * @param boolean $is_private Show private of public? (default 1)
    *
    * @return void
   **/
   function showSavedSearchesList($target, $is_private=1) {
      global $DB, $CFG_GLPI;

      if (!$is_private && !static::canView()) {
         return false;
      }

      $query = "SELECT `".$this->getTable()."`.*,
                       `glpi_savedsearches_users`.`id` AS IS_DEFAULT
                FROM `".$this->getTable()."`
                LEFT JOIN `glpi_savedsearches_users`
                  ON (`".$this->getTable()."`.`itemtype` = `glpi_savedsearches_users`.`itemtype`
                      AND `".$this->getTable()."`.`id` = `glpi_savedsearches_users`.`savedsearches_id`
                      AND `glpi_savedsearches_users`.`users_id` = '".Session::getLoginUserID()."')
                WHERE type='" . $this->getCurrentType() . "' AND ";

      if ($is_private) {
         $query .= "(`".$this->getTable()."`.`is_private`='1'
                     AND `".$this->getTable()."`.`users_id`='".Session::getLoginUserID()."') ";
      } else {
         $query .= "(`".$this->getTable()."`.`is_private`='0' ".
                     getEntitiesRestrictRequest("AND", $this->getTable(), "", "", true) . ")";
      }

      $query .= " ORDER BY `itemtype`, `name`";

      // get saved searches
      $searches = array();
      if ($result = $DB->query($query)) {
         if ($numrows = $DB->numrows($result)) {
            while ($data = $DB->fetch_assoc($result)) {
               $searches[$data['id']] = $data;
            }
         }
      }

      $ordered = array();

      // get personal order
      if ($is_private) {
         $user = new User();
         $personalorderfield = $this->getPersonalOrderField();

         if ($user->getFromDB(Session::getLoginUserID())) {
            $personalorder = importArrayFromDB($user->fields[$personalorderfield]);
         }
         if (!is_array($personalorder)) {
            $personalorder = array();
         }

         // Add on personal order
         if (count($personalorder)) {
            foreach ($personalorder as $val) {
               if (isset($searches[$val])) {
                  $ordered[$val] = $searches[$val];
                  unset($searches[$val]);
               }
            }
         }
      }
      // Add unsaved in order
      if (count($searches)) {
         foreach ($searches as $key => $val) {
            $ordered[$key] = $val;
         }
      }
      if ($is_private) {
         // New: save order
         $store = array_keys($ordered);
         $user->update(array('id'                => Session::getLoginUserID(),
                             $personalorderfield => exportArrayToDB($store)));
      }

      $rand    = mt_rand();
      $numrows = $DB->numrows($result);
      Html::openMassiveActionsForm('mass'.get_called_class().$rand);

      echo "<div class='center' id='tabsbody' >";
      $maactions = array('purge' => _x('button', 'Delete permanently'));
      if ($is_private) {
         $maactions[get_called_class().MassiveAction::CLASS_ACTION_SEPARATOR.'move_savedsearch'] = __('Move');
      }
      $massiveactionparams = array('num_displayed'     => $numrows,
                                    'container'        => 'mass'.get_called_class().$rand,
                                    'width'            => 600,
                                    'extraparams'      => array('is_private' => $is_private),
                                    'height'           => 200,
                                    'specific_actions' => $maactions);

      // No massive action on bottom
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr>";
      echo "<th class='small'>".Html::getCheckAllAsCheckbox('mass'.get_called_class().$rand)."</th>";
      echo "<th class='center' colspan='2'>".$this->getTypeName(Session::getPluralNumber())."</th>";
      echo "<th class='small'>&nbsp;</th>";
      echo "<th class='small'>".__('Default view')."</th>";
      $colspan = 5;
      if ($is_private) {
         $colspan+=2;
         echo "<th class='small' colspan='2'>&nbsp;</th>";
      }
      echo "</tr>";

      if ($totalcount = count($ordered)) {
         $current_type      = -1;
         $number            = 0;
         $current_type_name = NOT_AVAILABLE;
         foreach ($ordered as $key => $this->fields) {
            $number ++;
            if ($current_type != $this->fields['itemtype']) {
               $current_type      = $this->fields['itemtype'];
               $current_type_name = NOT_AVAILABLE;

               if ($current_type == "AllAssets") {
                  $current_type_name = __('Global');
               } else if ($item = getItemForItemtype($current_type)) {
                  $current_type_name = $item->getTypeName(1);
               }
            }
            $canedit = $this->canEdit($this->fields["id"]);

            echo "<tr class='tab_bg_1'>";
            echo "<td width='10px'>";
            if ($canedit) {
               Html::showMassiveActionCheckBox(get_called_class(), $this->fields["id"]);
            } else {
               echo "&nbsp;";
            }
            echo "</td>";
            echo "<td>$current_type_name</td>";
            echo "<td>";
            if ($canedit) {
               echo "<a href=\"".$CFG_GLPI['root_doc']."/front/bookmark.php?action=edit&amp;id=".
                      $this->fields["id"]."\" title='"._sx('button', 'Update')."'>".
                      $this->fields["name"]."</a><span class='count'></span>";
            } else {
               echo $this->fields["name"];
            }
            echo "</td>";

            echo "<td style='white-space: nowrap;'><a href=\"".$CFG_GLPI['root_doc']."/front/bookmark.php?action=load&amp;id=".
                       $this->fields["id"]."\" class='vsubmit'>".__('Load')."</a>";
            echo "&nbsp;<a href=\"".$CFG_GLPI['root_doc']."/ajax/bookmark.php?action=count&amp;id=".
                       $this->fields["id"]."\" class='vsubmit countSearches'>".__('Count')."</a>";
            echo "</td>";
            echo "<td class='center'>";
            if ($this->fields['type'] != self::URI) {
               if (is_null($this->fields['IS_DEFAULT'])) {
                  echo "<a href=\"".$CFG_GLPI['root_doc']."/front/bookmark.php?action=edit&amp;".
                         "mark_default=1&amp;id=".$this->fields["id"]."\" alt=\"".
                         __s('Not default search')."\" title=\"".__s('Not default search')."\">".
                         "<img src=\"".$CFG_GLPI['root_doc']."/pics/bookmark_record.png\" class='pointer'></a>";
               } else {
                  echo "<a href=\"".$CFG_GLPI['root_doc']."/front/bookmark.php?action=edit&amp;".
                         "mark_default=0&amp;id=".$this->fields["id"]."\" alt=\"".
                         __s('Default search')."\" title=\"".__s('Default search')."\">".
                         "<img src=\"".$CFG_GLPI['root_doc']."/pics/bookmark_default.png\" class='pointer'></a>";
               }
            }
            echo "</td>";
            if ($is_private) {
               if ($number != 1) {
                  echo "<td>";
                  Html::showSimpleForm($this->getSearchURL(), array('action' => 'up'), '',
                                       array('id'      => $this->fields["id"]),
                                       $CFG_GLPI["root_doc"]."/pics/puce-up.png");
                  echo "</td>";
               } else {
                  echo "<td>&nbsp;</td>";
               }

               if ($number != $totalcount) {
                  echo "<td>";
                  Html::showSimpleForm($this->getSearchURL(), array('action' => 'down'), '',
                                       array('id'      => $this->fields["id"]),
                                       $CFG_GLPI["root_doc"]."/pics/puce-down.png");
                  echo "</td>";
               } else {
                  echo "<td>&nbsp;</td>";
               }
            }
            echo "</tr>";
            $first = false;
         }
         echo "</table></div>";

         if ($is_private
             || Session::haveRight('bookmark_public', PURGE)) {
            $massiveactionparams['ontop']       = false;
            $massiveactionparams['forcecreate'] = true;
            Html::showMassiveActions($massiveactionparams);
         }

         $js = "$(function() {
            $('.countSearches').on('click', function(e) {
               e.preventDefault();
               var _this = $(this);
               var _dest = _this.closest('tr').find('span.count');
               $.ajax({
                  url: _this.attr('href'),
                  beforeSend: function() {
                     var _img = '<span id=\'loading\'><img src=\'{$CFG_GLPI["root_doc"]}/pics/spinner.gif\' alt=\'" . __('Loading...') . "\'/></span>';
                     _dest.append(_img);
                  },
                  success: function(res) {
                     _dest.html(' (' + res.count + ')');
                  },
                  complete: function() {
                     $('#loading').remove();
                  }
               });
            });
         });";
         echo Html::scriptBlock($js);

      } else {
         echo "<tr class='tab_bg_1'><td colspan='$colspan'>";
         echo sprintf(
            __('You have not recorded any %1$s yet'),
            $this->getTypeName(1)
         );
         echo "</td></tr></table>";
      }
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
    * @return void
    */
   function changeOrder($ID, $action) {

      $user = new User();
      $personalorderfield = $this->getPersonalOrderField();
      if ($user->getFromDB(Session::getLoginUserID())) {
         $personalorder = importArrayFromDB($user->fields[$personalorderfield]);
      }
      if (!is_array($personalorder)) {
         $personalorder = array();
      }

      if (in_array($ID, $personalorder)) {
         $pos = array_search($ID, $personalorder);
         switch ($action) {
            case 'up' :
               if (isset($personalorder[$pos-1])) {
                  $personalorder[$pos] = $personalorder[$pos-1];
                  $personalorder[$pos-1] = $ID;
               }
               break;

            case 'down' :
               if (isset($personalorder[$pos+1])) {
                  $personalorder[$pos] = $personalorder[$pos+1];
                  $personalorder[$pos+1] = $ID;
               }
               break;
         }
         $user->update(array('id'                => Session::getLoginUserID(),
                             $personalorderfield => exportArrayToDB($personalorder)));
      }
   }


   /**
    * Move in an ordered collection
    *
    * @since version 0.85
    *
    * @param array   $items  ID to move
    * @param integer $ref_ID Position  (0 means all, so before all or after all)
    * @param string  $action Either after or before ( default 'after')
    *
    * @return true if all ok
    */
   function move(array $items, $ref_ID, $action='after') {
      global $DB;

      if (count($items)) {
         // Clean IDS : drop ref_ID
         if (isset($items[$ref_ID])) {
            unset($items[$ref_ID]);
         }

         $user               = new User();
         $personalorderfield = $this->getPersonalOrderField();
         if ($user->getFromDB(Session::getLoginUserID())) {
            $personalorder = importArrayFromDB($user->fields[$personalorderfield]);
         }
         if (!is_array($personalorder)) {
            return false;
         }

         $newpersonalorder = array();
         foreach ($personalorder as $val) {
            // Found item
            if ($val == $ref_ID) {
               // Add after so add ref ID
               if ($action == 'after') {
                  $newpersonalorder[] = $ref_ID;
               }
               foreach ($items as $val2) {
                  $newpersonalorder[] = $val2;
               }
               if ($action == 'before') {
                  $newpersonalorder[] = $ref_ID;
               }
            } else if (!isset($items[$val])) {
               $newpersonalorder[] = $val;
            }
         }
         $user->update(array('id'                => Session::getLoginUserID(),
                             $personalorderfield => exportArrayToDB($newpersonalorder)));
         return true;
      }
      return false;
   }


   /**
    * Display buttons
    *
    * @param integer $type     Bookmark type to use
    * @param integer $itemtype Device type of item where is the bookmark (default 0)
    *
    * @return void
    */
   static function showSaveButton($type, $itemtype=0) {
      global $CFG_GLPI;

      $btntext = static::getBtntext();

      echo " <a href='#' onClick=\"".Html::jsGetElementbyID('savesearch').".dialog('open'); return false;\">";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/bookmark_record.png'
             title=\"$btntext\" alt=\"$btntext\"
             class='calendrier pointer'>";
      echo "</a>";
      Ajax::createIframeModalWindow('savesearch',
                                    $CFG_GLPI["root_doc"]."/front/bookmark.php?type=$type".
                                          "&action=edit&itemtype=$itemtype&".
                                          "url=".rawurlencode($_SERVER["REQUEST_URI"]),
                                    array('title'         => $btntext,
                                          'reloadonclose' => true));
   }

   static public function getTable($classname = null) {
      return parent::getTable(__CLASS__);
   }

   /**
    * Get personal order field name
    *
    * @return string
    */
   abstract protected function getPersonalOrderField();

   /**
    * Get current type.
    *
    * @return integer, either SavedSearch::SEARCH or SavedSearch::ALERT
    */
   abstract protected function getCurrentType();

   /**
    * Get save button text
    *
    * @return string
    */
   abstract static protected function getBtntext();

   /**
    * Get user related class name
    *
    * @return string
    */
   abstract static protected function getUserClass();
}

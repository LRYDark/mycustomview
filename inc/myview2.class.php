<?php
/*
 -------------------------------------------------------------------------
 MyCustomView plugin for GLPI
 Copyright (C) 2023 by the MyCustomView Development Team.

 https://github.com/pluginsGLPI/mycustomview
 -------------------------------------------------------------------------

 LICENSE

 This file is part of MyCustomView.

 MyCustomView is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 MyCustomView is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with MyCustomView. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginMycustomviewMyview2 extends CommonDBTM
{

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      global $PLUGIN_HOOKS, $DB;

      $UserId = $_SESSION['glpiID'];
      $result = $DB->query("SELECT * FROM glpi_plugin_mycustomview_preferences WHERE users_id = $UserId")->fetch_object();
      $result = $DB->query("SELECT * FROM glpi_groups WHERE id = $result->groupe_id_2")->fetch_object();

      if (!(PluginMycustomviewProfile::checkProfileRight($_SESSION['glpiactiveprofile']['id']))) {
         return false;
      }
      if ($item->getType() == 'Central') {
         if(!empty($result->comment)){
            return __("$result->comment", "mycustomview");
         }elseif(!empty($result->name)){
            return __("$result->name", "mycustomview");
         }else{
            return 0;
         }
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item->getType() == 'Central') {
         echo "test 2 ";
      }
      return true;
   }
}
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

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_mycustomview_install()
{

   global $DB;

   // ------- On include les classes importantes
   include_once(GLPI_ROOT . "/plugins/mycustomview/inc/profile.class.php");
   //include_once(GLPI_ROOT . "/plugins/mycustomview/inc/config.class.php");

   PluginMycustomviewProfile::createFirstAccess($_SESSION["glpiactiveprofile"]["id"]);

   // requete de création des tables
   if (!$DB->TableExists("glpi_plugin_mycustomview_preferences")) {
      $query = "CREATE TABLE `glpi_plugin_mycustomview_preferences` (
         `id` int unsigned NOT NULL auto_increment,
         `users_id` int unsigned NOT NULL default '0',
         `groups_id` text NULL,
         `Tickets_to_be_processed` int NULL,
         `Ticket_tasks_to_be_addressed` int NULL,
         `Pending_tickets` int NULL,
         `Current_tickets` int NULL,
         `Observed_tickets` int NULL,
         PRIMARY KEY  (`id`),
         KEY `users_id` (`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";

      $DB->query($query) or die("error creating glpi_plugin_mycustomview_preferences " . $DB->error());
   }

   if (!$DB->TableExists("glpi_plugin_mycustomview_config")) {
      $query = "CREATE TABLE `glpi_plugin_mycustomview_config` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `max_filters` TINYINT,
         PRIMARY KEY  (`id`)
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_mycustomview_config " . $DB->error());

      $DB->insert(
         'glpi_plugin_mycustomview_config',
         [
            'max_filters' => 4
         ]
      );
   }

   return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_mycustomview_uninstall()
{
   global $DB;

   $tables = array("glpi_plugin_mycustomview_preferences");

   foreach ($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }

   global $DB;

    $result = $DB->request([
        'SELECT' => ['profiles_id'],
        'FROM' => 'glpi_profilerights',
        'WHERE' => ['name' => ['LIKE', 'plugin_mycustomview%']]
    ]);

    foreach ($result as $id_profil) {
        $DB->delete(
            'glpi_profilerights', [
                'name' => ['LIKE', 'plugin_mycustomview%'],
                'profiles_id' => $id_profil
            ]
        );
    }

   return true;
}

function changePageOnHome()
{
   // vérification plugin activé + vérification du profil (si profil demandeur -> return)
   $plugin = new Plugin();
   if ($plugin->isActivated("mycustomview")) {
      if (isset($_SESSION['glpiactiveprofile']['id'])) {
         if ($_SESSION['glpiactiveprofile']['id'] == 1) {
            return;
         }
      }
   }
}
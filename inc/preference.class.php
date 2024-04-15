<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Mycustomview plugin for GLPI
 Copyright (C) 2014-2022 by the Mycustomview Development Team.

 https://github.com/InfotelGLPI/mycustomview
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Mycustomview.

 Mycustomview is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Mycustomview is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Mycustomview. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * class plugin_mycustomview_preference
 * Load and store the preference configuration from the database
 */
class PluginMycustomviewPreference extends CommonDBTM {

   static function checkIfPreferenceExists($users_id) {
      global $DB;

      $result = $DB->query("SELECT `id`
                FROM `glpi_plugin_mycustomview_preferences`
                WHERE `users_id` = '" . $users_id . "' ");
      if ($DB->numrows($result) > 0)
         return $DB->result($result, 0, "id");
      else
         return 0;
   }

   static function addDefaultPreference($users_id) {

      $self                                  = new self();
      $input["users_id"]                     = $users_id;
      $input["Tickets_to_be_processed"]      = 1;
      $input["Ticket_tasks_to_be_addressed"] = 1;
      $input["Pending_tickets"]              = 1;
      $input["Current_tickets"]              = 1;
      $input["Observed_tickets"]             = 1;
      return $self->add($input);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'Preference'
      && isset($_SESSION["glpiactiveprofile"]["interface"])
          && $_SESSION["glpiactiveprofile"]["interface"] != "helpdesk") {
         return __('Vue groupe(s)', 'mycustomview');
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      global $CFG_GLPI;

      if (get_class($item) == 'Preference') {
         $pref_ID = self::checkIfPreferenceExists(Session::getLoginUserID());
         if (!$pref_ID)
            $pref_ID = self::addDefaultPreference(Session::getLoginUserID());

         self::showPreferencesForm(PLUGIN_MYCUSTOMVIEW_WEBDIR . "/front/preference.form.php", $pref_ID);
      }
      return true;
   }

   static function showPreferencesForm($target, $ID) {
      global $DB;

      $self = new self();
      $self->getFromDB($ID);
      
      echo "<form action='" . $target . "' method='post'>";
      echo "<div align='center'>";

      echo "<table class='tab_cadre_fixe' style='margin: 0; margin-top: 5px;'>\n";
      echo " <tr><th colspan='2'>" .__("Affichage des groupes sur la page central", "mycustomview") . ".</th></tr>\n";

      echo "<tr class='tab_bg_1 center'><td>" . __('Affichage des groupe(s)', 'mycustomview') . "</td>";
      echo "<td>";
      $group   = new Group();;
      $result  = $group->find();

      $groups = [];
      foreach ($result as $data) {
         $groups[$data['id']] = $data['name'];
      }
      if ($self->fields['groups_id'] == NULL) {
         Dropdown::showFromArray("groups_id", $groups, ['multiple' => true,
                                                            'width'    => 200,
                                                            'max'      => 2,
                                                            'value'    => $self->fields["groups_id"]]);
      } else {
         Dropdown::showFromArray("groups_id", $groups, ['multiple' => true,
                                                            'width'    => 200,
                                                            'max'      => 2,
                                                            'values'   => json_decode($self->fields["groups_id"], true)]);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1 top'><td>" . __('Affichage des Tickets à traiter', 'rp') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("Tickets_to_be_processed", $self->fields["Tickets_to_be_processed"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1 top'><td>" . __('Affichage des Tâches de tickets à traiter', 'rp') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("Ticket_tasks_to_be_addressed", $self->fields["Ticket_tasks_to_be_addressed"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1 top'><td>" . __('Affichage des Tickets en attente', 'rp') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("Pending_tickets", $self->fields["Pending_tickets"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1 top'><td>" . __('Affichage des Tickets en cours', 'rp') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("Current_tickets", $self->fields["Current_tickets"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1 top'><td>" . __('Affichage des Tickets observés', 'rp') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("Observed_tickets", $self->fields["Observed_tickets"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1 center'><td colspan='2'>";
      echo Html::submit(_sx('button', 'Post'), ['name' => 'update_user_preferences_mycustomview', 'class' => 'btn btn-primary']);
      echo Html::hidden('id', ['value' => $ID]);
      echo "</td></tr>";
      echo "</table>";

      echo "</div>";
      Html::closeForm();

   }

   function prepareInputForUpdate($input) {
      if (isset($input['groups_id'])) {
         $input['groups_id'] = json_encode($input['groups_id']);
      } else {
         $input['groups_id'] = 'NULL';
      }
      return $input;
   }
}

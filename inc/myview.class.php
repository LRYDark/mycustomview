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

class PluginMycustomviewMyview extends CommonDBTM
{

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      global $PLUGIN_HOOKS, $DB;

      if (!(PluginMycustomviewProfile::checkProfileRight($_SESSION['glpiactiveprofile']['id']))) {
         return false;
      }
      if ($item->getType() == 'Central') {

         $group   = new PluginMycustomviewPreference();;
         $result  = $group->find();
         $groups = [];
         foreach ($result as $data) {
            $group_id = $data['groups_id'];
            echo $group_id;
            

            //array_push($tabs, __("TEST 3", "mycustomview"));
         }


        /* if(!empty($result->comment)){
            return __("$result->comment", "mycustomview");
         }elseif(!empty($result->name)){
            return __("$result->name", "mycustomview");
         }else{
            return 0;
         }*/


         $tabs = [
            1 => __("TEST", "mycustomview"),
            2 => __("TEST 2", "mycustomview"),
        ];

        return $tabs;
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      //global $PLUGIN_HOOKS, $DB;

      if ($item->getType() == 'Central') {
         switch ($tabnum) {
             case 1:
                 self::showMyViewGroup();
                 break;

             case 2:
                 $item->showGroupView();
                 break;
         }
     }
     return true;
   }

   public static function showMyViewGroup()
   {
      global $PLUGIN_HOOKS, $DB;

      $group   = new PluginMycustomviewPreference();;
      $result  = $group->find();
      $tabs = [];

      $i = 0;
      foreach ($result as $data) {
         $groups_id = $data['groups_id'];
         $group_id = json_decode($groups_id);
         foreach ($group_id as $data) {
            $result = $DB->query("SELECT * FROM glpi_plugin_rpauto_surveys WHERE id = $data")->fetch_object();
            if(!empty($result->comment)){
                return __("$result->comment", "mycustomview");

                array_push($tabs, __("TEST".$i, "mycustomview"));
             }elseif(!empty($result->name)){
                return __("$result->name", "mycustomview");

                array_push($tabs, __("TEST".$i, "mycustomview"));
             }
            $i++;
         }
      }

      print_r($tabs);
         


       /*$showticket = Session::haveRightsOr("ticket", [Ticket::READALL, Ticket::READASSIGN]);

       $showproblem = Session::haveRightsOr('problem', [Problem::READALL, Problem::READMY]);

       $showchange = Session::haveRightsOr('change', [Change::READALL, Change::READMY]);

       $lists = [];

       if ($showticket) {
           $lists[] = [
               'itemtype'  => Ticket::class,
               'status'    => 'process'
           ];
           $lists[] = [
               'itemtype'  => TicketTask::class,
               'status'    => 'todo'
           ];
       }
       if (Session::haveRight('ticket', Ticket::READGROUP)) {
           $lists[] = [
               'itemtype'  => Ticket::class,
               'status'    => 'waiting'
           ];
       }
       if ($showproblem) {
           $lists[] = [
               'itemtype'  => Problem::class,
               'status'    => 'process'
           ];
           $lists[] = [
               'itemtype'  => ProblemTask::class,
               'status'    => 'todo'
           ];
       }

       if ($showchange) {
           $lists[] = [
               'itemtype'  => Change::class,
               'status'    => 'process'
           ];
           $lists[] = [
               'itemtype'  => ChangeTask::class,
               'status'    => 'todo'
           ];
       }

       if (Session::haveRight('ticket', Ticket::READGROUP)) {
           $lists[] = [
               'itemtype'  => Ticket::class,
               'status'    => 'observed'
           ];
           $lists[] = [
               'itemtype'  => Ticket::class,
               'status'    => 'toapprove'
           ];
           $lists[] = [
               'itemtype'  => Ticket::class,
               'status'    => 'requestbyself'
           ];
       } else {
           $lists[] = [
               'itemtype'  => Ticket::class,
               'status'    => 'waiting'
           ];
       }

       $twig_params = [
           'cards' => [],
       ];
       foreach ($lists as $list) {
           $card_params = [
               'start'             => 0,
               'status'            => $list['status'],
               'showgrouptickets'  => 'true'
           ];
           $idor = Session::getNewIDORToken($list['itemtype'], $card_params);
           $twig_params['cards'][] = [
               'itemtype'  => $list['itemtype'],
               'widget'    => 'central_list',
               'params'    => $card_params + [
                   '_idor_token'  => $idor
               ]
           ];
       }
       TemplateRenderer::getInstance()->display('central/widget_tab.html.twig', $twig_params);*/
   }

   
}
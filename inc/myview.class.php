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

use Glpi\Application\View\TemplateRenderer;

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
        $tabs = [];
  
        $i = 0;
        foreach ($result as $data) {
           $groups_id = $data['groups_id'];
           $group_id = json_decode($groups_id);
           foreach ($group_id as $data) {
              $result = $DB->query("SELECT * FROM glpi_groups WHERE id = $data")->fetch_object();
              if(!empty($result->comment)){
                  array_push($tabs, __($result->comment, "mycustomview"));
               }elseif(!empty($result->name)){
                  array_push($tabs, __($result->name, "mycustomview"));
               }
              $i++;
           }
        }

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
      global $PLUGIN_HOOKS, $DB, $CFG_GLPI;
    
    //***************************************************REQUETE */
      /*$WHERE = [
        'is_deleted' => 0
      ];
      $criteria = [
        'SELECT'          => ['glpi_tickets.id', 'glpi_tickets.date_mod'],
        'DISTINCT'        => true,
        'FROM'            => 'glpi_tickets',
        'LEFT JOIN'       => [
            'glpi_tickets_users'    => [
                'ON' => [
                    'glpi_tickets_users' => 'tickets_id',
                    'glpi_tickets'       => 'id'
                ]
            ],
            'glpi_groups_tickets'   => [
                'ON' => [
                    'glpi_groups_tickets'   => 'tickets_id',
                    'glpi_tickets'          => 'id'
                ]
            ]
        ],
        'WHERE'           => $WHERE + getEntitiesRestrictCriteria('glpi_tickets'),
        'ORDERBY'         => 'glpi_tickets.date_mod DESC'
    ];*/
    $criteria ="SELECT glpi_tickets.id, glpi_tickets.name, glpi_tickets.content, glpi_tickets.entities_id  FROM glpi_tickets 
                LEFT JOIN glpi_groups_tickets ON glpi_groups_tickets.tickets_id = glpi_tickets.id 
                    WHERE glpi_groups_tickets.groups_id = 5 
                        AND glpi_tickets.status IN ('2', '3')
                        AND glpi_tickets.is_deleted = 0
                        ORDER BY glpi_tickets.date_mod DESC;";
    //***************************************************REQUETE */

    // Variables (requete)
    $iterator = $DB->request($criteria);
    $total_row_count = count($iterator);
    $displayed_row_count = min((int)$_SESSION['glpidisplay_count_on_home'], $total_row_count);

    //***************************************************TABLEAU */
      $main_header = "<a href=\"" . Ticket::getSearchURL() . "?" .
      Toolbox::append_params("test", '&amp;') . "\">" .
      Html::makeTitle(__('Your tickets in progress'), $displayed_row_count, $total_row_count) . "</a>";

      $twig_params = [
        'class'        => 'table table-borderless table-striped table-hover card-table',
        'header_rows'  => [
            [
                [
                    'colspan'   => 4,
                    'content'   => $main_header
                ],
            ]
        ],
        'rows'         => []
    ];
    $twig_params['header_rows'][] = [
        [
            'content'   => __('ID'),
            'style'     => 'width: 75px'
        ],
        [
            'content'   => _n('Requester', 'Requesters', 1),
            'style'     => 'width: 20%'
        ],
        [
            'content'   => _n('Entity', 'Entity', 1),
            'style'     => 'width: 20%'
        ],
        __('Description')
    ];
    //***************************************************TABLEAU */
    $i = 0;
    foreach ($iterator as $data) {
        $showprivate = false;
        if (Session::haveRight('followup', ITILFollowup::SEEPRIVATE)) {
            $showprivate = true;
        }

        $job = new self();
        $rand = mt_rand();
        $row = [
            'values' => []
        ];
            /************************************************************ID */
            $ID = $data['id'];
            $row['values'][] = [
                'content' => "<div class='priority_block' style='border-color: black'><span style='background: white'></span>&nbsp;$ID</div>"
            ];
            /************************************************************ID */

            /************************************************************demandeur */
            $requesters = [];
            if (
                isset($job->users[CommonITILActor::REQUESTER])
                && count($job->users[CommonITILActor::REQUESTER])
            ) {
                foreach ($job->users[CommonITILActor::REQUESTER] as $d) {
                    if ($d["users_id"] > 0) {
                        $userdata = getUserName($d["users_id"], 2);
                        $name = '<i class="fas fa-sm fa-fw fa-user text-muted me-1"></i>' .
                            $userdata['name'];
                        $requesters[] = $name;
                    } else {
                        $requesters[] = '<i class="fas fa-sm fa-fw fa-envelope text-muted me-1"></i>' .
                            $d['alternative_email'];
                    }
                }
            }

            if (
                isset($job->groups[CommonITILActor::REQUESTER])
                && count($job->groups[CommonITILActor::REQUESTER])
            ) {
                foreach ($job->groups[CommonITILActor::REQUESTER] as $d) {
                    $requesters[] = '<i class="fas fa-sm fa-fw fa-users text-muted me-1"></i>' .
                        Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
                }
            }
            $row['values'][] = implode('<br>', $requesters);
            /************************************************************demandeur */

            /************************************************************elements associés */
            $associated_elements = [];
            $entity_id = $data['entities_id'];

            $result = $DB->query("SELECT name, completename FROM glpi_entities WHERE id = $entity_id")->fetch_object();
            if(!empty($result->completename)){
                $associated_elements[] = __($result->completename);
            }else{
                $associated_elements[] = __($result->name);
            }

            $row['values'][] = implode('<br>', $associated_elements);
            /************************************************************elements associés */

            /************************************************************descritpion */
            $ticket_id = $data['id'];
            $ticket_name = $data['name'];
            $ticket_content = $data['content'];
            
            $link = "<a id='ticket" . $ticket_id . "' href='" . Ticket::getFormURLWithID($ticket_id);
            $link .= "'>";
            $link = sprintf(
                __('%1$s %2$s'),
                $link,
                Html::showToolTip(
                    Glpi\RichText\RichText::getEnhancedHtml($ticket_content),
                    ['applyto' => 'ticket' . $ticket_id,
                        'display' => false
                    ]
                )
            );
            $link .= $ticket_name;
            $row['values'][] = $link;
            /************************************************************descritpion */

        $twig_params['rows'][] = $row;

        $i++;
        if ($i == $displayed_row_count) {
            break;
        }
    }
    $output = TemplateRenderer::getInstance()->render('components/table.html.twig', $twig_params);
    echo $output;







   }
}
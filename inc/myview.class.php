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

        $group   = new PluginMycustomviewPreference();
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
      global $PLUGIN_HOOKS, $DB;

      if ($item->getType() == 'Central') {
        $group   = new PluginMycustomviewPreference();
        $result  = $group->find();
        $i = 0;
        foreach ($result as $data) {
            $groups_id = $data['groups_id'];
            $group_id = json_decode($groups_id);
            foreach ($group_id as $data) {
                switch ($tabnum) {
                    case $i:
                        self::showMyViewGroup($data);
                        break;
                }
                $i++;
            }
        }
     }
     return true;
    }

    public static function getProcessStatusArray()
    {
        return [Ticket::ASSIGNED, Ticket::PLANNED];
    }

    public static function showMyViewGroup($id_group){
        global $PLUGIN_HOOKS, $DB, $CFG_GLPI;

        $user_id = session::getLoginUserID();
        $glpi_config = $DB->query("SELECT display_count_on_home FROM glpi_users WHERE id = $user_id")->fetch_object();
        
        echo '<div class="masonry_grid row row-cards mb-5" style="position: relative; height: 183px;">';
            // _____________________________ TABLEAU 1 _____________________________ TICKETS À TRAITER 'process'

            echo '<div class="grid-item col-xl-6" style="position: absolute; left: 0%; top: 0px;">';
            echo '<div class="card">';
            echo '<div class="card-body p-0">';

                //***************************************************REQUETE */
                $criteria ="SELECT glpi_tickets.id, glpi_tickets.name, glpi_tickets.content, glpi_tickets.entities_id, glpi_tickets.priority FROM glpi_tickets 
                            LEFT JOIN glpi_groups_tickets ON glpi_groups_tickets.tickets_id = glpi_tickets.id 
                                WHERE glpi_groups_tickets.groups_id = $id_group 
                                    AND glpi_tickets.status IN ('1', '2')
                                    AND glpi_tickets.is_deleted = 0
                                    AND glpi_groups_tickets.type = 2
                                    ORDER BY glpi_tickets.date_mod DESC;";
                //***************************************************REQUETE */

                // Variables (requete)
                $iterator = $DB->request($criteria);
                $total_row_count = count($iterator);
                $displayed_row_count = min((int)$_SESSION['glpidisplay_count_on_home'], $total_row_count);

                $options['criteria'] = [
                    [
                        'field'        => 8,
                        'searchtype'   => 'equals',
                        'value'        => $id_group,
                        'link'         => 'AND',
                    ],
                    [
                        'link' => 'AND',
                        'criteria' => [
                            [
                                'link'        => 'AND',
                                'field'       => 12,
                                'searchtype'  => 'equals',
                                'value'       => Ticket::INCOMING,
                            ],
                            [
                                'link'        => 'OR',
                                'field'       => 12,
                                'searchtype'  => 'equals',
                                'value'       => 'process',
                            ]
                        ]
                    ]
                ];

                $main_header = "<a href=\"" . Ticket::getSearchURL() . "?" .
                Toolbox::append_params($options, '&amp;') . "\">" .
                Html::makeTitle(__('Tickets to be processed'), $displayed_row_count, $total_row_count) . "</a>";

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

                if ($displayed_row_count > 0) {
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

                    $i = 0;
                    foreach ($iterator as $data) {

                        if ($i == $glpi_config->display_count_on_home) {
                            break;
                        }

                        $row = [
                            'values' => []
                        ];
                            //************************************************************ID 
                            $bgcolor = $_SESSION["glpipriority_" . $data["priority"]];
                            $ID = $data['id'];
                            $row['values'][] = [
                                'content' => "<div class='priority_block' style='border-color: $bgcolor'><span style='background: $bgcolor'></span>&nbsp;$ID</div>"
                            ];
                            //************************************************************ID 

                            //************************************************************demandeur 
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
                            //************************************************************demandeur 

                            //************************************************************elements associés 
                            $associated_elements = [];
                            $entity_id = $data['entities_id'];

                            $result = $DB->query("SELECT name, completename FROM glpi_entities WHERE id = $entity_id")->fetch_object();
                            if(!empty($result->completename)){
                                $associated_elements[] = "<span class='glpi-badge form-field row col-12 d-flex align-items-center mb-2' style='margin-top:3px'> ".__($result->completename)." </span>";
                            }else{
                                $associated_elements[] = "<span class='glpi-badge form-field row col-12 d-flex align-items-center mb-2' style='margin-top:3px'> ".__($result->name)." </span>";
                            }

                            $row['values'][] = implode('<br>', $associated_elements);
                            //************************************************************elements associés 

                            //************************************************************descritpion 
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
                            //************************************************************descritpion 

                        $twig_params['rows'][] = $row;

                        if ($i == $displayed_row_count) {
                            break;
                        }
                        $i++;
                    }
                    $output = TemplateRenderer::getInstance()->render('components/table.html.twig', $twig_params);
                    echo $output;
                }
            echo '</div>';
            echo '</div>';
            echo '</div>';
            // _____________________________ TABLEAU 1 _____________________________

            //******************************************************************* */
            // _____________________________ TABLEAU 2 _____________________________ VOS TICKETS EN COURS 'requestbyself'
            echo '<div class="grid-item col-xl-6" style="position: absolute; left: 50%; top: 0px;">';
            echo '<div class="card">';
            echo '<div class="card-body p-0">';
                //***************************************************REQUETE */
                $criteria2 ="SELECT glpi_tickets.id, glpi_tickets.name, glpi_tickets.content, glpi_tickets.entities_id, glpi_tickets.priority FROM glpi_tickets 
                LEFT JOIN glpi_groups_tickets ON glpi_groups_tickets.tickets_id = glpi_tickets.id 
                    WHERE glpi_groups_tickets.groups_id = $id_group 
                        AND glpi_tickets.status IN ('2', '3')
                        AND glpi_tickets.is_deleted = 0
                        ORDER BY glpi_tickets.date_mod DESC;";
                //***************************************************REQUETE */

                // Variables (requete)
                $iterator2 = $DB->request($criteria2);
                $total_row_count2 = count($iterator2);
                $displayed_row_count2 = min((int)$_SESSION['glpidisplay_count_on_home'], $total_row_count2);

                $options2['criteria'][0]['field']      = 12; // status
                $options2['criteria'][0]['searchtype'] = 'equals';
                $options2['criteria'][0]['value']      = 'notold';
                $options2['criteria'][0]['link']       = 'AND';

                $options2['criteria'][1]['field']      = 71; // groups_id
                $options2['criteria'][1]['searchtype'] = 'equals';
                $options2['criteria'][1]['value']      = $id_group;
                $options2['criteria'][1]['link']       = 'AND';

                $main_header2 = "<a href=\"" . Ticket::getSearchURL() . "?" .
                Toolbox::append_params($options2, '&amp;') . "\">" .
                Html::makeTitle(__('Your tickets in progress'), $displayed_row_count2, $total_row_count2) . "</a>";

                $twig_params = [
                    'class'        => 'table table-borderless table-striped table-hover card-table',
                    'header_rows'  => [
                        [
                            [
                                'colspan'   => 4,
                                'content'   => $main_header2
                            ],
                        ]
                    ],
                    'rows'         => []
                ];

                if ($displayed_row_count2 > 0) {
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

                    $i = 0;
                    foreach ($iterator2 as $data) {

                        if ($i == $glpi_config->display_count_on_home) {
                            break;
                        }

                        $row = [
                            'values' => []
                        ];
                            /************************************************************ID */
                            $bgcolor = $_SESSION["glpipriority_" . $data["priority"]];
                            $ID = $data['id'];
                            $row['values'][] = [
                                'content' => "<div class='priority_block' style='border-color: $bgcolor'><span style='background: $bgcolor'></span>&nbsp;$ID</div>"
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
                                $associated_elements[] = "<span class='glpi-badge form-field row col-12 d-flex align-items-center mb-2' style='margin-top:3px'> ".__($result->completename)." </span>";
                            }else{
                                $associated_elements[] = "<span class='glpi-badge form-field row col-12 d-flex align-items-center mb-2' style='margin-top:3px'> ".__($result->name)." </span>";
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

                        if ($i == $displayed_row_count) {
                            break;
                        }
                        $i++;
                    }
                    $output = TemplateRenderer::getInstance()->render('components/table.html.twig', $twig_params);
                    echo $output;
                }
            echo '</div>';
            echo '</div>';
            echo '</div>';
            // _____________________________ TABLEAU 2 _____________________________

        echo '</div>';
    }
}
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
           if(!empty($groups_id)){
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
        $pref = $DB->query("SELECT * FROM glpi_plugin_mycustomview_preferences WHERE users_id = $user_id")->fetch_object();

        $tableau_nbr = 0;
        $taille_space = 0;
        $tableau_space = [];
        ?><script>
            var card = [];
            var card_pair = [];
            var card_impair = [];
        </script><?php

        $glpi_config = $DB->query("SELECT display_count_on_home FROM glpi_users WHERE id = $user_id")->fetch_object();
        
        echo '<div class="masonry_grid row row-cards mb-5" style="position: relative; height: 0px;" id="tableau">';
        
        // _____________________________ TABLEAU 1 _____________________________ TICKETS À TRAITER 'process'
                //***************************************************REQUETE */
                $criteria ="SELECT glpi_tickets.id, glpi_tickets.name, glpi_tickets.content, glpi_tickets.entities_id, glpi_tickets.priority, glpi_tickets.date_creation, glpi_tickets.date_mod FROM glpi_tickets 
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

                if ($displayed_row_count > 0 && $pref->Tickets_to_be_processed != 0) {
                   
                    if ($tableau_nbr % 2 == 0) {
                        echo '<div class="grid-item col-xl-6" style="position: absolute; left: 0%; top: 0px;" id="table_card_'.$tableau_nbr.'">';
                    } else {
                        echo '<div class="grid-item col-xl-6" style="position: absolute; left: 50%; top: 0px;" id="table_card_'.$tableau_nbr.'">';
                    }

                    ?><script>
                        // Supposons que l'ID de votre tableau soit "monTableau"
                        var tableau_nbr = <?php echo json_encode($tableau_nbr) ?>;
                        var tableau = document.getElementById("table_card_"+tableau_nbr);
                        // Obtenir la hauteur en pixels
                        var hauteur = tableau.offsetHeight;
                        card.push(hauteur);

                        if (tableau_nbr % 2 == 0) {
                            card_pair.push(hauteur);
                        } else {
                            card_impair.push(hauteur);
                        }

                        if (tableau_nbr > 1 && tableau_nbr < 4) {
                            var margin_top = card[tableau_nbr-2] + 15;
                        }else if (tableau_nbr >= 4){
                            var margin_top = card[tableau_nbr-2] + card[tableau_nbr-4] + 30;
                        }

                        document.getElementById("table_card_"+tableau_nbr).style.top = margin_top+'px';
                    </script><?php

                    $tableau_nbr ++;

                    echo '<div class="card">';
                    echo '<div class="card-body p-0">';
        
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
                                    'colspan'   => 5,
                                    'content'   => $main_header
                                ],
                            ]
                        ],
                        'rows'         => []
                    ];

                    $twig_params['header_rows'][] = [
                        [
                            'content'   => __('ID'),
                            'style'     => 'width: 75px',
                        ],
                        [
                            'content'   => _n('Date de création', 'Date de création', 1),
                            'style'     => 'width: 15%',
                        ],
                        [
                            'content'   => _n('Date de modification', 'Date de modification', 1),
                            'style'     => 'width: 18%',
                        ],
                        [
                            'content'   => _n('Entity', 'Entity', 1),
                            'style'     => 'width: 20%',
                        ],
                        __('Description')
                    ];

                    $i = 0;
                    foreach ($iterator as $data) {

                        if($glpi_config->display_count_on_home == NULL){
                            $glpi_config->display_count_on_home = 5;
                        }
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
                                'content' => "<div class='priority_block' style='border-color: $bgcolor; padding-bottom: -10px;'><span style='background: $bgcolor'></span>&nbsp;$ID</div>",
                            ];
                            //************************************************************ID 

                            //************************************************************DATE CREATION
                            $timestamp = strtotime($data['date_creation']);
                            $date = date("Y-m-d", $timestamp);
                                $row['values'][] = $date;
                            //************************************************************DATE CREATION

                            //************************************************************DATE MODIFICATION
                            $timestamp = strtotime($data['date_mod']);
                            $date = date("Y-m-d", $timestamp);
                                $row['values'][] = $date;
                            //************************************************************DATE MODIFICATION 

                            //************************************************************elements associés 
                            $associated_elements = [];
                            $entity_id = $data['entities_id'];

                            $result = $DB->query("SELECT name, completename FROM glpi_entities WHERE id = $entity_id")->fetch_object();
                            if(!empty($result->completename)){
                                $associated_elements[] = "<span class='glpi-badge form-field row col-12 d-flex align-items-center'style='padding: 2px'> ".__($result->completename)." </span>";
                            }else{
                                $associated_elements[] = "<span class='glpi-badge form-field row col-12 d-flex align-items-center'style='padding: 2px'> ".__($result->name)." </span>";
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

                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            // _____________________________ TABLEAU 1 _____________________________

            //******************************************************************* */
        // _____________________________ TABLEAU 2 _____________________________ VOS TICKETS EN COURS 'requestbyself'
                //***************************************************REQUETE */
                $criteria2 ="SELECT glpi_tickets.id, glpi_tickets.name, glpi_tickets.content, glpi_tickets.entities_id, glpi_tickets.priority, glpi_tickets.date_creation, glpi_tickets.date_mod FROM glpi_tickets 
                            LEFT JOIN glpi_groups_tickets ON glpi_groups_tickets.tickets_id = glpi_tickets.id 
                                WHERE glpi_groups_tickets.groups_id = $id_group 
                                    AND glpi_tickets.is_deleted = 0
                                    AND glpi_groups_tickets.type = 1
                                    ORDER BY glpi_tickets.date_mod DESC;";
                //***************************************************REQUETE */

                // Variables (requete)
                $iterator2 = $DB->request($criteria2);
                $total_row_count2 = count($iterator2);
                $displayed_row_count2 = min((int)$_SESSION['glpidisplay_count_on_home'], $total_row_count2);

                if ($displayed_row_count2 > 0 && $pref->Current_tickets != 0) {
                    
                    if ($tableau_nbr % 2 == 0) {
                        echo '<div class="grid-item col-xl-6" style="position: absolute; left: 0%; top: 0px;" id="table_card_'.$tableau_nbr.'">';
                    } else {
                        echo '<div class="grid-item col-xl-6" style="position: absolute; left: 50%; top: 0px;" id="table_card_'.$tableau_nbr.'">';
                    }

                    ?><script>
                        // Supposons que l'ID de votre tableau soit "monTableau"
                        var tableau_nbr = <?php echo json_encode($tableau_nbr) ?>;
                        var tableau = document.getElementById("table_card_"+tableau_nbr);
                        // Obtenir la hauteur en pixels
                        var hauteur = tableau.offsetHeight;
                        card.push(hauteur);

                        if (tableau_nbr % 2 == 0) {
                            card_pair.push(hauteur);
                        } else {
                            card_impair.push(hauteur);
                        }

                        if (tableau_nbr > 1 && tableau_nbr < 4) {
                            var margin_top = card[tableau_nbr-2] + 15;
                        }else if (tableau_nbr >= 4){
                            var margin_top = card[tableau_nbr-2] + card[tableau_nbr-4] + 30;
                        }

                        document.getElementById("table_card_"+tableau_nbr).style.top = margin_top+'px';
                    </script><?php

                    $tableau_nbr ++;

                    echo '<div class="card">';
                    echo '<div class="card-body p-0">';

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
                                    'colspan'   => 5,
                                    'content'   => $main_header2
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
                            'content'   => _n('Date de création', 'Date de création', 1),
                            'style'     => 'width: 15%',
                        ],
                        [
                            'content'   => _n('Date de modification', 'Date de modification', 1),
                            'style'     => 'width: 18%',
                        ],
                        [
                            'content'   => _n('Entity', 'Entity', 1),
                            'style'     => 'width: 20%'
                        ],
                        __('Description')
                    ];

                    $i = 0;
                    foreach ($iterator2 as $data) {

                        if($glpi_config->display_count_on_home == null){
                            $glpi_config->display_count_on_home = 5;
                        }
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

                            //************************************************************DATE CREATION
                            $timestamp = strtotime($data['date_creation']);
                            $date = date("Y-m-d", $timestamp);
                                $row['values'][] = $date;
                            //************************************************************DATE CREATION 

                            //************************************************************DATE MODIFICATION
                            $timestamp = strtotime($data['date_mod']);
                            $date = date("Y-m-d", $timestamp);
                                $row['values'][] = $date;
                            //************************************************************DATE MODIFICATION 

                            /************************************************************elements associés */
                            $associated_elements = [];
                            $entity_id = $data['entities_id'];

                            $result = $DB->query("SELECT name, completename FROM glpi_entities WHERE id = $entity_id")->fetch_object();
                            if(!empty($result->completename)){
                                $associated_elements[] = "<span class='glpi-badge form-field row col-12 d-flex align-items-center'style='padding: 2px'> ".__($result->completename)." </span>";
                            }else{
                                $associated_elements[] = "<span class='glpi-badge form-field row col-12 d-flex align-items-center'style='padding: 2px'> ".__($result->name)." </span>";
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

                        if ($i == $displayed_row_count2) {
                            break;
                        }
                        $i++;
                    }
                    $output = TemplateRenderer::getInstance()->render('components/table.html.twig', $twig_params);
                    echo $output;

                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            // _____________________________ TABLEAU 2 _____________________________

            //******************************************************************* */
        // _____________________________ TABLEAU 3 _____________________________ TICKET EN ATTENTE 'waiting'
                //***************************************************REQUETE */
                $criteria3 ="SELECT glpi_tickets.id, glpi_tickets.name, glpi_tickets.content, glpi_tickets.entities_id, glpi_tickets.priority, glpi_tickets.date_creation, glpi_tickets.date_mod FROM glpi_tickets 
                            LEFT JOIN glpi_groups_tickets ON glpi_groups_tickets.tickets_id = glpi_tickets.id 
                                WHERE glpi_groups_tickets.groups_id = $id_group 
                                    AND glpi_tickets.status = 4
                                    AND glpi_tickets.is_deleted = 0
                                    AND glpi_groups_tickets.type = 2
                                    ORDER BY glpi_tickets.date_mod DESC;";
                //***************************************************REQUETE */

                // Variables (requete)
                $iterator3 = $DB->request($criteria3);
                $total_row_count3 = count($iterator3);
                $displayed_row_count3 = min((int)$_SESSION['glpidisplay_count_on_home'], $total_row_count3);

                if ($displayed_row_count3 > 0 && $pref->Pending_tickets != 0) {
                                    
                    if ($tableau_nbr % 2 == 0) {
                        echo '<div class="grid-item col-xl-6" style="position: absolute; left: 0%; top: 0px;" id="table_card_'.$tableau_nbr.'">';
                    } else {
                        echo '<div class="grid-item col-xl-6" style="position: absolute; left: 50%; top: 0px;" id="table_card_'.$tableau_nbr.'">';
                    }

                    ?><script>
                        // Supposons que l'ID de votre tableau soit "monTableau"
                        var tableau_nbr = <?php echo json_encode($tableau_nbr) ?>;
                        var tableau = document.getElementById("table_card_"+tableau_nbr);
                        // Obtenir la hauteur en pixels
                        var hauteur = tableau.offsetHeight;
                        card.push(hauteur);

                        if (tableau_nbr % 2 == 0) {
                            card_pair.push(hauteur);
                        } else {
                            card_impair.push(hauteur);
                        }

                        if (tableau_nbr > 1 && tableau_nbr < 4) {
                            var margin_top = card[tableau_nbr-2] + 15;
                        }else if (tableau_nbr >= 4){
                            var margin_top = card[tableau_nbr-2] + card[tableau_nbr-4] + 30;
                        }

                        document.getElementById("table_card_"+tableau_nbr).style.top = margin_top+'px';
                    </script><?php

                    $tableau_nbr ++;

                    echo '<div class="card">';
                    echo '<div class="card-body p-0">';

                    $options3['criteria'][0]['field']      = 12; // status
                    $options3['criteria'][0]['searchtype'] = 'equals';
                    $options3['criteria'][0]['value']      = Ticket::WAITING;
                    $options3['criteria'][0]['link']       = 'AND';

                    $options3['criteria'][1]['field']      = 8; // groups_id_assign
                    $options3['criteria'][1]['searchtype'] = 'equals';
                    $options3['criteria'][1]['value']      = $id_group;
                    $options3['criteria'][1]['link']       = 'AND';

                    $main_header3 = "<a href=\"" . Ticket::getSearchURL() . "?" .
                    Toolbox::append_params($options3, '&amp;') . "\">" .
                    Html::makeTitle(__('Tickets on pending status'), $displayed_row_count3, $total_row_count3) . "</a>";

                    $twig_params = [
                        'class'        => 'table table-borderless table-striped table-hover card-table',
                        'header_rows'  => [
                            [
                                [
                                    'colspan'   => 5,
                                    'content'   => $main_header3
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
                            'content'   => _n('Date de création', 'Date de création', 1),
                            'style'     => 'width: 15%',
                        ],
                        [
                            'content'   => _n('Date de modification', 'Date de modification', 1),
                            'style'     => 'width: 18%',
                        ],
                        [
                            'content'   => _n('Entity', 'Entity', 1),
                            'style'     => 'width: 20%'
                        ],
                        __('Description')
                    ];

                    $i = 0;
                    foreach ($iterator3 as $data) {

                        if($glpi_config->display_count_on_home == null){
                            $glpi_config->display_count_on_home = 5;
                        }
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

                            //************************************************************DATE CREATION
                            $timestamp = strtotime($data['date_creation']);
                            $date = date("Y-m-d", $timestamp);
                                $row['values'][] = $date;
                            //************************************************************DATE CREATION 
                            
                            //************************************************************DATE MODIFICATION
                            $timestamp = strtotime($data['date_mod']);
                            $date = date("Y-m-d", $timestamp);
                                $row['values'][] = $date;
                            //************************************************************DATE MODIFICATION 

                            /************************************************************elements associés */
                            $associated_elements = [];
                            $entity_id = $data['entities_id'];

                            $result = $DB->query("SELECT name, completename FROM glpi_entities WHERE id = $entity_id")->fetch_object();
                            if(!empty($result->completename)){
                                $associated_elements[] = "<span class='glpi-badge form-field row col-12 d-flex align-items-center'style='padding: 2px'> ".__($result->completename)." </span>";
                            }else{
                                $associated_elements[] = "<span class='glpi-badge form-field row col-12 d-flex align-items-center'style='padding: 2px'> ".__($result->name)." </span>";
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

                        if ($i == $displayed_row_count3) {
                            break;
                        }
                        $i++;
                    }
                    $output = TemplateRenderer::getInstance()->render('components/table.html.twig', $twig_params);
                    echo $output;

                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            // _____________________________ TABLEAU 3 _____________________________

            //******************************************************************* */
        // _____________________________ TABLEAU 4 _____________________________ VOS TICKETS OBSERVES 'observed'
                //***************************************************REQUETE */
                $status_ticket_incoming     = Ticket::INCOMING;
                $status_ticket_planned      = Ticket::PLANNED;
                $status_ticket_assigned     = Ticket::ASSIGNED;
                $status_ticket_waiting      = Ticket::WAITING;
                $criteria4 ="SELECT glpi_tickets.id, glpi_tickets.name, glpi_tickets.content, glpi_tickets.entities_id, glpi_tickets.priority, glpi_tickets.date_creation, glpi_tickets.date_mod FROM glpi_tickets 
                            LEFT JOIN glpi_groups_tickets ON glpi_groups_tickets.tickets_id = glpi_tickets.id 
                                WHERE glpi_groups_tickets.groups_id = $id_group 
                                    AND glpi_tickets.status IN ('$status_ticket_incoming', '$status_ticket_planned' , '$status_ticket_assigned' , '$status_ticket_waiting')
                                    AND glpi_tickets.is_deleted = 0
                                    AND glpi_groups_tickets.type = 3
                                    ORDER BY glpi_tickets.date_mod DESC;";
                //***************************************************REQUETE */

                // Variables (requete)
                $iterator4 = $DB->request($criteria4);
                $total_row_count4 = count($iterator4);
                $displayed_row_count4 = min((int)$_SESSION['glpidisplay_count_on_home'], $total_row_count4);

                if ($displayed_row_count4 > 0 && $pref->Observed_tickets != 0) {
    
                    if ($tableau_nbr % 2 == 0) {
                        echo '<div class="grid-item col-xl-6" style="position: absolute; left: 0%; top: 0px;" id="table_card_'.$tableau_nbr.'">';
                    } else {
                        echo '<div class="grid-item col-xl-6" style="position: absolute; left: 50%; top: 0px;" id="table_card_'.$tableau_nbr.'">';
                    }

                    ?><script>
                        // Supposons que l'ID de votre tableau soit "monTableau"
                        var tableau_nbr = <?php echo json_encode($tableau_nbr) ?>;
                        var tableau = document.getElementById("table_card_"+tableau_nbr);
                        // Obtenir la hauteur en pixels
                        var hauteur = tableau.offsetHeight;
                        card.push(hauteur);

                        if (tableau_nbr % 2 == 0) {
                            card_pair.push(hauteur);
                        } else {
                            card_impair.push(hauteur);
                        }

                        if (tableau_nbr > 1 && tableau_nbr < 4) {
                            var margin_top = card[tableau_nbr-2] + 15;
                        }else if (tableau_nbr >= 4){
                            var margin_top = card[tableau_nbr-2] + card[tableau_nbr-4] + 30;
                        }

                        document.getElementById("table_card_"+tableau_nbr).style.top = margin_top+'px';
                    </script><?php

                    $tableau_nbr ++;
                    
                    echo '<div class="card">';
                    echo '<div class="card-body p-0">';

                    $options4['criteria'][0]['field']      = 12; // status
                    $options4['criteria'][0]['searchtype'] = 'equals';
                    $options4['criteria'][0]['value']      = 'notold';
                    $options4['criteria'][0]['link']       = 'AND';

                    $options4['criteria'][1]['field']      = 65; // groups_id
                    $options4['criteria'][1]['searchtype'] = 'equals';
                    $options4['criteria'][1]['value']      = $id_group;
                    $options4['criteria'][1]['link']       = 'AND';

                    $main_header4 = "<a href=\"" . Ticket::getSearchURL() . "?" .
                    Toolbox::append_params($options4, '&amp;') . "\">" .
                    Html::makeTitle(__('Your observed tickets'), $displayed_row_count4, $total_row_count4) . "</a>";

                    $twig_params = [
                        'class'        => 'table table-borderless table-striped table-hover card-table',
                        'header_rows'  => [
                            [
                                [
                                    'colspan'   => 5,
                                    'content'   => $main_header4
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
                            'content'   => _n('Date de création', 'Date de création', 1),
                            'style'     => 'width: 15%',
                        ],
                        [
                            'content'   => _n('Date de modification', 'Date de modification', 1),
                            'style'     => 'width: 18%',
                        ],
                        [
                            'content'   => _n('Entity', 'Entity', 1),
                            'style'     => 'width: 20%'
                        ],
                        __('Description')
                    ];

                    $i = 0;
                    foreach ($iterator4 as $data) {

                        if($glpi_config->display_count_on_home == null){
                            $glpi_config->display_count_on_home = 5;
                        }
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

                            //************************************************************DATE CREATION
                            $timestamp = strtotime($data['date_creation']);
                            $date = date("Y-m-d", $timestamp);
                                $row['values'][] = $date;
                            //************************************************************DATE CREATION
                            
                            //************************************************************DATE MODIFICATION
                            $timestamp = strtotime($data['date_mod']);
                            $date = date("Y-m-d", $timestamp);
                                $row['values'][] = $date;
                            //************************************************************DATE MODIFICATION 

                            /************************************************************elements associés */
                            $associated_elements = [];
                            $entity_id = $data['entities_id'];

                            $result = $DB->query("SELECT name, completename FROM glpi_entities WHERE id = $entity_id")->fetch_object();
                            if(!empty($result->completename)){
                                $associated_elements[] = "<span class='glpi-badge form-field row col-12 d-flex align-items-center'style='padding: 2px'> ".__($result->completename)." </span>";
                            }else{
                                $associated_elements[] = "<span class='glpi-badge form-field row col-12 d-flex align-items-center'style='padding: 2px'> ".__($result->name)." </span>";
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

                        if ($i == $displayed_row_count4) {
                            break;
                        }
                        $i++;
                    }
                    $output = TemplateRenderer::getInstance()->render('components/table.html.twig', $twig_params);
                    echo $output;

                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            // _____________________________ TABLEAU 4 _____________________________
                        
            //******************************************************************* */
        // _____________________________ TABLEAU 10 _____________________________ VOS TACHES DE TICKET A TRAITER 
                //***************************************************REQUETE
                $criteria10 ="	SELECT *, glpi_tickettasks.content AS task_content FROM glpi_tickets 
                                LEFT JOIN glpi_groups_tickets ON glpi_groups_tickets.tickets_id = glpi_tickets.id 
                                LEFT JOIN glpi_tickettasks ON glpi_tickettasks.tickets_id = glpi_tickets.id 
                                    WHERE glpi_groups_tickets.groups_id = 5 
                                        AND glpi_tickettasks.groups_id_tech = 5
                                        AND glpi_tickettasks.state = 1
                                        AND glpi_tickets.is_deleted = 0
                                        ORDER BY glpi_tickets.date_mod DESC;";
                //***************************************************REQUETE 
                
                // Variables (requete)
                $iterator10 = $DB->request($criteria10);
                $total_row_count10 = count($iterator10);
                $displayed_row_count10 = min((int)$_SESSION['glpidisplay_count_on_home'], $total_row_count10);

                if ($displayed_row_count10 > 0 && $pref->Ticket_tasks_to_be_addressed != 0) {

                    if ($tableau_nbr % 2 == 0) {
                        echo '<div class="grid-item col-xl-6" style="position: absolute; left: 0%; top: 0px;" id="table_card_'.$tableau_nbr.'">';
                    } else {
                        echo '<div class="grid-item col-xl-6" style="position: absolute; left: 50%; top: 0px;" id="table_card_'.$tableau_nbr.'">';
                    }

                    ?><script>
                        // Supposons que l'ID de votre tableau soit "monTableau"
                        var tableau_nbr = <?php echo json_encode($tableau_nbr) ?>;
                        var tableau = document.getElementById("table_card_"+tableau_nbr);
                        // Obtenir la hauteur en pixels
                        var hauteur = tableau.offsetHeight;
                        card.push(hauteur);

                        if (tableau_nbr % 2 == 0) {
                            card_pair.push(hauteur);
                        } else {
                            card_impair.push(hauteur);
                        }

                        if (tableau_nbr > 1 && tableau_nbr < 4) {
                            var margin_top = card[tableau_nbr-2] + 15;
                        }else if (tableau_nbr >= 4){
                            var margin_top = card[tableau_nbr-2] + card[tableau_nbr-4] + 30;
                        }

                        document.getElementById("table_card_"+tableau_nbr).style.top = margin_top+'px';
                    </script><?php

                    $tableau_nbr ++;

                    echo '<div class="card">';
                    echo '<div class="card-body p-0">';
                    
                    $options10 = [
                        'reset'    => 'reset',
                        'criteria' => [
                            [
                                'field'      => 12, // status
                                'searchtype' => 'equals',
                                'value'      => 'notold',
                                'link'       => 'AND',
                            ]
                        ],
                    ];
                    $options10['criteria'][] = [
                        'field'      => 112, // tech in charge of task
                        'searchtype' => 'equals',
                        'value'      => $id_group,
                        'link'       => 'AND',
                    ];
                    $options10['criteria'][] = [
                        'field'      => 33, // task status
                        'searchtype' => 'equals',
                        'value'      =>  Planning::TODO,
                        'link'       => 'AND',
                    ];

                    $main_header10 = "<a href=\"" . Ticket::getSearchURL() . "?" .
                    Toolbox::append_params($options10, '&amp;') . "\">" .
                    Html::makeTitle(__('Ticket tasks to do'), $displayed_row_count3, $total_row_count10) . "</a>";

                    $twig_params = [
                        'class'        => 'table table-borderless table-striped table-hover card-table',
                        'header_rows'  => [
                            [
                                [
                                    'colspan'   => 4,
                                    'content'   => $main_header10
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
                            'content'   => _n('Entity', 'Entity', 1),
                            'style'     => 'width: 20%'
                        ],
                        [
                            'content'   => _n('Titre (Ticket)', 'Titre (Ticket)', 1),
                            'style'     => 'width: 20%'
                        ],
                        __('Description (Tâche)')
                    ];

                    $i = 0;
                    foreach ($iterator10 as $data) {

                        if($glpi_config->display_count_on_home == null){
                            $glpi_config->display_count_on_home = 5;
                        }
                        if ($i == $glpi_config->display_count_on_home) {
                            break;
                        }

                        $row = [
                            'values' => []
                        ];
                            //************************************************************ID 
                            $bgcolor = $_SESSION["glpipriority_" . $data["priority"]];
                            $ID = $data['tickets_id'];
                            $row['values'][] = [
                                'content' => "<div class='priority_block' style='border-color: $bgcolor'><span style='background: $bgcolor'></span>&nbsp;$ID</div>"
                            ];
                            //************************************************************ID 

                            //************************************************************elements associés 
                            $associated_elements = [];
                            $entity_id = $data['entities_id'];

                            $result = $DB->query("SELECT name, completename FROM glpi_entities WHERE id = $entity_id")->fetch_object();
                            if(!empty($result->completename)){
                                $associated_elements[] = "<span class='glpi-badge form-field row col-12 d-flex align-items-center'style='padding: 2px'> ".__($result->completename)." </span>";
                            }else{
                                $associated_elements[] = "<span class='glpi-badge form-field row col-12 d-flex align-items-center'style='padding: 2px'> ".__($result->name)." </span>";
                            }

                            $row['values'][] = implode('<br>', $associated_elements);
                            //************************************************************elements associés

                            //************************************************************descritpion
                            $link = "<a id='ticket" . $ticket_id . "' href='" . Ticket::getFormURLWithID($ticket_id);
                            $link .= "'>";
                            $link .= $data['name'];
                            $row['values'][] = $link;
                            //************************************************************descritpion
                            
                            //************************************************************descritpion
                            $ticket_id = $data['tickets_id'];
                            $ticket_name = $data['name'];
                            $task_content = $data['task_content'];
                            
                            $link = "<a id='ticket" . $ticket_id . "' href='" . Ticket::getFormURLWithID($ticket_id);
                            $link .= "'>";

                            $chaine = html_entity_decode($task_content);
                            $chaine = strip_tags($chaine);
                            $nbr = mb_strlen($chaine);
                            $chaine = substr($chaine, 0, 40);
                            
                            if ($nbr > 40){
                                $link .= $chaine.' (...)';   
                            }else{
                                $link .= $chaine;
                            }
                            
                            $row['values'][] = $link;
                            //************************************************************descritpion 

                        $twig_params['rows'][] = $row;

                        if ($i == $displayed_row_count10) {
                            break;
                        }
                        $i++;
                    }
                    $output = TemplateRenderer::getInstance()->render('components/table.html.twig', $twig_params);
                    echo $output;

                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            // _____________________________ TABLEAU 10 _____________________________
        echo '</div>';

        ?><script>
            let sum = 0;
            for (let i = 0; i < card_pair.length; i++) {
                sum += card_pair[i];
            }
            let sum2 = 0;
            for (let i = 0; i < card_impair.length; i++) {
                sum2 += card_impair[i];
            }

            
            console.log(sum); // Affiche 10
            console.log(sum2); // Affiche 10
            document.getElementById("tableau").style.height = sum+'px';
        </script><?php

/*?><script>
// Supposons que l'ID de votre tableau soit "monTableau"
var tableau_nbr = <?php echo json_encode($tableau_nbr) ?>;
var tableau = document.getElementById("table_card_"+tableau_nbr);
// Obtenir la hauteur en pixels
var hauteur = tableau.offsetHeight;
card.push(hauteur);

if (tableau_nbr > 1 && tableau_nbr < 4) {
    var margin_top = card[tableau_nbr-2] + 15;
}else if (tableau_nbr >= 4){
    var margin_top = card[tableau_nbr-2] + card[tableau_nbr-4] + 30;
}

document.getElementById("table_card_"+tableau_nbr).style.top = margin_top+'px';
</script><?php*/
    }
}
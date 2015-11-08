<?php

/*
* PHP functions for OLZ Theme
*/

function olz_current_menu($type, $ident) {
    $menus = get_nav_menu_locations();
    $primary_menu = wp_get_nav_menu_items($menus['primary'], array('theme_location'=>'primary'));
    $id = -1;
    if ($type=='single' && $ident=='attachment') $id = 0;
    for ($i=0; $i<count($primary_menu) && $id==-1; $i++) {
        $mp = $primary_menu[$i];
        if (($type=='archive' || $type=='single') && isset($mp->classes[0]) && $mp->classes[0]=='posttype-archive-'.$ident) $id = $mp->ID;
        if ($type=='page' && $mp->object=='page' && $mp->object_id==$ident) $id = $mp->ID;
    }
    if (0<$id) {
        echo "<style>#menu-item-".$id." { background-color: rgba(0, 255, 0, 0.4); }</style>";
    } else if ($id==0) {
    } else {
        echo "<script>";
        echo "console.warn(\"Diese Seite ist nicht im Menu\\nType:\", ".json_encode($type).", \"\\nIdent:\", ".json_encode($ident).", \"\\nMenu:\", ".json_encode(var_export($primary_menu, true)).");";
        echo "</script>";
    }
}


function olz_form($fields, $submit) {
    $htmlout = "";
    $htmlout .= "<table class=\"form-table\">";
    $ucidents = array();
    for ($i=0; $i<count($fields); $i++) {
        $fld = $fields[$i];
        if ($fld['type']!='hidden') $htmlout .= "<tr><th><label for=\"".$fld['ident']."\">".$fld['name'].":</label></th><td style=\"position:relative;\">";
        $value = "";
        if (isset($fld['value'])) $value = $fld['value'];
        $placeholder = "";
        if (isset($fld['placeholder'])) $placeholder = $fld['placeholder'];
        $disabled = 0;
        if (isset($fld['disabled'])) $disabled = $fld['disabled'];
        $onchange = "";
        if (isset($fld['onchange'])) $onchange = $fld['onchange'];
        if ($fld['type']=='textarea') {
            $htmlout .= "<textarea name=\"".$fld['ident']."\" id=\"olz_form_".$fld['ident']."\"";
            if ($onchange) $htmlout .= " onkeyup=\"".esc_attr($onchange)."\" onchange=\"".esc_attr($onchange)."\"";
            if (0<$disabled) $htmlout .= " disabled=\"disabled\"";
            $htmlout .= " class=\"regular-text\"";
            if (isset($fld['textarea_height'])) $htmlout .= " style=\"height:".$fld['textarea_height'].";\"";
            $htmlout .= ">".esc_html($value)."</textarea>";
        } else if ($fld['type']=='hidden') {
            $htmlout .= "<input type=\"hidden\" name=\"".esc_attr($fld['ident'])."\" id=\"olz_form_".esc_attr($fld['ident'])."\" value=\"".esc_attr($value)."\" />";
        } else if ($fld['type']=='image' || $fld['type']=='images') {
            $htmlout .= "<div class=\"image-upload\" id=\"olz_form_".esc_attr($fld['ident'])."_ui\"></div><input type=\"hidden\" name=\"".esc_attr($fld['ident'])."\" id=\"olz_form_".esc_attr($fld['ident'])."\" value=\"".esc_attr($value)."\" />";
            $args = array('post_type'=>($fld['post_type']?$fld['post_type']:'post'), 'post_ID'=>($fld['post_ID']?intval($fld['post_ID']):-1));
            $htmlout .= "<script>
            olzImageUpload(".json_encode("olz_form_".esc_html($fld['ident'])."_ui").", ".($fld['type']=='images'?"1":"0").", [], ajaxurl, ".json_encode($args).", function (val) {
                jQuery('#olz_form_".esc_html($fld['ident'])."').val(JSON.stringify(val));
            });
            </script>";
        } else {
            $htmlout .= "<input type=\"".esc_attr($fld['type'])."\" name=\"".esc_attr($fld['ident'])."\" id=\"olz_form_".esc_attr($fld['ident'])."\"";
            $htmlout .= " value=\"".esc_attr($value)."\" placeholder=\"".esc_attr($placeholder)."\"";
            if ($onchange) $htmlout .= " onkeyup=\"".esc_attr($onchange)."\" onchange=\"".esc_attr($onchange)."\"";
            if (0<$disabled) $htmlout .= " disabled=\"disabled\"";
            $htmlout .= " class=\"regular-text\" />";
            if ($disabled==1) $htmlout .= "<div style=\"position:absolute; left:0; right:0; top:0; bottom:0;\" onclick=\"".esc_attr("var inp = this.parentElement.firstChild; if (inp.value!=\"Doppelklick zum Bearbeiten\") inp.setAttribute(\"actualvalue\", inp.value); inp.value = \"Doppelklick zum Bearbeiten\"; setTimeout(function () {inp.value = inp.getAttribute(\"actualvalue\");}, 1000);")."\" ondblclick=\"".esc_attr("var inp = this.parentElement.firstChild; inp.disabled = false; inp.value = inp.getAttribute(\"actualvalue\"); this.parentElement.removeChild(this);")."\"></div>";
        }
        if ($fld['type']!='hidden') $htmlout .= "</td></tr>";
    }
    if (!isset($submit['onclick'])) {
        if ($submit['action'] && isset($submit['ident'])) {
            if (isset($submit['args'])) $args = $submit['args'];
            else $args = array(''=>'');
            $ucidents = array();
            for ($i=0; $i<count($fields); $i++) $ucidents[] = $fields[$i]['ident'];
            $submit['onclick'] = "submitOLZForm(".esc_attr(json_encode($submit['ident'])).", ".esc_attr(json_encode($submit['action'])).", ".esc_attr(json_encode($args)).", ".esc_attr(json_encode($ucidents)).")";
        }
    }
    $htmlout .= "<tr><td style=\"text-align:center;\" colspan=\"2\"><button onclick=\"".$submit['onclick']."\" class=\"primary-button\" id=\"olz_form_".$submit['ident']."_submit_button\">".$submit['name']."</button></td></tr><tr><td style=\"text-align:center;\" colspan=\"2\" id=\"olz_form_".$submit['ident']."_response\"></td></tr>";
    $htmlout .= "</table>";
    if (isset($submit['handler'])) {
        $htmlout .= "<script>if (!window.handleOLZFormResponse) window.handleOLZFormResponse = {}; window.handleOLZFormResponse[".json_encode($submit['ident'])."] = ".$submit['handler'].";</script>";
    }
    return $htmlout;
}


function olz_unauthorized_contribution_form($ptident) {
    if (function_exists('olz_post_types')) {
        $pts = olz_post_types();
        if (isset($pts['post_types'][$ptident]) && isset($pts['post_types'][$ptident]['unauthorized_contribution'])) {
            $uc = $pts['post_types'][$ptident]['unauthorized_contribution'];
            $pt = get_post_type_object($ptident);
            $fields = $uc['fields'];
            $fields[] = array('ident'=>'activation_token', 'name'=>"Aktivierungs-Code", 'type'=>'hidden');
            $fields[] = array('ident'=>'postid', 'name'=>"Post-ID", 'type'=>'hidden');
            $uchtml = olz_form($fields, array(
                'name'=>$pt->labels->singular_name." abschicken",
                'ident'=>'uc_'.$ptident,
                'action'=>'unauthorized_contribution',
                'args'=>array('post_type'=>$ptident),
                'handler'=>"function (ident, data) {
                    jQuery('#olz_form_'+ident+'_submit_button').html(\"".$pt->labels->singular_name." ändern\");
                    if (data['postid']) jQuery('#olz_form_postid').val(data['postid']);
                    if (data['activation_token']) jQuery('#olz_form_activation_token').val(data['activation_token']);
                }",
            ));
            return $uchtml;
        }
    }
    return false;
}


function get_past_query_args($ptident) {
  if ($ptident == 'startseite') {
    return array(
      'post_type' => array('berichte', 'galerie', 'forum'),
      'orderby'=> 'post_date',
      'order' => 'DESC',
      'date_query' => array(
        'before' => date("Y-m-d H:i:s"),
        'inclusive' => true,
      ),
    );
  } else if ($ptident == 'termine') {
    return array(
      'post_type' => $ptident,
      'orderby'=>'timerange_start',
      'order' => 'DESC',
      'meta_query' => array(
        'relation' => 'AND',
        'past_clause' => array(
          'key' => 'timerange_start',
          'value' => date("Y-m-d H:i:s"),
          'compare' => '<',
        ),
      ),
    );
  } else {
    return array(
      'post_type' => $ptident,
      'orderby' => 'post_date',
      'order' => 'DESC',
      'date_query' => array(
        'before' => date("Y-m-d H:i:s"),
        'inclusive' => true,
      ),
    );
  }
}

function get_future_query_args($ptident) {
  if ($ptident == 'startseite') {
    return array(
      'post_type' => 'termine',
      'orderby'=> 'timerange_start',
      'order' => 'ASC',
      'meta_query' => array(
        'relation' => 'AND',
        'future_clause' => array(
          'key' => 'timerange_start',
          'value' => date("Y-m-d H:i:s"),
          'compare' => '>=',
        ),
      ),
    );
  } else if ($ptident == 'termine') {
    return array(
      'post_type' => $ptident,
      'orderby'=>'timerange_start',
      'order' => 'ASC',
      'meta_query' => array(
        'relation' => 'AND',
        'future_clause' => array(
          'key' => 'timerange_start',
          'value' => date("Y-m-d H:i:s"),
          'compare' => '>=',
        ),
      ),
    );
  } else {
    return array(
      'post_type' => $ptident,
      'orderby' => 'post_date',
      'order' => 'ASC',
      'date_query' => array(
        'after' => date("Y-m-d H:i:s"),
        'inclusive' => true,
      ),
    );
  }
}

$content_type_mapping = array(
  'berichte' => 'list-article',
  'forum' => 'list-article',
  'galerie' => 'list-galerie',
  'termine' => 'list-termine',
);

$archive_page_size = 15;

function get_archive_chunk($ptident, $page) {
  global $content_type_mapping, $archive_page_size;
  ob_start();
  $args = $page < 0 ? get_future_query_args($ptident) : get_past_query_args($ptident);
  $args['paged'] = $page < 0 ? -$page : $page;
  $args['posts_per_page'] = $archive_page_size;
  $q = new WP_Query($args);
  foreach (($page < 0 ? array_reverse($q->posts) : $q->posts) as $p) {
    global $post;
    $post = $p;
    setup_postdata($post);
    get_template_part('content', $content_type_mapping[$post->post_type]);
  }
  wp_reset_postdata();
  return ob_get_clean();
}
function olz_ajax_get_archive_chunk() {
  return get_archive_chunk($_POST['post_type'], intval($_POST['page']));
}
add_action('wp_ajax_get_archive_chunk', olz_ajaxify('olz_ajax_get_archive_chunk'));
add_action('wp_ajax_nopriv_get_archive_chunk', olz_ajaxify('olz_ajax_get_archive_chunk'));


function get_archive_page($ptident, $date) {
  global $archive_page_size;
  $is_future = date('Y-m-d H:i:s') < $date;
  if ($is_future) {
    $args = get_future_query_args($ptident);
    if (isset($args['meta_query'])) {
      $args['meta_query']['limit_clause'] = array(
        'key' => $args['orderby'],
        'value' => $date,
        'compare' => '<',
      );
    } else if (isset($args['date_query'])) {
      $args['date_query']['before'] = $date;
    }
  } else {
    $args = get_past_query_args($ptident);
    if (isset($args['meta_query'])) {
      $args['meta_query']['limit_clause'] = array(
        'key' => $args['orderby'],
        'value' => $date,
        'compare' => '>=',
      );
    } else if (isset($args['date_query'])) {
      $args['date_query']['after'] = $date;
    }
  }
  $args['posts_per_page'] = -1;
  $q = new WP_Query($args);
  return count($q->posts) / $archive_page_size;
}
function olz_ajax_get_archive_page() {
  return get_archive_page($_POST['post_type'], date('Y-m-d H:i:s', strtotime($_POST['date'])));
}
add_action('wp_ajax_get_archive_page', olz_ajaxify('olz_ajax_get_archive_page'));
add_action('wp_ajax_nopriv_get_archive_page', olz_ajaxify('olz_ajax_get_archive_page'));


function get_archive($ptident) {
  echo '<script>
  jQuery().ready(function () {
    setTimeout(function () {
      buildArchive.apply(window, ' . json_encode(array(
        $ptident,
        date('Y-m-d H:i:sP'),
        array(
          'contentAbove' => array(
            get_archive_chunk($ptident, -1),
            //get_archive_chunk($ptident, -2),
          ),
          'contentBelow' => array(
            get_archive_chunk($ptident, 1),
            get_archive_chunk($ptident, 2),
          ),
        )
      )) . ')
    }, 1)
  })
  </script>';

  echo '<div class="maincolumn"><div class="articlelist above"></div></div>';

  echo '<div id="menu-placeholder">';
  echo '<div class="rightcolumn above">
    <div class="timeline above" style="position: absolute; right: 0px; bottom: 0px; left: 0px;">
      Test1<br><br><br>Test2<br><br><br>Test3<br><br><br>Test4<br><br><br>Test5
    </div>
  </div>';

  include('menu.php');

  echo '<div class="rightcolumn below">
    <div class="timeline below" style="position: absolute; top: 0px; right: 0px; left: 0px;">
      Test1<br><br><br>Test2<br><br><br>Test3<br><br><br>Test4<br><br><br>Test5
    </div>
  </div>';
  echo '</div>';

  $edithtml = '';
  $pt = get_post_type_object($ptident);
  if (current_user_can($pt->cap->edit_posts) && current_user_can($pt->cap->publish_posts)) {
      $edithtml .= '<div style="float:left;"><button onclick="window.location.href = &quot;'.get_admin_url().'/post-new.php?post_type='.$ptident.'&quot;"><span class="dashicons dashicons-plus"></span>'.$pt->labels->add_new_item.'</button></div>';
  } else {
      $uchtml = olz_unauthorized_contribution_form($ptident);
      if ($uchtml) {
          $edithtml .= '<div style="display:none; padding:10px 0px 10px 0px; border-bottom:1px solid rgb(0,120,0); margin:0px 16px 10px 16px;" id="uc_form_'.$ptident.'">'.$uchtml.'</div>';
          $edithtml .= '<div style="float:left;"><button onclick="toggleUCForm('.esc_attr(json_encode($ptident)).')"><span class="dashicons dashicons-plus"></span>'.$pt->labels->add_new_item.'</button></div>';
      }
  }

  echo '<div class="maincolumn">' . ($edithtml ? '<div class="editbar">' . $edithtml . '</div>' : '') . '<div class="articlelist below"></div></div>';
}


/*
function olz_posts_query($ptident) {
    global $_GET;
    if ($ptident=='termine') {
        $args = array(
          'post_type'=>$ptident,
          'meta_query'=>array('key'=>'timerange_end', 'value'=>date('Y-m-d'), 'compare'=>'>='),
          'orderby'=>'meta_value',
          'meta_key'=>'timerange_start',
          'order'=>'ASC',
        );
    } else {
        //$paged = (get_query_var('paged')?get_query_var('paged'):1);
        $args = array(
          'post_type'=>$ptident,
          'orderby'=>'post_date',
          'order'=>'DESC',
        );
        if (!isset($_GET['archive'])) $args['date_query'] = array('after'=>array('year'=>date("Y")-4, 'month'=>1, 'day'=>1), 'inclusive'=>true);
    }
    $q = new WP_Query($args);
    return $q;
}


function olz_posts_list($q, $content_type, $no_entries="Keine Einträge") {
    global $_GET, $month_names, $post;
    $ptident = $q->query_vars['post_type'];
    $pt = get_post_type_object($ptident);
    $edithtml = "";
    if (current_user_can($pt->cap->edit_posts) && current_user_can($pt->cap->publish_posts)) {
        $edithtml = "<div style='float:left; padding-left:16px;'><button onclick=\"window.location.href = &quot;".get_admin_url()."/post-new.php?post_type=".$ptident."&quot;\"><span class=\"dashicons dashicons-plus\"></span>".$pt->labels->add_new_item."</button></div>";
    } else {
        $uchtml = olz_unauthorized_contribution_form($ptident);
        if ($uchtml) {
            echo "<div style=\"display:none; padding:10px 0px 10px 0px; border-bottom:1px solid rgb(0,120,0); margin:0px 16px 10px 16px;\" id=\"uc_form_".$ptident."\">".$uchtml."</div>";
            $edithtml = "<div style='float:left; padding-left:16px;'><button onclick=\"toggleUCForm(".esc_attr(json_encode($ptident)).")\"><span class=\"dashicons dashicons-plus\"></span>".$pt->labels->add_new_item."</button></div>";
        }
    }
    if ($ptident=='termine' && false) {
        $first_weekday = intval(date("w", strtotime($_GET['jahr']."-".str_pad($_GET['monat'], 2, "0", STR_PAD_LEFT)."-01")));
        $num_days = intval(date("t", strtotime($_GET['jahr']."-".str_pad($_GET['monat'], 2, "0", STR_PAD_LEFT)."-01")));
        echo "<h3 style='text-align:center;'>".$month_names[$_GET['monat']-1]." ".$_GET['jahr']."</h3>";
        echo "<div style='margin:0px 10px;'>";
        $qcount = count($q->posts);
        for ($monday=1-(($first_weekday+6)%7); $monday<=$num_days; $monday+=7) {
            echo "<div style='position:relative; width:100%; height:120px;'>";
            for ($day=$monday; $day<$monday+7; $day++) {
                echo "<div style='position:absolute; top:0px; left:".(($day-$monday)*14.2857)."%; width:14.2857%; height:120px;'><div style='border-right:1px solid rgb(200,200,200); border-bottom:1px solid rgb(200,200,200);'><div style='height:20px; line-height:20px; text-align:right; padding:0px 5px;".(($month==date("n") && $day==date("j"))?" border-top:1px solid green; border-bottom:1px solid green; background-color: rgba(0, 0, 0, 0.05); margin:-1px 0px -1px 0px;":"")."'>".((0<$day && $day<=$num_days)?$day:"")."</div><div style='height:100px; overflow-y:auto;'>";
                $morning = strtotime($_GET['jahr']."-".str_pad($_GET['monat'], 2, "0", STR_PAD_LEFT)."-".str_pad($day, 2, "0", STR_PAD_LEFT)." 00:00:00");
                $evening = strtotime($_GET['jahr']."-".str_pad($_GET['monat'], 2, "0", STR_PAD_LEFT)."-".str_pad($day+1, 2, "0", STR_PAD_LEFT)." 00:00:00");
                for ($ind=$last_post_ind; $ind<$qcount; $ind++) {
                    $post_id = $q->posts[$ind]->ID;
                    $start = strtotime(get_post_meta($post_id, 'timerange_start', true));
                    $end = strtotime(get_post_meta($post_id, 'timerange_end', true));
                    if (!($end<$morning || $evening<$start)) {
                        $terms = wp_get_post_terms($post_id, 'termin-typ');
                        $colors_bright = array();
                        for ($j=0; $j<count($terms); $j++) {
                            $r = get_term_meta($terms[$j]->term_id, 'color_red', true);
                            $g = get_term_meta($terms[$j]->term_id, 'color_green', true);
                            $b = get_term_meta($terms[$j]->term_id, 'color_blue', true);
                            if ($r=='' && $g=='' && $b=='') {
                                $r_bright = 180; $g_bright = 180; $b_bright = 180;
                            } else {
                                $r_bright = intval(235-(1-$r/($r+$g+$b))*235/3);
                                $g_bright = intval(200-(1-$g/($r+$g+$b))*200/3);
                                $b_bright = intval(255-(1-$b/($r+$g+$b))*255/3);
                            }
                            $color_bright = 'rgb('.$r_bright.','.$g_bright.','.$b_bright.')';
                            $colors_bright[] = $color_bright;
                            $colors_bright[] = $color_bright;
                        }
                        echo "<div style='margin:1px; border-radius:3px; background-color:".$colors_bright[0]."; background:-webkit-linear-gradient(to right, ".implode(", ", $colors_bright)."); background:-o-linear-gradient(to right, ".implode(", ", $colors_bright)."); background:-moz-linear-gradient(to right, ".implode(", ", $colors_bright)."); background:linear-gradient(to right, ".implode(", ", $colors_bright)."); overflow-x:hidden; white-space:nowrap;'>".($q->posts[$ind]->post_title)."</div>";
                    }
                }
                echo "</div></div></div>";
            }
            echo "</div>";
        }
        echo "</div>";
    } else {
        if ($q->have_posts()) {
            $big = 999999;
            $navhtml = $edithtml."<div style=\"text-align:center;\">".paginate_links(array(
                'format' => "?paged=%#%",
                'current' => max(1, $q->query['paged']),
                'total' => $q->max_num_pages,
                'prev_text'=>"&laquo;",
                'next_text'=>"&raquo;",
            ))."</div>";
            echo $navhtml;
            while ($q->have_posts()) {
                $q->the_post();
                get_template_part('content', $content_type);
            }
            //echo $navhtml;
        } else {
            echo $edithtml."<div style=\"text-align:center; padding:2em 16px;\">".$no_entries."</div>";
        }
    }
    wp_reset_postdata();
}

function olz_ajax_get_timeline() {
    $data = olz_post_types();
    if (!isset($data['post_types'][$_POST['post_type']])) return "Incorrect Post Type";
    $nq = new WP_Query(array('post_type'=>$_POST['post_type'], 'posts_per_page'=>-1, 'date_query'=>array('year'=>intval($_POST['year'])), 'orderby'=>'post_date', 'order'=>'DESC'));
    $htmlout = "";
    while ($nq->have_posts()) {
        $nq->the_post();
        $htmlout .= "<div>".(current_user_can('edit_post', $post->ID)?"<a href=\"".get_edit_post_link()."\" class=\"dashicons dashicons-edit\" style=\"float:left;\"></a>":"")."<a href=\"".get_the_permalink()."\">".get_the_date("j.n.").": ".get_the_title()."</a></div>";
    }
    if ($htmlout=='') $htmlout = "Hier ist nichts.";
    return $htmlout;
}
add_action('wp_ajax_get_timeline', olz_ajaxify('olz_ajax_get_timeline'));
add_action('wp_ajax_nopriv_get_timeline', olz_ajaxify('olz_ajax_get_timeline'));

function olz_posts_timeline($q) {
    global $post, $_GET, $month_names;
    $ptident = $q->query_vars['post_type'];
    $min_year = date('Y')-4;
    if (isset($_GET['archive'])) {
        $oq = new WP_Query(array('post_type'=>$ptident, 'posts_per_page'=>1, 'orderby'=>'post_date', 'order'=>'ASC'));
        if ($oq->have_posts()) {
            $oq->the_post();
            $min_year = get_the_date('Y');
        }
    }
    $shown_ids = array();
    $shown_years = array();
    $qcnt = count($q->posts);
    if ($ptident=='termine' && false) {
        $shown_ids[$_GET['jahr']*12+$_GET['monat']] = true;
        $shown_years[$_GET['jahr']] = true;
    } else {
        for ($i=0; $i<$qcnt; $i++) {
            $shown_ids[$q->posts[$i]->ID] = true;
            $shown_years[date('Y', strtotime($q->posts[$i]->post_date))] = true;
        }
    }
    for ($year=date('Y'); $year>=$min_year; $year--) {
        echo "<a href=\"javascript:accordion_toggle(".$year.")\" class=\"accordion-header\" id=\"accordion-header-".$year."\"><div>".$year."</div></a><div class=\"accordion-content\" id=\"accordion-content-".$year."\">";
        if (isset($shown_years[$year])) {
            $nq = new WP_Query(array('post_type'=>$ptident, 'posts_per_page'=>-1, 'date_query'=>array('year'=>intval($year)), 'orderby'=>'post_date', 'order'=>'DESC'));
            if ($ptident=='termine' && false) {
                for ($month=12; 0<$month; $month--) {
                    $is_shown = isset($shown_ids[$year*12+$month]);
                    echo "<div><a href=\"?jahr=".$year."&monat=".$month."\"".($is_shown?" class=\"shown-entry\"":"").">".$month_names[$month-1]."</a></div>";
                }
            } else {
                while ($nq->have_posts()) {
                    $nq->the_post();
                    $is_shown = isset($shown_ids[get_the_id()]);
                    echo "<div>".(current_user_can('edit_post', $post->ID)?"<a href=\"".get_edit_post_link()."\" class=\"dashicons dashicons-edit".($is_shown?" shown-entry":"")."\" style=\"float:left;\"></a>":"")."<a href=\"".get_the_permalink()."\"".($is_shown?" class=\"shown-entry\"":"").">".get_the_date("j.n.").": ".get_the_title()."</a></div>";
                }
            }
        }
        echo "</div>";
    }
    if (!isset($_GET['archive'])) echo "<a href=\"".add_query_arg('archive', '1')."\" class=\"accordion-header\"><div>ältere...</div></a>";
    echo "<script>
    var post_type = ".json_encode($ptident).";
    var shown_years = ".json_encode($shown_years).";
    for (var k in shown_years) {
        jQuery('#accordion-header-'+k).addClass('active-header');
        jQuery('#accordion-content-'+k).slideDown();
    }
    </script>";
    ?>
    <script>
    function accordion_toggle(year) {
        if (jQuery('#accordion-content-'+year).html()=="") {
            jQuery('#accordion-content-'+year).html("<p class=\"loading\">Lädt...</p>");
            jQuery.post(ajaxurl, {
                'action': 'get_timeline',
                'post_type': post_type,
                'year': year,
            }, function (response) {
                var tmp = JSON.parse(response);
                jQuery('#accordion-content-'+year).html(tmp);
            });
        }
        if (shown_years[year]) {
            jQuery('#accordion-header-'+year).removeClass('active-header');
            jQuery('#accordion-content-'+year).slideUp();
        } else {
            jQuery('#accordion-header-'+year).addClass('active-header');
            jQuery('#accordion-content-'+year).slideDown();
        }
        shown_years[year] = !shown_years[year];
    }
    </script>
    <?php
}
*/

function olz_posts_filter_taxonomy($taxonomy, $query=false) {
    global $_GET;
    $query_str = "";
    $filter = array();
    foreach ($_GET as $key => $value) {
        if ($key!=$taxonomy."-filter") {
            $query_str .= ($query_str==""?"?":"&");
            $query_str .= urlencode($key)."=".urlencode($value);
        } else {
            $filter = explode(',', $value);
            if (count($filter)==1 && $filter[0]=='') $filter = array();
        }
    }
    $query_with_filter = function ($taxonomy, $term, $query_str, $filter) {
        $pos = array_search($term['slug'], $filter);
        $active = false;
        if ($term['slug']=='all') {
            $active = (count($filter)==0);
            $filter = array();
        } else {
            $active = ($pos!==false);
            if ($pos!==false) {
                array_splice($filter, $pos, 1);
            } else {
                $filter[] = $term['slug'];
            }
        }
        $query_str .= ($query_str==""?"?":"&");
        $query_str .= urlencode($taxonomy."-filter")."=".urlencode(implode(',', $filter));
        $r = get_term_meta($term['id'], 'color_red', true);
        $g = get_term_meta($term['id'], 'color_green', true);
        $b = get_term_meta($term['id'], 'color_blue', true);
        if ($term['id']==-1 || ($r=='' && $g=='' && $b=='')) {
            $r_bright = 180; $g_bright = 180; $b_bright = 180;
            $r_dark = 80; $g_dark = 80; $b_dark = 80;
        } else {
            $r_bright = intval(235-(1-$r/($r+$g+$b))*235/3);
            $g_bright = intval(200-(1-$g/($r+$g+$b))*200/3);
            $b_bright = intval(255-(1-$b/($r+$g+$b))*255/3);
            $r_dark = intval($r*200/($r+$g+$b));
            $g_dark = intval($g*150/($r+$g+$b));
            $b_dark = intval($b*220/($r+$g+$b));
        }
        $color = 'rgb('.$r.','.$g.','.$b.')';
        return "<a href=\"".$query_str."\" class=\"filter\" style=\"".($active?"background-color:rgba(".$r_bright.",".$g_bright.",".$b_bright.", 0.5); ":"")."color:rgb(".$r_dark.",".$g_dark.",".$b_dark.");\">".$term['name']."</a>";
    };
    if ($query) {
        if (count($filter)==0) return $query;
        if (isset($query['tax_query'])) {
            $query['tax_query'] = array(
                'relation'=>'AND',
                $query['tax_query'],
                array(array('taxonomy'=>$taxonomy, 'field'=>'slug', 'terms'=>$filter))
            );
        } else {
            $query['tax_query'] = array(array('taxonomy'=>$taxonomy, 'field'=>'slug', 'terms'=>$filter));
        }
        return $query;
    } else {
        $htmlout = "";
        $htmlout .= $query_with_filter($taxonomy, array('slug'=>'all', 'name'=>"Alle", 'id'=>-1), $query_str, $filter);
        $terms = get_terms(array('taxonomy'=>$taxonomy, 'hide_empty'=>false));
        for ($i=0; $i<count($terms); $i++) {
            $htmlout .= $query_with_filter($taxonomy, array('slug'=>$terms[$i]->slug, 'name'=>$terms[$i]->name, 'id'=>$terms[$i]->term_id), $query_str, $filter);
        }
        return $htmlout;
    }
}

function olz_include_text($ident) {
    global $post;
    $tq = new WP_Query(array('post_type'=>'texte', 'meta_key'=>'ident', 'meta_value'=>$ident, 'meta_compare'=>'='));
    if ($tq->have_posts()) {
        $tq->the_post();
        ?>
        <div class="entry-edit entry-galerie"><?php edit_post_link("<span class=\"dashicons dashicons-edit\"></span>"); ?><b><?php the_title(); ?></b></div>
        <div class="entry-content entry-galerie"><?php echo olz_replace_placeholders(get_the_content()); ?></div>
        <?php
        wp_reset_postdata();
    } else {
        echo "Kein Text mit Identifikation \"".$ident."\"";
    }
}

function olz_replace_placeholders($text) {
    global $post;
    preg_match_all("/\<\<\!\"(((?!\"\!\>\>).)+)\"\!\>\>/i", $text, $matches);
    $cnt = count($matches[1]);
    for ($i=0; $i<$cnt; $i++) {
        $dict = json_decode(str_replace('&quot;', '"', $matches[1][$i]), true);
        $repl = "";
        if ($dict) {
            if ($dict['ident']=='texte') {
                $tq = new WP_Query(array('post_type'=>'texte', 'p'=>intval($dict['texte-id'])));
                $repl = olz_replace_placeholders($tq->posts[0]->post_content);
            } else if ($dict['ident']=='organigramm') {
                $bq = new WP_Query(array('post_type'=>'besetzung', 'meta_key'=>'organigramm', 'meta_value'=>intval($dict['organigramm-id']), 'meta_compare'=>'='));
                for ($l=0; $l<count($bq->posts); $l++) {
                    $u = new WP_User(get_post_meta($bq->posts[$l]->ID, 'user', true));
                    if (!isset($dict['contact-type'])) $dict['contact-type'] = 1;
                    switch (intval($dict['contact-type'])) {
                        case 1:
                            $repl .= "<div>".($u->display_name)."</div>";
                            break;
                        case 2:
                            $repl .= "<div>".olz_email_link($u->user_email, $u->display_name)."</div>";
                            break;
                        case 3:
                            $address = get_the_author_meta('address', $u->ID);
                            $city = get_the_author_meta('city', $u->ID);
                            $tel = get_the_author_meta('tel', $u->ID);
                            $repl .= "<table><tr><td>".get_avatar($u->ID, 'thumbnail')."</td><td><b>".($u->display_name)."</b>".($address?"<br />".$address:"").($city?"<br />".$city:"").($tel?"<br />".$tel:"")."<br />".olz_email_link($u->user_email, $u->user_email)."</td></tr></table>";
                            break;
                    }
                }
                if ($repl=="") $repl = "<div>Niemand</div>";
            } else if ($dict['ident']=='auto') {
                switch (intval($dict['auto-id'])) {
                    case 1:
                        $tq = new WP_Query(array('post_type'=>'termine', 'posts_per_page'=>3, 'paged'=>0, 'meta_query'=>array(
                            array('key'=>'timerange_start', 'value'=>date("Y-m-d"), 'compare'=>'>='),
                        ), 'tax_query' => array(
                            array('taxonomy'=>'termin-typ', 'field'=>'slug', 'terms'=>'training'),
                        ),'orderby'=>'timerange_start', 'order'=>'ASC'));
                        ob_start();
                        if ($tq->have_posts()) {
                            while ($tq->have_posts()) {
                                $tq->the_post();
                                get_template_part('content', 'list-termine');
                            }
                        }
                        wp_reset_postdata();
                        $repl = "<div class=\"articlelist\">".ob_get_contents()."</div>";
                        ob_end_clean();
                        break;
                }
            }
        }
        $text = str_replace($matches[0][$i], $repl, $text);
    }
    return $text;
}

function olz_email_link($address, $name, $subject=false) {
    if ($subject) $address .= "?subject=".$subject;
    $encaddr = "";
    $addresslen = strlen($address);
    for ($i=0; $i<$addresslen; $i++) {
        $ord = ord($address[$i]);
        $ord1 = ($ord&0x0F);
        $ord2 = (($ord>>4)&0x0F);
        $encaddr .= chr($ord1+65).chr($ord2+65);
    }
    $cnt_tmp = 0;
    $addrjs = "";
    $encaddrlen = strlen($encaddr);
    for ($i=0; $i<$encaddrlen; $i++) {
        $cnt_tmp += ord($encaddr[$i]);
        if (0xFF<$cnt_tmp) {
            $addrjs .= "\"+\"";
            $cnt_tmp = ($cnt_tmp & 0x7F);
        }
        $addrjs .= $encaddr[$i];
    }

    $cnt_tmp = 0;
    $htmlname = "";
    $namelen = strlen($name);
    for ($i=0; $i<$namelen; $i++) {
        $cnt_tmp += ord($name[$i]);
        if (0xFF<$cnt_tmp) {
            $cnt_tmp = ($cnt_tmp & 0x7F);
        }
        switch ($cnt_tmp%4) {
            case 0: $htmlname .= $name[$i]; break;
            case 1: $htmlname .= "&#".ord($name[$i]).";"; break;
            case 2: $htmlname .= "<span>".$name[$i]."</span>"; break;
            case 3: $htmlname .= "<span>&#".ord($name[$i]).";</span>"; break;
        }
        if (($cnt_tmp%8)<4) $htmlname .= "<span style=\"display:none\">".chr(65+$cnt_tmp%26)."</span>";
    }
    $phpident = md5($addrjs.$htmlname.rand().time());
    $htmlout .= "if (!window.emailhandlers) window.emailhandlers = {};\n";
    $htmlout .= "var ident = \"\";\n";
    $htmlout .= "while (ident.length<4 || window.emailhandlers[ident]) ident += String.fromCharCode(Math.floor(Math.random()*26)+65);\n";
    $htmlout .= "var encaddr = \"".$addrjs."\";\n";
    $htmlout .= "var addr = \"\";\n";
    $htmlout .= "for (var i=0; i<encaddr.length; i+=2) {addr += String.fromCharCode(encaddr.charCodeAt(i)-65+((encaddr.charCodeAt(i+1)-65)<<4));}\n";
    $htmlout .= "var encname = \"".$namejs."\";\n";
    $htmlout .= "var name = \"\";\n";
    $htmlout .= "for (var i=0; i<encname.length; i+=2) {name += String.fromCharCode(encname.charCodeAt(i)-65+((encname.charCodeAt(i+1)-65)<<4));}\n";
    $htmlout .= "window.emailhandlers[ident] = function () { window.location.href = \"mai\"+\"lto:\"+addr; };\n";
    $htmlout .= "document.getElementById(\"email_".$phpident."\").href = \"javascript:window.emailhandlers[\\\"\"+ident+\"\\\"]();\";\n";
    return "<a id=\"email_".$phpident."\">".$htmlname."</a><script type=\"text/javascript\">(function () {".$htmlout."})();</script>";
}

function olz_the_content_filter($content) {
    $res = preg_match_all("/[^\s\>]+\@[^\s\<]+/i", $content, $matches);
    for ($i=0; $i<count($matches[0]); $i++) {
        $content = str_replace($matches[0][$i], olz_email_link($matches[0][$i], $matches[0][$i]), $content);
    }
    return $content;
}
add_filter('the_content', 'olz_the_content_filter');

function olz_the_excerpt_filter($excerpt) {
    $res = preg_match_all("/[^\s\>]+\@[^\s\<]+/i", $excerpt, $matches);
    for ($i=0; $i<count($matches[0]); $i++) {
        $excerpt = str_replace($matches[0][$i], olz_email_link($matches[0][$i], $matches[0][$i]), $excerpt);
    }
    return $excerpt;
}
add_filter('the_excerpt', 'olz_the_excerpt_filter');

function olz_the_title_filter($title) {
    $res = preg_match_all("/[^\s\>]+\@[^\s\<]+/i", $title, $matches);
    for ($i=0; $i<count($matches[0]); $i++) {
        $title = str_replace($matches[0][$i], olz_email_link($matches[0][$i], $matches[0][$i]), $title);
    }
    return $title;
}
add_filter('the_title', 'olz_the_title_filter');

function olz_hide_wp_admin_bar() {
    return false;
}
add_filter('show_admin_bar', 'olz_hide_wp_admin_bar');

?>

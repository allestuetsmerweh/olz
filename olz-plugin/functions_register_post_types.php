<?php

/*
* Post Types & Taxonomies
*/

$olz_post_types_cache = false;
function olz_post_types() {
    global $olz_post_types_cache;
    if ($olz_post_types_cache!==false) return $olz_post_types_cache;
    $post_types = array();
    $taxonomies = array();


    // ### Post Types ###

    // Aktuell

    $labels = array(
        'name'               => 'Aktuell',
        'singular_name'      => 'Aktuell',
        'menu_name'          => 'Aktuell',
        'name_admin_bar'     => 'Aktuell',
        'add_new'            => 'Neuer Aktuell-Eintrag',
        'add_new_item'       => 'Neuer Aktuell-Eintrag',
        'new_item'           => 'Neuer Aktuell-Eintrag',
        'edit_item'          => 'Aktuell-Eintrag bearbeiten',
        'view_item'          => 'Aktuell-Eintrag anschauen',
        'all_items'          => 'Alles Aktuelle',
        'search_items'       => 'Aktuelles duchsuchen',
        'not_found'          => 'Nichts Aktuelles',
        'not_found_in_trash' => 'Nichts Aktuelles im Papierkorb',
    );
    $args = array(
        'labels'             => $labels,
        'description'        => "Aktuelles Beschreibung !?!",
        'public'             => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug'=>'aktuell'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_icon'          => 'dashicons-star-filled',
        'menu_position'      => 4.500,
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'comments')
    );
    $post_types['aktuell'] = array('args'=>$args);


    // Berichte

    $labels = array(
        'name'               => 'Berichte',
        'singular_name'      => 'Bericht',
        'menu_name'          => 'Berichte',
        'name_admin_bar'     => 'Berichte',
        'add_new'            => 'Neuer Bericht',
        'add_new_item'       => 'Neuer Bericht',
        'new_item'           => 'Neuer Bericht',
        'edit_item'          => 'Bericht bearbeiten',
        'view_item'          => 'Bericht anschauen',
        'all_items'          => 'Alle Berichte',
        'search_items'       => 'Berichte duchsuchen',
        'not_found'          => 'Keine Berichte',
        'not_found_in_trash' => 'Keine Berichte im Papierkorb',
    );
    $args = array(
        'labels'             => $labels,
        'description'        => "Berichte Beschreibung !?!",
        'public'             => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug'=>'berichte'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_icon'          => 'dashicons-admin-post',
        'menu_position'      => 4.501,
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'comments')
    );
    $post_types['berichte'] = array('args'=>$args, 'menu'=>array('title'=>"Berichte"));


    // Termine

    $labels = array(
        'name'               => 'Termine',
        'singular_name'      => 'Termin',
        'menu_name'          => 'Termine',
        'name_admin_bar'     => 'Termine',
        'add_new'            => 'Neuer Termin',
        'add_new_item'       => 'Neuer Termin',
        'new_item'           => 'Neuer Termin',
        'edit_item'          => 'Termin bearbeiten',
        'view_item'          => 'Termin anschauen',
        'all_items'          => 'Alle Termine',
        'search_items'       => 'Termine duchsuchen',
        'not_found'          => 'Keine Termine',
        'not_found_in_trash' => 'Keine Termine im Papierkorb',
    );
    $args = array(
        'labels'             => $labels,
        'description'        => "Termine Beschreibung !?!",
        'public'             => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug'=>'termine'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_icon'          => 'dashicons-calendar',
        'menu_position'      => 4.503,
        'supports'           => array('title', 'comments')
    );
    $post_types['termine'] = array('args'=>$args, 'menu'=>array('title'=>"Termine"));


    // Galerie

    $labels = array(
        'name'               => 'Galerien',
        'singular_name'      => 'Galerie',
        'menu_name'          => 'Galerie',
        'name_admin_bar'     => 'Galerie',
        'add_new'            => 'Neue Galerie',
        'add_new_item'       => 'Neue Galerie',
        'new_item'           => 'Neue Galerie',
        'edit_item'          => 'Galerie bearbeiten',
        'view_item'          => 'Galerie anschauen',
        'all_items'          => 'Alle Galerien',
        'search_items'       => 'Galerien duchsuchen',
        'not_found'          => 'Keine Galerien',
        'not_found_in_trash' => 'Keine Galerien im Papierkorb',
    );
    $args = array(
        'labels'             => $labels,
        'description'        => "Galerie Beschreibung !?!",
        'public'             => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug'=>'galerie'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_icon'          => 'dashicons-images-alt2',
        'menu_position'      => 4.504,
        'supports'           => array('title', 'author', 'comments')
    );
    $uc = array(
        'mode'=>'pending',
        'fields' => array(
            array('ident'=>'email', 'name'=>"Deine E-Mail", 'type'=>'text'),
            array('ident'=>'title', 'name'=>"Galerie-Titel", 'type'=>'text'),
            array('ident'=>'galerie', 'name'=>"Galerie", 'type'=>'images', 'post_type'=>'galerie'),
        ),
        'get_email' => function ($data) {
            return $data['email'];
        },
        'get_post_args' => function ($data) {
            return array(
                'post_title'     => $data['title'],
                'post_date'         => date("Y-m-d H:i:s"),
                'post_date_gmt'     => gmdate("Y-m-d H:i:s"),
            );
        },
        'update_post' => function ($postid, $data) {
            $galerie = json_decode($data['galerie'], true);
            if (is_array($galerie)) update_post_meta($postid, 'galerie', $galerie);
        },
    );
    $post_types['galerie'] = array('args'=>$args, 'menu'=>array('title'=>"Galerie"), 'unauthorized_contribution'=>$uc);


    // Forum

    $labels = array(
        'name'               => 'Forum',
        'singular_name'      => 'Forumseintrag',
        'menu_name'          => 'Forum',
        'name_admin_bar'     => 'Forum',
        'add_new'            => 'Neuer Forumseintrag',
        'add_new_item'       => 'Neuer Forumseintrag',
        'new_item'           => 'Neuer Forumseintrag',
        'edit_item'          => 'Forumseintrag bearbeiten',
        'view_item'          => 'Forumseintrag anschauen',
        'all_items'          => 'Alle Forumseinträge',
        'search_items'       => 'Forumseinträge duchsuchen',
        'not_found'          => 'Keine Forumseinträge',
        'not_found_in_trash' => 'Keine Forumseinträge im Papierkorb',
    );
    $args = array(
        'labels'             => $labels,
        'description'        => "Forum Beschreibung !?!",
        'public'             => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug'=>'forum'),
        'map_meta_cap'       => true,
        'capability_type'    => 'forum',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_icon'          => 'dashicons-format-chat',
        'menu_position'      => 4.505,
        'supports'           => array('title', 'editor', 'author', 'comments')
    );
    $uc = array(
        'mode'=>'publish',
        'fields' => array(
            array('ident'=>'email', 'name'=>"Deine E-Mail", 'type'=>'text'),
            array('ident'=>'title', 'name'=>"Beitrags-Titel", 'type'=>'text'),
            array('ident'=>'content', 'name'=>"Dein Beitrag", 'type'=>'textarea', 'textarea_height'=>"100px"),
        ),
        'get_email' => function ($data) {
            return $data['email'];
        },
        'get_post_args' => function ($data) {
            return array(
                'post_content'     => $data['content'],
                'post_title'     => $data['title'],
                'post_date'         => date("Y-m-d H:i:s"),
                'post_date_gmt'     => gmdate("Y-m-d H:i:s"),
            );
        },
    );
    $post_types['forum'] = array('args'=>$args, 'menu'=>array('title'=>"Forum"), 'unauthorized_contribution'=>$uc);


    // Bild der Woche

    $labels = array(
        'name'               => 'Bild der Woche',
        'singular_name'      => 'Bild der Woche',
        'menu_name'          => 'Bild der Woche',
        'name_admin_bar'     => 'Bild der Woche',
        'add_new'            => 'Neues Bild der Woche',
        'add_new_item'       => 'Neues Bild der Woche',
        'new_item'           => 'Neues Bild der Woche',
        'edit_item'          => 'Bild der Woche bearbeiten',
        'view_item'          => 'Bild der Woche anschauen',
        'all_items'          => 'Alle Bilder der Woche',
        'search_items'       => 'Bilder der Woche duchsuchen',
        'not_found'          => 'Keine Bilder der Woche',
        'not_found_in_trash' => 'Keine Bilder der Woche im Papierkorb',
    );
    $args = array(
        'labels'             => $labels,
        'description'        => "Bild der Woche Beschreibung !?!",
        'public'             => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug'=>'bild_der_woche'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_icon'          => 'dashicons-camera',
        'menu_position'      => 4.506,
        'supports'           => array('title', 'author', 'thumbnail', 'comments')
    );
    $uc = array(
        'mode'=>'pending',
        'fields' => array(
            array('ident'=>'email', 'name'=>"Deine E-Mail", 'type'=>'text'),
            array('ident'=>'title', 'name'=>"Bild-Titel", 'type'=>'text'),
            array('ident'=>'img0', 'name'=>"Bild", 'type'=>'image', 'post_type'=>'bild_der_woche'),
        ),
        'get_email' => function ($data) {
            return $data['email'];
        },
        'get_post_args' => function ($data) {
            return array(
                'post_title'     => $data['title'],
                'post_date'         => date("Y-m-d H:i:s"),
                'post_date_gmt'     => gmdate("Y-m-d H:i:s"),
            );
        },
        'update_post' => function ($postid, $data) {
            $bdw = json_decode($data['img0'], true);
            if (is_array($bdw) && 0<count($bdw)) set_post_thumbnail($postid, intval($bdw[0]));
        },
    );
    $post_types['bild_der_woche'] = array('args'=>$args, 'unauthorized_contribution'=>$uc);


    // Karten

    $labels = array(
        'name'               => 'Karten',
        'singular_name'      => 'Karte',
        'menu_name'          => 'Karten',
        'name_admin_bar'     => 'Karten',
        'add_new'            => 'Neue Karte',
        'add_new_item'       => 'Neue Karte',
        'new_item'           => 'Neue Karte',
        'edit_item'          => 'Karte bearbeiten',
        'view_item'          => 'Karte anschauen',
        'all_items'          => 'Alle Karten',
        'search_items'       => 'Karten duchsuchen',
        'not_found'          => 'Keine Karten',
        'not_found_in_trash' => 'Keine Karten im Papierkorb',
    );
    $args = array(
        'labels'             => $labels,
        'description'        => "Karten Beschreibung !?!",
        'public'             => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug'=>'karten'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_icon'          => 'dashicons-location-alt',
        'menu_position'      => 4.507,
        'supports'           => array('title', 'thumbnail', 'comments')
    );
    $post_types['karten'] = array('args'=>$args, 'menu'=>array('title'=>"Karten"));


    // Texte

    $labels = array(
        'name'               => 'Texte',
        'singular_name'      => 'Text',
        'menu_name'          => 'Texte',
        'name_admin_bar'     => 'Texte',
        'add_new'            => 'Neuer Text',
        'add_new_item'       => 'Neuer Text',
        'new_item'           => 'Neuer Text',
        'edit_item'          => 'Text bearbeiten',
        'view_item'          => 'Text anschauen',
        'all_items'          => 'Alle Texte',
        'search_items'       => 'Texte duchsuchen',
        'not_found'          => 'Keine Texte',
        'not_found_in_trash' => 'Keine Texte im Papierkorb',
    );
    $args = array(
        'labels'             => $labels,
        'description'        => "Texte Beschreibung !?!",
        'public'             => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug'=>'texte'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_icon'          => 'dashicons-text',
        'menu_position'      => 4.510,
        'supports'           => array('title', 'editor')
    );
    $post_types['texte'] = array('args'=>$args);


    // Organigramm (Organigramm Boxen)

    $args = array(
        'label'              => "Organigramm",
        'description'        => "Organigramm Beschreibung !?!",
        'public'             => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug'=>'organigramm'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => true,
        'show_in_menu'       => false,
        'supports'           => array('title', 'page-attributes')
    );
    $post_types['organigramm'] = array('args'=>$args, 'menu'=>array('title'=>"Kontakt"));


    // Besetzungen (Organigramm Personen)

    $args = array(
        'label'              => "Besetzung",
        'description'        => "Besetzung Beschreibung !?!",
        'public'             => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug'=>'besetzung'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'show_in_menu'       => false,
        'supports'           => array()
    );
    $post_types['besetzung'] = array('args'=>$args);


    // Mitglieder

    $args = array(
        'label'              => "Mitglieder",
        'description'        => "Mitglieder Beschreibung !?!",
        'public'             => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug'=>'mitglieder'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-id',
        'menu_position'      => 4.513,
        'supports'           => array('title')
    );
    $post_types['mitglieder'] = array('args'=>$args);


    // Zahlungen

    $args = array(
        'label'              => "Zahlungen",
        'description'        => "Zahlungen Beschreibung !?!",
        'public'             => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug'=>'zahlungen'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'show_in_menu'       => 'edit.php?post_type=mitglieder',
        'supports'           => array('')
    );
    $post_types['zahlungen'] = array('args'=>$args);


    // ### Taxonomies ###

    // Termin-Typen

    $labels = array(
        'name'              => 'Termin-Typen',
        'singular_name'     => 'Termin-Typ',
        'search_items'      => 'Termin-Typen durchsuchen',
        'all_items'         => 'Alle Termin-Typen',
        'parent_item'       => 'Übergeordneter Termin-Typ',
        'parent_item_colon' => 'Übergeordneter Termin-Typ:',
        'edit_item'         => 'Termin-Typ bearbeiten',
        'update_item'       => 'Termin-Typ aktualisieren',
        'add_new_item'      => 'Termin-Typ hinzufügen',
        'new_item_name'     => 'Neuer Termin-Typ',
        'menu_name'         => 'Termin-Typen',
    );
    $args = array(
        'hierarchical'      => false,
        'labels'            => $labels,
        'show_in_rest'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug'=>'termin-typ'),
    );
    $taxonomies['termin-typ'] = array('post_types'=>array('termine'), 'args'=>$args);


    // Karten-Typen

    $labels = array(
        'name'              => 'Karten-Typen',
        'singular_name'     => 'Karten-Typ',
        'search_items'      => 'Karten-Typen durchsuchen',
        'all_items'         => 'Alle Karten-Typen',
        'parent_item'       => 'Übergeordneter Karten-Typ',
        'parent_item_colon' => 'Übergeordneter Karten-Typ:',
        'edit_item'         => 'Karten-Typ bearbeiten',
        'update_item'       => 'Karten-Typ aktualisieren',
        'add_new_item'      => 'Karten-Typ hinzufügen',
        'new_item_name'     => 'Neuer Karten-Typ',
        'menu_name'         => 'Karten-Typen',
    );
    $args = array(
        'hierarchical'      => false,
        'labels'            => $labels,
        'show_in_rest'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug'=>'karten-typ'),
    );
    $taxonomies['karten-typ'] = array('post_types'=>array('karten'), 'args'=>$args);


    // Mitglieds-Typen

    $labels = array(
        'name'              => 'Mitglieds-Typen',
        'singular_name'     => 'Mitglieds-Typ',
        'search_items'      => 'Mitglieds-Typen durchsuchen',
        'all_items'         => 'Alle Mitglieds-Typen',
        'parent_item'       => 'Übergeordneter Mitglieds-Typ',
        'parent_item_colon' => 'Übergeordneter Mitglieds-Typ:',
        'edit_item'         => 'Mitglieds-Typ bearbeiten',
        'update_item'       => 'Mitglieds-Typ aktualisieren',
        'add_new_item'      => 'Mitglieds-Typ hinzufügen',
        'new_item_name'     => 'Neuer Mitglieds-Typ',
        'menu_name'         => 'Mitglieds-Typen',
    );
    $args = array(
        'hierarchical'      => false,
        'labels'            => $labels,
        'show_in_rest'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug'=>'mitglieder-typ'),
    );
    $taxonomies['mitglieder-typ'] = array('post_types'=>array('mitglieder'), 'args'=>$args);


    $olz_post_types_cache = array('post_types'=>$post_types, 'taxonomies'=>$taxonomies);
    return $olz_post_types_cache;
}

function olz_register_post_types() {
    global $wp_post_types;

    $pts = olz_post_types();
    foreach ($pts['post_types'] as $key => $value) {
        register_post_type($key, $value['args']);
    }
    foreach ($pts['taxonomies'] as $key => $value) {
        register_taxonomy($key, $value['post_types'], $value['args']);
    }
}
add_action('init', 'olz_register_post_types');


/*
* Register Menu Items
*/

function olz_menu_archive_post_types_meta_box() {
    $pts = olz_post_types();
    ?>
    <div id="posttype-archive" class="posttypediv">
        <div id="tabs-panel-posttype-archive" class="tabs-panel tabs-panel-active">
            <ul id ="posttype-archive-checklist" class="categorychecklist form-no-clear">
                <?php
                $ind = 1;
                foreach ($pts['post_types'] as $key => $value) {
                    if (isset($value['menu'])) {
                        ?>
                        <li>
                            <label class="menu-item-title">
                                <input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo $ind; ?>][menu-item-object-id]" value="<?php echo $ind; ?>"> <?php echo esc_attr($value['menu']['title']); ?>
                            </label>
                            <input type="hidden" class="menu-item-type" name="menu-item[<?php echo $ind; ?>][menu-item-type]" value="custom">
                            <input type="hidden" class="menu-item-title" name="menu-item[<?php echo $ind; ?>][menu-item-title]" value="<?php echo esc_attr($value['menu']['title']); ?>">
                            <input type="hidden" class="menu-item-url" name="menu-item[<?php echo $ind; ?>][menu-item-url]" value="<?php echo get_post_type_archive_link($key); ?>">
                            <input type="hidden" class="menu-item-classes" name="menu-item[<?php echo $ind; ?>][menu-item-classes]" value="posttype-archive-<?php echo $key; ?>">
                        </li>
                        <?php
                        $ind++;
                    }
                }
                ?>
            </ul>
        </div>
        <p class="button-controls">
            <span class="list-controls">
                <a href="/wordpress/wp-admin/nav-menus.php?page-tab=all&amp;selectall=1#posttype-page" class="select-all">Select All</a>
            </span>
            <span class="add-to-menu">
                <input type="submit" class="button-secondary submit-add-to-menu right" value="Add to Menu" name="add-post-type-menu-item" id="submit-posttype-archive">
                <span class="spinner"></span>
            </span>
        </p>
    </div>
    <?php
}
function olz_register_archive_menu_items() {
    add_meta_box('menu-archive-post-types', "Beitrags-Typ Archiv-Seiten", 'olz_menu_archive_post_types_meta_box', 'nav-menus', 'side', 'high');
}
add_action('admin_init', 'olz_register_archive_menu_items');


function olz_admin_menu() {
    remove_menu_page('users.php');
    add_menu_page("Benutzer", "Benutzer", 'list_users', 'users.php', '', 'dashicons-admin-users', 35);
    remove_submenu_page('users.php', 'user-new.php');
    remove_submenu_page('edit.php?post_type=mitglieder', 'post-new.php?post_type=mitglieder');
    add_submenu_page('edit.php?post_type=mitglieder', "Organigramm", "Organigramm", 'edit_users', 'organigramm', 'olz_organigramm_page');
    add_menu_page("Dateien", "Dateien", 'list_users', 'file_manager', 'olz_file_manager_page', 'dashicons-archive', 40);
}
add_action('admin_menu', 'olz_admin_menu');



/*
* Register unauthorized Contribution Meta-Boxes
*/

function olz_register_unauthorized_contribution_meta_boxes() {
    $pts = olz_post_types();
    foreach ($pts['post_types'] as $key => $value) {
        if (isset($value['unauthorized_contribution'])) {
            $meta_box_fn = function ($post) {
                echo "<div style=\"overflow-x:auto; white-space:nowrap;\">";
                echo "Post-ID: ".($post->ID)."<br>";
                echo "Token: ".get_post_meta($post->ID, 'activation_token', true)."<br>";
                echo "E-Mail: ".get_post_meta($post->ID, 'activation_email', true);
                echo "</div>";
            };
            $add_meta_box_fn = function () use ($meta_box_fn) {
                add_meta_box($key.'-activation', 'Aktivierung', $meta_box_fn, $key, 'side', 'low');
            };
            add_action('add_meta_boxes_'.$key, $add_meta_box_fn);
        }
    }
}
add_action('admin_init', 'olz_register_unauthorized_contribution_meta_boxes');


?>

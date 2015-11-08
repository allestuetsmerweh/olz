<?php
/**
* The template for displaying pages
*
* This is the template that displays all pages by default.
* Please note that this is the WordPress construct of pages and that
* other "pages" on your WordPress site will use a different template.
*
* @package WordPress
* @subpackage OLZ-Theme
* @since OLZ-Theme 1.0
*/

get_header(); ?>

<div class="rightcolumn">
    WTF
</div>
<div class="maincolumn">
    <?php
    function olz_unauthorized_contribution_activation() {
        global $_GET, $post;
        $uc_entry = olz_find_unauthorized_contribution($_GET);
        if ($uc_entry[0]!='OK') return array($uc_entry[0], $uc_entry[1], false);
        if ($uc_entry['warn']) return array('WARN', $uc_entry['warn'].".<br />Der Beitrag ist ".($uc_entry['definition']['mode']=='publish'?"für alle Besucher sichtbar.":"zur Kontrolle angemeldet."), false);
        if ($uc_entry['user']) {
            wp_update_post(array('ID'=>get_the_id(), 'post_status'=>$uc_entry['definition']['mode'], 'post_author'=>$uc_entry['user']->ID));
            wp_reset_postdata();
            return array('OK', "Bestätigung erfolgreich.<br />Der Beitrag ist ab sofort ".($uc_entry['definition']['mode']=='publish'?"für alle Besucher sichtbar.":"zur Kontrolle angemeldet."), false);
        }
        wp_reset_postdata();
        return array('TODO', "Benutzerkonto erforderlich", true);
    }

    $res = olz_unauthorized_contribution_activation();
    if ($res[0]!='TODO') echo "<div class=\"margin response-".$res[0]."\">".$res[1]."</div>";
    if ($res[2]) {
        $fields = array(
            array('ident'=>'first_name', 'name'=>"Dein Vorname", 'type'=>'text', 'placeholder'=>"Max", 'onchange'=>"var name = jQuery(\"#olz_form_first_name\").val()+\" \"+jQuery(\"#olz_form_last_name\").val(); var username = \"\"; for (var i=0; i<name.length; i++) {var val = name[i]; if (/[a-zA-Z0-9]/.exec(val)) username += val; else if (username[username.length-1]!=\".\") username += \".\";} jQuery(\"#olz_form_username\").val(username.toLowerCase());"),
            array('ident'=>'last_name', 'name'=>"Dein Nachname", 'type'=>'text', 'placeholder'=>"Muster", 'onchange'=>"var name = jQuery(\"#olz_form_first_name\").val()+\" \"+jQuery(\"#olz_form_last_name\").val(); var username = \"\"; for (var i=0; i<name.length; i++) {var val = name[i]; if (/[a-zA-Z0-9]/.exec(val)) username += val; else if (username[username.length-1]!=\".\") username += \".\";} jQuery(\"#olz_form_username\").val(username.toLowerCase());"),
            array('ident'=>'username', 'name'=>"Dein Benutzername", 'type'=>'text', 'placeholder'=>"max.muster"),
        );
        echo "<div class=\"margin\">".olz_form($fields, array(
            'name'=>"Bestätigen",
            'ident'=>'activation_signup',
            'action'=>'activation_signup',
            'args'=>array('activation_token'=>$_GET['activation_token'], 'postid'=>$_GET['postid']),
        ))."</div>";
    }

    ?>
</div>

<?php get_footer(); ?>

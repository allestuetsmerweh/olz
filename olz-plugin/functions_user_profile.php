<?php

require_once(dirname(__FILE__).'/functions_telegram.php');


// Benutzerprofil-Anpassungen

function olz_show_admin_bar() {
    return false;
}
add_filter('show_admin_bar', 'olz_show_admin_bar');


// Benutzerprofil-Extrafelder

$user_data_regexes = array(
    'tel' => '^((00[0-9]{2}) ?|(\\+[0-9]{2}) ?|(0))([0-9]{2}) ?([0-9]{3}) ?([0-9]{2}) ?([0-9]{2})$',
    'address' => '^(.*)$',
    'plz' => '^(([A-Z]{0,2}-)?[0-9]{0,5})$',
    'city' => '^(.*)$',
    'birthday' => '^([0-9]{1,2})\. ?([0-9]{1,2})\. ?([0-9]{4})$',
    'si-card-number' => '^([0-9]+)$',
);

$user_data_to_db = array(
    'tel' => function ($matches) {return $matches[2].$matches[3].$matches[4].$matches[5].$matches[6].$matches[7].$matches[8];},
    'address' => function ($matches) {return $matches[1];},
    'plz' => function ($matches) {return $matches[1];},
    'city' => function ($matches) {return $matches[1];},
    'birthday' => function ($matches) {return $matches[3].'-'.str_pad($matches[2], 2, '0', STR_PAD_LEFT).'-'.str_pad($matches[1], 2, '0', STR_PAD_LEFT);},
    'si-card-number' => function ($matches) {return $matches[1];},
);

function olz_extra_user_profile_fields($user) {
    global $user_data_regexes, $_CONFIG;
    ?>
    <table id="additional-fields">
        <tr>
            <th><label for="tel">Telefon</label></th>
            <td>
                <input type="text" name="tel" id="tel" class="regular-text"/><br />
                <div id="telegram-text" style="display: none;" class="description">Wilst du <a id="telegram-link">Telegramme von uns erhalten</a>?</div>
                <div id="tel-description" class="description"></div>
            </td>
        </tr>
        <tr>
            <th><label for="address">Adresse</label></th>
            <td>
                <input type="text" name="address" id="address" class="regular-text"/><br />
                <div id="address-description" class="description"></div>
            </td>
        </tr>
        <tr>
            <th><label for="plz">Postleitzahl</label></th>
            <td>
                <input type="text" name="plz" id="plz" class="regular-text"/><br />
                <div id="plz-description" class="description"></div>
            </td>
        </tr>
        <tr>
            <th><label for="city">Wohnort</label></th>
            <td>
                <input type="text" name="city" id="city" class="regular-text"/><br />
                <div id="city-description" class="description"></div>
            </td>
        </tr>
        <tr>
            <th><label for="birthday">Geburtstag</label></th>
            <td>
                <input type="text" name="birthday" id="birthday" class="regular-text"/><br />
                <div id="birthday-description" class="description"></div>
            </td>
        </tr>
        <tr>
            <th><label for="si-card-number">SI-Card Nummer</label></th>
            <td>
                <input type="text" name="si-card-number" id="si-card-number" class="regular-text"/><br />
                <div id="si-card-number-description" class="description"></div>
            </td>
        </tr>
    </table>
    <?php
    $user_data = array();
    foreach ($user_data_regexes as $key => $value) {
        $user_data[$key] = get_the_author_meta($key, $user->ID);
    }
    echo '<script>buildUserProfileEditor('.json_encode($user_data).', '.json_encode($user_data_regexes).', '.json_encode($_CONFIG['telegram_bot_username']).')</script>';
    /*
    echo "<pre>"; print_r(wp_get_current_user()); echo "</pre>";
    $role = get_role('subscriber');
    echo "<pre>"; print_r($role); echo "</pre>";
    $role = get_role('contributor');
    echo "<pre>"; print_r($role); echo "</pre>";
    $role = get_role('author');
    echo "<pre>"; print_r($role); echo "</pre>";
    $role = get_role('editor');
    echo "<pre>"; print_r($role); echo "</pre>";
    $role = get_role('administrator');
    echo "<pre>"; print_r($role); echo "</pre>";
    */
}
add_action('show_user_profile', 'olz_extra_user_profile_fields');
add_action('edit_user_profile', 'olz_extra_user_profile_fields');

function olz_save_extra_user_profile_fields($user_id) {
    global $user_data_regexes, $user_data_to_db;
    if (current_user_can('edit_user', $user_id)) {
        foreach ($user_data_regexes as $key => $regex) {
            $res = preg_match('/'.$regex.'/', $_POST[$key], $matches);
            $db_value = call_user_func($user_data_to_db[$key], $matches);
            update_user_meta($user_id, $key, $db_value);
        }
    }
}
add_action('personal_options_update', 'olz_save_extra_user_profile_fields');
add_action('edit_user_profile_update', 'olz_save_extra_user_profile_fields');


// Benutzerprofil: Telegram

function olz_ajax_get_telegram_pin() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ346789';
    $cont = true;
    while ($cont) {
        $pin = implode('', array_map(function ($char) use ($chars) {
            return substr($chars, ord($char) & 0x1F, 1);
        }, str_split(openssl_random_pseudo_bytes(6))));
        $uq = new WP_User_Query(array('meta_key' => 'telegram-pin', 'meta_value' => $pin));
        $cont = count($uq->results) > 0;
    }
    update_user_meta(get_current_user_id(), 'telegram-pin', $pin);
    return array('pin' => $pin);
}
add_action('wp_ajax_get_telegram_pin', olz_ajaxify('olz_ajax_get_telegram_pin'));


// Benutzer-Rechte-Anpassungen

function olz_add_role_caps() {
    // Forum Contribution Capabilities
    foreach (array('subscriber', 'contributor', 'author', 'editor', 'administrator') as $key => $value) {
        $role = get_role($value);
        $role->add_cap('edit_forums');
        $role->add_cap('publish_forums');
        $role->add_cap('edit_published_forums');
        $role->add_cap('delete_forums');
        $role->add_cap('delete_private_forums');
        $role->add_cap('delete_published_forums');
    }
    // Forum Moderation Capabilities
    foreach (array('editor', 'administrator') as $key => $value) {
        $role = get_role($value);
        $role->add_cap('read_private_forums');
        $role->add_cap('edit_others_forums');
        $role->add_cap('edit_private_forums');
        $role->add_cap('delete_others_forums');
    }
}
add_action('admin_init', 'olz_add_role_caps');

?>

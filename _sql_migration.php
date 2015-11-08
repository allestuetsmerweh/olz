<?php

// ####################

$author = 0;
$limit = 30;

// ####################

header("Content-Type:text/plain;charset=utf-8");

require_once('./_common/wgs84_ch1903.php');
require_once('./server_config_link.php');
require_once($deployment['public-root'] . '/wp-load.php');

$is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on');
$base_url = "http" . ($is_secure ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . "/";
echo "Base URL: " . $base_url . "\n";

function url_get_contents($URL) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $URL);
    $data = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_status < 200 || $http_status >= 300) {
        return false;
    }
    curl_close($ch);
    return $data;
}


if (isset($_GET["limit"]) && 0<intval($_GET["limit"])) $limit = intval($_GET["limit"]);

$dbsrc = new mysqli($_CONFIG['mysql_host_old'], $_CONFIG['mysql_username_old'], $_CONFIG['mysql_password_old'], $_CONFIG['mysql_scheme_old']);
if ($dbsrc->connect_error) {
    die("Could not connect to localhost MySQL DB olz_old");
}
$dbsrc->set_charset("utf8");

if (isset($_GET['update'])) {
    $sqlfile = url_get_contents("http://olzimmerberg.ch/_sql_export.php");
    $commands = json_decode($sqlfile, true);
    for ($i=0; $i<count($commands); $i++) {
        $dbsrc->query($commands[$i]);
    }
}

$dbdest = new mysqli($_CONFIG['mysql_host'], $_CONFIG['mysql_username'], $_CONFIG['mysql_password'], $_CONFIG['mysql_scheme']);
if ($dbdest->connect_error) {
    die("Could not connect to localhost MySQL DB olz");
}
$dbdest->set_charset("utf8");

$post_types = array('berichte', 'kaderblog', 'termine', 'galerie', 'forum', 'bild_der_woche', 'karten', 'texte');
$mode = "incremental";
$post_type = "all";
if (isset($_GET["clear"]) || isset($_GET["fresh"]) || isset($_GET["incremental"])) {

    // Get Params
    $mode = "incremental";
    if (isset($_GET["clear"])) $mode = "clear";
    if (isset($_GET["fresh"])) $mode = "fresh";
    if (array_search($_GET[$mode], $post_types)!==false) {
        $post_type = $_GET[$mode];
    } else if ($_GET[$mode]=="all") {
        $post_type = "all";
    } else {
        die("parameters 'incremental', 'clear' and 'fresh' must be empty or name of table");
    }

    // Clear
    if ($mode=="clear" || $mode=="fresh") {
        if (array_search($post_type, $post_types)!==false) {
            $q = new WP_Query(array('post_type'=>$post_type, 'posts_per_page'=>-1));
            while ($q->have_posts()) {
                $q->the_post();
                wp_delete_post(get_the_id(), true);
            }
        } else if ($post_type=="all") {
            for ($i=0; $i<count($post_types); $i++) {
                $q = new WP_Query(array('post_type'=>$post_types[$i], 'posts_per_page'=>-1));
                while ($q->have_posts()) {
                    $q->the_post();
                    wp_delete_post(get_the_id(), true);
                }
            }
        }
    }

    // Clean up
    $res = $dbdest->query("SELECT id FROM wp_posts");
    $ids = array();
    for ($j=0; $j<$res->num_rows; $j++) {
        $row = $res->fetch_assoc();
        $ids[] = intval($row["id"]);
    }
    $dbdest->query("DELETE FROM wp_postmeta WHERE post_id NOT IN ('".implode("', '", $ids)."')");

    // Only Clear => exit
    if ($mode=="clear") {
        echo "Clear successful.\n";
        exit();
    }
}

echo "Sanity Tests...";
$res = $dbdest->query("SELECT * FROM wp_posts");
$row = $res->fetch_assoc();
$expected_keys = array(
    'ID'                      => 0,
    'post_author'             => 0,
    'post_date'               => 0,
    'post_date_gmt'           => 0,
    'post_content'            => 0,
    'post_title'              => 0,
    'post_excerpt'            => 0,
    'post_status'             => 0,
    'comment_status'          => 0,
    'ping_status'             => 0,
    'post_password'           => 0,
    'post_name'               => 0,
    'to_ping'                 => 0,
    'pinged'                  => 0,
    'post_modified'           => 0,
    'post_modified_gmt'       => 0,
    'post_content_filtered'   => 0,
    'post_parent'             => 0,
    'guid'                    => 0,
    'menu_order'              => 0,
    'post_type'               => 0,
    'post_mime_type'          => 0,
    'comment_count'           => 0,
);
$keys = array_keys($row);
for ($i=0; $i<count($keys); $i++) {
    $expected_keys[$keys[$i]] = 1;
}
foreach ($expected_keys as $key => $value) {
    if ($value!=1) {
        echo "ERROR: Key ".$key." not present in wp_posts table\n";
        exit();
    }
}
echo " OK\n";
while (@ob_end_flush());
$flush = " ";
for ($i=0; $i<15; $i++) {
    $flush = $flush . $flush;
}

$authors = array();
$wp_upload_dir = wp_upload_dir();


function download_file($path, $type, $id, $ind) {
    $file_extensions = array('pdf', 'doc', 'zip', 'jpg');
    $extension = -1;
    $file = false;
    for ($k=0; $k<count($file_extensions); $k++) {
        $file_extension = $file_extensions[$k];
        if (is_file($path.$file_extension)) {
            $file = "dummy";
            $extension = $file_extension;
            break;
        } else {
            $file = @url_get_contents("http://olzimmerberg.ch/files/".$type."/".intval($id)."/".str_pad($ind+1, 3, "0", STR_PAD_LEFT).".".$file_extension);
            if ($file && 0<strlen($file)) {
                $extension = $file_extension;
                break;
            }
        }
    }
    return array($file, $extension);
}

function migrate_files($type_old, $type, $id_old, $id, $title, $timestamp) {
    global $wp_upload_dir, $mode, $post;
    if ($mode=="fresh") {
        $existing = scandir($wp_upload_dir["basedir"]."/".date("Y", $timestamp)."/".date("m", $timestamp)."/");
        for ($i=0; $i<count($existing); $i++) {
            if (preg_match("/".$type."\_".intval($id)."\_file([0-9]+)\.jpg/i", $existing[$i], $matches)) {
                // Deprecated
                $title_tmp = $title(intval($matches[1]));
                $aq = new WP_Query(array('post_type'=>'attachment', 'posts_per_page'=>-1, 'name'=>md5($title_tmp)));
                while ($aq->have_posts()) {
                    $aq->the_post();
                    echo "DELETE ATTACHMENT FILE ".json_encode($matches[1])." - ".get_the_id()."\n";
                    wp_delete_attachment(get_the_id(), true);
                }
                // New
                $aq = new WP_Query(array('post_type'=>'attachment', 'posts_per_page'=>-1, 'name'=>md5($title_tmp." - ".$matches[1])));
                while ($aq->have_posts()) {
                    $aq->the_post();
                    echo "DELETE ATTACHMENT FILE ".json_encode($matches[1])." - ".get_the_id()."\n";
                    wp_delete_attachment(get_the_id(), true);
                }
            }
        }
    }
    $fileids = array();
    $path = $wp_upload_dir["basedir"]."/".date("Y", $timestamp)."/".date("m", $timestamp)."/".$type."_".intval($id)."_file1.";
    $arr = download_file($path, $type_old, $id_old, 0);
    $file = $arr[0];
    $extension = $arr[1];
    $path = $path.$extension;
    for ($i=1; $file && 0<strlen($file) && $extension!=-1; $i++) {
        set_time_limit(60);
        if ($file!="dummy") {
            @mkdir(dirname($path), 0777, true);
            $fp = fopen($path, "w+");
            fwrite($fp, $file);
            fclose($fp);
        }
        $title_tmp = $title($i);
        $aq = new WP_Query(array('post_type'=>'attachment', 'posts_per_page'=>30, 'name'=>md5($title_tmp." - ".$i)));
        if (0<count($aq->posts)) {
            $fileid = $aq->posts[0]->ID;
        } else {
            $filetype = wp_check_filetype(basename($path), null);
            $fileid = wp_insert_attachment(array(
                'guid'             => $wp_upload_dir['url'].'/'.basename($path),
                'post_mime_type' => $filetype['type'],
                'post_title'     => $title_tmp,
                'post_name'         => md5($title_tmp." - ".$i),
                'post_content'     => '',
                'post_status'     => 'inherit',
            ), $path);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $metadata = wp_generate_attachment_metadata($fileid, $path);
            wp_update_attachment_metadata($fileid, $metadata);
        }
        $fileids[$i] = $fileid;
        $path = $wp_upload_dir["basedir"]."/".date("Y", $timestamp)."/".date("m", $timestamp)."/".$type."_".intval($id)."_file".($i+1).".";
        $arr = download_file($path, $type_old, $id_old, $i);
        $file = $arr[0];
        $extension = $arr[1];
        $path = $path.$extension;
    }
    return $fileids;
}

function migrate_images($type_old, $type, $id_old, $id, $title, $timestamp) {
    global $wp_upload_dir, $mode, $post;
    $imgspath = $wp_upload_dir["basedir"]."/".date("Y", $timestamp)."/".date("m", $timestamp)."/";
    @mkdir($imgspath, 0777, true);
    if ($mode=="fresh") {
        $existing = scandir($imgspath);
        for ($i=0; $i<count($existing); $i++) {
            if (preg_match("/".$type."\_".intval($id)."\_img([0-9]+)\.jpg/i", $existing[$i], $matches)) {
                // Deprecated
                $title_tmp = $title(intval($matches[1]));
                $aq = new WP_Query(array('post_type'=>'attachment', 'posts_per_page'=>-1, 'name'=>md5($title_tmp)));
                while ($aq->have_posts()) {
                    $aq->the_post();
                    echo "DELETE ATTACHMENT IMG ".json_encode($matches[1])." - ".get_the_id()."\n";
                    wp_delete_attachment(get_the_id(), true);
                }
                // New
                $aq = new WP_Query(array('post_type'=>'attachment', 'posts_per_page'=>-1, 'name'=>md5($title_tmp." - ".$matches[1])));
                while ($aq->have_posts()) {
                    $aq->the_post();
                    echo "DELETE ATTACHMENT IMG ".json_encode($matches[1])." - ".get_the_id()."\n";
                    wp_delete_attachment(get_the_id(), true);
                }
            }
        }
    }
    $imgids = array();
    $path = $imgspath."/".$type."_".intval($id)."_img1.jpg";
    if (is_file($path)) $img = "dummy";
    else $img = @url_get_contents("http://olzimmerberg.ch/img/".$type_old."/".intval($id_old)."/img/001.jpg");
    for ($i=1; $img && 0<strlen($img); $i++) {
        set_time_limit(60);
        if ($img!="dummy") {
            $fp = fopen($path, "w+");
            fwrite($fp, $img);
            fclose($fp);
        }
        $title_tmp = $title($i);
        $aq = new WP_Query(array('post_type'=>'attachment', 'posts_per_page'=>30, 'name'=>md5($title_tmp." - ".$i)));
        echo $path.": ".is_file($path)."\n";
        if (0<count($aq->posts)) {
            $imgid = $aq->posts[0]->ID;
        } else {
            $filetype = wp_check_filetype(basename($path), null);
            $imgid = wp_insert_attachment(array(
                'guid'             => $wp_upload_dir['url'].'/'.basename($path),
                'post_mime_type' => $filetype['type'],
                'post_title'     => $title_tmp,
                'post_name'         => md5($title_tmp." - ".$i),
                'post_content'     => '',
                'post_status'     => 'inherit',
            ), $path);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $metadata = wp_generate_attachment_metadata($imgid, $path);
            wp_update_attachment_metadata($imgid, $metadata);
        }
        $imgids[$i] = $imgid;
        $path = $imgspath."/".$type."_".intval($id)."_img".($i+1).".jpg";
        if (is_file($path)) $img = "dummy";
        else $img = @url_get_contents("http://olzimmerberg.ch/img/".$type_old."/".intval($id_old)."/img/".str_pad($i+1, 3, "0", STR_PAD_LEFT).".jpg");
    }
    return $imgids;
}


// TODO: Aktuell?


if ($post_type=="all" || $post_type=="init") {
    echo "\n\nINIT\n-------\n\n";
    $sq = new WP_Query(array('post_type'=>'page', 'posts_per_page'=>30, 'name'=>'beispiel-seite'));
    foreach ($sq->posts as $key => $value) {
        echo "DELETE PAGE ".($value->ID)."\n";
        wp_delete_post($value->ID, true);
    }
}

if ($post_type=="all" || $post_type=="startseite") {
    echo "\n\nSTARTSEITE\n-------\n\n";

    $timestamp = strtotime("2006-01-01 12:00:00");

    $post = array(
        'post_content'      => '',
        'post_name'         => "startseite",
        'post_title'        => "Startseite",
        'post_status'       => 'publish',
        'post_type'         => 'page',
        'post_author'       => "",
        'ping_status'       => 'closed',
        'post_parent'       => 0,
        'menu_order'        => 0,
        'to_ping'           => '',
        'pinged'            => '',
        'post_password'     => '',
        'post_excerpt'      => "",
        'post_date'         => date("Y-m-d H:i:s", $timestamp),
        'post_date_gmt'     => gmdate("Y-m-d H:i:s", $timestamp),
        'comment_status'    => 'open',
    );
    $sq = new WP_Query(array('post_type'=>'page', 'posts_per_page'=>30, 'name'=>'startseite'));
    if (0<count($sq->posts)) {
        $post['ID'] = $sq->posts[0]->ID;
    }
    $postid = wp_insert_post($post);
    update_option('page_on_front', $postid);
    update_option('show_on_front', 'page');
    echo "Startseite Page ID: ".$postid."\n";
}

if ($post_type=="all" || $post_type=="ueber-uns") {
    echo "\n\nÜber uns\n-------\n\n";

    $timestamp = strtotime("2006-01-01 12:00:00");

    $post = array(
        'post_content'      => '',
        'post_name'         => "ueber-uns",
        'post_title'        => "Über uns",
        'post_status'       => 'publish',
        'post_type'         => 'page',
        'post_author'       => "",
        'ping_status'       => 'closed',
        'post_parent'       => 0,
        'menu_order'        => 0,
        'to_ping'           => '',
        'pinged'            => '',
        'post_password'     => '',
        'post_excerpt'      => "",
        'post_date'         => date("Y-m-d H:i:s", $timestamp),
        'post_date_gmt'     => gmdate("Y-m-d H:i:s", $timestamp),
        'comment_status'    => 'open',
    );
    $sq = new WP_Query(array('post_type'=>'page', 'posts_per_page'=>30, 'name'=>'ueber-uns'));
    if (0<count($sq->posts)) {
        $post['ID'] = $sq->posts[0]->ID;
    }
    $postid = wp_insert_post($post);
    echo "Über uns Page ID: ".$postid."\n";
}

if ($post_type=="all" || $post_type=="berichte") {
    echo "\n\nBERICHTE\n--------\n\n";
    $res = $dbsrc->query("SELECT * FROM aktuell WHERE on_off='1' ORDER BY datum DESC");
    for ($j=0; $j<$res->num_rows && $j<$limit; $j++) {
        set_time_limit(60);
        $row = $res->fetch_assoc();
        if ($row["typ"]!="aktuell") continue;
        if ($row["titel"]=="") continue;
        $bq = new WP_Query(array('post_type'=>array("berichte"), 'posts_per_page'=>30, 'meta_key'=>'import_id', 'meta_value'=>$row["id"]*2, 'meta_compare'=>'='));
        if (!isset($authors[$row["autor"]])) $authors[$row["autor"]] = 0;
        $authors[$row["autor"]] += 1;
        echo str_pad($row["autor"], 3)." - ".$row["titel"];
        if ($bq->have_posts()) {
            echo " => ABORT".$flush."\n";
            continue;
        }
        echo $flush."\n";

        $timestamp = strtotime($row["datum"]." ".($row["zeit"]?$row["zeit"]:"12:00:00"));
        $text = $row["text"];
        $textlang = $row["textlang"];

        $imgids = migrate_images('aktuell', 'berichte', $row['id'], $row['id']*2, function ($i) use ($row) {
            return ($i==1?"Beitragsbild":"Bild-".($i-1))." ".$row['titel']." (ID:berichte".($row['id']*2).")";
        }, $timestamp);

        $fileids = migrate_files('aktuell', 'berichte', $row['id'], $row['id']*2, function ($i) use ($row) {
            return "File-".($i-1)." ".$row["titel"]." (ID:aktuell".($row['id']*2).")";
        }, $timestamp);

        $content = $text."<br><br>".nl2br($textlang);
        $excerpt = $text;

        preg_match_all("/<bild([0-9]+)(\s+size=([0-9]+))?([^>]*)>/i", $content, $matches);
        for ($i=0; $i<count($matches[0]); $i++) {
            $size = intval($matches[3][$i]);
            if ($size<1) $size = 240;
            $ind = intval($matches[1][$i]);
            $tmp_html = "";
            if (1<$ind) {
                $arr = wp_get_attachment_image_src($imgids[$ind], array($size, $size));
                if ($arr) {
                    $tmp_html = "<img class=\"size-medium wp-image-11725 alignleft\" src=\"".$arr[0]."\" width=\"".$arr[1]."\" height=\"".$arr[2]."\" />";
                }
            }
            $content = str_replace($matches[0][$i], $tmp_html, $content);
        }

        preg_match_all("/<datei([0-9]+)(\s+text=(\"|\')([^\"\']+)(\"|\'))?([^>]*)>/i", $content, $matches);
        for ($i=0; $i<count($matches[0]); $i++) {
            $tmptext = $matches[4][$i];
            if (mb_strlen($tmptext)<1) $tmptext = "Datei ".$matches[1][$i];
            $ind = intval($matches[1][$i]);
            $tmp_html = "";
            if (0<$ind) {
                $arr = wp_get_attachment_image_src($fileids[$ind], array(16, 16), true);
                if ($arr) {
                    $tmp_html = "<a href=\"".esc_attr(get_attachment_link($fileids[$ind]))."\"><img src=\"".$arr[0]."\" width=\"".$arr[1]."\" height=\"".$arr[2]."\" style=\"height:16px; width:auto;\" /> ".$tmptext."</a>";
                }
            }
            $content = str_replace($matches[0][$i], $tmp_html, $content);
        }

        $postid = wp_insert_post(array(
            'post_content'     => $content,
            'post_name'         => "berichte_".($row["id"]*2),
            'post_title'     => $row["titel"],
            'post_status'     => 'publish',
            'post_type'         => 'berichte',
            'post_author'     => $author,
            'ping_status'     => 'closed',
            'post_parent'     => 0,
            'menu_order'     => 0,
            'to_ping'         => '',
            'pinged'         => '',
            'post_password'     => '',
            'post_excerpt'     => $excerpt,
            'post_date'         => date("Y-m-d H:i:s", $timestamp),
            'post_date_gmt'     => gmdate("Y-m-d H:i:s", $timestamp),
            'comment_status' => 'open',
        ));

        add_post_meta($postid, 'import_id', $row["id"]*2);
        add_post_meta($postid, 'import_autor', $row["autor"]);
        if ($row["link"] && 1<strlen($row["link"])) {
            add_post_meta($postid, 'import_link', $row["link"]);
        }
        if ($row["newsletter"]==1 && 86400<strtotime($row["newsletter_datum"])) {
            add_post_meta($postid, 'newsletter', $row["newsletter_datum"]);
        }
        if (isset($imgids[1])) {
            set_post_thumbnail($postid, $imgids[1]);
        }
    }

    echo "\n\nKADERBLOG\n---------\n\n";
    $res = $dbsrc->query("SELECT * FROM blog WHERE on_off='1' ORDER BY datum DESC");
    for ($j=0; $j<$res->num_rows && $j<$limit; $j++) {
        set_time_limit(60);
        $row = $res->fetch_assoc();
        if ($row["titel"]=="") continue;
        $bq = new WP_Query(array('post_type'=>array("berichte"), 'posts_per_page'=>30, 'meta_key'=>'import_id', 'meta_value'=>$row["id"]*2+1, 'meta_compare'=>'='));
        if (!isset($authors[$row["autor"]])) $authors[$row["autor"]] = 0;
        $authors[$row["autor"]] += 1;
        echo str_pad($row["autor"], 3)." - ".$row["titel"];
        if ($bq->have_posts()) {
            echo " => ABORT".$flush."\n";
            continue;
        }
        echo $flush."\n";

        $timestamp = strtotime($row["datum"]." ".($row["zeit"]?$row["zeit"]:"12:00:00"));
        $text = $row["text"];

        $imgids = migrate_images('blog', 'berichte', $row['id'], $row['id']*2+1, function ($i) use ($row) {
            return ($i==1?"Beitragsbild":"Bild-".($i-1))." ".$row['titel']." (ID:berichte".($row['id']*2+1).")";
        }, $timestamp);

        $fileids = migrate_files('blog', 'berichte', $row['id'], $row['id']*2+1, function ($i) use ($row) {
            return "File-".($i-1)." ".$row['titel']." (ID:berichte".($row['id']*2+1).")";
        }, $timestamp);

        $content = nl2br($text);

        preg_match_all("/<bild([0-9]+)(\s+size=([0-9]+))?([^>]*)>/i", $content, $matches);
        for ($i=0; $i<count($matches[0]); $i++) {
            $size = intval($matches[3][$i]);
            if ($size<1) $size = 240;
            $ind = intval($matches[1][$i]);
            $tmp_html = "";
            if (1<$ind) {
                $arr = wp_get_attachment_image_src($imgids[$ind], array($size, $size));
                if ($arr) {
                    $tmp_html = "<img class=\"size-medium wp-image-11725 alignleft\" src=\"".$arr[0]."\" width=\"".$arr[1]."\" height=\"".$arr[2]."\" />";
                }
            }
            $content = str_replace($matches[0][$i], $tmp_html, $content);
        }

        preg_match_all("/<datei([0-9]+)(\s+text=(\"|\')([^\"\']+)(\"|\'))?([^>]*)>/i", $content, $matches);
        for ($i=0; $i<count($matches[0]); $i++) {
            $tmptext = $matches[4][$i];
            if (mb_strlen($tmptext)<1) $tmptext = "Datei ".$matches[1][$i];
            $ind = intval($matches[1][$i]);
            $tmp_html = "";
            if (0<$ind) {
                $arr = wp_get_attachment_image_src($fileids[$ind], array(16, 16), true);
                if ($arr) {
                    $tmp_html = "<a href=\"".esc_attr(get_attachment_link($fileids[$ind]))."\"><img src=\"".$arr[0]."\" width=\"".$arr[1]."\" height=\"".$arr[2]."\" style=\"height:16px; width:auto;\" /> ".$tmptext."</a>";
                }
            }
            $content = str_replace($matches[0][$i], $tmp_html, $content);
        }


        $postid = wp_insert_post(array(
            'post_content'     => $content,
            'post_name'         => "berichte_".($row["id"]*2+1),
            'post_title'     => $row["titel"],
            'post_status'     => 'publish',
            'post_type'         => 'berichte',
            'post_author'     => $author,
            'ping_status'     => 'closed',
            'post_parent'     => 0,
            'menu_order'     => 0,
            'to_ping'         => '',
            'pinged'         => '',
            'post_password'     => '',
            'post_date'         => date("Y-m-d H:i:s", $timestamp),
            'post_date_gmt'     => gmdate("Y-m-d H:i:s", $timestamp),
            'comment_status' => 'open',
        ));

        add_post_meta($postid, 'import_id', $row["id"]*2+1);
        add_post_meta($postid, 'import_autor', $row["autor"]);
        if ($row["newsletter"]==1 && 86400<strtotime($row["newsletter_datum"])) {
            add_post_meta($postid, 'newsletter', $row["newsletter_datum"]);
        }
        if (isset($imgids[1])) {
            set_post_thumbnail($postid, $imgids[1]);
        }
    }
}

if ($post_type=="all" || $post_type=="termine") {
    echo "\n\nTERMINE\n--------------\n\n";
    $res = $dbsrc->query("SELECT * FROM termine WHERE on_off='1' ORDER BY datum DESC");
    for ($j=0; $j<$res->num_rows && $j<$limit; $j++) {
        set_time_limit(60);
        $row = $res->fetch_assoc();
        $bq = new WP_Query(array('post_type'=>array("termine"), 'posts_per_page'=>30, 'meta_key'=>'import_id', 'meta_value'=>$row["id"], 'meta_compare'=>'='));
        echo str_pad("??", 3)." - ".$row["titel"];
        if ($bq->have_posts()) {
            echo " => ABORT".$flush."\n";
            continue;
        }
        echo $flush."\n";

        $timestamp = strtotime($row["datum"]);
        $text = $row["text"];
        if ($row["link"]) $text .= "\n".$row["link"];

        $postid = wp_insert_post(array(
            'post_content'      => nl2br($text),
            'post_name'         => "termine_".$row["id"],
            'post_title'        => $row["titel"],
            'post_status'       => 'publish',
            'post_type'         => 'termine',
            'post_author'       => "",
            'ping_status'       => 'closed',
            'post_parent'       => 0,
            'menu_order'        => 0,
            'to_ping'           => '',
            'pinged'            => '',
            'post_password'     => '',
            'post_excerpt'      => "",
            'post_date'         => date("Y-m-d H:i:s"),
            'post_date_gmt'     => gmdate("Y-m-d H:i:s"),
            'comment_status'    => 'open',
        ));

        add_post_meta($postid, 'import_id', $row["id"]);
        add_post_meta($postid, 'timerange_start', date("Y-m-d", $timestamp));
        $timestamp_end = strtotime($row["datum_end"]);
        add_post_meta($postid, 'timerange_end', date("Y-m-d", max($timestamp, $timestamp_end)));
        if (0<$row["xkoord"] && 0<$row["ykoord"]) {
            add_post_meta($postid, 'location_lat', CHtoWGSlat(floatval($row["xkoord"]), floatval($row["ykoord"])));
            add_post_meta($postid, 'location_lng', CHtoWGSlong(floatval($row["xkoord"]), floatval($row["ykoord"])));
        }
        if (0<$row["solv_uid"]) {
            add_post_meta($postid, 'solv', $row["solv_uid"]);
        }
        if ($row["go2ol"]) {
            add_post_meta($postid, 'go2ol', $row["go2ol"]);
        }
        $types = explode(" ", $row["typ"]);
        for ($i=0; $i<count($types); $i++) {
            $type = $types[$i];
            if (array_search($type, array("club", "ol", "training"))===false) {
                echo "Unknown Termin-Typ: \"".$type."\"\n";
            } else {
                wp_set_object_terms($postid, $type, "termin-typ", true);
            }
        }
        if ($row["newsletter"]==1 && 86400<strtotime($row["newsletter_datum"])) {
            add_post_meta($postid, 'newsletter', $row["newsletter_datum"]);
        }
    }
}

if ($post_type=="all" || $post_type=="galerie") {
    echo "\n\nGALERIE\n-------\n\n";
    $res = $dbsrc->query("SELECT * FROM galerie WHERE on_off='1' AND typ='foto' ORDER BY datum DESC");
    for ($j=0; $j<$res->num_rows && $j<$limit; $j++) {
        set_time_limit(60);
        $row = $res->fetch_assoc();
        if ($row["titel"]=="") continue;
        $bq = new WP_Query(array('post_type'=>array("galerie"), 'posts_per_page'=>30, 'meta_key'=>'import_id', 'meta_value'=>$row["id"], 'meta_compare'=>'='));
        if (!isset($authors[$row["autor"]])) $authors[$row["autor"]] = 0;
        $authors[$row["autor"]] += 1;
        echo str_pad($row["autor"], 3)." - ".$row["titel"];
        if ($bq->have_posts()) {
            echo " => ABORT".$flush."\n";
            continue;
        }
        echo $flush."\n";

        $timestamp = strtotime($row["datum"]." "."12:00:00");

        $imgids = migrate_images('galerie', 'galerie', $row['id'], $row['id'], function ($i) use ($row) {
            return "Bild-".$i." ".$row['titel']." (ID:galerie".$row['id'].")";
        }, $timestamp);
        $galerie = array();
        for ($i=1; isset($imgids[$i]); $i++) {
            $galerie[] = $imgids[$i];
        }

        $postid = wp_insert_post(array(
            'post_content'   => "",
            'post_name'      => "galerie_".$row["id"],
            'post_title'     => $row["titel"],
            'post_status'    => 'publish',
            'post_type'      => 'galerie',
            'post_author'    => "",
            'ping_status'    => 'closed',
            'post_parent'    => 0,
            'menu_order'     => 0,
            'to_ping'        => '',
            'pinged'         => '',
            'post_password'  => '',
            'post_excerpt'   => "",
            'post_date'      => date("Y-m-d H:i:s", $timestamp),
            'post_date_gmt'  => gmdate("Y-m-d H:i:s", $timestamp),
            'comment_status' => 'open',
        ));

        add_post_meta($postid, 'import_id', $row["id"]);
        add_post_meta($postid, 'galerie', $galerie);
    }
}

if ($post_type=="all" || $post_type=="forum") {
    echo "\n\nFORUM\n-------\n\n";
    $res = $dbsrc->query("SELECT * FROM forum WHERE on_off='1' ORDER BY datum DESC, zeit DESC");
    for ($j=0; $j<$res->num_rows && $j<$limit; $j++) {
        set_time_limit(60);
        $row = $res->fetch_assoc();
        if ($row["eintrag"]=="") continue;
        $bq = new WP_Query(array('post_type'=>array("forum"), 'posts_per_page'=>30, 'meta_key'=>'import_id', 'meta_value'=>$row["id"], 'meta_compare'=>'='));
        if (!isset($authors[$row["name"]])) $authors[$row["name"]] = 0;
        $authors[$row["name"]] += 1;
        echo str_pad($row["name"], 3)." - ".$row["email"];
        if ($bq->have_posts()) {
            echo " => ABORT".$flush."\n";
            continue;
        }
        echo $flush."\n";

        $timestamp = strtotime($row["datum"]." ".$row["zeit"]);

        $postid = wp_insert_post(array(
            'post_content'     => $row["eintrag"],
            'post_name'         => "forum_".$row["id"],
            'post_title'     => $row["name"],
            'post_status'     => 'publish',
            'post_type'         => 'forum',
            'post_author'     => "",
            'ping_status'     => 'closed',
            'post_parent'     => 0,
            'menu_order'     => 0,
            'to_ping'         => '',
            'pinged'         => '',
            'post_password'     => '',
            'post_excerpt'     => "",
            'post_date'         => date("Y-m-d H:i:s", $timestamp),
            'post_date_gmt'     => gmdate("Y-m-d H:i:s", $timestamp),
            'comment_status' => 'open',
        ));

        add_post_meta($postid, 'email', $row["email"]);
        add_post_meta($postid, 'import_id', $row["id"]);
        add_post_meta($postid, 'import_uid', $row["uid"]);
        if ($row["newsletter"]==1 && 86400<strtotime($row["newsletter_datum"])) {
            add_post_meta($postid, 'newsletter', $row["newsletter_datum"]);
        }
    }
}

if ($post_type=="all" || $post_type=="bild_der_woche") {
    echo "\n\nBILD DER WOCHE\n--------------\n\n";
    $res = $dbsrc->query("SELECT * FROM bild_der_woche ORDER BY datum DESC");
    for ($j=0; $j<$res->num_rows && $j<$limit; $j++) {
        set_time_limit(60);
        $row = $res->fetch_assoc();
        $bq = new WP_Query(array('post_type'=>array("bild_der_woche"), 'posts_per_page'=>30, 'meta_key'=>'import_id', 'meta_value'=>$row["id"], 'meta_compare'=>'='));
        echo str_pad("??", 3)." - ".$row["text"];
        if ($bq->have_posts()) {
            echo " => ABORT".$flush."\n";
            continue;
        }
        echo $flush."\n";

        $timestamp = strtotime($row["datum"]." "."12:00:00");

        $imgids = migrate_images('bild_der_woche', 'bild_der_woche', $row['id'], $row['id'], function ($i) use ($row) {
            return ($i==1?"Bild der Woche":($i==2?"Bild der Woche (Mouseover)":"Bild-".($i-1))).": ".$row['text']." (ID:bild_der_woche".$row['id'].")";
        }, $timestamp);

        $postid = wp_insert_post(array(
            'post_content'     => "",
            'post_name'         => "bild_der_woche_".$row["id"],
            'post_title'     => $row["text"],
            'post_status'     => 'publish',
            'post_type'         => 'bild_der_woche',
            'post_author'     => "",
            'ping_status'     => 'closed',
            'post_parent'     => 0,
            'menu_order'     => 0,
            'to_ping'         => '',
            'pinged'         => '',
            'post_password'     => '',
            'post_excerpt'     => "",
            'post_date'         => date("Y-m-d H:i:s", $timestamp),
            'post_date_gmt'     => gmdate("Y-m-d H:i:s", $timestamp),
            'comment_status' => 'open',
        ));

        add_post_meta($postid, 'import_id', $row["id"]);
        if (isset($imgids[1])) {
            set_post_thumbnail($postid, $imgids[1]);
        }
    }
}

if ($post_type=="all" || $post_type=="karten") {
    echo "\n\nKARTEN\n-------\n\n";
    $res = $dbsrc->query("SELECT * FROM karten");
    for ($j=0; $j<$res->num_rows && $j<$limit; $j++) {
        set_time_limit(60);
        $row = $res->fetch_assoc();
        if ($row["name"]=="") continue;
        $bq = new WP_Query(array('post_type'=>array("karten"), 'posts_per_page'=>30, 'meta_key'=>'import_id', 'meta_value'=>$row["id"], 'meta_compare'=>'='));
        echo str_pad("??", 3)." - ".$row["name"];
        if ($bq->have_posts()) {
            echo " => ABORT".$flush."\n";
            continue;
        }
        echo $flush."\n";

        $postid = wp_insert_post(array(
            'post_content'     => "",
            'post_name'         => "karten_".$row["id"],
            'post_title'     => $row["name"],
            'post_status'     => 'publish',
            'post_type'         => 'karten',
            'post_author'     => "",
            'ping_status'     => 'closed',
            'post_parent'     => 0,
            'menu_order'     => 0,
            'to_ping'         => '',
            'pinged'         => '',
            'post_password'     => '',
            'post_excerpt'     => "",
            'post_date'         => date("Y-m-d H:i:s"),
            'post_date_gmt'     => gmdate("Y-m-d H:i:s"),
            'comment_status' => 'open',
        ));

        add_post_meta($postid, 'position', $row["position"]);
        add_post_meta($postid, 'kartennr', $row["kartennr"]);
        if (0<$row["center_x"] && 0<$row["center_y"]) {
            $center_lat = CHtoWGSlat(floatval($row["center_x"]), floatval($row["center_y"]));
            $center_lng = CHtoWGSlong(floatval($row["center_x"]), floatval($row["center_y"]));
            add_post_meta($postid, 'map_min_lat', $center_lat-0.01);
            add_post_meta($postid, 'map_min_lng', $center_lng-0.01);
            add_post_meta($postid, 'map_max_lat', $center_lat+0.01);
            add_post_meta($postid, 'map_max_lng', $center_lng+0.01);
        }
        add_post_meta($postid, 'year', $row["jahr"]);
        add_post_meta($postid, 'scale', $row["massstab"]);
        add_post_meta($postid, 'city', $row["ort"]);
        add_post_meta($postid, 'zoom', $row["zoom"]);
        $type = $row["typ"];
        if (array_search($type, array("ol", "stadt", "scool"))===false) {
            echo "Unknown Termin-Typ: \"".$type."\"\n";
        } else {
            $type_mappings = array("ol" =>"ol", "stadt" =>"city", "scool" =>"scool");
            wp_set_object_terms($postid, $type_mappings[$type], "karten-typ", true);
        }
        add_post_meta($postid, 'import_id', $row["id"]);

        $title_tmp = "Karte"." ".$row["name"];
        if ($mode=="fresh") {
            $existing = scandir($wp_upload_dir["basedir"]."/".intval($row["jahr"])."/01/");
            for ($i=0; $i<count($existing); $i++) {
                if (preg_match("/karten\_".intval($id)."\_thumbnail\.jpg/i", $existing[$i], $matches)) {
                    // Deprecated
                    $aq = new WP_Query(array('post_type'=>'attachment', 'posts_per_page'=>-1, 'name'=>md5($title_tmp)));
                    while ($aq->have_posts()) {
                        $aq->the_post();
                        echo "DELETE ATTACHMENT IMG  - ".get_the_id()."\n";
                        wp_delete_attachment(get_the_id(), true);
                    }
                    // New
                    $aq = new WP_Query(array('post_type'=>'attachment', 'posts_per_page'=>-1, 'name'=>md5($title_tmp." - ")));
                    while ($aq->have_posts()) {
                        $aq->the_post();
                        echo "DELETE ATTACHMENT IMG  - ".get_the_id()."\n";
                        wp_delete_attachment(get_the_id(), true);
                    }
                }
            }
        }
        $ident_old = str_replace(array("ä","ö","ü","-"," ","/"), array("ae","oe","ue","_","_","_"), $row["name"]);
        $ident_old .= "_".$row["jahr"]."_".preg_replace("/[^0-9]/", "", substr($row["massstab"],2));
        $ident_old = strtolower($ident_old);
        $path = $wp_upload_dir["basedir"]."/".intval($row["jahr"])."/01/karten_".intval($row["id"])."_thumbnail.jpg";
        if (is_file($path)) $img = "dummy";
        else $img = @url_get_contents("http://olzimmerberg.ch/img/karten/".$ident_old.".jpg");
        if ($img!="dummy" && $img && 0<strlen($img)) {
            @mkdir(dirname($path), 0777, true);
            $fp = fopen($path, "w+");
            fwrite($fp, $img);
            fclose($fp);
        }
        if (is_file($path)) {
            $aq = new WP_Query(array('post_type'=>'attachment', 'posts_per_page'=>30, 'name'=>md5($title_tmp." - ")));
            echo count($aq->posts)."\n";
            if (0<count($aq->posts)) {
                $imgid = $aq->posts[0]->ID;
            } else {
                $filetype = wp_check_filetype(basename($path), null);
                $imgid = wp_insert_attachment(array(
                    'guid'             => $wp_upload_dir['url'].'/'.basename($path),
                    'post_mime_type' => $filetype['type'],
                    'post_title'     => $title_tmp,
                    'post_name'         => md5($title_tmp." - "),
                    'post_content'     => '',
                    'post_status'     => 'inherit',
                ), $path);
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                $metadata = wp_generate_attachment_metadata($imgid, $path);
                wp_update_attachment_metadata($imgid, $metadata);
            }
            set_post_thumbnail($postid, $imgid);
        }
    }
}

if ($post_type=="all" || $post_type=="texte") {
    echo "\n\nTEXTE\n-----\n\n";
    $id_name_map = array(
        1     => array('termine_trainings', "Termine: Trainings"),
        2     => array('termine_links', "Termine: Externe Links"),
        3     => array('termine_newsletter', "Termine: Newsletter-Hinweis"),
        4     => array('forum_regeln', "Forum: Regeln"),
        5     => array(),
        6     => array('service_newsletter', "Service: Newsletter-Hinweis"),
        7     => array(),
        8     => array(),
        9     => array(),
        10     => array(),
        11     => array(),
    );
    $res = $dbsrc->query("SELECT * FROM olz_text WHERE on_off='1'");
    for ($j=0; $j<$res->num_rows && $j<$limit; $j++) {
        set_time_limit(60);
        $row = $res->fetch_assoc();
        $arr = $id_name_map[intval($row["id"])];
        if ($row["text"]=="" || count($arr)==0) continue;
        $ident = $arr[0];
        $desc = $arr[1];
        $bq = new WP_Query(array('post_type'=>array("texte"), 'posts_per_page'=>30, 'meta_key'=>'import_id', 'meta_value'=>$row["id"], 'meta_compare'=>'='));
        echo str_pad("??", 3)." - ".$ident;
        if ($bq->have_posts()) {
            echo " => ABORT".$flush."\n";
            continue;
        }
        echo $flush."\n";

        $postid = wp_insert_post(array(
            'post_content'     => $row["text"],
            'post_name'         => "texte_".$row["id"],
            'post_title'     => $desc,
            'post_status'     => 'publish',
            'post_type'         => 'texte',
            'post_author'     => "",
            'ping_status'     => 'closed',
            'post_parent'     => 0,
            'menu_order'     => 0,
            'to_ping'         => '',
            'pinged'         => '',
            'post_password'     => '',
            'post_excerpt'     => "",
            'post_date'         => date("Y-m-d H:i:s"),
            'post_date_gmt'     => gmdate("Y-m-d H:i:s"),
            'comment_status' => 'open',
        ));

        add_post_meta($postid, 'ident', $ident);
        add_post_meta($postid, 'import_id', $row["id"]);
    }
}

// TODO: authors
echo "\n\n\nAUTOREN\n-------\n\n";
print_r($authors);

?>

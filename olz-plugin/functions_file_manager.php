<?php

function olz_ajax_get_folder_contents() {
    global $deployment;
    $root = $deployment['secrets-root'] . '/file_manager_root/';
    if (!is_dir($root)) {
        mkdir($root, 0700, true);
    }
    $path = realpath($root . '/' . $_POST['path']) . '/';
    if (substr($path, 0, strlen($root)) != $root) {
        return null;
    }
    $valid_entries = array();
    foreach (scandir($path) as $key => $entry) {
        if (substr($entry, 0, 1) != '.') {
            $entry_path = $path . '/' . $entry;
            $valid_entries[] = array(
                'name' => $entry,
                'size' => filesize($entry_path),
                'modification' => filemtime($entry_path),
                'mime' => is_dir($entry_path) ? 'directory' : mime_content_type($entry_path),
            );
        }
    }
    return $valid_entries;
}
add_action('wp_ajax_get_folder_contents', olz_ajaxify('olz_ajax_get_folder_contents'));

function olz_ajax_rename() {
    global $deployment;
    $root = $deployment['secrets-root'] . '/file_manager_root/';
    $path = realpath($root) . '/' . $_POST['path'];
    if (substr($path, 0, strlen($root)) != $root) {
        return null;
    }
    $new_path = realpath($root) . '/' . $_POST['new_path'];
    if (substr($new_path, 0, strlen($root)) != $root) {
        return null;
    }
    rename($path, $new_path);
    return 'OK';
}
add_action('wp_ajax_rename', olz_ajaxify('olz_ajax_rename'));

function olz_ajax_delete() {
    global $deployment;
    $root = $deployment['secrets-root'] . '/file_manager_root/';
    $path = realpath($root) . '/' . $_POST['path'];
    if (substr($path, 0, strlen($root)) != $root) {
        return null;
    }
    if (is_dir($path)) {
        return rmdir($path) ? 'OK' : 'NOK';
    } else {
        return unlink($path) ? 'OK' : 'NOK';
    }
    return 'NOK';
}
add_action('wp_ajax_delete', olz_ajaxify('olz_ajax_delete'));

function olz_ajax_download() {
    global $deployment;
    $root = $deployment['secrets-root'] . '/file_manager_root/';
    $path = realpath($root . '/' . $_GET['path']);
    if (substr($path, 0, strlen($root)) != $root) {
        return null;
    }
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-type: ' . mime_content_type($path) . '');
    wp_die(file_get_contents($path));
}
add_action('wp_ajax_download', 'olz_ajax_download');

function olz_ajax_create_folder() {
    global $deployment;
    $root = $deployment['secrets-root'] . '/file_manager_root/';
    $folder_path = realpath($root) . '/' . $_POST['folder_path'];
    if (substr($folder_path, 0, strlen($root)) != $root) {
        return null;
    }
    mkdir($folder_path);
    return 'OK';
}
add_action('wp_ajax_create_folder', olz_ajaxify('olz_ajax_create_folder'));

function olz_ajax_upload_file() {
    global $deployment;
    $root = $deployment['secrets-root'] . '/file_manager_root/';
    $file_path = realpath($root) . '/' . $_POST['file_path'];
    if (substr($file_path, 0, strlen($root)) != $root) {
        return null;
    }
    $file_content = $_POST['file_content'];
    $res = preg_match('/data:([^;]+);base64,/', $file_content, $matches);
    $file_binary = base64_decode(substr($file_content, strlen($matches[0])));
    file_put_contents($file_path, $file_binary);
    return 'OK';
}
add_action('wp_ajax_upload_file', olz_ajaxify('olz_ajax_upload_file'));

function olz_file_manager_page() {
    ?>
    <script type="text/javascript">
    function get_icon(file) {
        if (file.mime == 'directory') {
            return 'dashicons-portfolio';
        }
        return 'dashicons-media-text';
    }
    var path = [];
    window.onhashchange = function () {
        var hash = location.hash.substr(1);
        setPath(hash ? hash.split('/') : []);
    };
    function setPath(newPath) {
        if (path.join('/') === newPath.join('/')) {
            return;
        }
        if (newPath[0].trim() === '') {
            newPath = newPath.slice(1);
        }
        path = newPath;
        location.hash = path.join('/');
        redraw();
    }

    function redraw() {
        var rootElem = jQuery('<a href="">Dateien</a>');
        rootElem.on('click', function () {
            setPath([]);
            return false;
        });
        var folderPath = jQuery('.folder-path').html('').append(rootElem);
        path.map(function (pathComponent, index) {
            var componentElem = jQuery('<span> / <a href="">' + pathComponent + '</a></span>');
            componentElem.find('a').on('click', function () {
                setPath(path.slice(0, index + 1));
                return false;
            });
            folderPath.append(componentElem);
        });
        var folderActions = jQuery('.folder-actions').html('').append('<span><input type="file" id="upload-file" multiple="true" /> <a href="" id="create-folder">Ordner erstellen</a></span>');
        folderActions.find('input#upload-file').on('change', function (e) {
            var files = e.target.files;
            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                var reader = new FileReader();
                reader.onload = function (file, e) {
                    jQuery.post(ajaxurl, {
                        'action': 'upload_file',
                        'file_path': path.concat([file.name]).join('/'),
                        'file_content': e.target.result,
                    }, function (response) {
                        redraw();
                    });
                }.bind(this, file);
                reader.readAsDataURL(file);
            }
            return false;
        });
        folderActions.find('a#create-folder').on('click', function () {
            var newName = window.prompt('Name des neuen Ordners:');
            jQuery.post(ajaxurl, {
                'action': 'create_folder',
                'folder_path': path.concat([newName]).join('/'),
            }, function (response) {
                redraw();
            });
            return false;
        });
        jQuery('#folder-content').html('');
        get_folder_contents(path, function (entries) {
            var folderContent = jQuery('#folder-content').html('');
            entries.map(function (entry) {
                var entryElem = jQuery('<tr><td><span class="dashicons ' + get_icon(entry) + '"></span> <a href="" id="title">' + entry.name + '</a></td><td>' + entry.size + '</td><td>' + new Date(entry.modification * 1000).toLocaleDateString('de-CH') + '</td><td><a href="" id="rename">Umbenennen</a></td><td><a href="" id="delete">Löschen</a></td><td><a href="' + ajaxurl + '?action=download&path=' + path.concat([entry.name]).join('/') + '">Herunterladen</a></td></tr>');
                if (entry.mime == 'directory') {
                    entryElem.find('a#title').on('click', function () {
                        setPath(path.concat([entry.name]));
                        return false;
                    });
                } else {
                    entryElem.find('a#title').prop('href', ajaxurl + '?action=download&path=' + path.concat([entry.name]).join('/'));
                }
                entryElem.find('a#rename').on('click', function () {
                    var newName = window.prompt('Neuer Dateiname:');
                    jQuery.post(ajaxurl, {
                        'action': 'rename',
                        'path': path.concat([entry.name]).join('/'),
                        'new_path': path.concat([newName]).join('/'),
                    }, function (response) {
                        redraw();
                    });
                    return false;
                });
                entryElem.find('a#delete').on('click', function () {
                    if (window.confirm('Wirklich löschen?')) {
                        jQuery.post(ajaxurl, {
                            'action': 'delete',
                            'path': path.concat([entry.name]).join('/'),
                        }, function (response) {
                            var respData = JSON.parse(response);
                            if (respData === 'NOK') {
                                alert('Aktion fehlgeschlagen');
                            }
                            redraw();
                        });
                    }
                    return false;
                });
                folderContent.append(entryElem);
            });
        });
    }
    function get_folder_contents(path, onComplete) {
        jQuery.post(ajaxurl, {
            'action': 'get_folder_contents',
            'path': path.join('/'),
        }, function (response) {
            var tmp = null;
            try {
                var tmp = JSON.parse(response);
            } catch (exc) {}
            onComplete(tmp);
        });
    }

    jQuery(function () {
        setPath(path);
        redraw();
    });
    </script>
    <div>
        <span class="folder-path"></span>
        <span style="margin-left: 30px;" class="folder-actions"></span>
    </div>
    <br />
    <table id="folder-content"></table>
    <br />
    <div>
        <span class="folder-path"></span>
        <span style="margin-left: 30px;" class="folder-actions"></span>
    </div>
    <?php
}

?>

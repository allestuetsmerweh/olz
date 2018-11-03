<?php

$solv_events_fields = array(
    // array(db_col_name, db_type, csv_col_name, placeholder, conversion_fn)
    array('solv_uid', 'INT(11) PRIMARY KEY', 'unique_id', '%d', intval),
    array('event_date', 'DATE', 'date', '%s', dateval),
    array('duration', 'INT(11)', 'duration', '%d', intval),
    array('kind', 'TEXT', 'kind', '%s', strval),
    array('day_night', 'TEXT', 'day_night', '%s', strval),
    array('national', 'INT(11)', 'national', '%d', intval),
    array('region', 'TEXT', 'region', '%s', strval),
    array('type', 'TEXT', 'type', '%s', strval),
    array('name', 'TEXT', 'event_name', '%s', strval),
    array('link', 'TEXT', 'event_link', '%s', strval),
    array('club', 'TEXT', 'club', '%s', strval),
    array('map', 'TEXT', 'map', '%s', strval),
    array('location', 'TEXT', 'location', '%s', strval),
    array('coord_x', 'INT(11)', 'coord_x', '%d', intval),
    array('coord_y', 'INT(11)', 'coord_y', '%d', intval),
    array('deadline', 'DATE', 'deadline', '%s', dateval),
    array('entryportal', 'INT(11)', 'entryportal', '%d', intval),
    array('last_modification', 'TIMESTAMP NOT NULL DEFAULT \'0000-00-00 00:00:00\'', 'last_modification', '%s', datetimeval),
);

$solv_entryportals = array(
    1 => array('GO2OL', 'http://go2ol.ch'),
    2 => array('picoTIMING', 'http://entry.picoevents.ch/'),
    3 => array('anderes', null),
);

function load_solv_fixtures($year) {
    $url = 'https://www.o-l.ch/cgi-bin/fixtures?&year='.$year.'&kind=&csv=1';
    echo "Loading $url...\n";
    $resp = utf8_encode(file_get_contents($url));
    $data = str_getcsv($resp, "\n");
    $header = false;
    foreach ($data as $row_index => $csv_row) {
        $tmp = str_getcsv($csv_row, ";");
        if ($row_index==0) {
            $header = $tmp;
        } else {
            $data[$row_index] = array();
            foreach ($header as $col_index => $col) {
                $data[$row_index][$col] = $tmp[$col_index];
            }
        }
    }
    array_splice($data, 0, 1);
    return $data;
}

?>

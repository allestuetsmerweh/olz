<?php
declare(strict_types=1);

require_once(dirname(__FILE__).'/../../olz-plugin/utils/general.php');
require_once(dirname(__FILE__).'/../../olz-plugin/utils/solv.php');

$year = date('Y');

$expected_solv_csv_fields = array(
    'unique_id' => function ($v) {return intval($v) == $v;},
    'date' => function ($v) {
        global $year;
        return !!preg_match('/^'.$year.'\-[0-9]{2}\-[0-9]{2}$/', dateval($v));
    },
    'duration' => function ($v) {return intval($v) == $v && $v > 0;},
    'kind' => function ($v) {return isset(array('foot' => 1, 'bike' => 1, 'ski' => 1)[$v]);},
    'day_night' => function ($v) {return isset(array('day' => 1, 'night' => 1)[$v]);},
    'national' => function ($v) {return $v == 0 || $v == 1;},
    'region' => false,
    'type' => false,
    'event_name' => false,
    'event_link' => function ($v) {return $v == '' || !!preg_match('/^http(s?)\:\/\//', $v);},
    'club' => false,
    'map' => false,
    'location' => false,
    'coord_x' => function ($v) {return $v == '' || (400000 < $v && $v < 850000) || (2480000 < $v && $v < 2835000);},
    'coord_y' => function ($v) {return $v == '' || (50000 < $v && $v < 300000) || (1060000 < $v && $v < 1300000);},
    'deadline' => function ($v) {
        global $year;
        return $v == '' || !!preg_match('/^'.$year.'\-[0-9]{2}\-[0-9]{2}$/', dateval($v));
    },
    'entryportal' => function ($v) {
        global $solv_entryportals;
        return $v == 0 || isset($solv_entryportals[$v]);
    },
    'last_modification' => function ($v) {
        global $year;
        return strtotime($v) < strtotime(date('Y-m-d H:i:s'));
    },
);

use PHPUnit\Framework\TestCase;

final class SolvTest extends TestCase {
    public function test_load_solv_fixtures(): void {
        global $year, $expected_solv_csv_fields;
        $data = load_solv_fixtures($year);
        $this->assertGreaterThan(3, count($data));
        foreach ($data as $row_index => $entry) {
            foreach ($expected_solv_csv_fields as $field_name => $field_check) {
                $this->assertArrayHasKey($field_name, $entry);
                if ($field_check !== false) {
                    $this->assertTrue($field_check($entry[$field_name]));
                }
            }
        }
    }
}
?>

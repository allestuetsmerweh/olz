<?php
declare(strict_types=1);

require_once(dirname(__FILE__).'/../../olz-plugin/utils/general.php');

use PHPUnit\Framework\TestCase;

final class GeneralTest extends TestCase {
    public function test_dateval(): void {
        $this->assertEquals('2018-11-03', dateval('2018-11-03'));
        $this->assertEquals('2018-11-03', dateval('2018-11-3'));
        $this->assertEquals('2000-01-01', dateval('2000-1-1'));
        $this->assertEquals(null, dateval('test'));
    }

    public function test_datetimeval(): void {
        $this->assertEquals('2018-11-03 17:29:23', datetimeval('2018-11-03 17:29:23'));
        $this->assertEquals('2018-11-03 17:29:03', datetimeval('2018-11-3 17:29:3'));
        $this->assertEquals('2000-01-01 01:01:01', datetimeval('2000-1-1 1:1:1'));
        $this->assertEquals(null, datetimeval('test'));
    }
}
?>

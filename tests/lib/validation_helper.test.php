<?php

use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for validation_helper.php
 */
class ValidationHelperTestCase extends LucidFrameTestCase
{
    public function testDateValidation()
    {
        $validations = array();
        $values = array(
            1 => array('31/12/2014', 'd/m/y'),
            2 => array('12/31/2014', 'm/d/y'),
            3 => array('2014-12-31', ''),
            4 => array('28.2.2014', 'd.m.y'),
            5 => array('02.28.2014', 'm.d.y')
        );
        foreach ($values as $key => $val) {
            $validations['txtDate'.$key] = array(
                'caption'   => 'Date '.$key,
                'value'     => $val[0],
                'rules'     => array('date'),
                'dateFormat'=> $val[1],
            );
        }
        $this->assertTrue(validation_check($validations));
    }

    public function testTimeValidation()
    {
        $validations = array();
        $values = array(
            1 => '13:59',
            2 => '13:59:59',
            3 => '1:00pm',
            4 => '01:59:59 PM',
            5 => '04:00',
            6 => '05:00 AM',
            7 => '12:59 PM'
        );
        foreach ($values as $key => $val) {
            $validations['txtTime'.$key] = array(
                'caption'   => 'Time '.$key,
                'value'     => $val,
                'rules'     => array('time'),
            );
        }
        $this->assertTrue(validation_check($validations));

        $validations = array();
        $values = array(
            3 => '1:00pm',
            4 => '01:59:59 PM',
            6 => '05:00 AM',
            7 => '12:59 PM'
        );
        foreach ($values as $key => $val) {
            $validations['txtTime'.$key] = array(
                'caption'   => 'Time '.$key,
                'value'     => $val,
                'rules'     => array('time'),
                'timeFormat'=> '12'
            );
        }
        $this->assertTrue(validation_check($validations));

        $validations = array();
        $values = array(
            1 => '13:59',
            2 => '13:59:59',
            3 => '04:00',
            4 => '23:59',
        );
        foreach ($values as $key => $val) {
            $validations['txtTime'.$key] = array(
                'caption'   => 'Time '.$key,
                'value'     => $val,
                'rules'     => array('time'),
                'timeFormat'=> '24'
            );
        }
        $this->assertTrue(validation_check($validations));
    }

    public function testDateTimeValidation()
    {
        $validations = array();
        $values = array(
            1 => array('31/12/2014 13:59', 'd/m/y'),
            2 => array('12/31/2014 13:59:59', 'm/d/y'),
            3 => array('2014-12-31 1:00pm', 'y-m-d'),
            4 => array('28.2.2014 01:59:59 PM', 'd.m.y'),
            5 => array('02.28.2014 11:59 am', 'm.d.y')
        );
        foreach ($values as $key => $val) {
            $validations['txtDateTime'.$key] = array(
                'caption'   => 'DateTime '.$key,
                'value'     => $val[0],
                'rules'     => array('datetime'),
                'dateFormat'=> $val[1]
            );
        }
        $this->assertTrue(validation_check($validations));

        $validations = array();
        $values = array(
            1 => array('31/12/2014 13:59', 'd/m/y'),
            2 => array('12/31/2014 13:59:59', 'm/d/y'),
            3 => array('12/31/2014 02:00:00', 'm/d/y'),
        );
        foreach ($values as $key => $val) {
            $validations['txtDateTime'.$key] = array(
                'caption'   => 'DateTime '.$key,
                'value'     => $val[0],
                'rules'     => array('datetime'),
                'dateFormat'=> $val[1],
                'timeFormat'=> '24'
            );
        }
        $this->assertTrue(validation_check($validations));

        $validations = array();
        $values = array(
            1 => array('2014-12-31 1:00pm', 'y-m-d'),
            2 => array('28.2.2014 01:59:59 PM', 'd.m.y'),
            3 => array('02.28.2014 11:59 am', 'm.d.y')
        );
        foreach ($values as $key => $val) {
            $validations['txtDateTime'.$key] = array(
                'caption'   => 'DateTime '.$key,
                'value'     => $val[0],
                'rules'     => array('datetime'),
                'dateFormat'=> $val[1],
                'timeFormat'=> '12'
            );
        }
        $this->assertTrue(validation_check($validations));
    }
}

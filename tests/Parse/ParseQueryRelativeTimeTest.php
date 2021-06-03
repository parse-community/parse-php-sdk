<?php
/**
 * Class ParseQueryRelativeTimeTest | Parse/Test/ParseQueryRelativeTimeTest.php
 */

namespace Parse\Test;

use Parse\ParseObject;
use Parse\ParseQuery;

use PHPUnit\Framework\TestCase;

class ParseQueryRelativeTimeTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
    }

    public function setup() : void
    {
        Helper::clearClass('TestObject');
    }

    public function tearDown() : void
    {
        Helper::tearDown();
    }

    public function provideDateTestObjects()
    {
        $obj = new ParseObject('TestObject');
        $obj->save();

        // use server date
        $baselineDate = $obj->getCreatedAt()->format('m/d/Y H:i:s');

        // 4 days ago
        $date = \DateTime::createFromFormat('m/d/Y H:i:s', $baselineDate);
        $date->sub((new \DateInterval('P4D')));
        $obj->set('date', $date);
        $obj->set('name', 'obj1');
        $obj->save();

        // 4 hours ago
        $obj = new ParseObject('TestObject');
        $date = \DateTime::createFromFormat('m/d/Y H:i:s', $baselineDate);
        $date->sub((new \DateInterval('PT4H')));
        $obj->set('date', $date);
        $obj->set('name', 'obj2');
        $obj->save();

        // 4 hours from now
        $obj = new ParseObject('TestObject');
        $date = \DateTime::createFromFormat('m/d/Y H:i:s', $baselineDate);
        $date->add((new \DateInterval('PT4H')));
        $obj->set('date', $date);
        $obj->set('name', 'obj3');
        $obj->save();

        // 4 days from now
        $obj = new ParseObject('TestObject');
        $date = \DateTime::createFromFormat('m/d/Y H:i:s', $baselineDate);
        $date->add((new \DateInterval('P4D')));
        $obj->set('date', $date);
        $obj->set('name', 'obj4');
        $obj->save();
    }

    public function provideExtendedDateTestObjects()
    {
        $obj = new ParseObject('TestObject');
        $obj->save();

        // use server date
        $baselineDate = $obj->getCreatedAt()->format('m/d/Y H:i:s');

        // 1 year 20 days ago
        $date = \DateTime::createFromFormat('m/d/Y H:i:s', $baselineDate);
        $date->sub((new \DateInterval('P01Y20D')));
        $obj->set('date', $date);
        $obj->set('name', 'obj1');
        $obj->save();

        // 1 year 8 days ago
        $obj = new ParseObject('TestObject');
        $date = \DateTime::createFromFormat('m/d/Y H:i:s', $baselineDate);
        $date->sub((new \DateInterval('P01Y8D')));
        $obj->set('date', $date);
        $obj->set('name', 'obj2');
        $obj->save();

        // 1 year 8 days from now
        $obj = new ParseObject('TestObject');
        $date = \DateTime::createFromFormat('m/d/Y H:i:s', $baselineDate);
        $date->add((new \DateInterval('P01Y8D')));
        $obj->set('date', $date);
        $obj->set('name', 'obj3');
        $obj->save();

        // 1 year 20 days from now
        $obj = new ParseObject('TestObject');
        $date = \DateTime::createFromFormat('m/d/Y H:i:s', $baselineDate);
        $date->add((new \DateInterval('P01Y20D')));
        $obj->set('date', $date);
        $obj->set('name', 'obj4');
        $obj->save();
    }

    /**
     * @group relative-time-queries
     */
    public function testGreaterThanRelativeTime()
    {
        $this->provideDateTestObjects();

        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', '5 days ago');
        $this->assertEquals(4, $query->count());

        $query->equalTo('name', 'obj1');
        $this->assertEquals(1, $query->count());

        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', '1 day ago');
        $this->assertEquals(3, $query->count());

        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', 'in 1 hour');
        $this->assertEquals(2, $query->count());

        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', 'in 1 day');
        $this->assertEquals(1, $query->count());

        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', 'in 5 days');
        $this->assertEquals(0, $query->count());
    }

    /**
     * @group relative-time-queries
     */
    public function testLessThanRelativeTime()
    {
        $this->provideDateTestObjects();

        $query = new ParseQuery('TestObject');
        $query->lessThanRelativeTime('date', '5 days ago');
        $this->assertEquals(0, $query->count());

        $query = new ParseQuery('TestObject');
        $query->lessThanRelativeTime('date', '1 day ago');
        $this->assertEquals(1, $query->count());

        $query->equalTo('name', 'obj1');
        $this->assertEquals(1, $query->count());

        $query = new ParseQuery('TestObject');
        $query->lessThanRelativeTime('date', 'in 1 hour');
        $this->assertEquals(2, $query->count());

        $query = new ParseQuery('TestObject');
        $query->lessThanRelativeTime('date', 'in 1 day');
        $this->assertEquals(3, $query->count());

        $query = new ParseQuery('TestObject');
        $query->lessThanRelativeTime('date', 'in 5 days');
        $this->assertEquals(4, $query->count());
    }

    /**
     * @group relative-time-queries
     */
    public function testGreaterThanEqualToRelativeTime()
    {
        $this->provideDateTestObjects();

        $query = new ParseQuery('TestObject');
        $query->greaterThanOrEqualToRelativeTime('date', '5 days ago');
        $this->assertEquals(4, $query->count());

        $query->equalTo('name', 'obj1');
        $this->assertEquals(1, $query->count());

        $query = new ParseQuery('TestObject');
        $query->greaterThanOrEqualToRelativeTime('date', '1 day ago');
        $this->assertEquals(3, $query->count());

        $query = new ParseQuery('TestObject');
        $query->greaterThanOrEqualToRelativeTime('date', 'in 1 hour');
        $this->assertEquals(2, $query->count());

        $query = new ParseQuery('TestObject');
        $query->greaterThanOrEqualToRelativeTime('date', 'in 1 day');
        $this->assertEquals(1, $query->count());

        $query = new ParseQuery('TestObject');
        $query->greaterThanOrEqualToRelativeTime('date', 'in 5 days');
        $this->assertEquals(0, $query->count());
    }

    /**
     * @group relative-time-queries
     */
    public function testLessThanEqualToRelativeTime()
    {
        $this->provideDateTestObjects();

        $query = new ParseQuery('TestObject');
        $query->lessThanOrEqualToRelativeTime('date', '5 days ago');
        $this->assertEquals(0, $query->count());

        $query = new ParseQuery('TestObject');
        $query->lessThanOrEqualToRelativeTime('date', '1 day ago');
        $this->assertEquals(1, $query->count());

        $query->equalTo('name', 'obj1');
        $this->assertEquals(1, $query->count());

        $query = new ParseQuery('TestObject');
        $query->lessThanOrEqualToRelativeTime('date', 'in 1 hour');
        $this->assertEquals(2, $query->count());

        $query = new ParseQuery('TestObject');
        $query->lessThanOrEqualToRelativeTime('date', 'in 1 day');
        $this->assertEquals(3, $query->count());

        $query = new ParseQuery('TestObject');
        $query->lessThanOrEqualToRelativeTime('date', 'in 5 days');
        $this->assertEquals(4, $query->count());
    }

    /**
     * @group relative-time-queries
     */
    public function testRelativeTimeUnits()
    {
        $this->provideDateTestObjects();

        // with full units
        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', 'in 3 days 2 hours 15 minutes 30 seconds');
        $this->assertEquals(1, $query->count());

        // shorthand units
        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', 'in 3 d 2 hrs 15 mins 30 secs');
        $this->assertEquals(1, $query->count());

        // singular units
        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', 'in 3 days 1 hour 1 minute 1 second');
        $this->assertEquals(1, $query->count());

        // singular shorthand units
        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', 'in 3 d 1 hr 1 min 1 sec');
        $this->assertEquals(1, $query->count());
    }

    /**
     * @group relative-time-queries
     */
    public function testLongRelativeTime()
    {
        $this->provideExtendedDateTestObjects();

        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', 'in 1 year 2 weeks');
        $this->assertEquals(1, $query->count());

        $query = new ParseQuery('TestObject');
        $query->lessThanRelativeTime('date', '1 yr 2 wks ago');
        $this->assertEquals(1, $query->count());

        $query = new ParseQuery('TestObject');
        $query->lessThanRelativeTime('date', '1 year 3 weeks ago');
        $this->assertEquals(0, $query->count());

        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', 'in 1 year 3 weeks');
        $this->assertEquals(0, $query->count());

        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', '1 year 3 weeks ago');
        $this->assertEquals(4, $query->count());
    }

    /**
     * @group relative-time-queries
     */
    public function testNowRelativeTime()
    {
        $this->provideDateTestObjects();

        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', 'now');
        $this->assertEquals(2, $query->count());

        $query = new ParseQuery('TestObject');
        $query->lessThanRelativeTime('date', 'now');
        $this->assertEquals(2, $query->count());
    }

    /**
     * @group relative-time-queries
     */
    public function testBetweenRelativeTimes()
    {
        $this->provideDateTestObjects();

        // test within bounds
        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', '1 day ago');
        $query->lessThanRelativeTime('date', 'in 1 day');
        $this->assertEquals(2, $query->count());

        // try getting 2 weeks ago up until NOW
        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', '14 days ago');
        $query->lessThanOrEqualToRelativeTime('date', 'in 0 day');
        $this->assertEquals(2, $query->count());
    }

    /**
     * @group relative-time-queries
     */
    public function testBadRelativeTimeString()
    {
        $this->expectException(
            'Parse\ParseException',
            'bad $relativeTime ($lt) value. Time should either start with \'in\' or end with \'ago\''
        );
        $query = new ParseQuery('TestObject');
        $query->lessThanRelativeTime('date', 'asdf');
        $query->count();
    }

    /**
     * @group relative-time-queries
     */
    public function testRelativeTimeStringDanglingNumber()
    {
        $this->expectException(
            'Parse\ParseException',
            'bad $relativeTime ($lt) value. Invalid time string. Dangling unit or number.'
        );
        $query = new ParseQuery('TestObject');
        $query->lessThanRelativeTime('date', 'in 4 days 13');
        $query->count();
    }

    /**
     * @group relative-time-queries
     */
    public function testRelativeTimeStringDanglingUnit()
    {
        $this->expectException(
            'Parse\ParseException',
            'bad $relativeTime ($gt) value. Invalid time string. Dangling unit or number.'
        );
        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', 'in 4 days minutes');
        $query->count();
    }

    /**
     * @group relative-time-queries
     */
    public function testRelativeTimeCannotUseBothInAndAgo()
    {
        $this->expectException(
            'Parse\ParseException',
            'bad $relativeTime ($lt) value. Time cannot have both \'in\' and \'ago\''
        );
        $query = new ParseQuery('TestObject');
        $query->lessThanRelativeTime('date', 'in 4 days ago');
        $query->count();
    }

    /**
     * @group relative-time-queries
     */
    public function testRelativeTimeNotOnDateField()
    {
        $this->expectException(
            'Parse\ParseException',
            '$relativeTime can only be used with Date field'
        );

        $obj = new ParseObject('TestObject');
        $obj->set('not_a_date', 'string');
        $obj->save();

        $query = new ParseQuery('TestObject');
        $query->lessThanRelativeTime('not_a_date', 'in 4 days');
        $query->count();
    }

    /**
     * @group relative-time-queries
     */
    public function testRelativeTimeOnNonExistantField()
    {
        $this->provideDateTestObjects();

        // should NOT throw an exception
        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('not_an_actual_field', '4 days ago');
        // nothing should match
        $this->assertEquals(0, $query->count());
    }

    /**
     * @group relative-time-queries
     */
    public function testRelativeTimeEqualTo()
    {
        $this->expectException(
            'Parse\ParseException',
            'bad constraint: $relativeTime'
        );


        $query = new ParseQuery('TestObject');
        $query->equalTo('date', [
            '$relativeTime' => '4 days ago'
        ]);
        $query->count();
    }

    /**
     * @group relative-time-queries
     */
    public function testRelativeTimeNotEqualTo()
    {
        $this->expectException(
            'Parse\ParseException',
            '$relativeTime can only be used with the $lt, $lte, $gt, and $gte operators'
        );

        $query = new ParseQuery('TestObject');
        $query->notEqualTo('date', [
            '$relativeTime' => '4 days ago'
        ]);
        $query->count();
    }

    /**
     * @group relative-time-queries
     */
    public function testRelativeTimeMissingAgoAndIn()
    {
        $this->expectException(
            'Parse\ParseException',
            'bad $relativeTime ($lt) value. Time should either start with \'in\' or end with \'ago\''
        );
        $query = new ParseQuery('TestObject');
        $query->lessThanRelativeTime('date', '32 hrs');
        $query->count();
    }

    /**
     * @group relative-time-queries
     */
    public function testEmptyRelativeTime()
    {
        $this->expectException(
            'Parse\ParseException',
            'bad atom: {"$relativeTime":""}'
        );
        $query = new ParseQuery('TestObject');
        $query->lessThanOrEqualToRelativeTime('date', '');
        $query->count();
    }

    /**
     * @group relative-time-queries
     */
    public function testFractionalRelativeTime()
    {
        $this->expectException(
            'Parse\ParseException',
            'bad $relativeTime ($gte) value. \'2.5\' is not an integer'
        );
        $query = new ParseQuery('TestObject');
        $query->greaterThanOrEqualToRelativeTime('date', 'in 2.5 days');
        $query->count();
    }

    /**
     * @group relative-time-queries
     */
    public function testBadRelativeTimeUnit()
    {
        $this->expectException(
            'Parse\ParseException',
            'bad $relativeTime ($gt) value. Invalid interval: \'zorks\''
        );
        $query = new ParseQuery('TestObject');
        $query->greaterThanRelativeTime('date', 'in 8 zorks');
        $query->count();
    }
}

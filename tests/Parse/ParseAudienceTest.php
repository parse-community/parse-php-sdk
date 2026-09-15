<?php

namespace Parse\Test;

use Parse\ParseAudience;
use Parse\ParseInstallation;
use Parse\ParseObject;
use Parse\ParsePush;
use Parse\ParseQuery;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class ParseAudienceTest extends TestCase
{
    public function setup() : void
    {
        Helper::clearClass('_Audience');
        Helper::clearClass('_Installation');
    }

    public function createInstallations()
    {
        $androidInstallation = new ParseInstallation();
        $androidInstallation->set('installationId', 'id1');
        $androidInstallation->set('deviceToken', '12345');
        $androidInstallation->set('deviceType', 'android');
        $androidInstallation->save(true);

        $iOSInstallation = new ParseInstallation();
        $iOSInstallation->set('installationId', 'id2');
        $iOSInstallation->set('deviceToken', '54321');
        $iOSInstallation->set('deviceType', 'ios');
        $iOSInstallation->save();

        ParseObject::saveAll([
            $androidInstallation,
            $iOSInstallation
        ]);
    }

    #[Group('audience-tests')]
    public function testPushAudiences()
    {
        $this->createInstallations();

        $androidQuery = ParseInstallation::query()
            ->equalTo('deviceType', 'android');

        $audience = ParseAudience::createAudience('MyAudience', $androidQuery);
        $audience->save();

        // count no master should be 0
        $query = new ParseQuery('_Audience');
        $this->assertEquals(0, $query->count(), 'No master was not 0');

        $query = new ParseQuery('_Audience');
        $audience = $query->first(true);
        $this->assertNotNull($audience);

        $this->assertEquals('MyAudience', $audience->getName());
        $this->assertEquals($androidQuery, $audience->getQuery());
        $this->assertNull($audience->getLastUsed());
        $this->assertEquals(0, $audience->getTimesUsed());
    }

    #[Group('audience-tests')]
    public function testSaveWithoutMaster()
    {
        $query = ParseAudience::query();
        $this->assertEquals(0, $query->count(true), 'Did not start at 0');

        $audience = ParseAudience::createAudience(
            'MyAudience',
            ParseInstallation::query()
                ->equalTo('deviceType', 'android')
        );
        $audience->save();

        $query = ParseAudience::query();
        $this->assertEquals(1, $query->count(true), 'Did not end at 1');
    }

    #[Group('audience-tests')]
    public function testPushWithAudience()
    {
        $this->createInstallations();

        $audience = ParseAudience::createAudience(
            'MyAudience',
            ParseInstallation::query()
                ->equalTo('deviceType', 'android')
        );
        $audience->save(true);

        ParsePush::send([
            'data'          => [
                'alert' => 'sample message'
            ],
            'where'         => $audience->getQuery(),
            'audience_id'   => $audience->getObjectId()
        ], true);

        // Parse Server updates the audience counters without waiting for that
        // write to resolve, so poll until they show up rather than racing it.
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $audience->fetch(true);
            if ($audience->getTimesUsed() === 1) {
                break;
            }
            usleep(100000);
        }

        $this->assertEquals(1, $audience->getTimesUsed());
        $this->assertNotNull($audience->getLastUsed());
    }
}

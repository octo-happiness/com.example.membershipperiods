<?php

use CRM_MembershipPeriods_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;


/**
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class CRM_MembershipPeriods_MembershipPeriodTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface
{
    protected $mockedMembershipId;

    public function setUpHeadless()
    {
        // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
        // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
        return \Civi\Test::headless()
            ->installMe(__DIR__)
            ->apply();
    }

    public function setUp()
    {
        parent::setUp();

        $mockedMembership = \CRM_Core_DAO::createTestObject('CRM_Member_DAO_Membership');
        $this->mockedMembershipId = $mockedMembership->id;

        \CRM_Core_DAO::createTestObject('CRM_MembershipPeriods_DAO_MembershipPeriod', [
            'membership_id' => $this->mockedMembershipId,
            'start_date'    => '2016-05-23',
            'end_date'      => '2017-05-23',
        ]);
        \CRM_Core_DAO::createTestObject('CRM_MembershipPeriods_DAO_MembershipPeriod', [
            'membership_id' => $this->mockedMembershipId,
            'start_date'    => '2017-05-23',
            'end_date'      => '2018-05-23',
        ]);


        $mockedMembership2 = \CRM_Core_DAO::createTestObject('CRM_Member_DAO_Membership');
        \CRM_Core_DAO::createTestObject('CRM_MembershipPeriods_DAO_MembershipPeriod', [
            'membership_id' => $mockedMembership2->id,
            'start_date'    => '2016-05-23',
            'end_date'      => '2017-05-23',
        ]);
        \CRM_Core_DAO::createTestObject('CRM_MembershipPeriods_DAO_MembershipPeriod', [
            'membership_id' => $mockedMembership2->id,
            'start_date'    => '2017-05-23',
            'end_date'      => '2018-05-23',
        ]);
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testDeleteAllPeriods()
    {
        CRM_MembershipPeriods_BAO_MembershipPeriod::deleteAllPeriods($this->mockedMembershipId);

        $this->assertEquals(2, (new CRM_MembershipPeriods_DAO_MembershipPeriod())->count());
    }

    public function testDeleteAllPeriodsEndingAfter()
    {
        CRM_MembershipPeriods_BAO_MembershipPeriod::deleteAllPeriodsEndingAfter($this->mockedMembershipId, '2017-05-23');

        $this->assertEquals(3, (new CRM_MembershipPeriods_DAO_MembershipPeriod())->count());
    }

    public function testDeleteAllPeriodsStartingBefore()
    {
        CRM_MembershipPeriods_BAO_MembershipPeriod::deleteAllPeriodsEndingAfter($this->mockedMembershipId, '2017-05-23');

        $this->assertEquals(3, (new CRM_MembershipPeriods_DAO_MembershipPeriod())->count());
    }

    public function testFindLastMembershipPeriod()
    {
        $lastPeriod = CRM_MembershipPeriods_BAO_MembershipPeriod::findLastMembershipPeriod($this->mockedMembershipId);
        $this->assertEquals('2017-05-23', $lastPeriod->start_date);
        $this->assertEquals('2018-05-23', $lastPeriod->end_date);
    }

    public function testFindFirstMembershipPeriod()
    {
        $firstPeriod = CRM_MembershipPeriods_BAO_MembershipPeriod::findFirstMembershipPeriod($this->mockedMembershipId);
        $this->assertEquals('2016-05-23', $firstPeriod->start_date);
        $this->assertEquals('2017-05-23', $firstPeriod->end_date);
    }

    public function testFetchAllMembershipPeriods()
    {
        $periods = CRM_MembershipPeriods_BAO_MembershipPeriod::fetchAllMembershipPeriods($this->mockedMembershipId);
        $this->assertCount(2, $periods);
        $this->assertEquals('2016-05-23', $periods[0]['start_date']);
        $this->assertEquals('2017-05-23', $periods[0]['end_date']);
        $this->assertEquals('2017-05-23', $periods[1]['start_date']);
        $this->assertEquals('2018-05-23', $periods[1]['end_date']);
    }

    public function testAddMembershipPeriodIfMembershipWasExtended_ShouldDoNothingWhenDateDidntChange()
    {
        CRM_MembershipPeriods_BAO_MembershipPeriod::addMembershipPeriodIfMembershipWasExtended(
            $this->mockedMembershipId,
            '2016-05-23',
            '2018-05-23'
        );

        $this->assertEquals(4, (new CRM_MembershipPeriods_DAO_MembershipPeriod())->count());
        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->membership_id = $this->mockedMembershipId;
        $dao->orderBy('start_date ASC');
        $this->assertEquals(2, $dao->find());
        $this->assertTrue($dao->fetch());
        $this->assertEquals('2016-05-23', $dao->start_date);
        $this->assertEquals('2017-05-23', $dao->end_date);
        $this->assertTrue($dao->fetch());
        $this->assertEquals('2017-05-23', $dao->start_date);
        $this->assertEquals('2018-05-23', $dao->end_date);
        $this->assertFalse($dao->fetch());
    }

    public function testAddMembershipPeriodIfMembershipWasExtended_ShouldAddTwoPeriodsIfBothDatesWereExtended()
    {
        CRM_MembershipPeriods_BAO_MembershipPeriod::addMembershipPeriodIfMembershipWasExtended(
            $this->mockedMembershipId,
            '2015-05-23',
            '2019-05-23'
        );

        $this->assertEquals(6, (new CRM_MembershipPeriods_DAO_MembershipPeriod())->count());
        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->membership_id = $this->mockedMembershipId;
        $dao->orderBy('start_date ASC');
        $this->assertEquals(4, $dao->find());
        $this->assertTrue($dao->fetch());
        $this->assertEquals('2015-05-23', $dao->start_date);
        $this->assertEquals('2016-05-23', $dao->end_date);
        $this->assertTrue($dao->fetch());
        $this->assertEquals('2016-05-23', $dao->start_date);
        $this->assertEquals('2017-05-23', $dao->end_date);
        $this->assertTrue($dao->fetch());
        $this->assertEquals('2017-05-23', $dao->start_date);
        $this->assertEquals('2018-05-23', $dao->end_date);
        $this->assertTrue($dao->fetch());
        $this->assertEquals('2018-05-23', $dao->start_date);
        $this->assertEquals('2019-05-23', $dao->end_date);
        $this->assertFalse($dao->fetch());
    }

    public function testAddMembershipPeriodIfMembershipWasExtended_ShouldAddOnePeriodIfStartDateChanged()
    {
        CRM_MembershipPeriods_BAO_MembershipPeriod::addMembershipPeriodIfMembershipWasExtended(
            $this->mockedMembershipId,
            '2015-05-23',
            '2018-05-23'
        );

        $this->assertEquals(5, (new CRM_MembershipPeriods_DAO_MembershipPeriod())->count());
        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->membership_id = $this->mockedMembershipId;
        $dao->orderBy('start_date ASC');
        $this->assertEquals(3, $dao->find());
        $this->assertTrue($dao->fetch());
        $this->assertEquals('2015-05-23', $dao->start_date);
        $this->assertEquals('2016-05-23', $dao->end_date);
        $this->assertTrue($dao->fetch());
        $this->assertEquals('2016-05-23', $dao->start_date);
        $this->assertEquals('2017-05-23', $dao->end_date);
        $this->assertTrue($dao->fetch());
        $this->assertEquals('2017-05-23', $dao->start_date);
        $this->assertEquals('2018-05-23', $dao->end_date);
        $this->assertFalse($dao->fetch());
    }

    public function testAddMembershipPeriodIfMembershipWasExtended_ShouldAddOnePeriodIfEndDateChanged()
    {
        CRM_MembershipPeriods_BAO_MembershipPeriod::addMembershipPeriodIfMembershipWasExtended(
            $this->mockedMembershipId,
            '2016-05-23',
            '2019-05-23'
        );

        $this->assertEquals(5, (new CRM_MembershipPeriods_DAO_MembershipPeriod())->count());
        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->membership_id = $this->mockedMembershipId;
        $dao->orderBy('start_date ASC');
        $this->assertEquals(3, $dao->find());
        $this->assertTrue($dao->fetch());
        $this->assertEquals('2016-05-23', $dao->start_date);
        $this->assertEquals('2017-05-23', $dao->end_date);
        $this->assertTrue($dao->fetch());
        $this->assertEquals('2017-05-23', $dao->start_date);
        $this->assertEquals('2018-05-23', $dao->end_date);
        $this->assertTrue($dao->fetch());
        $this->assertEquals('2018-05-23', $dao->start_date);
        $this->assertEquals('2019-05-23', $dao->end_date);
        $this->assertFalse($dao->fetch());
    }
}
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
class CRM_MembershipPeriods_Hook_PostHandlerTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface
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

        $mockedMembership = \CRM_Core_DAO::createTestObject('CRM_Member_DAO_Membership', [
            'start_date' => '2016-05-23',
            'end_date'   => '2018-05-23'
        ]);
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


        $mockedMembership2 = \CRM_Core_DAO::createTestObject('CRM_Member_DAO_Membership', [
            'start_date' => '2016-05-23',
            'end_date'   => '2018-05-23'
        ]);
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

    public function testOnMembershipPaymentChange()
    {
        $contribution = \CRM_Core_DAO::createTestObject('CRM_Contribute_DAO_Contribution');
        $payment = new CRM_Member_DAO_MembershipPayment();
        $payment->membership_id = $this->mockedMembershipId;
        $payment->contribution_id = $contribution->id;
        CRM_MembershipPeriods_Hook_PostHandler::onMembershipPaymentChange('create', $payment);

        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->membership_id = $this->mockedMembershipId;
        $dao->orderBy('start_date DESC');
        $this->assertEquals(2, $dao->find());
        $this->assertTrue($dao->fetch());
        $this->assertEquals($payment->contribution_id, $dao->contribution_id);
    }

    public function testOnMembershipChange_ShouldRemoveAllPeriodsOnRemove()
    {
        $membership = CRM_Member_DAO_Membership::findById($this->mockedMembershipId);
        CRM_MembershipPeriods_Hook_PostHandler::onMembershipChange('remove', $membership);

        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->membership_id = $this->mockedMembershipId;
        $this->assertEquals(0, $dao->find());
    }

    public function testOnMembershipChange_ShouldCreatePeriodOnCreate()
    {
        $newMembership = \CRM_Core_DAO::createTestObject('CRM_Member_DAO_Membership', [
            'start_date' => '2017-01-01',
            'end_date'   => '2018-01-01'
        ]);
        CRM_MembershipPeriods_Hook_PostHandler::onMembershipChange('create', $newMembership);

        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->membership_id = $newMembership->id;
        $this->assertEquals(1, $dao->find());
        $this->assertTrue($dao->fetch());
        $this->assertEquals($newMembership->id, $dao->membership_id);
        $this->assertEquals('2017-01-01', $dao->start_date);
        $this->assertEquals('2018-01-01', $dao->end_date);
    }

    public function testOnMembershipChange_ShouldAdjustPeriodsOnEdit()
    {
        $membership = CRM_Member_DAO_Membership::findById($this->mockedMembershipId);
        $membership->join_date = '2016-06-23';
        $membership->start_date = '2016-06-23';
        $membership->end_date = '2018-05-23';
        CRM_MembershipPeriods_Hook_PostHandler::onMembershipChange('edit', $membership);

        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->membership_id = $this->mockedMembershipId;
        $dao->orderBy('start_date ASC');
        $this->assertEquals(2, $dao->find());
        $this->assertTrue($dao->fetch());
        $this->assertEquals($this->mockedMembershipId, $dao->membership_id);
        $this->assertEquals('2016-06-23', $dao->start_date);
        $this->assertEquals('2017-05-23', $dao->end_date);
        $this->assertTrue($dao->fetch());
        $this->assertEquals($this->mockedMembershipId, $dao->membership_id);
        $this->assertEquals('2017-05-23', $dao->start_date);
        $this->assertEquals('2018-05-23', $dao->end_date);
    }

    public function testOnMembershipChange_ShouldAllowLifeTimeSubscription()
    {
        $membership = CRM_Member_DAO_Membership::findById($this->mockedMembershipId);
        $membership->end_date = null;
        CRM_MembershipPeriods_Hook_PostHandler::onMembershipChange('edit', $membership);

        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->membership_id = $this->mockedMembershipId;
        $dao->orderBy('start_date ASC');

        $this->assertEquals(3, $dao->find());
        $this->assertTrue($dao->fetch());
        $this->assertEquals($this->mockedMembershipId, $dao->membership_id);
        $this->assertEquals('2016-05-23', $dao->start_date);
        $this->assertEquals('2017-05-23', $dao->end_date);
        $this->assertTrue($dao->fetch());
        $this->assertEquals($this->mockedMembershipId, $dao->membership_id);
        $this->assertEquals('2017-05-23', $dao->start_date);
        $this->assertEquals('2018-05-23', $dao->end_date);
        $this->assertTrue($dao->fetch());
        $this->assertEquals($this->mockedMembershipId, $dao->membership_id);
        $this->assertEquals('2018-05-23', $dao->start_date);
        $this->assertEquals(null, $dao->end_date);
    }

}

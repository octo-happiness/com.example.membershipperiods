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
class CRM_MembershipPeriods_Hook_BuildFormHandlerTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface
{
    protected $mockedMembershipId;
    protected $mockedContributionId;
    protected $mockedContactId;

    protected $mockedPeriod1Id;
    protected $mockedPeriod2Id;

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
        $mockedContribution = \CRM_Core_DAO::createTestObject('CRM_Contribute_DAO_Contribution', [
            'membership_id' => $mockedMembership->id
        ]);

        $this->mockedMembershipId = $mockedMembership->id;
        $this->mockedContributionId = $mockedContribution->id;
        $this->mockedContactId = $mockedContribution->contact_id;

        CRM_MembershipPeriods_BAO_MembershipPeriod::create([
            'membership_id'   => $this->mockedMembershipId,
            'contribution_id' => null,
            'start_date'      => '2016-05-23',
            'end_date'        => '2017-05-23',
        ]);
        CRM_MembershipPeriods_BAO_MembershipPeriod::create([
            'membership_id'   => $this->mockedMembershipId,
            'contribution_id' => $this->mockedContributionId,
            'start_date'      => '2017-05-23',
            'end_date'        => '2018-05-23',
        ]);
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testOnMembershipView()
    {
        $contactId = $this->mockedContactId;
        $contributionId = $this->mockedContributionId;

        $form = new CRM_Member_Form_MembershipView();
        $form->assign('membership_id', $this->mockedMembershipId);
        $form->assign('contact_id', $this->mockedContactId);
        $form->assign('viewCustomData', []);

        CRM_MembershipPeriods_Hook_BuildFormHandler::onMembershipView($form);

        $expectedViewCustomDataRow = [
            'title'  => 'Membership Periods',
            'fields' =>
                [
                    [
                        'field_title' => 'Period 1',
                        'field_value' => "May 23rd, 2016 - May 23rd, 2017",
                    ],
                    [
                        'field_title' => 'Period 2',
                        'field_value' => "May 23rd, 2017 - May 23rd, 2018 <a class='action-item crm-hover-button' href='/index.php?q=civicrm/contact/view/contribution&amp;reset=1&amp;id=$contributionId&amp;cid=$contactId&amp;action=view&amp;context=membership&amp;selectedChild=contribute'>View contribution</a>",
                    ],
                ],
        ];
        $this->assertEquals([[$expectedViewCustomDataRow]], $form->get_template_vars('viewCustomData'));
    }

}

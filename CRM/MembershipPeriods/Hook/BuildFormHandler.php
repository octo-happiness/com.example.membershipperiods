<?php

use CRM_MembershipPeriods_ExtensionUtil as E;

class CRM_MembershipPeriods_Hook_BuildFormHandler
{
    /**
     * Adds membership periods data to membership's custom data
     *
     * @param CRM_Member_Form_MembershipView $form
     */
    static public function onMembershipView($form)
    {
        $periods = CRM_MembershipPeriods_BAO_MembershipPeriod::fetchAllMembershipPeriods($form->get_template_vars('membership_id'));

        // if no data was found, there's nothing to do
        if (!$periods) {
            return;
        }

        // add membership periods to custom data
        $viewCustomData = $form->get_template_vars('viewCustomData');
        $viewCustomData[] = array(
            array(
                'title'  => E::ts('Membership Periods'),
                'fields' => self::createPeriodsCustomFormData($periods, $form->get_template_vars('contact_id')),
            ),
        );
        $form->assign('viewCustomData', $viewCustomData);
    }

    /**
     * Creates custom form data from periods
     *
     * @param array $periods
     * @param int $contactId
     * @return array
     */
    static private function createPeriodsCustomFormData($periods, $contactId)
    {
        $periodNumbers = range(1, count($periods));

        return array_map(function ($period, $periodNumber) use ($contactId) {
            $startDate = CRM_Utils_Date::customFormat($period['start_date']);
            $endDate = $period['end_date'] ? CRM_Utils_Date::customFormat($period['end_date']) : '';
            $contributionLink = '';

            if ($period['contribution_id']) {
                $href = CRM_Utils_System::crmURL([
                    'p' => 'civicrm/contact/view/contribution',
                    'q' => "reset=1&id=${period['contribution_id']}&cid=$contactId&action=view&context=membership&selectedChild=contribute"
                ]);
                $contributionLink = " <a class='action-item crm-hover-button' href='$href'>" . E::ts("View contribution") . '</a>';
            }


            return array(
                'field_title' => E::ts('Period %1', array(1 => $periodNumber)),
                'field_value' => "$startDate - $endDate$contributionLink"
            );
        }, $periods, $periodNumbers);
    }
}
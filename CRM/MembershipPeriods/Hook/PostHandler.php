<?php

class CRM_MembershipPeriods_Hook_PostHandler
{
    /**
     * @param string $op
     * @param CRM_Member_DAO_Membership $objectRef
     */
    static function onMembershipChange($op, &$objectRef)
    {
        if ($op == 'remove') {
            CRM_MembershipPeriods_BAO_MembershipPeriod::deleteAllPeriods($objectRef->id);
        } elseif ($op == 'edit') {
            CRM_MembershipPeriods_BAO_MembershipPeriod::deleteAllPeriodsStartingBefore($objectRef->id, $objectRef->join_date);
            CRM_MembershipPeriods_BAO_MembershipPeriod::deleteAllPeriodsEndingAfter($objectRef->id, $objectRef->end_date);
            CRM_MembershipPeriods_BAO_MembershipPeriod::addMembershipPeriodIfMembershipWasExtended($objectRef->id, $objectRef->start_date, $objectRef->end_date);
        } elseif ($op == 'create') {
            CRM_MembershipPeriods_BAO_MembershipPeriod::addMembershipPeriodIfMembershipWasExtended($objectRef->id, $objectRef->start_date, $objectRef->end_date);
        }
    }

    /**
     * @param string $op
     * @param CRM_Member_DAO_MembershipPayment $objectRef
     */
    static function onMembershipPaymentChange($op, &$objectRef)
    {
        if ($op != 'create' || !$objectRef->membership_id || !$objectRef->contribution_id) {
            return;
        }

        $period = CRM_MembershipPeriods_BAO_MembershipPeriod::findLastMembershipPeriod($objectRef->membership_id);
        if ($period) {
            $period->contribution_id = $objectRef->contribution_id;
            $period->save();
        }
    }
}
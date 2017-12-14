<?php

class CRM_MembershipPeriods_BAO_MembershipPeriod extends CRM_MembershipPeriods_DAO_MembershipPeriod
{
    /**
     * Create a new MembershipPeriod based on array-data
     *
     * @param array $params key-value pairs
     * @return CRM_MembershipPeriods_DAO_MembershipPeriod|NULL
     **/
    public static function create($params)
    {
        $entityName = 'MembershipPeriod';
        $hook = empty($params['id']) ? 'create' : 'edit';

        CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
        $instance = new CRM_Membershipperiods_DAO_MembershipPeriod();
        $instance->copyValues($params);
        $instance->save();
        CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

        return $instance;
    }

    /**
     * Removes all membership periods for a given membership.
     *
     * @param int $membershipId
     */
    static public function deleteAllPeriods($membershipId)
    {
        if (!$membershipId) {
            return;
        }

        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->membership_id = $membershipId;
        $dao->delete();
    }

    /**
     * Removes all membership periods ending after a given date.
     *
     * @param int $membershipId
     * @param string $date
     */
    static public function deleteAllPeriodsStartingBefore($membershipId, $date)
    {
        $escapedDate = CRM_Core_DAO::escapeString($date);

        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->whereAdd(sprintf('membership_id = %d', $membershipId));
        $dao->whereAdd("start_date < '$escapedDate'");
        $dao->delete(true);
    }

    /**
     * Removes all membership periods ending after a given date.
     *
     * @param int $membershipId
     * @param string $date
     */
    static public function deleteAllPeriodsEndingAfter($membershipId, $date)
    {
        if (!$date || CRM_Utils_System::isNull($date)) {
            // null means lifetime subscription
            return;
        }

        $escapedDate = CRM_Core_DAO::escapeString($date);

        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->whereAdd(sprintf('membership_id = %d', $membershipId));
        $dao->whereAdd("end_date > '$escapedDate' OR end_date IS NULL");
        $dao->delete(true);
    }

    /**
     * Saves membership period for given dates.
     *
     * If membership for given dates already exists does nothing. If it doesn't, creates new period.
     *
     * @param int $membershipId
     * @param string $startDate
     * @param string|null $endDate
     */
    static public function addMembershipPeriodIfMembershipWasExtended($membershipId, $startDate, $endDate)
    {
        $startDate = CRM_Utils_Date::processDate($startDate);
        $endDate = CRM_Utils_System::isNull($endDate) ? 'null' : CRM_Utils_Date::processDate($endDate);

        $lastPeriod = CRM_MembershipPeriods_BAO_MembershipPeriod::findLastMembershipPeriod($membershipId);

        // if there are no periods at all, this is the first one: save entire membership as a single period
        // we can safely return here
        if (!$lastPeriod) {
            CRM_MembershipPeriods_BAO_MembershipPeriod::createNewMembershipPeriod($membershipId, $startDate, $endDate);
            return;
        }

        $lastPeriodEndDate = CRM_Utils_Date::processDate($lastPeriod->end_date);

        // if last period ends before new membership's endDate, add new period (starting when the previous one ends)
        if ($lastPeriod && $endDate != 'null' && $lastPeriodEndDate < $endDate) {
            CRM_MembershipPeriods_BAO_MembershipPeriod::createNewMembershipPeriod(
                $membershipId, max($lastPeriod->end_date, $startDate), $endDate
            );
        } elseif ($lastPeriod && $endDate == 'null' && $lastPeriodEndDate != null) {
            // endDate = null means lifetime subscription, save it as a new period
            CRM_MembershipPeriods_BAO_MembershipPeriod::createNewMembershipPeriod(
                $membershipId, max($lastPeriod->end_date, $startDate), $endDate
            );
        }

        $firstPeriod = CRM_MembershipPeriods_BAO_MembershipPeriod::findFirstMembershipPeriod($membershipId);
        if (!$firstPeriod) {
            return;
        }
        $firstPeriodStartDate = CRM_Utils_Date::processDate($firstPeriod->start_date);
        // if first period starts later than the startDate, add new membership period (ending when the existing period starts)
        if ($firstPeriod && $firstPeriodStartDate > $startDate) {
            CRM_MembershipPeriods_BAO_MembershipPeriod::createNewMembershipPeriod(
                $membershipId, $startDate, $firstPeriod->start_date
            );
        }
    }

    /**
     * Create new membership period.
     *
     * @param int $membershipId
     * @param string $startDate
     * @param string $endDate
     */
    static private function createNewMembershipPeriod($membershipId, $startDate, $endDate)
    {
        self::create([
            'membership_id' => $membershipId,
            'start_date'    => $startDate,
            'end_date'      => $endDate
        ]);
    }

    /**
     * Returns last membership period (or null if no periods were found).
     *
     * @param int $membershipId
     * @return CRM_MembershipPeriods_DAO_MembershipPeriod|null
     */
    static public function findLastMembershipPeriod($membershipId)
    {
        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->membership_id = $membershipId;
        $dao->orderBy('start_date DESC');
        if (!$dao->find()) {
            return null;
        }

        $dao->fetch();
        return $dao;
    }

    /**
     * Returns first membership period (or null if no periods were found).
     *
     * @param int $membershipId
     * @return CRM_MembershipPeriods_DAO_MembershipPeriod|null
     */
    static public function findFirstMembershipPeriod($membershipId)
    {
        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->membership_id = $membershipId;
        $dao->orderBy('start_date ASC');
        if (!$dao->find()) {
            return null;
        }

        $dao->fetch();
        return $dao;
    }

    /**
     * Returns all membership periods for given membershipId
     *
     * @param int $membershipId
     *
     * @return array
     */
    static public function fetchAllMembershipPeriods($membershipId)
    {
        $dao = new CRM_MembershipPeriods_DAO_MembershipPeriod();
        $dao->membership_id = $membershipId;
        $dao->orderBy('start_date ASC');
        $dao->find();
        return $dao->fetchAll();
    }
}
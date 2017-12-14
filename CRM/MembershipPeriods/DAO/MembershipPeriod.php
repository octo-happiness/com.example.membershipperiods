<?php

require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';

use CRM_Membershipperiods_ExtensionUtil as E;

class CRM_MembershipPeriods_DAO_MembershipPeriod extends CRM_Core_DAO
{
    /**
     * static instance to hold the table name
     *
     * @var string
     */
    static $_tableName = 'civicrm_membershipperiod_membership_period';
    /**
     * static instance to hold the field values
     *
     * @var array
     */
    static $_fields = null;
    /**
     * static instance to hold the keys used in $_fields for each field.
     *
     * @var array
     */
    static $_fieldKeys = null;
    /**
     * static instance to hold the FK relationships
     *
     * @var string
     */
    static $_links = null;
    /**
     * static instance to hold the values that can
     * be imported
     *
     * @var array
     */
    static $_import = null;
    /**
     * static instance to hold the values that can
     * be exported
     *
     * @var array
     */
    static $_export = null;
    /**
     * static value to see if we should log any modifications to
     * this table in the civicrm_log table
     *
     * @var boolean
     */
    static $_log = true;

    /**
     *
     * @var int unsigned
     */
    public $id;
    /**
     * FK to Membership.
     *
     * @var int unsigned
     */
    public $membership_id;
    /**
     * Optional FK to Contribution.
     *
     * @var int unsigned
     */
    public $contribution_id;
    /**
     * Beginning of membership period.
     *
     * @var date
     */
    public $start_date;
    /**
     * Membership period expire date.
     *
     * @var date
     */
    public $end_date;

    /**
     * class constructor
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns foreign keys and entity references
     *
     * @return array
     *   [CRM_Core_Reference_Interface]
     */
    static function getReferenceColumns()
    {
        if (!self::$_links) {
            self::$_links = static::createReferenceColumns(__CLASS__);
            self::$_links[] = new CRM_Core_Reference_Basic(self::getTableName(), 'membership_id', 'civicrm_membership', 'id');
            self::$_links[] = new CRM_Core_Reference_Basic(self::getTableName(), 'contribution_id', 'civicrm_contribution', 'id');
        }
        return self::$_links;
    }

    /**
     * Returns all the column names of this table
     *
     * @return array
     */
    static function &fields()
    {
        if (!(self::$_fields)) {
            self::$_fields = array(
                'id'              => array(
                    'name'        => 'id',
                    'type'        => CRM_Utils_Type::T_INT,
                    'title'       => E::ts('Membership Period ID'),
                    'description' => 'Membership Period ID',
                    'required'    => true,
                ),
                'membership_id'   => array(
                    'name'        => 'membership_id',
                    'type'        => CRM_Utils_Type::T_INT,
                    'title'       => E::ts('Membership ID'),
                    'description' => 'Foreign key to the Membership for this record',
                    'required'    => true,
                    'FKClassName' => 'CRM_Member_DAO_Membership',
                ),
                'contribution_id' => array(
                    'name'        => 'contribution_id',
                    'type'        => CRM_Utils_Type::T_INT,
                    'title'       => E::ts('Contribution ID'),
                    'description' => 'Foreign key to the Contribution for this record',
                    'required'    => true,
                    'FKClassName' => 'CRM_Contribute_DAO_Contribution',
                ),
                'start_date'      => array(
                    'name'          => 'start_date',
                    'type'          => CRM_Utils_Type::T_DATE,
                    'title'         => E::ts('Membership Period Start Date'),
                    'description'   => 'Beginning of membership period.',
                    'import'        => true,
                    'where'         => 'civicrm_membershipperiod_membership_period.start_date',
                    'headerPattern' => '',
                    'dataPattern'   => '/\d{4}-?\d{2}-?\d{2}/',
                    'export'        => true,
                    'table_name'    => 'civicrm_membershipperiod_membership_period',
                    'entity'        => 'Membership',
                    'bao'           => 'CRM_MembershipPeriods_BAO_MembershipPeriod',
                    'localizable'   => 0,
                    'required'      => true,
                    'html'          => array(
                        'type'       => 'Select Date',
                        'formatType' => 'activityDate',
                    ),
                ),
                'end_date'        => array(
                    'name'          => 'end_date',
                    'type'          => CRM_Utils_Type::T_DATE,
                    'title'         => E::ts('Membership Period Expiration Date'),
                    'description'   => 'Membership period expire date.',
                    'import'        => true,
                    'where'         => 'civicrm_membershipperiod_membership_period.end_date',
                    'headerPattern' => '',
                    'dataPattern'   => '/\d{4}-?\d{2}-?\d{2}/',
                    'export'        => true,
                    'table_name'    => 'civicrm_membershipperiod_membership_period',
                    'entity'        => 'Membership',
                    'bao'           => 'CRM_MembershipPeriods_BAO_MembershipPeriod',
                    'localizable'   => 0,
                    'required'      => false,
                    'html'          => array(
                        'type'       => 'Select Date',
                        'formatType' => 'activityDate',
                    ),
                )
            );
        }
        return self::$_fields;
    }

    /**
     * Returns an array containing, for each field, the array key used for that
     * field in self::$_fields.
     *
     * @return array
     */
    static function &fieldKeys()
    {
        if (!(self::$_fieldKeys)) {
            self::$_fieldKeys = array(
                'id'              => 'id',
                'membership_id'   => 'membership_id',
                'contribution_id' => 'contribution_id',
                'start_date'      => 'start_date',
                'end_date'        => 'end_date'
            );
        }
        return self::$_fieldKeys;
    }

    /**
     * Returns the names of this table
     *
     * @return string
     */
    static function getTableName()
    {
        return self::$_tableName;
    }

    /**
     * Returns if this table needs to be logged
     *
     * @return boolean
     */
    function getLog()
    {
        return self::$_log;
    }

    /**
     * Returns the list of fields that can be imported
     *
     * @param bool $prefix
     *
     * @return array
     */
    static function &import($prefix = false)
    {
        if (!(self::$_import)) {
            self::$_import = array();
            $fields = self::fields();
            foreach ($fields as $name => $field) {
                if (CRM_Utils_Array::value('import', $field)) {
                    if ($prefix) {
                        self::$_import['membership_period'] = &$fields[$name];
                    } else {
                        self::$_import[$name] = &$fields[$name];
                    }
                }
            }
        }
        return self::$_import;
    }

    /**
     * Returns the list of fields that can be exported
     *
     * @param bool $prefix
     *
     * @return array
     */
    static function &export($prefix = false)
    {
        if (!(self::$_export)) {
            self::$_export = array();
            $fields = self::fields();
            foreach ($fields as $name => $field) {
                if (CRM_Utils_Array::value('export', $field)) {
                    if ($prefix) {
                        self::$_export['membership_period'] = &$fields[$name];
                    } else {
                        self::$_export[$name] = &$fields[$name];
                    }
                }
            }
        }
        return self::$_export;
    }
}
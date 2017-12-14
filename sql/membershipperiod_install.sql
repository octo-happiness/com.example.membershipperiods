CREATE TABLE IF NOT EXISTS civicrm_membershipperiod_membership_period (
  id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Membership Period Id',
  membership_id int(10) unsigned NOT NULL COMMENT 'FK to civicrm_membership.',
  contribution_id int(10) unsigned DEFAULT NULL COMMENT 'FK to civicrm_contribution.',
  start_date date NOT NULL COMMENT 'Membership Period Start Date.',
  end_date date COMMENT 'Membership Period End Date.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_membership_period` (`membership_id`, `start_date`, `end_date`),
  CONSTRAINT FK_civicrm_membershipperiod_membership_period_membership FOREIGN KEY (`membership_id`) REFERENCES `civicrm_membership`(`id`) ON DELETE CASCADE,
  CONSTRAINT FK_civicrm_membershipperiod_membership_period_contribution FOREIGN KEY (`contribution_id`) REFERENCES `civicrm_contribution`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT INTO civicrm_membershipperiod_membership_period (membership_id, start_date, end_date)
  SELECT id, start_date, end_date
  FROM civicrm_membership;
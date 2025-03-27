-- Table pour stocker les informations sur les rappels d'expiration de crédit envoyés
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `credit_reminder`;
CREATE TABLE `credit_reminder` (
  `id` INTEGER NOT NULL AUTO_INCREMENT,
  `customer_id` INTEGER NOT NULL,
  `emails_sent` INTEGER DEFAULT 0 NOT NULL,
  `last_sent_date` DATETIME,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `fk_credit_reminder_customer_id` (`customer_id`),
  CONSTRAINT `fk_credit_reminder_customer_id`
    FOREIGN KEY (`customer_id`)
    REFERENCES `customer` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;


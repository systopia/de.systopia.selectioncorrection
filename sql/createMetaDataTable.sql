DROP TABLE IF EXISTS civicrm_group_contact_metadata;

CREATE TABLE civicrm_group_contact_metadata
(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id INT UNSIGNED NOT NULL,
    contact_id INT UNSIGNED NOT NULL,
    relationship_id INT UNSIGNED NOT NULL
)
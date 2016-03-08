-- ---
-- Fill tables with default values
-- ---

INSERT INTO AccountType (acc_type_id, name) VALUES (0, 'default');
INSERT INTO AccountType (acc_type_id, name) VALUES (1, 'google+');
INSERT INTO AccountType (acc_type_id, name) VALUES (2, 'facebook');

INSERT INTO ChallengeType (challenge_type_id, acronym, full_name) VALUES (0, 'Race', 'Default Challenge');
INSERT INTO ChallengeType (challenge_type_id, acronym, full_name) VALUES (1, 'CTF', 'Capture the Flag');

INSERT INTO GeoCat(db_version, db_revision) VALUES ("%DB_VERSION%", %DB_REVISION%);

-- ---
-- Fill tables with default values
-- ---

INSERT INTO AccountType (acc_type_id, name) VALUES (0, 'default');
INSERT INTO AccountType (acc_type_id, name) VALUES (1, 'guest');
INSERT INTO AccountType (acc_type_id, name) VALUES (2, 'google+');
INSERT INTO AccountType (acc_type_id, name) VALUES (3, 'facebook');

INSERT INTO ChallengeType (challenge_type_id, acronym, full_name) VALUES (0, 'Race', 'Default Challenge');
INSERT INTO ChallengeType (challenge_type_id, acronym, full_name) VALUES (1, 'CTF', 'Capture the Flag');

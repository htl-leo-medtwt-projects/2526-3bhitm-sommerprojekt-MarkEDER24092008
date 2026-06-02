-- =============================================================
--  IndiGO – German A1 Seed Data
--  Chain: language → unit → lesson → exercise
--  Covers all 3 exercise types:
--    TRANSLATE       = text input (user types the answer)
--    MULTIPLE_CHOICE = tap one of N options  (options in extra_data)
--    SPEAK           = voice / conversation   (not graded)
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;   -- lets us insert in any order during seeding

-- -------------------------------------------------------------
-- 1. LANGUAGE
-- -------------------------------------------------------------
INSERT INTO `language` (`id`, `name`, `code`, `flag_url`, `is_active`) VALUES
(1, 'German', 'de', './media/images/languages/de.png', 1);


-- -------------------------------------------------------------
-- 2. UNIT  (one unit for A1 – "The Basics")
-- -------------------------------------------------------------
INSERT INTO `unit` (`id`, `language_id`, `title`, `description`, `order_index`, `is_published`) VALUES
(1, 1, 'The Basics', 'Greetings, numbers, and everyday words', 0, 1);


-- -------------------------------------------------------------
-- 3. LESSONS  (3 lessons inside the unit)
--    order_index drives the unlock tree: 0 → 1 → 2
-- -------------------------------------------------------------
INSERT INTO `lesson` (`id`, `unit_id`, `title`, `difficulty_level`, `order_index`, `xp_reward`, `is_published`) VALUES
(1, 1, 'Greetings',        'A1', 0, 10, 1),
(2, 1, 'Numbers 1–10',     'A1', 1, 10, 1),
(3, 1, 'First Conversation','A1', 2, 15, 1);


-- =============================================================
-- 4. EXERCISES
-- =============================================================

-- ── LESSON 1 · Greetings ─────────────────────────────────────
--   Mix: TRANSLATE + MULTIPLE_CHOICE

INSERT INTO `exercise`
  (`id`, `lesson_id`, `type`, `question_text`, `correct_answer`, `hint`, `explanation`, `audio_url`, `image_url`, `order_index`, `extra_data`)
VALUES

-- 1. Text input: translate to German
(1, 1, 'TRANSLATE',
 'Translate to German: "Hello"',
 'Hallo',
 'Think of a very common greeting.',
 '"Hallo" is the standard informal greeting in German, used at any time of day.',
 NULL, NULL, 0, NULL),

-- 2. Multiple choice: what does "Guten Morgen" mean?
(2, 1, 'MULTIPLE_CHOICE',
 'What does "Guten Morgen" mean?',
 'Good morning',
 NULL,
 '"Guten Morgen" is used until around noon. "Guten Tag" follows for the rest of the day.',
 NULL, NULL, 1,
 '{"options": ["Good morning", "Good night", "Good evening", "Goodbye"]}'),

-- 3. Text input: translate "Goodbye"
(3, 1, 'TRANSLATE',
 'Translate to German: "Goodbye"',
 'Auf Wiedersehen',
 'Formal farewell – literally "until we see each other again".',
 'The informal version is "Tschüss", much more common in everyday speech.',
 NULL, NULL, 2, NULL),

-- 4. Multiple choice: how do you say "Thank you"?
(4, 1, 'MULTIPLE_CHOICE',
 'How do you say "Thank you" in German?',
 'Danke',
 NULL,
 '"Danke" = thank you. "Danke schön" or "Vielen Dank" are more emphatic forms.',
 NULL, NULL, 3,
 '{"options": ["Danke", "Bitte", "Entschuldigung", "Hallo"]}'),

-- 5. Text input: translate "Please"
(5, 1, 'TRANSLATE',
 'Translate to German: "Please"',
 'Bitte',
 'Also used to mean "You\'re welcome".',
 '"Bitte" doubles as "please" and "you\'re welcome", depending on context.',
 NULL, NULL, 4, NULL),

-- 6. Multiple choice: "Gute Nacht" means…
(6, 1, 'MULTIPLE_CHOICE',
 'What does "Gute Nacht" mean?',
 'Good night',
 NULL,
 '"Gute Nacht" is said when someone is going to sleep, not just in the evening.',
 NULL, NULL, 5,
 '{"options": ["Good night", "Good morning", "Good day", "See you later"]}'),


-- ── LESSON 2 · Numbers 1–10 ──────────────────────────────────
--   Mix: TRANSLATE + MULTIPLE_CHOICE

-- 7. Text input
(7, 2, 'TRANSLATE',
 'Translate to German: "One"',
 'Eins',
 'Sounds a bit like the English word "once".',
 '"Eins" is the standalone number. As a prefix in compounds it becomes "ein-" (e.g. einundzwanzig).',
 NULL, NULL, 0, NULL),

-- 8. Multiple choice
(8, 2, 'MULTIPLE_CHOICE',
 'Which number is "Drei"?',
 '3',
 NULL,
 '"Drei" = 3. It shares its root with the English word "three".',
 NULL, NULL, 1,
 '{"options": ["3", "2", "7", "5"]}'),

-- 9. Text input
(9, 2, 'TRANSLATE',
 'Translate to German: "Five"',
 'Fünf',
 'The ü is like the French "u" – round your lips and say "ee".',
 '"Fünf" is one of the first German words that introduces the umlaut ü.',
 NULL, NULL, 2, NULL),

-- 10. Multiple choice
(10, 2, 'MULTIPLE_CHOICE',
 'What number is "Zehn"?',
 '10',
 NULL,
 '"Zehn" = 10. The root "zehn" appears in "zwanzig" (20), "dreißig" (30), etc.',
 NULL, NULL, 3,
 '{"options": ["10", "6", "8", "4"]}'),

-- 11. Text input
(11, 2, 'TRANSLATE',
 'Translate to German: "Seven"',
 'Sieben',
 'Sounds similar to the English word.',
 '"Sieben" → "seven". Notice how many Germanic numbers still resemble English ones.',
 NULL, NULL, 4, NULL),

-- 12. Multiple choice
(12, 2, 'MULTIPLE_CHOICE',
 'Which word means "Nine" in German?',
 'Neun',
 NULL,
 '"Neun" = 9. Compare to English "nine" – same Germanic root.',
 NULL, NULL, 5,
 '{"options": ["Neun", "Acht", "Sechs", "Zwei"]}'),


-- ── LESSON 3 · First Conversation (SPEAK – not graded) ───────

-- 13. Voice exercise 1
(13, 3, 'SPEAK',
 'You run into a classmate in the morning. Greet them, ask how they are, and say goodbye.',
 '',   -- no graded answer
 'Try using: Hallo, Guten Morgen, Wie geht es dir?, Tschüss',
 'This is a free speaking exercise. Focus on natural flow, not perfection.',
 NULL, NULL, 0, NULL),

-- 14. Voice exercise 2
(14, 3, 'SPEAK',
 'You are at a shop. Greet the shopkeeper, say "please" when asking for something, and thank them.',
 '',
 'Try: Guten Tag, Bitte, Danke, Auf Wiedersehen',
 'German shopkeepers usually expect a proper greeting when you enter.',
 NULL, NULL, 1, NULL),

-- 15. Voice exercise 3
(15, 3, 'SPEAK',
 'Introduce yourself: say your name, where you are from, and how old you are using numbers you learned.',
 '',
 'Try: Ich heiße …, Ich komme aus …, Ich bin … Jahre alt.',
 '"Ich heiße" = my name is. "Ich komme aus" = I come from. "Jahre alt" = years old.',
 NULL, NULL, 2, NULL);


SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
--  Quick sanity-check queries (run these after importing)
-- =============================================================
-- SELECT l.title, COUNT(e.id) AS exercise_count
-- FROM lesson l
-- JOIN exercise e ON e.lesson_id = l.id
-- GROUP BY l.id;
--
-- SELECT * FROM exercise WHERE type = 'MULTIPLE_CHOICE';
-- SELECT id, type, question_text, extra_data FROM exercise;

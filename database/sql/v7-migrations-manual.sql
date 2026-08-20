-- ============================================================================
--  بديل يدوي لمايجريشن v7 (لو `php artisan migrate --force` مش متاح)
-- ============================================================================
--  الأفضل دايمًا تشغّل:  php artisan migrate --force
--  الملف ده للحالة اللي مينفعش فيها تشغيل artisan على السيرفر.
--
--  التعريفات دي مأخوذة من الأعمدة بعد ما اتطبقت فعلًا، مش من الذاكرة.
-- ============================================================================

START TRANSACTION;

-- 1) §2 — حقول طلب تغيير رقم الجوال المعلّق
ALTER TABLE `users`
  ADD COLUMN `pending_phone`            VARCHAR(255)     NULL DEFAULT NULL AFTER `phone_not_code`,
  ADD COLUMN `pending_country_code`     VARCHAR(8)       NULL DEFAULT NULL AFTER `pending_phone`,
  ADD COLUMN `pending_phone_code`       VARCHAR(8)       NULL DEFAULT NULL AFTER `pending_country_code`,
  ADD COLUMN `pending_phone_expires_at` TIMESTAMP        NULL DEFAULT NULL AFTER `pending_phone_code`,
  ADD COLUMN `pending_phone_attempts`   TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `pending_phone_expires_at`;

-- 2) §5 — التقييم والشارة على المادة  ← ** ده اللي بيحل الإيرور بتاع 'rating' **
ALTER TABLE `subjects`
  ADD COLUMN `rating` DECIMAL(3,2) NULL DEFAULT NULL AFTER `price`,
  ADD COLUMN `tag`    VARCHAR(32)  NULL DEFAULT NULL AFTER `rating`;

-- 3) §9 — النص العربي الطويل كان بيتقطّع عند حد TEXT (65,535 بايت)
ALTER TABLE `settings` MODIFY `value` LONGTEXT NULL;

-- 4) §11 — الدينار الكويتي 3 خانات عشرية (كانت خانتين)
ALTER TABLE `orders`      MODIFY `total`               DECIMAL(12,3) NOT NULL DEFAULT 0.000;
ALTER TABLE `orders`      MODIFY `discount`            DECIMAL(12,3)     NULL DEFAULT 0.000;
ALTER TABLE `orders`      MODIFY `discount_amount`     DECIMAL(12,3)     NULL DEFAULT 0.000;
ALTER TABLE `order_items` MODIFY `price`               DECIMAL(12,3) NOT NULL DEFAULT 0.000;
ALTER TABLE `subjects`    MODIFY `price`               DECIMAL(12,3) NOT NULL DEFAULT 0.000;
ALTER TABLE `grades`      MODIFY `all_materials_price` DECIMAL(12,3)     NULL DEFAULT 0.000;

-- 5) تسجيل المايجريشن عشان artisan ميحاولش يعيدها تاني
SET @batch := (SELECT IFNULL(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('2026_08_19_100000_add_pending_phone_change_to_users_table', @batch),
  ('2026_08_19_100100_add_rating_and_tag_to_subjects_table',    @batch),
  ('2026_08_19_100200_change_settings_value_to_long_text',      @batch),
  ('2026_08_19_100300_widen_money_columns_to_three_decimals',   @batch);

COMMIT;

-- 6) تأكيد
SELECT
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subjects'
       AND COLUMN_NAME IN ('rating','tag'))                       AS subjects_جديد_المفروض_2,
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
       AND COLUMN_NAME LIKE 'pending\_%')                         AS users_جديد_المفروض_5,
  (SELECT DATA_TYPE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings'
       AND COLUMN_NAME = 'value')                                 AS settings_value_المفروض_longtext,
  (SELECT COUNT(*) FROM `migrations`
     WHERE `migration` LIKE '2026\_08\_19\_%')                    AS مايجريشن_مسجّل_المفروض_4;

-- ⚠️  ALTER TABLE في MySQL فيه implicit commit — الـ transaction فوق مش
--     بيحمي فعليًا من نص تنفيذ. لو وقفت في النص، شغّل الجمل الباقية لوحدها
--     (كلها آمنة للتكرار ما عدا ADD COLUMN اللي بترمي "Duplicate column").

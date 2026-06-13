-- Добавить колонку style_config в profile_card_template для хранения JSON-конфигурации стилей карточки
ALTER TABLE `profile_card_template`
  ADD COLUMN `style_config` TEXT DEFAULT NULL AFTER `preview_image`;

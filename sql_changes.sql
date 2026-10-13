ALTER TABLE `food_dishes` ADD `food_type` VARCHAR(255) NULL DEFAULT NULL AFTER `base_price`;

update `food_dishes` set food_type='regular' where food_type is null;
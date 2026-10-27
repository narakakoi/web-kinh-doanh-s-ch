ALTER TABLE `orders`
  ADD COLUMN `payment_reference` VARCHAR(100) NULL,
  ADD COLUMN `paid_at` DATETIME NULL,
  ADD UNIQUE KEY `uq_orders_payment_reference` (`payment_reference`);

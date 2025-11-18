
ALTER TABLE `bids` CHANGE `ride_id` `waste_pickup_id` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `sos_alerts` CHANGE `ride_id` `waste_pickup_id` INT(10) UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `reviews` CHANGE `driver_id` `collector_id` INT(10) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `reviews` CHANGE `ride_id` `waste_pickup_id` INT(10) UNSIGNED NOT NULL DEFAULT '0';
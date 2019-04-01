CREATE TABLE `quest_items` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `npc` varchar(64) NOT NULL DEFAULT '',
  `zone` varchar(64) NOT NULL DEFAULT '',
  `rewarded` tinyint(4) NOT NULL DEFAULT '0',
  `handed` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`,`npc`,`zone`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM AUTO_INCREMENT=67544 DEFAULT CHARSET=latin1;
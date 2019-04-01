DROP TABLE IF EXISTS `eqbnews`;
CREATE TABLE `eqbnews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` int(11) NOT NULL,
  `title` varchar(250) NOT NULL DEFAULT '',
  `content` varchar(999) NOT NULL DEFAULT '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=67544 DEFAULT CHARSET=latin1;

INSERT INTO eqbnews VALUES (1,UNIX_TIMESTAMP(NOW()),'This is an example','This news is in the `eqbnews` table, in your Eqemu database. You can add or remove news by editing this table. This is a feature from EqBrowser version 0.3.\r\nIt can contain some HTML code as long as there\'s no BODY, HTML tags and as long as the TABLE are correclty closed.');
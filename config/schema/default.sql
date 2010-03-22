/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES UTF8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
  `id` char(36) NOT NULL,
  `site_id` char(36) NOT NULL,
  `entry_id` char(36) NOT NULL,
  `parent_id` char(36) NOT NULL,
  `web_id` int(11) DEFAULT NULL,
  `user_id` char(36) NOT NULL,
  `type` varchar(20) DEFAULT 'public',
  `status` varchar(15) NOT NULL DEFAULT 'pending',
  `order` smallint(3) DEFAULT NULL,
  `display_name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `body` text,
  `ip` int(11) DEFAULT NULL,
  `rule_matches` tinytext,
  `junk_score` double(7,2) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Allow comments to be linked to anything';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entries` (
  `id` char(36) NOT NULL,
  `web_id` int(11) unsigned DEFAULT NULL,
  `site_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `category_id` char(36) DEFAULT NULL,
  `series_id` char(36) DEFAULT NULL,
  `status` varchar(15) DEFAULT 'draft',
  `comment_policy` varchar(15) DEFAULT NULL,
  `publish_date` datetime DEFAULT NULL,
  `comment_deadline` datetime DEFAULT NULL,
  `comment_count` smallint(4) NOT NULL DEFAULT '0',
  `page_count` tinyint(2) NOT NULL DEFAULT '1',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type_id` (`publish_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='"Structural" Info. Not the text"';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pages` (
  `id` char(36) NOT NULL,
  `site_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `model` varchar(50) NOT NULL DEFAULT 'Entry',
  `foreign_id` char(36) NOT NULL,
  `media_type` varchar(20) DEFAULT NULL COMMENT 'If media_id is set (the page isn''t html) what is it',
  `media` varchar(255) DEFAULT NULL COMMENT 'If the entire page is media (an image, pdf, movie, sound file) where is it',
  `locale` char(5) NOT NULL DEFAULT 'eng',
  `layout` varchar(30) NOT NULL DEFAULT 'full',
  `page_number` tinyint(2) NOT NULL DEFAULT '1',
  `icon` varchar(255) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL DEFAULT '',
  `intro` mediumtext,
  `body` longtext,
  `auto_intro` tinyint(1) NOT NULL DEFAULT '1',
  `tags` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_keywords` varchar(255) NOT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `model` (`model`,`foreign_id`,`page_number`),
  FULLTEXT KEY `title` (`title`,`intro`,`body`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='The meat. Allow the possibility to be linked to other tables';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50712
 Source Host           : localhost
 Source Database       : cyxbsmobile

 Target Server Type    : MySQL
 Target Server Version : 50712
 File Encoding         : utf-8

 Date: 08/02/2016 10:23:17 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `cyxbsmobile_administrators`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_administrators`;
CREATE TABLE `cyxbsmobile_administrators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_articlephoto`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_articlephoto`;
CREATE TABLE `cyxbsmobile_articlephoto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `stunum` int(11) NOT NULL,
  `thumbnail_src` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `photosrc` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `state` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `article_id` (`id`,`article_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_articlepraises`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_articlepraises`;
CREATE TABLE `cyxbsmobile_articlepraises` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stunum` int(11) NOT NULL,
  `update_time` datetime NOT NULL,
  `created_time` datetime NOT NULL,
  `article_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `articletype_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_articleremarks`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_articleremarks`;
CREATE TABLE `cyxbsmobile_articleremarks` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `articletypes_id` int(11) NOT NULL DEFAULT '5',
  `user_id` int(11) NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `created_time` datetime NOT NULL,
  `state` int(11) NOT NULL DEFAULT '1',
  `answer_user_id` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_articles`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_articles`;
CREATE TABLE `cyxbsmobile_articles` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `created_time` datetime NOT NULL,
  `updated_time` datetime NOT NULL,
  `thumbnail_src` text COLLATE utf8_unicode_ci NOT NULL,
  `photo_src` text COLLATE utf8_unicode_ci NOT NULL,
  `type_id` int(255) NOT NULL,
  `read_num` int(11) NOT NULL,
  `like_num` int(11) NOT NULL,
  `remark_num` int(11) NOT NULL,
  `state` int(255) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `titleorder` (`id`,`title`,`created_time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_articletypes`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_articletypes`;
CREATE TABLE `cyxbsmobile_articletypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `typename` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `state` int(255) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_cyxw`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_cyxw`;
CREATE TABLE `cyxbsmobile_cyxw` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `articleid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `head` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `read` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=171 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_hotarticles`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_hotarticles`;
CREATE TABLE `cyxbsmobile_hotarticles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `articletype_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `like_num` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `remark_num` int(11) NOT NULL,
  `created_time` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `updated_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_jwzx`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_jwzx`;
CREATE TABLE `cyxbsmobile_jwzx` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '标题',
  `date` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '发布日期',
  `content` text COLLATE utf8_unicode_ci,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '附件名字',
  `address` text COLLATE utf8_unicode_ci COMMENT '附件地址',
  `articleid` text COLLATE utf8_unicode_ci,
  `read` int(11) DEFAULT NULL COMMENT '阅读量',
  `head` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=177 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_news`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_news`;
CREATE TABLE `cyxbsmobile_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `articletype_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `like_num` int(11) NOT NULL DEFAULT '0',
  `unit` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `remark_num` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `articleid` int(11) NOT NULL,
  `read` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `head` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_notices`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_notices`;
CREATE TABLE `cyxbsmobile_notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `updated_time` datetime NOT NULL,
  `created_time` datetime NOT NULL,
  `content` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `like_num` int(11) NOT NULL DEFAULT '0',
  `remark_num` int(11) NOT NULL DEFAULT '0',
  `thumbnail_src` text COLLATE utf8_unicode_ci NOT NULL,
  `photo_src` text COLLATE utf8_unicode_ci NOT NULL,
  `state` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_photo`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_photo`;
CREATE TABLE `cyxbsmobile_photo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stunum` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `thumbnail_src` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `photosrc` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `state` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_trends`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_trends`;
CREATE TABLE `cyxbsmobile_trends` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `content` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `state` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_users`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_users`;
CREATE TABLE `cyxbsmobile_users` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `stunum` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `idnum` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `photo_thumbnail_src` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `photo_src` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nickname` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `passwd` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `gender` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `introduction` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `birthday` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `qq` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_time` datetime NOT NULL,
  `updated_time` datetime NOT NULL COMMENT '头像',
  `state` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_xsjz`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_xsjz`;
CREATE TABLE `cyxbsmobile_xsjz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `articleid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `head` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci,
  `unit` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=192 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `cyxbsmobile_xwgg`
-- ----------------------------
DROP TABLE IF EXISTS `cyxbsmobile_xwgg`;
CREATE TABLE `cyxbsmobile_xwgg` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `articleid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `head` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `unit` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci,
  `address` tinytext COLLATE utf8_unicode_ci,
  `name` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

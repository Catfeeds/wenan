/*
Navicat MySQL Data Transfer

Source Server         : 121.40.187.122_3307
Source Server Version : 50624
Source Host           : 121.40.187.122:3307
Source Database       : wenan

Target Server Type    : MYSQL
Target Server Version : 50624
File Encoding         : 65001

Date: 2018-06-13 14:26:40
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for wa_admin
-- ----------------------------
DROP TABLE IF EXISTS `wa_admin`;
CREATE TABLE `wa_admin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(20) NOT NULL DEFAULT '' COMMENT '姓名',
  `group_id` int(11) NOT NULL DEFAULT '0' COMMENT '权限组id',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `password` varchar(32) NOT NULL DEFAULT '' COMMENT '密码',
  `salt` varchar(30) NOT NULL DEFAULT '' COMMENT '密码盐',
  `avatar` varchar(100) NOT NULL DEFAULT '' COMMENT '头像',
  `phone` char(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `hos_id` int(11) NOT NULL DEFAULT '0' COMMENT '医馆id',
  `depart_id` int(11) NOT NULL DEFAULT '0' COMMENT '科室id',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '电子邮箱',
  `loginfailure` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `logintime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登录时间',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `token` varchar(59) NOT NULL DEFAULT '' COMMENT 'Session标识',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:正常 2:禁用 -1:删除',
  `edit_password` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1:已修改过密码',
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='管理员表';

-- ----------------------------
-- Records of wa_admin
-- ----------------------------
INSERT INTO `wa_admin` VALUES ('1', 'admin', '1', 'Admin', 'e863d6ff17ccfdfc566e820bfe01439d', 'c901ed', '/assets/img/avatar.png', 'admin', '0', '0', 'admin@admin.com', '0', '1528855879', '1492186163', '1528855879', 'c4e8d2c1-cc63-459d-a59a-d3edcd3a4f4c', '0', '1');

-- ----------------------------
-- Table structure for wa_admin_account
-- ----------------------------
DROP TABLE IF EXISTS `wa_admin_account`;
CREATE TABLE `wa_admin_account` (
  `admin_id` int(10) NOT NULL DEFAULT '0',
  `appoint_interval` mediumint(5) NOT NULL DEFAULT '0' COMMENT '预约区间，单位:分钟',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `update_time` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理员其它信息表';

-- ----------------------------
-- Records of wa_admin_account
-- ----------------------------

-- ----------------------------
-- Table structure for wa_admin_log
-- ----------------------------
DROP TABLE IF EXISTS `wa_admin_log`;
CREATE TABLE `wa_admin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `username` varchar(30) NOT NULL DEFAULT '' COMMENT '管理员名字',
  `url` varchar(100) NOT NULL DEFAULT '' COMMENT '操作页面',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '日志标题',
  `content` text NOT NULL COMMENT '内容',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP',
  `useragent` varchar(255) NOT NULL DEFAULT '' COMMENT 'User-Agent',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `name` (`username`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='管理员日志表';

-- ----------------------------
-- Table structure for wa_admin_sms
-- ----------------------------
DROP TABLE IF EXISTS `wa_admin_sms`;
CREATE TABLE `wa_admin_sms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` char(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `captcha` char(6) NOT NULL DEFAULT '' COMMENT '验证码',
  `content` varchar(50) NOT NULL DEFAULT '' COMMENT '短信内容',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1:注册 2:医馆管理员设置 3:通知 4:忘记密码',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1:验证过',
  `create_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='管理员短信表';

-- ----------------------------
-- Table structure for wa_appointment
-- ----------------------------
DROP TABLE IF EXISTS `wa_appointment`;
CREATE TABLE `wa_appointment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `hos_id` int(100) NOT NULL DEFAULT '0' COMMENT '门店id',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '客户姓名',
  `patient_visit_record_id` int(100) NOT NULL COMMENT '患者ID',
  `member_id` int(100) NOT NULL DEFAULT '0' COMMENT '会员id',
  `telphone` varchar(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `gender` int(1) NOT NULL DEFAULT '1' COMMENT '1:男2：女',
  `day` int(20) NOT NULL DEFAULT '0' COMMENT '预约日期',
  `start_time` int(20) NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(20) NOT NULL DEFAULT '0' COMMENT '结束时间',
  `doctor_id` int(100) NOT NULL DEFAULT '0' COMMENT '医生id',
  `doctor_name` varchar(100) NOT NULL DEFAULT '' COMMENT '医生姓名',
  `project_type` int(1) NOT NULL DEFAULT '0' COMMENT '就诊项目(单选):1=针灸,2=',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '-1:删除  0 ：取消 1：已预约  2:确认',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `is_occupy` int(1) NOT NULL DEFAULT '0' COMMENT '是否为占用时间段 0：否，1：是',
  `charge_info_id` int(10) NOT NULL DEFAULT '0' COMMENT '费用id',
  `patient_in_member_id` int(10) NOT NULL DEFAULT '0' COMMENT '绑定会员的患者id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='预约详情表';

-- ----------------------------
-- Table structure for wa_attachment
-- ----------------------------
DROP TABLE IF EXISTS `wa_attachment`;
CREATE TABLE `wa_attachment` (
  `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '物理路径',
  `imagewidth` varchar(30) NOT NULL DEFAULT '' COMMENT '宽度',
  `imageheight` varchar(30) NOT NULL DEFAULT '' COMMENT '宽度',
  `imagetype` varchar(30) NOT NULL DEFAULT '' COMMENT '图片类型',
  `imageframes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '图片帧数',
  `filesize` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `mimetype` varchar(30) NOT NULL DEFAULT '' COMMENT 'mime类型',
  `extparam` varchar(255) NOT NULL DEFAULT '' COMMENT '透传数据',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建日期',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `uploadtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上传时间',
  `storage` enum('local','upyun','qiniu') NOT NULL DEFAULT 'local' COMMENT '存储位置',
  `sha1` varchar(40) NOT NULL DEFAULT '' COMMENT '文件 sha1编码',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for wa_auth_group
-- ----------------------------
DROP TABLE IF EXISTS `wa_auth_group`;
CREATE TABLE `wa_auth_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hos_id` int(10) NOT NULL DEFAULT '0' COMMENT '医馆id',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父组别',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '组名',
  `rules` text NOT NULL COMMENT '规则ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '-1:删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='分组表';

-- ----------------------------
-- Records of wa_auth_group
-- ----------------------------
INSERT INTO `wa_auth_group` VALUES ('1', '0', '0', 'Admin group', '*', '0', '0', '0');
INSERT INTO `wa_auth_group` VALUES ('2', '0', '1', '管理员', '66,67,68,69,70,71,73,74,75,76,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,105,106,107,108,109,110,111,112,113,114,115,116,121,122,123,124,125,126,127,130,131,132,133,134,135,136,137,138,139,140,141,142,143,144', '0', '1525916041', '0');
INSERT INTO `wa_auth_group` VALUES ('3', '0', '2', '医生', '', '1521795814', '0', '0');
INSERT INTO `wa_auth_group` VALUES ('4', '0', '2', '护士', '', '0', '0', '0');
INSERT INTO `wa_auth_group` VALUES ('5', '0', '2', '收银', '', '0', '0', '0');

-- ----------------------------
-- Table structure for wa_auth_group_access
-- ----------------------------
DROP TABLE IF EXISTS `wa_auth_group_access`;
CREATE TABLE `wa_auth_group_access` (
  `uid` int(10) unsigned NOT NULL COMMENT '会员ID',
  `group_id` int(10) unsigned NOT NULL COMMENT '级别ID',
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`) USING BTREE,
  KEY `uid` (`uid`) USING BTREE,
  KEY `group_id` (`group_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='权限分组表';

-- ----------------------------
-- Records of wa_auth_group_access
-- ----------------------------

-- ----------------------------
-- Table structure for wa_auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `wa_auth_rule`;
CREATE TABLE `wa_auth_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('menu','file') NOT NULL DEFAULT 'file' COMMENT 'menu为菜单,file为权限节点',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则名称',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '规则名称',
  `icon` varchar(50) NOT NULL DEFAULT '' COMMENT '图标',
  `condition` varchar(255) NOT NULL DEFAULT '' COMMENT '条件',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `ismenu` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否为菜单',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) NOT NULL DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE,
  KEY `weigh` (`weigh`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8 COMMENT='节点表';

-- ----------------------------
-- Records of wa_auth_rule
-- ----------------------------
INSERT INTO `wa_auth_rule` VALUES ('1', 'file', '5', 'dashboard', 'Dashboard', 'fa fa-dashboard', '', 'Dashboard tips', '0', '1497429920', '1517903036', '3', 'hidden');
INSERT INTO `wa_auth_rule` VALUES ('2', 'file', '0', 'general', 'General', 'fa fa-cogs', '', '', '1', '1497429920', '1528353394', '9', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('3', 'file', '5', 'category', 'Category', 'fa fa-list', '', 'Category tips', '0', '1497429920', '1517555931', '15', 'hidden');
INSERT INTO `wa_auth_rule` VALUES ('4', 'file', '0', 'addon', 'Addon', 'fa fa-rocket', '', 'Addon tips', '1', '1502035509', '1528353966', '67', 'hidden');
INSERT INTO `wa_auth_rule` VALUES ('5', 'file', '0', 'auth', 'Auth', 'fa fa-group', '', '', '1', '1497429920', '1497430092', '1', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('6', 'file', '2', 'general/config', 'Config', 'fa fa-cog', '', 'Config tips', '1', '1497429920', '1528806602', '36', 'hidden');
INSERT INTO `wa_auth_rule` VALUES ('7', 'file', '2', 'general/attachment', 'Attachment', 'fa fa-file-image-o', '', 'Attachment tips', '1', '1497429920', '1497430699', '43', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('8', 'file', '2', 'general/profile', 'Profile', 'fa fa-user\r', '', '', '1', '1497429920', '1497429920', '49', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('9', 'file', '5', 'auth/admin', 'Admin', 'fa fa-user', '', 'Admin tips', '0', '1497429920', '1517559225', '16', 'hidden');
INSERT INTO `wa_auth_rule` VALUES ('10', 'file', '5', 'auth/adminlog', 'Admin log', 'fa fa-list-alt', '', 'Admin log tips', '1', '1497429920', '1497430307', '21', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('11', 'file', '5', 'auth/group', 'Group', 'fa fa-group', '', 'Group tips', '1', '1497429920', '1497429920', '25', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('12', 'file', '5', 'auth/rule', 'Rule', 'fa fa-bars', '', 'Rule tips', '1', '1497429920', '1497430581', '30', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('13', 'file', '1', 'dashboard/index', 'View', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '10', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('14', 'file', '1', 'dashboard/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '11', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('15', 'file', '1', 'dashboard/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '13', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('16', 'file', '1', 'dashboard/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '12', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('17', 'file', '1', 'dashboard/multi', 'Multi', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '14', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('18', 'file', '6', 'general/config/index', 'View', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '44', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('19', 'file', '6', 'general/config/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '45', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('20', 'file', '6', 'general/config/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '46', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('21', 'file', '6', 'general/config/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '47', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('22', 'file', '6', 'general/config/multi', 'Multi', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '48', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('23', 'file', '7', 'general/attachment/index', 'View', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '37', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('24', 'file', '7', 'general/attachment/select', 'Select attachment', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '38', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('25', 'file', '7', 'general/attachment/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '39', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('26', 'file', '7', 'general/attachment/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '40', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('27', 'file', '7', 'general/attachment/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '41', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('28', 'file', '7', 'general/attachment/multi', 'Multi', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '42', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('29', 'file', '8', 'general/profile/index', 'View', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '50', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('30', 'file', '8', 'general/profile/update', 'Update profile', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '51', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('31', 'file', '8', 'general/profile/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '52', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('32', 'file', '8', 'general/profile/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '53', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('33', 'file', '8', 'general/profile/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '54', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('34', 'file', '8', 'general/profile/multi', 'Multi', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '55', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('35', 'file', '3', 'category/index', 'View', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '4', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('36', 'file', '3', 'category/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '5', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('37', 'file', '3', 'category/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '6', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('38', 'file', '3', 'category/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '7', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('39', 'file', '3', 'category/multi', 'Multi', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '8', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('40', 'file', '9', 'auth/admin/index', 'View', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '17', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('41', 'file', '9', 'auth/admin/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '18', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('42', 'file', '9', 'auth/admin/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '19', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('43', 'file', '9', 'auth/admin/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '20', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('44', 'file', '10', 'auth/adminlog/index', 'View', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '22', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('45', 'file', '10', 'auth/adminlog/detail', 'Detail', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '23', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('46', 'file', '10', 'auth/adminlog/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '24', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('47', 'file', '11', 'auth/group/index', 'View', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '26', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('48', 'file', '11', 'auth/group/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '27', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('49', 'file', '11', 'auth/group/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '28', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('50', 'file', '11', 'auth/group/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '29', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('51', 'file', '12', 'auth/rule/index', 'View', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '31', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('52', 'file', '12', 'auth/rule/add', 'Add', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '32', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('53', 'file', '12', 'auth/rule/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '33', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('54', 'file', '12', 'auth/rule/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1497429920', '1497429920', '34', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('55', 'file', '4', 'addon/index', 'View', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '66', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('56', 'file', '4', 'addon/add', 'Add', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '65', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('57', 'file', '4', 'addon/edit', 'Edit', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '64', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('58', 'file', '4', 'addon/del', 'Delete', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '63', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('59', 'file', '4', 'addon/local', 'Local install', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '62', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('60', 'file', '4', 'addon/state', 'Update state', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '61', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('61', 'file', '4', 'addon/install', 'Install', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '60', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('62', 'file', '4', 'addon/uninstall', 'Uninstall', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '59', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('63', 'file', '4', 'addon/config', 'Setting', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '58', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('64', 'file', '4', 'addon/refresh', 'Refresh', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '57', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('65', 'file', '4', 'addon/multi', 'Multi', 'fa fa-circle-o', '', '', '0', '1502035509', '1502035509', '56', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('66', 'file', '0', 'user', '用户大厅', 'fa fa-desktop', '', '用户大厅', '1', '1516159441', '1517205373', '35', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('67', 'file', '0', 'system', '系统管理', 'fa fa-wrench', '', '系统管理', '1', '1516160749', '1517198774', '2', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('68', 'file', '67', 'system/member', '人员管理', 'fa fa-user', '', '人员管理', '1', '1516171624', '1518079285', '122', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('69', 'file', '67', 'system/schedul', '排班管理', 'fa fa-address-card', '', '排班管理', '1', '1516172087', '1518079462', '121', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('70', 'file', '67', 'system/fee', '收费项目管理', 'fa fa-bluetooth', '', '收费项目管理', '1', '1516172142', '1518079476', '120', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('71', 'file', '67', 'system/register', '挂号管理', 'fa fa-diamond', '', '挂号管理', '1', '1516172188', '1528806614', '119', 'hidden');
INSERT INTO `wa_auth_rule` VALUES ('72', 'file', '5', 'system/hospital', '医馆管理', 'fa fa-location-arrow', '', '医馆管理', '1', '1516172243', '1518081769', '69', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('73', 'file', '68', 'system/member/index', '列表', 'fa fa-circle-o', '', '', '0', '1516351170', '1517198819', '117', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('74', 'file', '68', 'system/member/add', '添加', 'fa fa-circle-o', '', '', '0', '1516351369', '1517205399', '116', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('75', 'file', '68', 'system/member/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1516351422', '1517205409', '115', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('76', 'file', '68', 'system/member/del', '删除', 'fa fa-circle-o', '', '', '0', '1516351453', '1517205420', '114', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('77', 'file', '5', 'system/dict', '字典管理', 'fa fa-bars', '', '字典管理', '1', '1516584170', '1517559463', '113', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('78', 'file', '66', 'user/patientvisitrecord', '患者管理', 'fa fa-user', '', '', '1', '1516331805', '1516333069', '112', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('79', 'file', '78', 'user/patientvisitrecord/index', '查看', 'fa fa-circle-o', '', '', '0', '1516331805', '1516331805', '111', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('80', 'file', '78', 'user/patientvisitrecord/pay', '付款', 'fa fa-circle-o', '', '', '0', '1516331805', '1516331805', '110', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('81', 'file', '78', 'user/patientvisitrecord/privacy', '隐私', 'fa fa-circle-o', '', '', '0', '1516331805', '1516331805', '109', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('82', 'file', '78', 'user/patientvisitrecord/del', '删除', 'fa fa-circle-o', '', '', '0', '1516331805', '1516331805', '108', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('83', 'file', '78', 'user/patientvisitrecord/detail', '详情', 'fa fa-circle-o', '', '', '0', '1516331805', '1516331805', '107', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('84', 'file', '66', 'user/member', '会员管理', 'fa fa-id-card', '', '', '1', '1516331950', '1516333184', '106', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('85', 'file', '84', 'user/member/index', '查看', 'fa fa-circle-o', '', '', '0', '1516331951', '1516331951', '105', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('86', 'file', '84', 'user/member/add', '添加', 'fa fa-circle-o', '', '', '0', '1516331951', '1516331951', '104', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('87', 'file', '84', 'user/member/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1516331951', '1516331951', '103', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('88', 'file', '84', 'user/member/forbidden', '禁用', 'fa fa-circle-o', '', '', '0', '1516331951', '1516331951', '102', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('89', 'file', '84', 'user/member/detail', '详情', 'fa fa-circle-o', '', '', '0', '1516331951', '1516331951', '101', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('90', 'file', '66', 'user/register', '挂号管理', 'fa fa-bell', '', '', '1', '1516331980', '1516333361', '100', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('91', 'file', '90', 'user/register/index', '查看', 'fa fa-circle-o', '', '', '0', '1516331980', '1516331980', '99', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('92', 'file', '90', 'user/register/add', '添加', 'fa fa-circle-o', '', '', '0', '1516331980', '1516331980', '98', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('93', 'file', '90', 'user/register/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1516331980', '1516331980', '97', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('94', 'file', '90', 'user/register/cancel', '取消挂号', 'fa fa-circle-o', '', '', '0', '1516331980', '1516331980', '96', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('95', 'file', '90', 'user/register/detail', '详情', 'fa fa-circle-o', '', '', '0', '1516331980', '1516331980', '95', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('96', 'file', '66', 'user/appointment', '预约管理', 'fa fa-handshake-o', '', '', '1', '1516332012', '1516333446', '94', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('97', 'file', '96', 'user/appointment/index', '查看', 'fa fa-circle-o', '', '', '0', '1516332012', '1516332012', '93', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('98', 'file', '96', 'user/appointment/add', '添加', 'fa fa-circle-o', '', '', '0', '1516332012', '1516332012', '92', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('99', 'file', '96', 'user/appointment/edit', '编辑', 'fa fa-circle-o', '', '', '0', '1516332012', '1516332012', '91', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('100', 'file', '96', 'user/appointment/del', '删除', 'fa fa-circle-o', '', '', '0', '1516331980', '1516331980', '90', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('101', 'file', '96', 'user/appointment/softdelete', '取消预约', 'fa fa-circle-o', '', '', '0', '1516331980', '1516331980', '89', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('102', 'file', '72', 'system/hospital/index', '查看', 'fa fa-dot', '', '', '0', '1517198303', '1517205493', '88', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('103', 'file', '72', 'system/hospital/add', '添加', 'fa fa-dot', '', '', '0', '1517198335', '1517205503', '87', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('104', 'file', '72', 'system/hospital/edit', '编辑', 'fa fa-dot', '', '', '0', '1517198374', '1517205511', '86', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('105', 'file', '69', 'system/schedul/index', '查看', 'fa fa-dot', '', '', '0', '1517198457', '1517205456', '85', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('106', 'file', '69', 'system/schedul/edit', '设置作息时间', 'fa fa-dot', '', '', '0', '1517198512', '1518159948', '84', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('107', 'file', '69', 'system/schedul/set', '设置医生的排班', 'fa fa-dot', '', '设置医生的排班', '0', '1517198558', '1517283822', '83', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('108', 'file', '71', 'system/register/index', '查看', 'fa fa-dot', '', '', '0', '1517558220', '1517558220', '82', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('109', 'file', '71', 'system/register/edit', '编辑', 'fa fa-dot', '', '', '0', '1517558267', '1517558267', '81', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('110', 'file', '71', 'system/register/lock', '锁定', 'fa fa-dot', '', '', '0', '1517558304', '1517558304', '80', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('111', 'file', '68', 'system/member/forbidden', '禁用', 'fa fa-dot', '', '', '0', '1517558430', '1517558772', '79', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('112', 'file', '70', 'system/fee/index', '列表', 'fa fa-dot', '', '', '0', '1517558747', '1517558747', '78', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('113', 'file', '70', 'system/fee/add', '添加', 'fa fa-dot', '', '', '0', '1517558799', '1517558799', '77', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('114', 'file', '70', 'system/fee/edit', '编辑', 'fa fa-dot', '', '', '0', '1517558814', '1517558814', '76', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('115', 'file', '70', 'system/fee/del', '删除', 'fa fa-dot', '', '', '0', '1517558835', '1517558835', '75', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('116', 'file', '70', 'system/fee/forbidden', '禁用', 'fa fa-dot', '', '', '0', '1517558852', '1517558852', '74', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('117', 'file', '77', 'system/dict/index', '列表', 'fa fa-dot', '', '', '0', '1517559356', '1517559356', '73', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('118', 'file', '77', 'system/dict/add', '添加', 'fa fa-dot', '', '', '0', '1517559371', '1517559371', '72', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('119', 'file', '77', 'system/dict/edit', '编辑', 'fa fa-dot', '', '', '0', '1517559392', '1517559392', '71', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('120', 'file', '77', 'system/dict/del', '删除', 'fa fa-dot', '', '', '0', '1517559406', '1517559406', '70', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('121', 'file', '67', 'system/group', '权限管理', 'fa fa-dedent', '', '', '1', '1518077494', '1518079506', '118', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('122', 'file', '121', 'system/group/index', '查看', 'fa fa-dot', '', '', '0', '1518077570', '1518077570', '68', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('123', 'file', '121', 'system/group/add', '添加', 'fa fa-dot', '', '', '0', '1518077932', '1518077932', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('124', 'file', '121', 'system/group/edit', '编辑', 'fa fa-dot', '', '', '0', '1518077953', '1518077953', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('125', 'file', '121', 'system/group/del', '删除', 'fa fa-dot', '', '', '0', '1518077978', '1518077978', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('126', 'file', '68', 'system/member/startus', '启用', 'fa fa-dot', '', '', '0', '1522310192', '1522310192', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('127', 'file', '84', 'user/member/pay', '充值', 'fa fa-circle-o', '', '', '0', '1516331951', '1516331951', '101', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('130', 'file', '0', 'doctor', '医生站', 'fa fa-user', '', '', '1', '1523867219', '1523867265', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('131', 'file', '130', 'doctor/appointment', '预约', 'fa fa-handshake-o', '', '', '1', '1523867425', '1523867452', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('132', 'file', '130', 'doctor/schedul', '我的排班', 'fa fa-address-card', '', '', '1', '1523867516', '1523867533', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('133', 'file', '130', 'doctor/workload', '我的工作量', 'fa fa-battery', '', '', '1', '1523867590', '1523867605', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('134', 'file', '132', 'doctor/schedul/index', '查看', 'fa fa-dot', '', '', '0', '1523930370', '1523930370', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('135', 'file', '84', 'user/chargeinfo/del', '删除收费', 'fa fa-dot', '', '', '0', '1523958644', '1527474426', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('136', 'file', '84', 'user/chargeinfo/add', '新增收费', 'fa fa-dot', '', '', '0', '1523958644', '1527474426', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('139', 'file', '84', 'user/chargeinfo/pay', '收费支付', 'fa fa-dot', '', '', '0', '1524107020', '1527474461', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('140', 'file', '67', 'system/performance', '员工绩效', 'fa fa-windows', '', '', '1', '1524206418', '1524206472', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('141', 'file', '140', 'system/performance/index', '查看', 'fa fa-calendar-check-o', '', '', '0', '1524206515', '1524206515', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('142', 'file', '133', 'doctor/workload/index', '查看', 'fa fa-dot', '', '', '0', '1525227790', '1525227790', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('143', 'file', '131', 'doctor/appointment/index', '查看', 'fa fa-handshake-o', '', '', '0', '1525402881', '1525402881', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('144', 'file', '131', 'doctor/appointment/setup', '设置预约时间', 'fa fa-dot', '', '', '0', '1525414971', '1525414971', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('145', 'file', '2', 'general/crontab', '定时任务', 'fa fa-tasks', '', '类似于Linux的Crontab定时任务,可以按照设定的时间进行任务的执行,目前仅支持三种任务:请求URL、执行SQL、执行Shell', '1', '1528352644', '1528352644', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('146', 'file', '145', 'general/crontab/index', '查看', 'fa fa-circle-o', '', '', '0', '1528352644', '1528352644', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('147', 'file', '145', 'general/crontab/add', '添加', 'fa fa-circle-o', '', '', '0', '1528352644', '1528352644', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('148', 'file', '145', 'general/crontab/edit', '编辑 ', 'fa fa-circle-o', '', '', '0', '1528352644', '1528352644', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('149', 'file', '145', 'general/crontab/del', '删除', 'fa fa-circle-o', '', '', '0', '1528352644', '1528352644', '0', 'normal');
INSERT INTO `wa_auth_rule` VALUES ('150', 'file', '145', 'general/crontab/multi', '批量更新', 'fa fa-circle-o', '', '', '0', '1528352645', '1528352645', '0', 'normal');

-- ----------------------------
-- Table structure for wa_charge_info
-- ----------------------------
DROP TABLE IF EXISTS `wa_charge_info`;
CREATE TABLE `wa_charge_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `hos_id` int(10) NOT NULL DEFAULT '0' COMMENT '门店id',
  `admin_input_id` int(10) NOT NULL DEFAULT '0' COMMENT '录入者id',
  `admin_input_name` varchar(20) NOT NULL DEFAULT '' COMMENT '录入者姓名',
  `admin_collect_id` int(10) NOT NULL DEFAULT '0' COMMENT '收款操作者id',
  `admin_collect_name` varchar(20) NOT NULL DEFAULT '' COMMENT '收款者姓名',
  `patient_in_member_id` int(10) NOT NULL DEFAULT '0' COMMENT '患者ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '患者姓名',
  `doctor_name` varchar(20) NOT NULL DEFAULT '' COMMENT '医生姓名',
  `member_id` int(10) NOT NULL DEFAULT '0' COMMENT '会员id',
  `fee_id` int(1) NOT NULL DEFAULT '0' COMMENT '费用类型，字典表费用id 1：挂号费 3：充值',
  `hos_fee_id` int(10) NOT NULL DEFAULT '0' COMMENT '医馆费用id',
  `hos_fee_name` varchar(20) NOT NULL DEFAULT '' COMMENT '费用名称',
  `should_pay` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '应付款',
  `pay_way` varchar(10) NOT NULL DEFAULT '0' COMMENT '付款方式 0.未付 1.线上付款 2.支付宝 3.微信支付',
  `already_paid` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '已付款',
  `serial_number` int(10) DEFAULT '0' COMMENT '流水号',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '支付状态 0：未付 1：已付',
  `newfee` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1:来自病人新增收费',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='收费信息详情表';

-- ----------------------------
-- Table structure for wa_config
-- ----------------------------
DROP TABLE IF EXISTS `wa_config`;
CREATE TABLE `wa_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '变量名',
  `group` varchar(30) NOT NULL DEFAULT '' COMMENT '分组',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '变量标题',
  `tip` varchar(100) NOT NULL DEFAULT '' COMMENT '变量描述',
  `type` varchar(30) NOT NULL DEFAULT '' COMMENT '类型:string,text,int,bool,array,datetime,date,file',
  `value` text NOT NULL COMMENT '变量值',
  `content` text NOT NULL COMMENT '变量字典数据',
  `rule` varchar(100) NOT NULL DEFAULT '' COMMENT '验证规则',
  `extend` varchar(255) NOT NULL DEFAULT '' COMMENT '扩展属性',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8 COMMENT='系统配置';

-- ----------------------------
-- Records of wa_config
-- ----------------------------
INSERT INTO `wa_config` VALUES ('1', 'name', 'basic', 'Site name', '请填写站点名称', 'string', '问安管理平台', '', 'required', '');
INSERT INTO `wa_config` VALUES ('2', 'beian', 'basic', 'Beian', '粤ICP备15054802号-4', 'string', '', '', '', '');
INSERT INTO `wa_config` VALUES ('3', 'cdnurl', 'basic', 'Cdn url', '如果使用CDN云储存请配置该值', 'string', '', '', '', '');
INSERT INTO `wa_config` VALUES ('4', 'version', 'basic', 'Version', '如果静态资源有变动请重新配置该值', 'string', '1.0.1', '', 'required', '');
INSERT INTO `wa_config` VALUES ('5', 'timezone', 'basic', 'Timezone', '', 'string', 'Asia/Shanghai', '', 'required', '');
INSERT INTO `wa_config` VALUES ('6', 'forbiddenip', 'basic', 'Forbidden ip', '一行一条记录', 'text', '', '', '', '');
INSERT INTO `wa_config` VALUES ('7', 'languages', 'basic', 'Languages', '', 'array', '{\"backend\":\"zh-cn\",\"frontend\":\"zh-cn\"}', '', 'required', '');
INSERT INTO `wa_config` VALUES ('8', 'fixedpage', 'basic', 'Fixed page', '请尽量输入左侧菜单栏存在的链接', 'string', 'general/profile', '', 'required', '');
INSERT INTO `wa_config` VALUES ('9', 'categorytype', 'dictionary', 'Cateogry type', '', 'array', '{\"default\":\"Default\",\"page\":\"Page\",\"article\":\"Article\",\"test\":\"Test\"}', '', '', '');
INSERT INTO `wa_config` VALUES ('10', 'configgroup', 'dictionary', 'Config group', '', 'array', '{\"basic\":\"Basic\",\"email\":\"Email\",\"dictionary\":\"Dictionary\",\"user\":\"User\",\"example\":\"Example\"}', '', '', '');
INSERT INTO `wa_config` VALUES ('11', 'mail_type', 'email', 'Mail type', '选择邮件发送方式', 'select', '1', '[\"Please select\",\"SMTP\",\"Mail\"]', '', '');
INSERT INTO `wa_config` VALUES ('12', 'mail_smtp_host', 'email', 'Mail smtp host', '错误的配置发送邮件会导致服务器超时', 'string', 'smtp.qq.com', '', '', '');
INSERT INTO `wa_config` VALUES ('13', 'mail_smtp_port', 'email', 'Mail smtp port', '(不加密默认25,SSL默认465,TLS默认587)', 'string', '465', '', '', '');
INSERT INTO `wa_config` VALUES ('14', 'mail_smtp_user', 'email', 'Mail smtp user', '（填写完整用户名）', 'string', '10000', '', '', '');
INSERT INTO `wa_config` VALUES ('15', 'mail_smtp_pass', 'email', 'Mail smtp password', '（填写您的密码）', 'string', 'password', '', '', '');
INSERT INTO `wa_config` VALUES ('16', 'mail_verify_type', 'email', 'Mail vertify type', '（SMTP验证方式[推荐SSL]）', 'select', '2', '[\"None\",\"TLS\",\"SSL\"]', '', '');
INSERT INTO `wa_config` VALUES ('17', 'mail_from', 'email', 'Mail from', '', 'string', '10000@qq.com', '', '', '');

-- ----------------------------
-- Table structure for wa_crontab
-- ----------------------------
DROP TABLE IF EXISTS `wa_crontab`;
CREATE TABLE `wa_crontab` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `type` varchar(10) NOT NULL DEFAULT '' COMMENT '事件类型',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '事件标题',
  `content` text NOT NULL COMMENT '事件内容',
  `schedule` varchar(100) NOT NULL DEFAULT '' COMMENT 'Crontab格式',
  `sleep` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '延迟秒数执行',
  `maximums` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最大执行次数 0为不限',
  `executes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '已经执行的次数',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `begintime` int(10) NOT NULL DEFAULT '0' COMMENT '开始时间',
  `endtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `executetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后执行时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `status` enum('completed','expired','hidden','normal') NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='定时任务表';

-- ----------------------------
-- Records of wa_crontab
-- ----------------------------
INSERT INTO `wa_crontab` VALUES ('1', 'url', '检查发送回访短信', '/admin/crontab/sendmessage/atReturnTimeSendMessage', '0 * * * *', '0', '0', '0', '1497070825', '1528869602', '1483200000', '1546272000', '1528869602', '1', 'normal');

-- ----------------------------
-- Table structure for wa_doctor_register
-- ----------------------------
DROP TABLE IF EXISTS `wa_doctor_register`;
CREATE TABLE `wa_doctor_register` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '员工id',
  `work_day` date NOT NULL,
  `morning` tinyint(2) NOT NULL DEFAULT '0',
  `afternoon` tinyint(2) NOT NULL DEFAULT '0',
  `evening` tinyint(2) NOT NULL DEFAULT '0',
  `morning_lock` tinyint(2) NOT NULL DEFAULT '0',
  `afternoon_lock` tinyint(2) NOT NULL DEFAULT '0',
  `evening_lock` tinyint(2) NOT NULL DEFAULT '0',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='医生挂号设置表';

-- ----------------------------
-- Table structure for wa_hos_depart
-- ----------------------------
DROP TABLE IF EXISTS `wa_hos_depart`;
CREATE TABLE `wa_hos_depart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hos_id` int(11) NOT NULL DEFAULT '0' COMMENT '医馆id',
  `depart_id` int(11) NOT NULL DEFAULT '0' COMMENT '科室id',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hos_depart_id` (`hos_id`,`depart_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='医馆科室表';

-- ----------------------------
-- Table structure for wa_hos_fee
-- ----------------------------
DROP TABLE IF EXISTS `wa_hos_fee`;
CREATE TABLE `wa_hos_fee` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fee_name` varchar(10) NOT NULL DEFAULT '' COMMENT '费用名称',
  `fee_id` int(11) NOT NULL DEFAULT '0' COMMENT '费用id',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit` varchar(5) NOT NULL DEFAULT '' COMMENT '单位',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '-1:删除 0:未启用 1:启用 2:禁用',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  `disable_time` int(11) NOT NULL DEFAULT '0' COMMENT '禁用操作时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='医院费用表';

-- ----------------------------
-- Table structure for wa_hos_rest
-- ----------------------------
DROP TABLE IF EXISTS `wa_hos_rest`;
CREATE TABLE `wa_hos_rest` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hos_id` int(11) NOT NULL DEFAULT '0' COMMENT '医馆id',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1:上午 2:下午 3:晚上',
  `start_time` char(5) NOT NULL DEFAULT '',
  `end_time` char(5) NOT NULL DEFAULT '',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='医馆作息表';

-- ----------------------------
-- Records of wa_hos_rest
-- ----------------------------
INSERT INTO `wa_hos_rest` VALUES ('1', '0', '1', '09:00', '12:00', '1522035417', '1528683644');
INSERT INTO `wa_hos_rest` VALUES ('2', '0', '2', '13:30', '18:00', '1522035417', '1528683644');
INSERT INTO `wa_hos_rest` VALUES ('3', '0', '3', '19:00', '21:00', '1522035417', '1528683644');

-- ----------------------------
-- Table structure for wa_hos_staff_rest
-- ----------------------------
DROP TABLE IF EXISTS `wa_hos_staff_rest`;
CREATE TABLE `wa_hos_staff_rest` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '员工id',
  `rest_day` date NOT NULL COMMENT '休息日期',
  `year` char(4) NOT NULL DEFAULT '',
  `money` char(2) NOT NULL DEFAULT '',
  `day` char(2) NOT NULL DEFAULT '',
  `create_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='医馆员工休息表';

-- ----------------------------
-- Table structure for wa_hospital
-- ----------------------------
DROP TABLE IF EXISTS `wa_hospital`;
CREATE TABLE `wa_hospital` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hos_name` varchar(20) NOT NULL DEFAULT '' COMMENT '名字',
  `admin_phone` char(11) NOT NULL DEFAULT '' COMMENT '管理员帐号',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='医馆表';

-- ----------------------------
-- Table structure for wa_member
-- ----------------------------
DROP TABLE IF EXISTS `wa_member`;
CREATE TABLE `wa_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(10) NOT NULL DEFAULT '' COMMENT '姓名',
  `gender` int(1) NOT NULL DEFAULT '1' COMMENT '性别(单选):1=男,2=女',
  `doctor_id` int(10) NOT NULL DEFAULT '0' COMMENT '常用医生id',
  `doctor_name` varchar(10) NOT NULL DEFAULT '' COMMENT '常用医生姓名',
  `common_hos_id` int(10) NOT NULL DEFAULT '0' COMMENT '常用医院id',
  `birth_time` int(10) DEFAULT '0' COMMENT '出生日期',
  `medical_record_number` varchar(20) NOT NULL DEFAULT '' COMMENT '病例号',
  `telphone` varchar(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `home_address` varchar(50) NOT NULL DEFAULT '' COMMENT '家庭住址',
  `medical_status` int(1) NOT NULL DEFAULT '1' COMMENT '就诊状态(单选):1=已预约,2=候诊中,3=看诊中',
  `integral` int(10) NOT NULL DEFAULT '0' COMMENT '积分',
  `hos_id` int(10) NOT NULL DEFAULT '0' COMMENT '创建门店id',
  `card_number` varchar(20) NOT NULL DEFAULT '' COMMENT '卡号',
  `balance` int(10) NOT NULL DEFAULT '0' COMMENT '余额',
  `card_type` int(10) NOT NULL DEFAULT '1' COMMENT '会员卡类型 1.充值卡',
  `project_discount` varchar(50) NOT NULL DEFAULT '' COMMENT '项目折扣',
  `sale_discount` varchar(50) NOT NULL DEFAULT '' COMMENT '卖品折扣',
  `open_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开卡时间',
  `open_member` int(1) NOT NULL DEFAULT '0' COMMENT '0:未开通 ，1：开通',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `last_consumption_time` int(10) NOT NULL DEFAULT '0' COMMENT '最后消费时间',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '-1：软删除 ，1：正常 ,2：禁用',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='会员表';

-- ----------------------------
-- Table structure for wa_member_operate_log
-- ----------------------------
DROP TABLE IF EXISTS `wa_member_operate_log`;
CREATE TABLE `wa_member_operate_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(100) DEFAULT '' COMMENT '操作标题',
  `content` text NOT NULL COMMENT '操作内容',
  `url` varchar(100) DEFAULT '' COMMENT '操作链接',
  `member_id` int(10) NOT NULL COMMENT '会员ID',
  `admin_id` int(10) NOT NULL DEFAULT '0' COMMENT '操作者id',
  `admin_name` varchar(20) DEFAULT '' COMMENT '操作者姓名',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `useragent` varchar(10) DEFAULT '' COMMENT '用户代理',
  `ip` varchar(100) DEFAULT '' COMMENT '操作者所在ip',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='会员操作记录';

-- ----------------------------
-- Table structure for wa_patient_in_member
-- ----------------------------
DROP TABLE IF EXISTS `wa_patient_in_member`;
CREATE TABLE `wa_patient_in_member` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '绑定会员病人id',
  `member_id` int(10) NOT NULL DEFAULT '0' COMMENT '会员id',
  `name` varchar(10) NOT NULL DEFAULT '' COMMENT '病人姓名',
  `gender` tinyint(1) NOT NULL DEFAULT '1' COMMENT '性别 1:男 2：女',
  `birth_time` int(10) NOT NULL DEFAULT '0' COMMENT '出生年月',
  `relation` tinyint(1) NOT NULL DEFAULT '0' COMMENT '与会员所属关系',
  `return_cycle` int(10) NOT NULL DEFAULT '0' COMMENT '回访周期/天',
  `createtime` int(10) NOT NULL DEFAULT '0' COMMENT ' 创建时间',
  `updatetime` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for wa_patient_in_member_case
-- ----------------------------
DROP TABLE IF EXISTS `wa_patient_in_member_case`;
CREATE TABLE `wa_patient_in_member_case` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '病例id',
  `image` varchar(100) NOT NULL DEFAULT '' COMMENT '病例图',
  `admin_id` int(10) NOT NULL DEFAULT '0' COMMENT '上传病例的操作者id',
  `admin_name` varchar(10) NOT NULL DEFAULT '' COMMENT '上传病例的操作这名称',
  `hos_id` int(10) NOT NULL DEFAULT '0' COMMENT '病人所在医院id',
  `patient_in_member_id` int(10) NOT NULL DEFAULT '0' COMMENT '绑定会员的病人id',
  `createtime` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for wa_patient_return_content
-- ----------------------------
DROP TABLE IF EXISTS `wa_patient_return_content`;
CREATE TABLE `wa_patient_return_content` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '回访内容id',
  `hos_id` int(10) NOT NULL DEFAULT '0' COMMENT '医院id',
  `content` varchar(100) NOT NULL DEFAULT '' COMMENT '回访内容',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 0：删除 1正常',
  `createtime` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;


-- ----------------------------
-- Table structure for wa_patient_return_record
-- ----------------------------
DROP TABLE IF EXISTS `wa_patient_return_record`;
CREATE TABLE `wa_patient_return_record` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `patient_in_member_id` int(10) NOT NULL DEFAULT '0' COMMENT '绑定会员病人id',
  `member_id` int(10) NOT NULL DEFAULT '0' COMMENT '会员id',
  `return_time` int(10) NOT NULL DEFAULT '0' COMMENT '回访时间',
  `createtime` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `admin_name` varchar(20) NOT NULL DEFAULT '' COMMENT '操作者姓名',
  `admin_id` varchar(10) NOT NULL DEFAULT '' COMMENT '操作者id',
  `content` varchar(20) NOT NULL DEFAULT '' COMMENT '回访内容',
  `next_time` int(10) NOT NULL DEFAULT '0' COMMENT '下次回访时间',
  `is_send` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已经发送短信 0：否 1：是',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for wa_patient_visit_record
-- ----------------------------
DROP TABLE IF EXISTS `wa_patient_visit_record`;
CREATE TABLE `wa_patient_visit_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `hos_id` int(10) NOT NULL DEFAULT '0' COMMENT '所属门店id',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '名称',
  `name_pinyin` varchar(20) NOT NULL DEFAULT '' COMMENT '名称全拼',
  `doctor_register_id` int(10) NOT NULL DEFAULT '0' COMMENT '挂号医生id',
  `doctor_register_name` varchar(20) NOT NULL DEFAULT '' COMMENT '挂号医生',
  `treatment_department` int(10) NOT NULL DEFAULT '1' COMMENT '就诊科室',
  `doctor_appointment_id` int(10) NOT NULL DEFAULT '0' COMMENT '预约医生id',
  `doctor_appointment_name` varchar(10) NOT NULL DEFAULT '' COMMENT '预约医生姓名',
  `gender` int(1) NOT NULL DEFAULT '1' COMMENT '性别(单选):1=男,2=女',
  `birth_time` int(10) NOT NULL DEFAULT '0' COMMENT '出生日期',
  `medical_record_number` varchar(20) NOT NULL DEFAULT '' COMMENT '病例号',
  `telphone` varchar(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `home_address` varchar(50) NOT NULL DEFAULT '' COMMENT '家庭住址',
  `medical_status` int(1) NOT NULL DEFAULT '0' COMMENT '就诊状态(单选):1=已预约,2=候诊中,3=看诊中',
  `appointment_time` int(10) NOT NULL DEFAULT '0' COMMENT '预约时间',
  `register_time` int(10) NOT NULL DEFAULT '0' COMMENT '挂号时间',
  `charge_info_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收费信息id',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `member_id` int(10) NOT NULL DEFAULT '0' COMMENT '会员id',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '-1:软删除 0.取消 1.正常 2确认',
  `patient_in_member_id` int(10) NOT NULL DEFAULT '0' COMMENT '绑定会员的病人id',
  `admin_id` int(10) NOT NULL DEFAULT '0' COMMENT '操作者id',
  `admin_name` varchar(20) NOT NULL DEFAULT '' COMMENT '操作者姓名',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for wa_register
-- ----------------------------
DROP TABLE IF EXISTS `wa_register`;
CREATE TABLE `wa_register` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `hos_id` int(10) NOT NULL DEFAULT '0' COMMENT '门店id',
  `patient_visit_record_id` int(10) NOT NULL COMMENT '患者ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '患者姓名',
  `member_id` int(10) NOT NULL DEFAULT '0' COMMENT '会员id',
  `telphone` varchar(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `gender` int(1) NOT NULL DEFAULT '1' COMMENT '性别 1：男 2：女',
  `register_time` int(10) NOT NULL DEFAULT '0' COMMENT '挂号时间',
  `doctor_name` varchar(20) NOT NULL DEFAULT '' COMMENT '医生姓名',
  `doctor_id` int(10) NOT NULL DEFAULT '0' COMMENT '医生id',
  `treatment_type` int(1) NOT NULL DEFAULT '1' COMMENT '就诊类型(单选):1=初诊,2=复诊',
  `treatment_department` int(1) NOT NULL DEFAULT '1' COMMENT '就诊科室(单选):1=内科,2=外科,3=推拿',
  `charge_info_id` int(10) NOT NULL DEFAULT '0' COMMENT '挂号费用id',
  `stage` tinyint(1) NOT NULL DEFAULT '1' COMMENT '时间阶段 1:morning 2:afternoon 3:evening',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '-1:删除 0.取消 1：正常 ',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `patient_in_member_id` int(10) NOT NULL DEFAULT '0' COMMENT '绑定会员的患者id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for wa_system_dict
-- ----------------------------
DROP TABLE IF EXISTS `wa_system_dict`;
CREATE TABLE `wa_system_dict` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dict_name` varchar(30) NOT NULL DEFAULT '' COMMENT '字典名称',
  `dict_value` varchar(30) NOT NULL DEFAULT '' COMMENT '字典标识',
  `create_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `dict_value` (`dict_value`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of wa_system_dict
-- ----------------------------
INSERT INTO `wa_system_dict` VALUES ('1', '科室', 'DEPARTMENT', '1516600208');
INSERT INTO `wa_system_dict` VALUES ('2', '会员卡类型', 'CARD_TYPE', '1517285280');
INSERT INTO `wa_system_dict` VALUES ('3', '费用类型', 'FEE_TYPE', '1517551629');
INSERT INTO `wa_system_dict` VALUES ('4', '预约项目', 'PROJECT_TYPE', '1517985892');
INSERT INTO `wa_system_dict` VALUES ('5', '就诊类型', 'TREATMENT_TYPE', '1519885932');
INSERT INTO `wa_system_dict` VALUES ('6', '就诊状态', 'MEDICAL_STATUS', '1519886682');
INSERT INTO `wa_system_dict` VALUES ('7', '单位', 'UNIT', '1522036897');
INSERT INTO `wa_system_dict` VALUES ('9', '病人绑定会员关系', 'RELATION_TYPE', '1525251356');

-- ----------------------------
-- Table structure for wa_system_dict_data
-- ----------------------------
DROP TABLE IF EXISTS `wa_system_dict_data`;
CREATE TABLE `wa_system_dict_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dict_id` int(10) NOT NULL DEFAULT '0' COMMENT '字典id',
  `dict_data_name` varchar(30) NOT NULL DEFAULT '',
  `dict_data_value` varchar(30) NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of wa_system_dict_data
-- ----------------------------
INSERT INTO `wa_system_dict_data` VALUES ('1', '5', '初诊', '1', '1');
INSERT INTO `wa_system_dict_data` VALUES ('2', '5', '复诊', '2', '2');
INSERT INTO `wa_system_dict_data` VALUES ('3', '5', '诊疗', '3', '3');
INSERT INTO `wa_system_dict_data` VALUES ('4', '4', '复诊', '1', '1');
INSERT INTO `wa_system_dict_data` VALUES ('5', '4', '初诊', '2', '2');
INSERT INTO `wa_system_dict_data` VALUES ('6', '7', '个', '1', '1');
INSERT INTO `wa_system_dict_data` VALUES ('7', '7', '次', '2', '2');
INSERT INTO `wa_system_dict_data` VALUES ('8', '1', '内科', '1', '0');
INSERT INTO `wa_system_dict_data` VALUES ('9', '1', '外科', '2', '0');
INSERT INTO `wa_system_dict_data` VALUES ('10', '1', '推拿', '3', '0');
INSERT INTO `wa_system_dict_data` VALUES ('11', '1', '脾胃科', '4', '0');
INSERT INTO `wa_system_dict_data` VALUES ('12', '1', '小儿科', '5', '0');
INSERT INTO `wa_system_dict_data` VALUES ('13', '1', '其他', '0', '0');
INSERT INTO `wa_system_dict_data` VALUES ('14', '2', '充值卡', '1', '3');
INSERT INTO `wa_system_dict_data` VALUES ('15', '9', '父母', '1', '1');
INSERT INTO `wa_system_dict_data` VALUES ('16', '9', '朋友', '2', '2');
INSERT INTO `wa_system_dict_data` VALUES ('17', '9', '其他', '0', '3');
INSERT INTO `wa_system_dict_data` VALUES ('18', '9', '子女', '3', '4');
INSERT INTO `wa_system_dict_data` VALUES ('19', '3', '挂号费', '1', '1');
INSERT INTO `wa_system_dict_data` VALUES ('20', '3', '其他收费', '2', '2');
INSERT INTO `wa_system_dict_data` VALUES ('21', '3', '服务项目', '3', '3');
INSERT INTO `wa_system_dict_data` VALUES ('22', '3', '药品', '4', '4');
INSERT INTO `wa_system_dict_data` VALUES ('23', '6', '预约中', '1', '1');
INSERT INTO `wa_system_dict_data` VALUES ('24', '6', '看诊中', '2', '2');
INSERT INTO `wa_system_dict_data` VALUES ('25', '6', '看诊结束', '3', '3');
INSERT INTO `wa_system_dict_data` VALUES ('26', '6', '取消预约', '0', '0');

-- ----------------------------
-- Table structure for wa_test
-- ----------------------------
DROP TABLE IF EXISTS `wa_test`;
CREATE TABLE `wa_test` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` int(10) NOT NULL COMMENT '管理员ID',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID(单选)',
  `category_ids` varchar(100) NOT NULL COMMENT '分类ID(多选)',
  `week` enum('monday','tuesday','wednesday') NOT NULL COMMENT '星期(单选):monday=星期一,tuesday=星期二,wednesday=星期三',
  `flag` set('hot','index','recommend') NOT NULL DEFAULT '' COMMENT '标志(多选):hot=热门,index=首页,recommend=推荐',
  `genderdata` enum('male','female') NOT NULL DEFAULT 'male' COMMENT '性别(单选):male=男,female=女',
  `hobbydata` set('music','reading','swimming') NOT NULL COMMENT '爱好(多选):music=音乐,reading=读书,swimming=游泳',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `image` varchar(100) NOT NULL DEFAULT '' COMMENT '图片',
  `images` varchar(1500) NOT NULL DEFAULT '' COMMENT '图片组',
  `attachfile` varchar(100) NOT NULL DEFAULT '' COMMENT '附件',
  `keywords` varchar(100) NOT NULL DEFAULT '' COMMENT '关键字',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '描述',
  `city` varchar(100) NOT NULL DEFAULT '' COMMENT '省市',
  `price` float(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '价格',
  `views` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点击',
  `startdate` date DEFAULT NULL COMMENT '开始日期',
  `activitytime` datetime DEFAULT NULL COMMENT '活动时间(datetime)',
  `year` year(4) DEFAULT NULL COMMENT '年',
  `times` time DEFAULT NULL COMMENT '时间',
  `refreshtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '刷新时间(int)',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `switch` tinyint(1) NOT NULL DEFAULT '0' COMMENT '开关',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `state` enum('0','1','2') NOT NULL DEFAULT '1' COMMENT '状态值:0=禁用,1=正常,2=推荐',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='测试表';

-- ----------------------------
-- Records of wa_test
-- ----------------------------
INSERT INTO `wa_test` VALUES ('1', '0', '12', '12,13', 'monday', 'hot,index', 'male', 'music,reading', '我是一篇测试文章', '<p>我是测试内容</p>', '/assets/img/avatar.png', '/assets/img/avatar.png,/assets/img/qrcode.png', '/assets/img/avatar.png', '关键字', '描述', '广西壮族自治区/百色市/平果县', '0.00', '0', '2017-07-10', '2017-07-10 18:24:45', '2017', '18:24:45', '1499682285', '1499682526', '1499682526', '0', '1', 'normal', '1');


DROP TABLE IF EXISTS admin_users;
CREATE TABLE admin_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  email VARCHAR(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  password VARCHAR(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  name VARCHAR(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  mobile VARCHAR(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  avatar VARCHAR(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  remember_token VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  status int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (id),
  UNIQUE KEY admin_users_username_unique (username),
  UNIQUE KEY admin_users_mobile_unique (mobile)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
--  Records of admin_users
-- ----------------------------
BEGIN;
INSERT INTO admin_users VALUES ('1', 'admin', null, '$2y$10$C.GmjqTSu0u9qjyQgk7k0OPYrBDt/nTNxQtm0jD5sLOJNhLVX5q96', 'Administrator', null, 'http://file.92zhineng.com/images/2020/06/08/image_1591610999_RFoo0kz0.jpg', null, '2020-06-12 14:07:26', '2020-06-12 14:07:26', '1');
COMMIT;

DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  order_sn CHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '订单号',
  send_order_no CHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '三方订单号',
  product_id int(11) NOT NULL DEFAULT '0' COMMENT '礼品id',
  store_id int(11) NOT NULL DEFAULT '0' COMMENT '仓库id',
  num smallint(6) NOT NULL DEFAULT '0' COMMENT '订单数量',
  receiver_name CHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '收货人姓名',
  receiver_phone CHAR(11) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '收货人电话',
  receiver_province CHAR(30) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '收货人所有在省',
  receiver_city CHAR(30) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '收货人所在市',
  receiver_district CHAR(30) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '收货人所在地区',
  recieiver_address VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '收货人地址',
  send_name CHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '发货人',
  send_phone CHAR(11) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '发货人电话',
  freight decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '运费',
  total_price decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '订单总价=num*price+freight',
  price decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '商品单价',
  status tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态0未下单 1已下单 2已取消',
  express_no CHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '物流单号',
  channel_id int(11) NOT NULL DEFAULT '0' COMMENT '渠道订单号id',
  channel tinyint(1) NOT NULL DEFAULT '1' COMMENT '渠道 1平台自营 2秀品街',
  created_at timestamp NULL DEFAULT NULL COMMENT '创建时间',
  updated_at timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS products;
CREATE TABLE products (
  id int(11) NOT NULL AUTO_INCREMENT,
  product_name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '礼品名称',
  product_img VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '礼品图片',
  price decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '平台价格',
  api_price decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '接口价格',
  store_id int(11) NOT NULL DEFAULT '0' COMMENT '仓库id',
  store_name CHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '仓库名称',
  weight CHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '0.00' COMMENT '重量',
  status tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0未上架 1上架',
  sort int(11) NOT NULL DEFAULT '99' COMMENT '排序',
  channel_id int(11) NOT NULL DEFAULT '0' COMMENT '第三方渠道id',
  channel tinyint(2) NOT NULL DEFAULT '1' COMMENT '渠道 1 自营 2秀品街',
  created_at datetime DEFAULT NULL COMMENT '创建时间',
  updated_at datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

-- ----------------------------
--  Records of products
-- ----------------------------
BEGIN;
INSERT INTO products VALUES ('1', '长夜灯', 'http://www.xiupinjie.net/upload/img/22.jpg', '1.90', '1.80', '107', '圆通广州仓', '0.1', '0', '99', '128', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('2', '牙刷', 'http://www.xiupinjie.net/upload/img/33.jpg', '0.65', '0.55', '107', '圆通广州仓', '0.1', '0', '99', '130', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('3', '小夜灯', 'http://www.xiupinjie.net/upload/img/55.jpg', '1.70', '1.60', '107', '圆通广州仓', '0.1', '0', '99', '131', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('4', '洗衣粉【200-245克】', 'http://www.xiupinjie.net/upload/img/66.jpg', '0.45', '0.35', '107', '圆通广州仓', '0.5', '0', '99', '132', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('5', '香包', 'http://www.xiupinjie.net/upload/img/11.jpg', '0.26', '0.16', '107', '圆通广州仓', '0.1', '0', '99', '133', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('6', '抽纸一包', 'http://www.xiupinjie.net/upload/img/44.jpg', '0.66', '0.56', '107', '圆通广州仓', '0.1', '0', '99', '134', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('7', '小老鼠剥橙器10g', 'http://www.xiupinjie.net/upload/pic/202004221944206596.png', '0.30', '0.20', '111', '圆通绍兴仓', '10g', '0', '99', '143', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('8', '家用小皮尺10g', 'http://www.xiupinjie.net/upload/pic/202004221945164282.png', '0.50', '0.40', '111', '圆通绍兴仓', '10g', '0', '99', '144', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('9', '洗衣粉260g', 'http://www.xiupinjie.net/upload/pic/202004221946322327.png', '0.45', '0.35', '111', '圆通绍兴仓', '260g', '0', '99', '145', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('10', '洗衣香皂100g', 'http://www.xiupinjie.net/upload/pic/202004221947251054.png', '1.10', '1.00', '111', '圆通绍兴仓', '100g', '0', '99', '146', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('11', '双头医用棉签100g', 'http://www.xiupinjie.net/upload/pic/202004221952120055.png', '0.60', '0.50', '111', '圆通绍兴仓', '100g', '0', '99', '147', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('12', '小包面巾纸30g', 'http://www.xiupinjie.net/upload/pic/202004221959568476.png', '0.50', '0.40', '111', '圆通绍兴仓', '30g', '0', '99', '148', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('13', '迷你装湿巾40g', 'http://www.xiupinjie.net/upload/pic/202004222005139684.png', '0.60', '0.50', '111', '圆通绍兴仓', '40g', '0', '99', '149', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('14', '双面洗碗海绵60g', 'http://www.xiupinjie.net/upload/pic/202004222009067407.png', '1.00', '0.90', '111', '圆通绍兴仓', '60g', '0', '99', '151', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('15', '水果削皮器10g', 'http://www.xiupinjie.net/upload/pic/202004222017593216.png', '0.50', '0.40', '111', '圆通绍兴仓', '10g', '0', '99', '152', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('16', '办公双面胶20g', 'http://www.xiupinjie.net/upload/pic/202004222020427267.png', '0.75', '0.65', '111', '圆通绍兴仓', '20g', '1', '99', '153', '2', '2020-06-19 08:21:46', '2020-06-19 12:27:25'), ('17', '一次性手套10g', 'http://www.xiupinjie.net/upload/pic/202004222021231225.png', '1.10', '1.00', '111', '圆通绍兴仓', '10g', '0', '99', '154', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('18', '多用途挤压器10g', 'http://www.xiupinjie.net/upload/pic/202004222024177991.png', '0.65', '0.55', '111', '圆通绍兴仓', '10g', '0', '99', '155', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('19', '双面皮鞋刷20g', 'http://www.xiupinjie.net/upload/pic/15.png', '0.90', '0.80', '111', '圆通绍兴仓', '20g', '0', '99', '156', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('20', '清洁海绵20g', 'http://www.xiupinjie.net/upload/pic/16.png', '0.45', '0.35', '111', '圆通绍兴仓', '20g', '0', '99', '157', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('21', '纸袋香包10g', 'http://www.xiupinjie.net/upload/pic/17.png', '0.22', '0.12', '111', '圆通绍兴仓', '10g', '0', '99', '158', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('22', '环保垃圾袋30g', 'http://www.xiupinjie.net/upload/pic/18.png', '0.78', '0.68', '111', '圆通绍兴仓', '30g', '0', '99', '159', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('23', '懒人手机支架10g', 'http://www.xiupinjie.net/upload/pic/19.png', '0.30', '0.20', '111', '圆通绍兴仓', '10g', '1', '99', '160', '2', '2020-06-19 08:21:46', '2020-06-19 12:28:01'), ('24', '可爱刘海夹10g', 'http://www.xiupinjie.net/upload/pic/20.png', '0.22', '0.12', '111', '圆通绍兴仓', '10g', '0', '99', '161', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('25', '便携式牙刷盒20g', 'http://www.xiupinjie.net/upload/pic/21.png', '0.80', '0.70', '111', '圆通绍兴仓', '20g', '0', '99', '162', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('26', '漱口杯40g', 'http://www.xiupinjie.net/upload/pic/22.png', '0.85', '0.75', '111', '圆通绍兴仓', '40g', '0', '99', '163', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('27', '塑料长柄杯刷10g', 'http://www.xiupinjie.net/upload/pic/23.png', '0.70', '0.60', '111', '圆通绍兴仓', '10g', '0', '99', '164', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('28', '折叠衣架30g', 'http://www.xiupinjie.net/upload/pic/24.png', '0.90', '0.80', '111', '圆通绍兴仓', '30g', '0', '99', '165', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('29', '卡通指甲剪20g', 'http://www.xiupinjie.net/upload/pic/25.png', '0.99', '0.89', '111', '圆通绍兴仓', '20g', '0', '99', '166', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('30', '塑料零食盘50g', 'http://www.xiupinjie.net/upload/pic/26.png', '1.10', '1.00', '111', '圆通绍兴仓', '50g', '0', '99', '167', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('31', '水垢清洁剂10g', 'http://www.xiupinjie.net/upload/pic/27.png', '0.48', '0.38', '111', '圆通绍兴仓', '10g', '0', '99', '168', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('32', '手动包饺子器30g', 'http://www.xiupinjie.net/upload/pic/29.png', '0.50', '0.40', '111', '圆通绍兴仓', '30g', '0', '99', '170', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('33', 'USB节能灯30g', 'http://www.xiupinjie.net/upload/pic/33.png', '0.99', '0.89', '111', '圆通绍兴仓', '30g', '0', '99', '174', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('34', '小老鼠剥橙器10g', 'http://www.xiupinjie.net/upload/pic/202004221944206596.png', '0.30', '0.20', '114', '绍兴拼多多仓', '10g', '0', '99', '178', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('35', '家用小皮尺10g', 'http://www.xiupinjie.net/upload/pic/202004221945164282.png', '0.50', '0.40', '114', '绍兴拼多多仓', '10g', '0', '99', '179', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('36', '洗衣粉260g', 'http://www.xiupinjie.net/upload/pic/202004221946322327.png', '0.45', '0.35', '114', '绍兴拼多多仓', '260g', '0', '99', '180', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('37', '洗衣香皂100g', 'http://www.xiupinjie.net/upload/pic/202004221947251054.png', '1.10', '1.00', '114', '绍兴拼多多仓', '100g', '0', '99', '181', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('38', '双头医用棉签100g', 'http://www.xiupinjie.net/upload/pic/202004221952120055.png', '0.60', '0.50', '114', '绍兴拼多多仓', '100g', '0', '99', '182', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('39', '小包面巾纸30g', 'http://www.xiupinjie.net/upload/pic/202004221959568476.png', '0.50', '0.40', '114', '绍兴拼多多仓', '30g', '0', '99', '183', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('40', '迷你装湿巾40g', 'http://www.xiupinjie.net/upload/pic/202004222005139684.png', '0.60', '0.50', '114', '绍兴拼多多仓', '40g', '0', '99', '184', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('41', '双面洗碗海绵60g', 'http://www.xiupinjie.net/upload/pic/202004222009067407.png', '1.00', '0.90', '114', '绍兴拼多多仓', '60g', '0', '99', '185', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('42', '水果削皮器10g', 'http://www.xiupinjie.net/upload/pic/202004222017593216.png', '0.50', '0.40', '114', '绍兴拼多多仓', '10g', '0', '99', '186', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('43', '办公双面胶20g', 'http://www.xiupinjie.net/upload/pic/202004222020427267.png', '0.75', '0.65', '114', '绍兴拼多多仓', '20g', '0', '99', '187', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('44', '一次性手套10g', 'http://www.xiupinjie.net/upload/pic/202004222021231225.png', '1.10', '1.00', '115', '绍兴京东仓,绍兴拼多多仓', '10g', '0', '99', '188', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('45', '多用途挤压器10g', 'http://www.xiupinjie.net/upload/pic/202004222024177991.png', '0.65', '0.55', '114', '绍兴拼多多仓', '10g', '0', '99', '189', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('46', '双面皮鞋刷20g', 'http://www.xiupinjie.net/upload/pic/15.png', '0.90', '0.80', '114', '绍兴拼多多仓', '20g', '0', '99', '190', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('47', '清洁海绵20g', 'http://www.xiupinjie.net/upload/pic/16.png', '0.45', '0.35', '114', '绍兴拼多多仓', '20g', '0', '99', '191', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('48', '纸袋香包10g', 'http://www.xiupinjie.net/upload/pic/17.png', '0.22', '0.12', '114', '绍兴拼多多仓', '10g', '0', '99', '192', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('49', '环保垃圾袋30g', 'http://www.xiupinjie.net/upload/pic/18.png', '0.78', '0.68', '114', '绍兴拼多多仓', '30g', '0', '99', '193', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('50', '懒人手机支架10g', 'http://www.xiupinjie.net/upload/pic/19.png', '0.30', '0.20', '114', '绍兴拼多多仓', '10g', '0', '99', '194', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('51', '可爱刘海夹10g', 'http://www.xiupinjie.net/upload/pic/20.png', '0.22', '0.12', '114', '绍兴拼多多仓', '10g', '0', '99', '195', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('52', '便携式牙刷盒20g', 'http://www.xiupinjie.net/upload/pic/21.png', '0.80', '0.70', '114', '绍兴拼多多仓', '20g', '0', '99', '196', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('53', '漱口杯40g', 'http://www.xiupinjie.net/upload/pic/22.png', '0.85', '0.75', '114', '绍兴拼多多仓', '40g', '0', '99', '197', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('54', '塑料长柄杯刷10g', 'http://www.xiupinjie.net/upload/pic/23.png', '0.70', '0.60', '114', '绍兴拼多多仓', '10g', '0', '99', '198', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('55', '折叠衣架30g', 'http://www.xiupinjie.net/upload/pic/24.png', '0.90', '0.80', '114', '绍兴拼多多仓', '30g', '0', '99', '199', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('56', '卡通指甲剪20g', 'http://www.xiupinjie.net/upload/pic/25.png', '0.99', '0.89', '114', '绍兴拼多多仓', '20g', '0', '99', '200', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('57', '塑料零食盘50g', 'http://www.xiupinjie.net/upload/pic/26.png', '1.10', '1.00', '114', '绍兴拼多多仓', '50g', '0', '99', '201', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('58', '水垢清洁剂10g', 'http://www.xiupinjie.net/upload/pic/27.png', '0.48', '0.38', '114', '绍兴拼多多仓', '10g', '0', '99', '202', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('59', '创意开瓶器40g', 'http://www.xiupinjie.net/upload/pic/28.png', '0.60', '0.50', '114', '绍兴拼多多仓', '40g', '0', '99', '203', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('60', '手动包饺子器30g', 'http://www.xiupinjie.net/upload/pic/29.png', '0.50', '0.40', '114', '绍兴拼多多仓', '30g', '0', '99', '204', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('61', '键盘清洁胶80g', 'http://www.xiupinjie.net/upload/pic/30.png', '0.75', '0.65', '114', '绍兴拼多多仓', '80g', '0', '99', '205', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('62', 'USB节能灯30g', 'http://www.xiupinjie.net/upload/pic/33.png', '0.99', '0.89', '114', '绍兴拼多多仓', '30g', '0', '99', '207', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('63', '小老鼠剥橙器10g', 'http://www.xiupinjie.net/upload/pic/202004221944206596.png', '0.30', '0.20', '115', '绍兴京东仓', '10g', '0', '99', '209', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('64', '家用小皮尺10g', 'http://www.xiupinjie.net/upload/pic/202004221945164282.png', '0.50', '0.40', '115', '绍兴京东仓', '10g', '0', '99', '210', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('65', '洗衣粉260g', 'http://www.xiupinjie.net/upload/pic/202004221946322327.png', '0.45', '0.35', '115', '绍兴京东仓', '260g', '0', '99', '211', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('66', '洗衣香皂100g', 'http://www.xiupinjie.net/upload/pic/202004221947251054.png', '1.10', '1.00', '115', '绍兴京东仓', '100g', '0', '99', '212', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('67', '双头医用棉签100g', 'http://www.xiupinjie.net/upload/pic/202004221952120055.png', '0.60', '0.50', '115', '绍兴京东仓', '100g', '0', '99', '213', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('68', '小包面巾纸30g', 'http://www.xiupinjie.net/upload/pic/202004221959568476.png', '0.50', '0.40', '115', '绍兴京东仓', '30g', '0', '99', '214', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('69', '迷你装湿巾40g', 'http://www.xiupinjie.net/upload/pic/202004222005139684.png', '0.60', '0.50', '115', '绍兴京东仓', '40g', '0', '99', '215', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('70', '双面洗碗海绵60g', 'http://www.xiupinjie.net/upload/pic/202004222009067407.png', '1.00', '0.90', '115', '绍兴京东仓', '60g', '0', '99', '216', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('71', '水果削皮器10g', 'http://www.xiupinjie.net/upload/pic/202004222017593216.png', '0.50', '0.40', '115', '绍兴京东仓', '10g', '0', '99', '217', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('72', '办公双面胶20g', 'http://www.xiupinjie.net/upload/pic/202004222020427267.png', '0.75', '0.65', '115', '绍兴京东仓', '20g', '0', '99', '218', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('73', '一次性手套10g', 'http://www.xiupinjie.net/upload/pic/202004222021231225.png', '1.10', '1.00', '115', '绍兴京东仓', '10g', '0', '99', '219', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('74', '多用途挤压器10g', 'http://www.xiupinjie.net/upload/pic/202004222024177991.png', '0.65', '0.55', '115', '绍兴京东仓', '10g', '0', '99', '220', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('75', '双面皮鞋刷20g', 'http://www.xiupinjie.net/upload/pic/15.png', '0.90', '0.80', '115', '绍兴京东仓', '20g', '0', '99', '221', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('76', '清洁海绵20g', 'http://www.xiupinjie.net/upload/pic/16.png', '0.45', '0.35', '115', '绍兴京东仓', '20g', '0', '99', '222', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('77', '纸袋香包10g', 'http://www.xiupinjie.net/upload/pic/17.png', '0.22', '0.12', '115', '绍兴京东仓', '10g', '0', '99', '223', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('78', '环保垃圾袋30g', 'http://www.xiupinjie.net/upload/pic/18.png', '0.78', '0.68', '115', '绍兴京东仓', '30g', '0', '99', '224', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('79', '懒人手机支架10g', 'http://www.xiupinjie.net/upload/pic/19.png', '0.30', '0.20', '115', '绍兴京东仓', '10g', '0', '99', '225', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('80', '可爱刘海夹10g', 'http://www.xiupinjie.net/upload/pic/20.png', '0.22', '0.12', '115', '绍兴京东仓', '10g', '0', '99', '226', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('81', '便携式牙刷盒20g', 'http://www.xiupinjie.net/upload/pic/21.png', '0.80', '0.70', '115', '绍兴京东仓', '20g', '0', '99', '227', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('82', '漱口杯40g', 'http://www.xiupinjie.net/upload/pic/22.png', '0.85', '0.75', '115', '绍兴京东仓', '40g', '0', '99', '228', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('83', '塑料长柄杯刷10g', 'http://www.xiupinjie.net/upload/pic/23.png', '0.70', '0.60', '115', '绍兴京东仓', '10g', '0', '99', '229', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('84', '折叠衣架30g', 'http://www.xiupinjie.net/upload/pic/24.png', '0.90', '0.80', '115', '绍兴京东仓', '30g', '0', '99', '230', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('85', '卡通指甲剪20g', 'http://www.xiupinjie.net/upload/pic/25.png', '0.99', '0.89', '115', '绍兴京东仓', '20g', '0', '99', '231', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('86', '塑料零食盘50g', 'http://www.xiupinjie.net/upload/pic/26.png', '1.10', '1.00', '115', '绍兴京东仓', '50g', '0', '99', '232', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('87', '水垢清洁剂10g', 'http://www.xiupinjie.net/upload/pic/27.png', '0.48', '0.38', '115', '绍兴京东仓', '10g', '0', '99', '233', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('88', '创意开瓶器40g', 'http://www.xiupinjie.net/upload/pic/28.png', '0.60', '0.50', '115', '绍兴京东仓', '40g', '0', '99', '234', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('89', '手动包饺子器30g', 'http://www.xiupinjie.net/upload/pic/29.png', '0.50', '0.40', '115', '绍兴京东仓', '30g', '0', '99', '235', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('90', '键盘清洁胶80g', 'http://www.xiupinjie.net/upload/pic/30.png', '0.75', '0.65', '115', '绍兴京东仓', '80g', '0', '99', '236', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('91', 'USB节能灯30g', 'http://www.xiupinjie.net/upload/pic/33.png', '0.99', '0.89', '115', '绍兴京东仓', '30g', '0', '99', '238', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('92', '塑料梳子30g-不易丢件', 'http://www.xiupinjie.net/upload/pic/202005192027477494.jpg', '0.20', '0.10', '111', '圆通绍兴仓', '30g', '1', '99', '299', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('93', '塑料梳子30g-不易丢件', 'http://www.xiupinjie.net/upload/pic/202005192030556029.jpg', '0.20', '0.10', '115', '绍兴京东仓', '30g', '0', '99', '300', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46'), ('94', '塑料梳子30g-不易丢件', 'http://www.xiupinjie.net/upload/pic/202005192033344074.jpg', '0.20', '0.10', '114', '绍兴拼多多仓', '30g', '0', '99', '301', '2', '2020-06-19 08:21:46', '2020-06-19 08:21:46');
COMMIT;

-- ----------------------------
--  Table structure for public_stores
-- ----------------------------
DROP TABLE IF EXISTS public_stores;
CREATE TABLE public_stores (
  id int(11) NOT NULL AUTO_INCREMENT,
  store_name CHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '仓库名称',
  price decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '平台价',
  api_price decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '接口价格',
  typename CHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '物流名称',
  address VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '仓库地址',
  status tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  channel tinyint(1) NOT NULL DEFAULT '1' COMMENT '渠道 1平台 2秀品街',
  channel_id int(11) NOT NULL DEFAULT '0' COMMENT '渠道id',
  created_at timestamp NULL DEFAULT NULL COMMENT '创建时间',
  updated_at timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS user_balance_logs;
CREATE TABLE user_balance_logs (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  type tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型1添加 2减少',
  amount decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '变动金额',
  mark VARCHAR(155) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '标记',
  created_at timestamp NULL DEFAULT NULL COMMENT '创建时间',
  updated_at timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
--  Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  name VARCHAR(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户名称',
  balance decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '余额',
  total decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '总额',
  mobile VARCHAR(11) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户手机号',
  password VARCHAR(155) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '密码',
  app_id VARCHAR(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT 'AppID',
  app_secret VARCHAR(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'appSecret',
  status tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 0 禁用 1正常',
  remember_token VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '记住登陆状态',
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY users_mobile_unique (mobile)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


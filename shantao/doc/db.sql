
DROP TABLE IF EXISTS admin;
CREATE TABLE admin (
    id              INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT 'auto id',
    username        VARCHAR(16)  NOT NULL COMMENT '用户名',
    email           VARCHAR(64)  COMMENT '邮箱',
    password        VARCHAR(64)  NOT NULL,
    name            VARCHAR(16)  COMMENT '真实姓名',
    mobile          CHAR(11)     COMMENT '手机号',
    avatar          VARCHAR(255) COMMENT '头像',
    create_time     DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time     TIMESTAMP COMMENT '更新时间',
    status          CHAR(1) NOT NULL COMMENT 'ADMIN_USER_STATUS_',
    UNIQUE KEY username (username),
    UNIQUE KEY mobile (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS product;
CREATE TABLE product (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL DEFAULT '' COMMENT '礼品名称',
    thumb           TEXT COMMENT '商品缩略图',
    cost_price      BIGINT UNSIGNED NOT NULL COMMENT '成本价格',
    warehouse_id    INT UNSIGNED NOT NULL COMMENT 'store.id',
    warehouse_name  CHAR(50) NOT NULL COMMENT '仓库名称',
    weight          INT UNSIGNED COMMENT '重：单位克',
    status          CHAR(1) NOT NULL COMMENT 'PRODUCT_STATUS_',
    isort           INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    channel_id      CHAR(1) NOT NULL COMMENT 'channel.id',
    ext_order_sn    VARCHAR(32) COMMENT '三方商品id',
    create_time     DATETIME DEFAULT NULL COMMENT '创建时间',
    update_time     DATETIME DEFAULT NULL COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS product_additional;
CREATE TABLE product_additional (
    id              INT UNSIGNED NOT NULL PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL COMMENT 'product.id',
    additional      TEXT COMMENT '详情数据 json格式'
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4;

CREATE TABLE product_stock (

) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4;

DROP TABLE IF EXISTS site;
CREATE TABLE site (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(16) NOT NULL COMMENT '分站名称',
    create_time DATETIME NOT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS site_product;
CREATE TABLE site_product (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED NOT NULL COMMENT 'product.id',
    site_id     INT UNSIGNED NOT NULL COMMENT 'site.id',
    price       BIGINT UNSIGNED NOT NULL COMMENT '分站价格 单位分',
    KEY product_id (product_id),
    KEY site_id (site_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS user;
CREATE TABLE user (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username    VARCHAR(16) NOT NULL COMMENT '用户名称',
    balance     BIGINT UNSIGNED NOT NULL DEFAULT '0' COMMENT '余额',
    mobile      VARCHAR(11) NOT NULL COMMENT '用户手机号',
    password    VARCHAR(155) NOT NULL COMMENT '密码',
    verify_code VARCHAR(6) COMMENT '验证码',
    app_id      VARCHAR(60) COMMENT 'AppID',
    app_secret  VARCHAR(60) COMMENT 'appSecret',
    status      CHAR(1) NOT NULL COMMENT 'USER_STATUS_',
    create_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_time TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY username (username),
    UNIQUE KEY mobile (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS cart;
CREATE TABLE cart (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL COMMENT 'user.id',
    product_id      INT UNSIGNED NOT NULL COMMENT 'product.id',
    product_name    VARCHAR(120) NOT NULL COMMENT 'product.name',
    product_number  SMALLINT UNSIGNED NOT NULL COMMENT '购买份数',
    price           BIGINT UNSIGNED NOT NULL COMMENT '单价 单位 分',
    last_op_time    DATETIME NOT NULL COMMENT '最后操作时间',
    KEY user_id(user_id)
);

DROP TABLE IF EXISTS user_order;
CREATE TABLE user_order (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id',
    order_sn            CHAR(64) COMMENT '订单号',
    ext_order_sn        CHAR(64) COMMENT '上家三方订单号',
    product_id          INT UNSIGNED NOT NULL COMMENT '礼品id',
    product_number      SMALLINT UNSIGNED NOT NULL COMMENT '商品数量',
    warehouse_id            INT UNSIGNED NOT NULL COMMENT '仓库id',
    consignee           CHAR(60) NOT NULL DEFAULT '' COMMENT '收货人姓名',
    mobile              CHAR(11) NOT NULL DEFAULT '' COMMENT '收货人手机号',
    province_id         SMALLINT UNSIGNED NOT NULL COMMENT 'province.id',
    city_id             SMALLINT UNSIGNED NOT NULL COMMENT 'city.id',
    district_id         SMALLINT UNSIGNED NOT NULL COMMENT 'district.id',
    address             VARCHAR(255) NOT NULL COMMENT '收货人地址',
    consignor           CHAR(50) NOT NULL COMMENT '发货人',
    consignor_mobile    CHAR(11) NOT NULL COMMENT '发货人电话',
    shipping_fee        BIGINT UNSIGNED NOT NULL COMMENT '运费',
    price               BIGINT UNSIGNED NOT NULL COMMENT '商品单价',
    status              CHAR(1) NOT NULL COMMENT 'ORDER_STATUS_',
    express_no          CHAR(50) NOT NULL DEFAULT '' COMMENT '物流单号',
    follow_ext_order_sn VARCHAR(32) COMMENT '下家三方商品id',
    channel_id          CHAR(1) NOT NULL COMMENT 'PRODUCT_CHANNEL_',
    create_time         TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    KEY user_id(user_id),
    KEY product_id(product_id),
    KEY mobile(mobile),
    KEY warehouse_id(warehouse_id)
) ENGINE=InnoDB CHARSET=utf8mb4;

DROP TABLE IF EXISTS warehouse;
CREATE TABLE warehouse (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ext_id      INT UNSIGNED COMMENT '三方仓库id',
    name        CHAR(50) NOT NULL DEFAULT '' COMMENT '仓库名称',
    price       BIGINT UNSIGNED NOT NULL COMMENT '平台价',
    cost_price   BIGINT UNSIGNED NOT NULL COMMENT '成本价',
    typename    CHAR(50) NOT NULL DEFAULT '' COMMENT '物流名称',
    address     VARCHAR(255) NOT NULL COMMENT '仓库地址',
    channel_id  INT UNSIGNED NOT NULL COMMENT 'channel.id',
    status      CHAR(1) NOT NULL COMMENT 'WAREHOUSE_STATUS_',
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS channel;
CREATE TABLE channel (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(64) NOT NULL COMMENT '供应商渠道名称',
    mobile  CHAR(11) COMMENT '渠道联系方式'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS user_balance_log;
CREATE TABLE user_balance_log (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id',
    type        CHAR(1) NOT NULL COMMENT 'USER_BALANCE_TYPE',
    amount      BIGINT UNSIGNED NOT NULL COMMENT '金额',
    mark        VARCHAR(155) COMMENT '备注',
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    KEY user_id(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




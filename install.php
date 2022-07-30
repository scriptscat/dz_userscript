<?php

runquery("CREATE TABLE `cdb_tampermonkey_script_code` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT '此版本发布人id',
  `script_id` int NOT NULL COMMENT '脚本id',
  `code` text NOT NULL COMMENT '代码内容',
  `meta` text NOT NULL COMMENT '代码信息块',
  `version` varchar(32) NOT NULL COMMENT '版本号',
  `changelog` text CHARACTER SET utf8mb4 NOT NULL COMMENT '更新内容',
  `status` tinyint NOT NULL COMMENT '状态',
  `createtime` bigint NOT NULL COMMENT '发布时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;");

runquery("CREATE TABLE `cdb_tampermonkey_script` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int DEFAULT NULL COMMENT 'dz帖子id',
  `user_id` int NOT NULL COMMENT '发布人id',
  `name` varchar(512) CHARACTER SET utf8mb4 NOT NULL COMMENT '脚本名',
  `description` varchar(2048) CHARACTER SET utf8mb4 NOT NULL COMMENT '脚本描述',
  `content` text CHARACTER SET utf8mb4 NOT NULL COMMENT '脚本详细说明',
  `status` tinyint NOT NULL COMMENT '脚本状态',
  `createtime` bigint NOT NULL COMMENT '创建时间',
  `updatetime` bigint NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;");

$finish = TRUE;

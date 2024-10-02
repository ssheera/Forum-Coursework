CREATE TABLE `user` (
						`id` int NOT NULL AUTO_INCREMENT,
						`username` varchar(255) NOT NULL,
						`email` varchar(255) NOT NULL,
						`password` varchar(255) DEFAULT NULL,
						`created` date DEFAULT NULL,
						`token` varchar(255) NOT NULL,
						PRIMARY KEY (`id`),
						UNIQUE KEY `username_UNIQUE` (`username`),
						UNIQUE KEY `id_UNIQUE` (`id`),
						UNIQUE KEY `email_UNIQUE` (`email`),
						UNIQUE KEY `token_UNIQUE` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `staff` (
						 `id` int NOT NULL AUTO_INCREMENT,
						 `userId` int NOT NULL,
						 PRIMARY KEY (`id`),
						 UNIQUE KEY `id_UNIQUE` (`id`),
						 UNIQUE KEY `userId_UNIQUE` (`userId`),
						 CONSTRAINT `staff.userKey` FOREIGN KEY (`userId`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `category` (
							`id` int NOT NULL AUTO_INCREMENT,
							`name` varchar(255) NOT NULL,
							`locked` tinyint DEFAULT '0',
							PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `posts` (
						 `id` int NOT NULL AUTO_INCREMENT,
						 `author` int NOT NULL,
						 `title` longtext NOT NULL,
						 `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
						 `category` int NOT NULL,
						 `keywords` longtext,
						 `parent` int DEFAULT NULL,
						 `created` datetime DEFAULT NULL,
						 `updated` datetime DEFAULT NULL,
						 PRIMARY KEY (`id`),
						 UNIQUE KEY `id_UNIQUE` (`id`),
						 KEY `authorKey_idx` (`author`),
						 KEY `parentKey_idx` (`parent`),
						 KEY `categoryKey_idx` (`category`),
						 CONSTRAINT `posts.authorKey` FOREIGN KEY (`author`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
						 CONSTRAINT `posts.categoryKey` FOREIGN KEY (`category`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
						 CONSTRAINT `posts.parentKey` FOREIGN KEY (`parent`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `attachments` (
							   `id` int NOT NULL AUTO_INCREMENT,
							   `post` int NOT NULL,
							   `path` varchar(255) NOT NULL,
							   `name` longtext NOT NULL,
							   `size` int NOT NULL,
							   `system_path` varchar(255) DEFAULT NULL,
							   PRIMARY KEY (`id`),
							   UNIQUE KEY `id_UNIQUE` (`id`),
							   KEY `postKey_idx` (`post`),
							   CONSTRAINT `attachments.postKey` FOREIGN KEY (`post`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

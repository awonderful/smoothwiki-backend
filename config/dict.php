<?php

return [
	'SpaceType'          => [  //空间类型
		'PERSON'           => 1, //个人
		'GROUP'            => 2, //团队
		'PROJECT'          => 3, //项目
	],
	'SpaceMemberRole'    => [  //空间成员角色类型
		'CREATOR'          => 1, //创建者
		'ADMIN'            => 2, //管理员
		'ORDINARY'         => 3, //普通成员
	],
	'SpaceMenuType'      => [ //空间菜单指向的对像类型
		'WIKI'             => 1, //wiki
		'POST'	           => 2, //贴子
		'DATABASE'         => 3, //数据库
		'API'              => 4, //api
		'LINK'             => 5, //链接
	],
	'TreeNodeType'       => [  //树结点类型
		'ARTICLE_PAGE'     => 1, //文档
		'DISSCUSSION_PAGE' => 2, //帖子
		'API_PAGE'         => 2, //帖子
		'MOCK_PAGE'        => 2, //API的mock数据
	],
	'TreeId'             => [
		'MAIN'             => 1, //主树
		'TRASH'            => 2, //回收站
	],
	'ArticleType'        => [  //文档类型
		'RICHTEXT'         => 1, //富文本
		'MARKDOWN'         => 2, //MARKDOWN
		'ATTACHMENT'       => 3, //附件
		'MIND'             => 4, //脑图
	],
];
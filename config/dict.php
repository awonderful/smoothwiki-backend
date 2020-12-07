<?php

return [
	'SpaceType'       => [  //空间类型
		'PERSON'        => 1, //个人
		'GROUP'         => 2, //团队
		'PROJECT'       => 3, //项目
	],
	'SpaceMemberType' => [  //空间成员类型
		'PERSON'        => 1, //个人
		'GROUP'         => 2, //团队
	],
	'SpaceMemberRole' => [  //空间成员角色类型
		'ADMIN'         => 1, //管理员
		'ORDINARY'      => 2, //普通成员
	],
	'SpaceMemberStatus' => [
		'PENDING'       => 1, //审核中
		'APPROVED'      => 2, //通过
		'DENIED'        => 3, //拒绝
	],
	'SpaceMenuType'   => [ //空间菜单指向的对像类型
		'WIKI'          => 1, //wiki
		'POST'		     	=> 2, //贴子
		'DATABASE'      => 3, //数据库
		'API'           => 4, //api
		'LINK'          => 5, //链接
	],
	'TreeId'          => [
		'MAIN'          => 1, //主树
		'TRASH'         => 2, //回收站
	],
	'ArticleType'     => [  //文档类型
		'RICHTEXT'      => 1, //富文本
		'MARKDOWN'      => 2, //MARKDOWN
		'ATTACHMENT'    => 3, //附件
	],
];
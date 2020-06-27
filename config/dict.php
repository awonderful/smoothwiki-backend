<?php

return [
	'SpaceType'        => [  //空间类型
		'PERSONAL'     => 1, //个人
		'GROUP'        => 2, //团体
		'PROJECT'      => 3, //项目
	],
	'TreeNodeCategory' => [  //树结点分类
		'COLLECT'      => 1, //收藏夹
		'HOMEPAGE'     => 2, //主页
		'MAINTREE'     => 3, //空间主树
		'TRASH'        => 4, //回收站
		'DRAFT'        => 5, //草稿
	],
	'TreeNodeType'     => [  //树结点类型
		'ROOT'         => 1, //根结点
		'DIRECTORY'    => 2, //文件夹
		'ARTICLE_PAGE' => 3, //普通文档页
	],
	'ArticleType'      => [  //文档类型
		'ATTACHMENT'   => 1, //附件
		'MARKDOWN'     => 2, //markdown
        'RICHTEXT'     => 3, //富文本
        'API'          => 4, //API
	],
];
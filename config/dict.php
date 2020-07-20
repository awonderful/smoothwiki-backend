<?php

return [
	'SpaceType'        => [  //空间类型
		'PERSONAL'     => 1, //个人
		'GROUP'        => 2, //团体
		'PROJECT'      => 3, //项目
	],
	'TreeNodeCategory' => [  //树结点分类
		'MAIN'         => 1, //空间主树
		'TRASH'        => 2, //回收站
	],
	'TreeNodeType'     => [  //树结点类型
		'ARTICLE'      => 0, //普通文档页
		'DISCUSSTION'  => 1, //讨论页
	],
	'ArticleType'      => [  //文档类型
		'ATTACHMENT'   => 1, //附件
		'MARKDOWN'     => 2, //markdown
        'RICHTEXT'     => 3, //富文本
        'API'          => 4, //API
	],
];
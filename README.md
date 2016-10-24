阿里云--存储对象OSS图片操作接口文档 v-0.01
1. 获取图片根目录结构
	> 请求链接：/OSSController.php
	> 请求类型：POST/GET
	> 请求参数：{action: 'catalog'}
	> 返回结果：json对象，例如
		[{"name":"临汾","children":[{"name":"主图"},{"name":"促销"},{"name":"站点图"}]}]

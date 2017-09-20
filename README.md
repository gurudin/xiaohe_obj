# Phalcon 接口说明

## 目录结构
~~~
productName:      项目名称
├─app             主代码文件存放目录
│  ├─Controller   控制器文件
│  ├─Logic        逻辑层文件
│  ├─Model        模型文件
│
├─common          公共文件存放目录
│  ├─function.php 默认引入公共文件
│  ├─...          扩展公共文件（可自定义）
│
├─conf            配置目录
│  ├─conf.php     默认配置存放文件
│  ├─db.ini       数据库配置文件
│  ├─dbpool.php   数据连接池文件
│  ├─tip.php      错误代码配置文件
│  ├─...          更多配置文件（可以自定义）
│
├─tmp             临时文件存放目录
│  ├─cache        缓存目录
│  ├─logs         日志存放目录
│
├─vendor          第三方类库目录
│  ├─...          更多第三方类库（可以自定义）
│
├─doc             版本升级、SQL升级文档存放目录
│  ├─...
├─webroot         入口目录
│  ├─.htaccess    url重写文件
│  ├─index.php    入口文件
│
├─README.md    接口说明文件 
~~~

## 命名规范
* 新增文件均以`.php`后缀名为结尾
* 住代码文件存放目录下均以驼峰式命名
* 控制器文件命名规则`ClassNameController.php`，类名与文件名称保持一致，继承`\Phalcon\Mvc\Controller`
* 逻辑层文件命名规则`ClassNameLogic.php`，类名与文件名称保持一致，继承`\Phalcon\Mvc\Controller`
* 模型文件命名罪责`TableNameModel.php`，表明驼峰式命名方式，继承`\Phalcon\Mvc\Model`
* 除去自定义文件命名，依照php-fig命名规范

## 文件权限
* 临时文件存放目录 需要可读写权限

## URL重写规则
* .htaccess文件重写规则
```
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^((?s).*)$ index.php?_url=/$1 [QSA,L]
</IfModule>
```
* nginx 重写规则
```
RewriteRule ^((?s).*)$ index.php?_url=/$1 last;
```

## 试用数据测试
```
// POST 请求
http://<DOMAIN>/Index/test

// 请求参数
$params = [
    'keys' => [
        'timestamp' => '时间戳',
        'packey'    => '平台key',
        'data_type' => 'json',
        'data_sign' => '签名',
        'token_key' => 'key',
        'token_val' => 'value',
    ],
    'data' => [
        'key' => 'value',
        ... // 自定义参数
    ]
];

// 返回值
{
    "status": true,
    "type": "json",
    "code": "API_COMM_001",
    "code_msg": "success",
    "code_user_msg": "success",
    "result": {
        "uname": "gaox",
        "email": "gaoxiang@xiaohe.com",
        "remark": "这是一个测试"
    }
}
```

## composer install
```
composer create-project gaoxiang001/xiaohe_obj test_obj dev-master -vvv
```

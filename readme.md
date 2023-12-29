
# 礼品商城系统安装说明

    注：php系统函数symlink() proc_get_status() proc_open()需打开在php.ini中

> Composer安装

    composer install

> 复制环境配置文件 配置环境变量 

    cp .env.example .env
    
>    .env需配置信息  数据库相关配置 域名 及debug关闭
    
    DB_DATABASE=数据库
    DB_USERNAME=数据库用户名
    DB_PASSWORD=数据库密码
    
    APP_URL=当前域名
    
    API_DEBUG=false
    
    PAY_DEBUG=false  
    
> 生成系统密钥

       修改.env APP_KEY base64:xaMrdzfDyAtzUwsuXlWiwDPAzXf4fdKqGKDDwOwBgyo=

    
> 注：
    
    如oauth-*.key 有问题可重新安装passort
    
    php artisan passport:install
    
    


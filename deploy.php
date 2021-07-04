<?php
namespace Deployer;

require 'recipe/laravel.php';

// Project name
set('application', 'my_project');

// Project repository
set('repository', 'git@github.com:jfxyl/shop.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// Shared files/dirs between deploys
add('shared_files', []);
add('shared_dirs', []);

// Writable dirs by web server
add('writable_dirs', []);
add('copy_dirs', ['node_modules']);


// Hosts

host('139.196.157.136')
    ->stage('debug')
    ->user('deployer') // 使用 root 账号登录
    ->identityFile('~/.ssh/deployerkey') // 指定登录密钥文件路径
    ->set('http_user', 'www')
    ->set('http_group', 'www')
    ->set('writable_use_sudo', true)
    ->set('deploy_path', '/home/wwwroot/shop');

// Tasks

// 定义一个上传 .env 文件的任务
desc('Upload .env file');
task('env:upload', function() {
    // 将本地的 .env 文件上传到代码目录的 .env
    upload('.env.pro', '{{release_path}}/.env');
});

task('build', function () {
    run('cd {{release_path}} && build');
});

desc('Yarn');
task('deploy:yarn', function () {
    // release_path 是 Deployer 的一个内部变量，代表当前代码目录路径
    // run() 的默认超时时间是 5 分钟，而 yarn 相关的操作又比较费时，因此我们在第二个参数传入 timeout = 600，指定这个命令的超时时间是 10 分钟
    run('cd {{release_path}} && SASS_BINARY_SITE=http://npm.taobao.org/mirrors/node-sass yarn && yarn production', ['timeout' => 600]);
});

desc('Execute elasticsearch migrate');
task('es:migrate', function() {
    // {{bin/php}} 是 Deployer 内置的变量，是 PHP 程序的绝对路径。
    run('{{bin/php}} {{release_path}}/artisan es:migrate');
});

// 定义一个后置钩子，在 artisan:migrate 之后执行 es:migrate 任务
after('artisan:migrate', 'es:migrate');

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.

before('deploy:symlink', 'artisan:migrate');

after('deploy:shared', 'env:upload');

task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:copy_dirs',
    'deploy:yarn',
    'artisan:storage:link',
    'artisan:view:cache',
    'artisan:config:cache',
    'artisan:optimize',
    'deploy:symlink',
    'change-owner',
    'deploy:unlock',
    'cleanup',
]);
task('change-owner',function(){
    cd('{{release_path}}');
    $sudo = get('writable_use_sudo') ? 'sudo' : '';
    run("$sudo chown -R www:www ./*");
});




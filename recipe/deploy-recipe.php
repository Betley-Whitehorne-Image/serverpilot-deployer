<?php


/**
 * Task serverpilot:symlink_public
 * Links the public folder to the current release
 */


use function Deployer\desc;
use function Deployer\get;
use function Deployer\run;
use function Deployer\task;
use function Deployer\writeln;

desc('Make symlink for public to current');
task('serverpilot:symlink_public', function () {
	$deployPath = get('deploy_path');
	$publicPath = str_replace('deployments', 'apps', $deployPath) . '/public';

	run('rm ' . $publicPath . ' -rf');
	run('ln -s ' . $deployPath . '/current/public ' . $publicPath);

	writeln("Don’t forget changes to the .env file and restarting queues!");
});

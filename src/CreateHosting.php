<?php


namespace Riclep\ServerpilotDeployer;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use ServerPilotException;

class CreateHosting
{
	/**
	 * @var \ServerPilot
	 */
	private $serverPilot;
	private $serverName;
	private $deployment;
	private $domain;
	private $app;
	private $password;
	private $user;
	private $staging;
	private $stagingApp;

	public function __construct($serverName, $domain, $deployment, $staging)
	{
		$this->serverPilot = new \ServerPilot([
			'id' => config('serverpilot-deployer.serverpilot_client'),
			'key' => config('serverpilot-deployer.serverpilot_api_key'),
		]);

		$this->serverName = $serverName;
		$this->domain = $domain;
		$this->deployment = $deployment;
		$this->staging = $staging;
	}

	public function setupApp() {
		$this->cleanDomain();

		$this->server = $this->getServer();

		$this->createUser();

		$this->createApp();

		if ($this->staging) {
			$this->createStagingApp();
		}

		if ($this->deployment) {
			$this->createDeployment();
		}

		echo 'Generate deployer script' . "\r\n\r\n";

		echo 'Save these details!' . "\r\n\r\n";

		echo 'Server' . "\r\n";
		echo 'Server name: ' . $this->server->name . "\r\n";
		echo 'Server IP: ' . $this->server->lastaddress . "\r\n";

		echo "\r\n" . 'User' . "\r\n";
		echo 'Username: ' . $this->user->data->name . "\r\n";
		echo 'Password: ' . $this->password . "\r\n";

		echo "\r\n" . 'Production' . "\r\n";
		echo 'App name: ' . $this->app->data->name . "\r\n";
		echo 'Runtime: ' . $this->app->data->runtime . "\r\n";
		echo 'Domains: ' . implode(', ', $this->app->data->domains) . "\r\n";

		if ($this->staging) {
			echo "\r\n" . 'Staging' . "\r\n";
			echo 'Staging app name: ' . $this->stagingApp->data->name . "\r\n";
			echo 'Runtime: ' . $this->stagingApp->data->runtime . "\r\n";
			echo 'Domains: ' . implode(', ', $this->stagingApp->data->domains) . "\r\n";
		}
	}

	private function getServer() {
		try {
			$servers = $this->serverPilot->server_list();

			return current(array_filter($servers->data, function ($server) {
				return $server->name === $this->serverName;
			}));
		} catch(ServerPilotException $e) {
			echo $e->getCode() . ': ' .$e->getMessage();
		}
	}

	private function createUser() {
		$username = $this->serverPilotFriendlyName($this->domain);
		$this->password = bin2hex(random_bytes(20));

		try {
			$this->user = $this->serverPilot->sysuser_create($this->server->id, $username, $this->password);
		} catch(ServerPilotException $e) {
			exit($e->getCode() . ': ' .$e->getMessage());
		}
	}

	private function createApp() {
		$appName = $this->serverPilotFriendlyName($this->domain, 30);

		try {
			$this->app = $this->serverPilot->app_create($appName, $this->user->data->id, config('serverpilot-deployer.php_version'), [
				$this->domain,
				'www.' . $this->domain,
			]);
		} catch(ServerPilotException $e) {
			exit($e->getCode() . ': ' .$e->getMessage());
		}
	}

	private function createStagingApp() {
		$appName = $this->serverPilotFriendlyName('' . $this->domain, 22) . '-staging';

		try {
			$this->stagingApp = $this->serverPilot->app_create($appName, $this->user->data->id, config('serverpilot-deployer.php_version'), [
				'staging.' . $this->domain,
			]);
		} catch(ServerPilotException $e) {
			exit($e->getCode() . ': ' .$e->getMessage());
		}
	}

	private function cleanDomain() {
		$this->domain = (string) Str::of($this->domain)->replaceFirst('www.', '')->replaceLast('/', '');
	}

	private function serverPilotFriendlyName($string, $length = 32) {
		// create name based off the appName
		$output = Str::of($string)->replace('.', '-')->slug()->limit($length)->lower();

		// names can not start with a number
		if ($this->startsWithNumber($output)) {
			$output = $this->removeStartingNumbers($output);
		}

		// names must be at least 3 characters
		if (strlen($output) < 3) {
			$output = $output . 'bwi';
		}

		return (string) $output;
	}

	private function startsWithNumber($string) {
		return preg_match('/^\d/', $string) === 1;
	}

	private function removeStartingNumbers($string) {
		return ltrim($string, '0..9');
	}

	private function createDeployment() {
		if (!file_exists(config_path('deploy.php'))) {
			Artisan::call('deploy:init ' . $this->server->lastaddress . ' -a');

			$configFile = config_path('deploy.php');

			$config = File::get($configFile);

			$config = str_replace([
				"'deploy_path' => '/var/www/" . $this->server->lastaddress . "'",
				"'user' => 'root'",
				"'https://bitbucket.org",
				//"include"
				"'npm:install',",
				"'npm:production',",
				"'artisan:migrate',",
				"'artisan:horizon:terminate',",
				"'fpm:reload',",
			], [
				"'deploy_path' => '/srv/users/" . $this->user->data->name . "/deployments/" . $this->app->data->name . "'",
				"'user' => '" . $this->user->data->name . "'",
				"'https://'  . env('BITBUCKET') . '",
				//"'vendor/riclep/serverpilot-deployer/recipe/deploy-recipe.php'",
				"", // remove fpm:reload
				"", // remove artisan:horizon:terminate
				"", // remove artisan:migrate
				"", // remove npm:production
				"", // remove npm:install
			], $config);

			$config = preg_replace([
				"/(success'[^\\n\\r]+\s+)(\/\/)/",
				"/(include'[^\\n\\r]+\s+)(\/\/)/",
			], [
				"$1'serverpilot:symlink_public',",
				"$1'vendor/riclep/serverpilot-deployer/recipe/deploy-recipe.php',",
			],  $config);

			File::put($configFile, $config);

			/*
			desc('Make symlink for public to current');
			task('serverpilot:symlink_public', function () {
				$deployPath = get('deploy_path');
				$publicPath = str_replace('deployments', 'apps', $deployPath) . '/public';

				run('rm ' . $publicPath . ' -rf');
				run('ln -s ' . $deployPath . '/current/public ' . $publicPath);
			});
*/

			// TODO
			/*
			 * this fails - wrong user
			  The command "echo "" | sudo -S /usr/sbin/service php7.3-fpm reload" failed.

  Exit Code: 1 (General error)

  Host Name: 167.99.207.229

  ================
  [sudo] password for deploy6-com: Sorry, try again.
  [sudo] password for deploy6-com:
  sudo: no password was provided
  sudo: 1 incorrect password attempt
			 * */
		}
	}
}
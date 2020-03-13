<?php


namespace Riclep\ServerpilotDeployer;


use Illuminate\Support\Str;
use ServerPilotException;

class CreateHosting
{
	/**
	 * @var \ServerPilot
	 */
	private $serverPilot;
	private $serverName;
	private $domain;
	private $app;
	private $password;
	private $user;
	private $staging;
	private $stagingApp;

	public function __construct($serverName, $domain, $staging)
	{
		$this->serverPilot = new \ServerPilot([
			'id' => config('serverpilot-deployer.serverpilot_client'),
			'key' => config('serverpilot-deployer.serverpilot_api_key'),
		]);

		$this->serverName = $serverName;
		$this->domain = $domain;
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
}
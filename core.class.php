<?php

class Core
{
	private $rootDirectory;
	private $excludeDirectories = [];

	private $removeMode;

	private $findedDirectories = [];

	public function __construct($removeMode = false)
	{
		$this->removeMode = $removeMode;

		if (file_exists('config.json')) {
			$config = json_decode(file_get_contents('config.json'), true);

			if (!empty($config['rootDirectory'])) {
				$this->rootDirectory = rtrim($config['rootDirectory'], '/');
			} else {
				echo 'rootDirectory is empty' . PHP_EOL;
				exit;
			}
		} else {
			echo 'config.json file not exist' . PHP_EOL;
			echo 'You must to be copy config.json.sample to a config.json' . PHP_EOL;
			echo 'cp config.json.sample config.json' . PHP_EOL;
			exit;
		}

		if (!empty($config['excludeDirectories'])) {
			foreach ($config['excludeDirectories'] as $key => $directory) {
				$this->excludeDirectories[$key] = rtrim($directory, '/');
			}
		}
	}

	public function run()
	{
		echo 'Scanning...' . PHP_EOL;
		
		$this->process($this->rootDirectory);
		$this->printDirectories();

		if ($this->removeMode) {
			$this->removeDirectories();
		}
	}

	private function process($processDirectory)
	{
		$directories = scandir($processDirectory);

		if (is_array($directories)) {
			foreach ($directories as $directory) {
				$currentDirectory = "{$processDirectory}/{$directory}";

				if (!in_array($directory, ['.', '..'])) {
					if (!in_array($currentDirectory, $this->excludeDirectories)) {
						if (is_writable($currentDirectory)) {
							if (is_dir($currentDirectory)) {
								if ($directory == 'node_modules') {
									$this->findedDirectories[] = $currentDirectory;
								} else {
									$this->process($currentDirectory);
								}
							}
						}
					}
				}
			}
		}
	}

	private function printDirectories()
	{
		if (!empty($this->findedDirectories)) {
			foreach ($this->findedDirectories as $directory) {
				echo $directory . PHP_EOL;
			}
		} else {
			echo 'Not found node_modules directories' . PHP_EOL;
		}
	}

	private function removeDirectories()
	{
		if (!empty($this->findedDirectories)) {
			foreach ($this->findedDirectories as $directory) {
				exec('rm -rf ' . $directory);
			}
		}
	}
}

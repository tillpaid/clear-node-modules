<?php

class Core
{
	private $rootDirectory;
	private $excludeDirectories = [];

	private $removeMode;

	private $findedDirectories = [];

	private $borderLength = 0;
	private $borderOut = '';
	private $borderIn = '';
	private $maxPathLength = 0;
	private $maxSizeLength = 0;

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
		echo '+=============+' . PHP_EOL;
		echo '| Scanning... |' . PHP_EOL;
		echo '+=============+' . PHP_EOL;

		$this->process($this->rootDirectory);
		$this->setBorderLength();
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
									$this->findedDirectories[] = [
										'path' => $currentDirectory,
										'size' => $this->getDirectorySize($currentDirectory),
									];
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

	private function setBorderLength()
	{
		if (!empty($this->findedDirectories)) {
			foreach ($this->findedDirectories as $directory) {
				if (strlen($directory['path']) > $this->maxPathLength) {
					$this->maxPathLength = strlen($directory['path']);
				}

				if (strlen($directory['size']) > $this->maxSizeLength) {
					$this->maxSizeLength = strlen(number_format($directory['size'], 0, ',', ' '));
				}
			}

			$this->borderLength = 15;
			$this->borderLength += $this->maxPathLength;
			$this->borderLength += $this->maxSizeLength;
		} else {
			$this->borderLength = 36;
		}

		for ($i = 0; $i < $this->borderLength; $i++) {
			$this->borderIn .= '-';
			$this->borderOut .= '=';
		}

		$this->borderIn = "+{$this->borderIn}+";
		$this->borderOut = "+{$this->borderOut}+";
	}

	private function printDirectories()
	{
		$totalSize = 0;

		if (!empty($this->findedDirectories)) {
			echo $this->borderOut . PHP_EOL;

			foreach ($this->findedDirectories as $directory) {
				$totalSize += $directory['size'];
				$formatSize = number_format($directory['size'], 0, ',', ' ');
				$formatSizeLength = strlen($formatSize);

				$sizeSpaces = '';
				if ($formatSizeLength < $this->maxSizeLength) {
					for ($i = $formatSizeLength; $i < $this->maxSizeLength; $i++) {
						$sizeSpaces .= ' ';
					}
				}

				$pathSpaces = '';
				$pathLength = strlen($directory['path']);
				if ($pathLength < $this->maxPathLength) {
					for ($i = $pathLength; $i < $this->maxPathLength; $i++) {
						$pathSpaces .= ' ';
					}
				}

				echo "| Size: {$formatSize}(MB){$sizeSpaces} | {$directory['path']}{$pathSpaces} |" . PHP_EOL;
			}

			$formatTotalSize = number_format($totalSize, 0, ',', ' ') . "(MB)";

			echo $this->borderIn . PHP_EOL;

			$totalSizeSpaces = '';
			$totalSizeSpacesLength = $this->borderLength - strlen($formatTotalSize) - 14;
			if ($totalSizeSpacesLength > 0) {
				for ($i = 0; $i < $totalSizeSpacesLength; $i++) {
					$totalSizeSpaces .= ' ';
				}
			}

			echo "| Total size: {$formatTotalSize}{$totalSizeSpaces} |" . PHP_EOL;
		} else {
			echo $this->borderIn . PHP_EOL;
			echo "| Not found node_modules directories |" . PHP_EOL;
		}

		echo $this->borderOut . PHP_EOL;
	}

	private function removeDirectories()
	{
		if (!empty($this->findedDirectories)) {
			foreach ($this->findedDirectories as $directory) {
				exec('rm -rf ' . $directory['path']);
			}
		}
	}

	private function getDirectorySize($f)
	{
		$io = popen('/usr/bin/du -sk ' . $f, 'r');
		$size = fgets($io, 4096);
		$size = substr($size, 0, strpos($size, "\t"));
		pclose($io);

		$size = $size / 1000;
		$size = ceil($size);

		return $size;
	}
}

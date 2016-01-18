<?php

namespace Milo\VendorVersions;

use Tracy;

/**
 * Bar panel for Tracy (https://tracy.nette.org/) shows versions of libraries parsed from composer.lock.
 *
 * @licence  MIT
 * @link     https://github.com/milo/vendor-versions
 */
class Panel implements Tracy\IBarPanel
{
	/** @var string */
	private $error;

	/** @var string */
	private $dir;


	/**
	 * @param  string $composerLockDir  path to composer.lock's directory
	 */
	public function __construct($composerLockDir = NULL)
	{
		$composerLockDir = $composerLockDir ?: __DIR__ . '/../../../../';
		if (!is_dir($dir = @realpath($composerLockDir))) {
			$this->error = "Path '$composerLockDir' is not a directory.";
		} elseif (!is_file($dir . DIRECTORY_SEPARATOR . 'composer.lock')) {
			$this->error = "Directory '$dir' does not contain the composer.lock file.";
		} else {
			$this->dir = $dir;
		}
	}


	/**
	 * @return string
	 */
	public function getTab()
	{
		ob_start();
		require __DIR__ . '/templates/Panel.tab.phtml';
		return ob_get_clean();
	}


	/**
	 * @return string
	 */
	public function getPanel()
	{
		ob_start();

		if ($this->error === NULL) {
			$file = $this->dir . DIRECTORY_SEPARATOR . 'composer.lock';
			$json = @file_get_contents($file);
			if ($json === FALSE) {
				$this->error = error_get_last()['message'];
				return NULL;
			}

			$decoded = @json_decode($json, TRUE);
			if (!is_array($decoded)) {
				$this->error = error_get_last()['message'];
				return NULL;
			}

			$data = [
				'Packages' => self::format($decoded['packages']),
				'Dev Packages' => self::format($decoded['packages-dev']),
			];
		}

		$error = $this->error;

		require __DIR__ . '/templates/Panel.panel.phtml';
		return ob_get_clean();
	}


	/**
	 * @param  array $packages
	 * @return array
	 */
	private static function format(array $packages)
	{
		$data = [];
		foreach ($packages as $p) {
			$data[$p['name']] = (object) [
				'version' => $p['version'] . ($p['version'] === 'dev-master'
					? (' #' . substr($p['source']['reference'], 0, 7))
					: ''
				),
				'url' => isset($p['source']['url'])
					? preg_replace('/\.git$/', '', $p['source']['url'])
					: NULL,
			];
		}

		ksort($data);
		return $data;
	}

}
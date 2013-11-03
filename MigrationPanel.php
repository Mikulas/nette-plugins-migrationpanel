<?php

namespace Migration;

use DibiConnection;
use Nette;


/**
 * Migration Panel for Nette Debugger
 * @author Mikulas Dite <mikulas@dite.pro>
 */
class NettePanel extends Nette\Application\UI\Control implements Nette\Diagnostics\IBarPanel
{

	/** @var string path */
	private $appDir;

	/** @var DibiConnection */
	private $dibi;

	/** @var MigrationFinder */
	private $finder;

	/** @var array findNewMigrations cache */
	private $tmp;

	public function __construct($appDir, DibiConnection $dibi, \Nette\Application\Application $application)
	{
		parent::__construct($application->getPresenter(), $this->getId());
		$this->appDir = $appDir;
		$this->dibi = $dibi;
		$this->finder = new Finders\MultipleDirectories;
	}

	/**
	 * @return array file => DibiRow
	 */
	protected function getRunMigrations()
	{
		try
		{
			return $this->dibi->query('SELECT * FROM [migrations]')->fetchAssoc('file');
		}
		catch (\DibiDriverException $e)
		{
			if ($e->getCode() === 1146) // table not yet set
			{
				return array();
			}
		}
	}

	/**
	 * @param string $dir filesystem path
	 *
	 * @example addDirectory(%appDir%/../migrations/data)
	 */
	public function addDirectory($dir)
	{
		if (!file_exists($dir))
		{
			throw new \InvalidArgumentException("Directory $dir does not exist");
		}
		$this->finder->addDirectory($dir);
	}

	private function findNewMigrations()
	{
		if ($this->tmp !== NULL)
		{
			return $this->tmp;
		}

		$new = array();
		$all = $this->finder->find([new Extensions\Enumerator]);
		$run = $this->getRunMigrations();

		foreach ($all as $migration)
		{
			$skip = FALSE;
			foreach ($run as $ignore)
			{
				if ($migration->file === $ignore->file)
				{
					$skip = TRUE;
					break;
				}
			}
			if (!$skip)
			{
				$new[] = $migration;
			}
		}

		$this->tmp = $new;
		return $new;
	}

	/**
	 * Loads versioning information from git
	 */
	private function getRevisionMetadata(File $migration)
	{
		$out = array();

		/** see https://www.kernel.org/pub/software/scm/git/docs/git-show.html */
		$format = "%H\x04%aN\x04%at\x04%s"; // hash, author name, author date as unix timestamp, subject
		exec('cd ' . escapeshellarg($this->appDir) . ' && git log --pretty=format:' . $format . ' ' . escapeshellarg($migration->path), $out);
		if (!$out)
		{
			return FALSE;
		}

		list($hash, $author, $timestamp, $subject) = explode("\x04", $out[0]);
		$time = new Nette\DateTime;
		$time->setTimestamp($timestamp);

		return (object) array('hash' => $hash, 'time' => $time, 'author' => $author, 'subject' => $subject);
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return __CLASS__;
	}

	public function getTab()
	{
		$template = $this->getFileTemplate(__DIR__ . '/templates/tab.latte');
		$count = count($this->findNewMigrations());
		if ($count === 0)
		{
			return FALSE;
		}
		$template->count = $count;
		return $template;
	}

	public function getPanel()
	{
		$template = $this->getFileTemplate(__DIR__ . '/templates/panel.latte');

		$meta = array();
		foreach ($this->findNewMigrations() as $migration)
		{
			$data = $this->getRevisionMetadata($migration);
			if ($data) // skips migrations not under vcs
			{
				$meta[$migration->file] = $data;
			}
		}
		$skips = array();
		foreach ($meta as $migration)
		{
			$skips[$migration->hash] = isset($skips[$migration->hash]) ? $skips[$migration->hash] + 1 : 1;
		}
		$template->meta = $meta;
		$template->skips = $skips;

		return $template;
	}

	private function getFileTemplate($templateFilePath)
	{
		if (file_exists($templateFilePath))
		{
			$template = new Nette\Templating\FileTemplate($templateFilePath);
			$template->onPrepareFilters[] = callback($this, 'templatePrepareFilters');
			$template->registerHelperLoader('\Nette\Templating\Helpers::loader');
			$template->basePath = realpath(__DIR__);
			return $template;
		}
		else
		{
			throw new Nette\FileNotFoundException('Requested template file is not exist.');
		}
	}

	public function templatePrepareFilters($template)
	{
		$template->registerFilter($latte = new Nette\Latte\Engine());
		$set = Nette\Latte\Macros\MacroSet::install($latte->getCompiler());
		$set->addMacro('src', NULL, NULL, 'echo \'src="\'.\Nette\Templating\Helpers::dataStream(file_get_contents(%node.word)).\'"\'');
	}

}

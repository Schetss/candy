<?php

namespace ForkCMS\Bundle\InstallerBundle\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\Container;
use Backend\Core\Engine\Model;
use Backend\Core\Installer\CoreInstaller;
use Backend\Core\Installer\ModuleInstaller;
use Backend\Modules\Locale\Engine\Model as BackendLocaleModel;
use ForkCMS\Bundle\InstallerBundle\Entity\InstallationData;

/**
 * This service installs fork
 */
class ForkInstaller
{
    /**
     * The root dir of our project
     *
     * @var string
     */
    private $rootDir;

    /**
     * The Dependency injection container
     *
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $defaultExtras = array();

    /**
     * @todo: - make sure the Container doesn't have to be injected
     *        - make sure the Model::setContainer isn't needed anymore
     *
     * @param Container $container
     * @param           $rootDir
     */
    public function __construct(Container $container, $rootDir)
    {
        $this->container = $container;
        $this->rootDir = $rootDir;

        Model::setContainer($container);
    }

    /**
     * Installs Fork
     *
     * @param  InstallationData $data The collected data required for Fork
     *
     * @return bool                   Is Fork successfully installed?
     */
    public function install(InstallationData $data)
    {
        if (!$data->isValid()) {
            return false;
        }

        // extend execution limit
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $this->createYAMLConfig($data);

        $this->deleteCachedData();

        $this->buildDatabase($data);
        $this->installCore($data);

        $this->installModules($data);
        $this->installExtras();

        $this->createLocaleFiles($data);

        return true;
    }

    /**
     * Fetches the required modules
     *
     * @return string[]
     */
    public static function getRequiredModules()
    {
        return array(
            'Locale',
            'Settings',
            'Users',
            'Groups',
            'Extensions',
            'Pages',
            'Search',
            'ContentBlocks',
            'Tags',
        );
    }

    /**
     * Fetches the hidden modules
     *
     * @return string[]
     */
    public static function getHiddenModules()
    {
        return array(
            'Authentication',
            'Dashboard',
            'Error',
        );
    }

    /**
     * Delete the cached data
     */
    private function deleteCachedData()
    {
        $finder = new Finder();
        $filesystem = new Filesystem();
        foreach ($finder->files()->in(BACKEND_CACHE_PATH)->in(FRONTEND_CACHE_PATH) as $file) {
            /** @var $file \SplFileInfo */
            $filesystem->remove($file->getRealPath());
        }
    }

    /**
     * @param InstallationData $data
     */
    protected function installCore(InstallationData $data)
    {
        // install the core
        $installer = $this->getCoreInstaller($data);
        $installer->install();

        // add the default extras
        $moduleDefaultExtras = $installer->getDefaultExtras();
        if (!empty($moduleDefaultExtras)) {
            $this->defaultExtras = array_merge($this->defaultExtras, $moduleDefaultExtras);
        }
    }

    /**
     * @param InstallationData $data
     */
    protected function buildDatabase(InstallationData $data)
    {
        // put a new instance of the database in the container
        $database = new \SpoonDatabase(
            'mysql',
            $data->getDbHostname(),
            $data->getDbUsername(),
            $data->getDbPassword(),
            $data->getDbDatabase(),
            $data->getDbPort()
        );
        $database->execute(
            'SET CHARACTER SET :charset, NAMES :charset, time_zone = "+0:00"',
            array('charset' => 'utf8mb4')
        );
        $this->container->set('database', $database);
    }

    /**
     * @param  InstallationData $data
     *
     * @return CoreInstaller
     */
    protected function getCoreInstaller(InstallationData $data)
    {
        // create the core installer
        return new CoreInstaller(
            $this->container->get('database'),
            $data->getLanguages(),
            $data->getInterfaceLanguages(),
            $data->hasExampleData(),
            $this->getInstallerData($data)
        );
    }

    /**
     * @param InstallationData $data
     */
    protected function installModules(InstallationData $data)
    {
        foreach (self::getHiddenModules() as $hiddenModule) {
            $data->addModule($hiddenModule);
        }

        // loop modules
        foreach ($data->getModules() as $module) {
            $class = 'Backend\\Modules\\' . $module . '\\Installer\\Installer';

            // install exists
            if (class_exists($class)) {
                // create installer
                /** @var $install ModuleInstaller */
                $installer = new $class(
                    $this->container->get('database'),
                    $data->getLanguages(),
                    $data->getInterfaceLanguages(),
                    $data->hasExampleData(),
                    $this->getInstallerData($data)
                );

                // install the module
                $installer->install();

                // add the default extras
                $moduleDefaultExtras = $installer->getDefaultExtras();
                if (!empty($moduleDefaultExtras)) {
                    $this->defaultExtras = array_merge($this->$defaultExtras, $moduleDefaultExtras);
                }
            }
        }
    }

    protected function installExtras()
    {
        // loop default extras
        foreach ($this->defaultExtras as $extra) {
            // get pages without this extra
            $revisionIds = $this->container->get('database')->getColumn(
                'SELECT i.revision_id
                 FROM pages AS i
                 WHERE i.revision_id NOT IN (
                     SELECT DISTINCT b.revision_id
                     FROM pages_blocks AS b
                     WHERE b.extra_id = ?
                    GROUP BY b.revision_id
                 )',
                array($extra['id'])
            );

            // build insert array for this extra
            $insertExtras = array();
            foreach ($revisionIds as $revisionId) {
                $insertExtras[] = array(
                    'revision_id' => $revisionId,
                    'position' => $extra['position'],
                    'extra_id' => $extra['id'],
                    'created_on' => gmdate('Y-m-d H:i:s'),
                    'edited_on' => gmdate('Y-m-d H:i:s'),
                    'visible' => 'Y',
                );
            }

            // insert block
            $this->container->get('database')->insert('pages_blocks', $insertExtras);
        }
    }

    /**
     * Create locale cache files
     *
     * @param InstallationData $data
     */
    protected function createLocaleFiles(InstallationData $data)
    {
        // all available languages
        $languages = array_unique(
            array_merge($data->getLanguages(), $data->getInterfaceLanguages())
        );

        // loop all the languages
        foreach ($languages as $language) {
            // get applications
            $applications = $this->container->get('database')->getColumn(
                'SELECT DISTINCT application
                 FROM locale
                 WHERE language = ?',
                array((string) $language)
            );

            // loop applications
            foreach ((array) $applications as $application) {
                // build application locale cache
                BackendLocaleModel::buildCache($language, $application);
            }
        }
    }

    /**
     * Writes a config file to app/config/parameters.yml.
     *
     * @param InstallationData $data
     */
    protected function createYAMLConfig(InstallationData $data)
    {
        // these variables should be parsed inside the config file(s).
        $variables = $this->getConfigurationVariables($data);

        // map the config templates to their destination filename
        $yamlFiles = array(
            PATH_WWW . '/app/config/parameters.yml.dist' => PATH_WWW . '/app/config/parameters.yml',
        );

        foreach ($yamlFiles as $sourceFilename => $destinationFilename) {
            $yamlContent = file_get_contents($sourceFilename);
            $yamlContent = str_replace(
                array_keys($variables),
                array_values($variables),
                $yamlContent
            );

            // write app/config/parameters.yml
            $filesystem = new Filesystem();
            $filesystem->dumpFile($destinationFilename, $yamlContent);
        }
    }

    /**
     * @param  InstallationData $data
     *
     * @return array A list of variables that should be parsed into the configuration file(s).
     */
    protected function getConfigurationVariables(InstallationData $data)
    {
        return array(
            '<debug-email>' => $data->hasDifferentDebugEmail() ?
                $data->getDebugEmail() :
                $data->getEmail()
            ,
            '<database-name>' => $data->getDbDatabase(),
            '<database-host>' => addslashes($data->getDbHostname()),
            '<database-user>' => addslashes($data->getDbUsername()),
            '<database-password>' => addslashes($data->getDbPassword()),
            '<database-port>' => $data->getDbPort(),
            '<site-protocol>' => isset($_SERVER['SERVER_PROTOCOL']) ?
                (mb_strpos(mb_strtolower($_SERVER['SERVER_PROTOCOL']), 'https') === false ? 'http' : 'https') :
                'http'
            ,
            '<site-domain>' => (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : 'fork.local',
            '<site-default-title>' => 'Fork CMS',
            '<site-multilanguage>' => $data->getLanguageType() === 'multiple' ? 'true' : 'false',
            '<site-default-language>' => $data->getDefaultLanguage(),
            '<path-www>' => PATH_WWW,
            '<path-library>' => PATH_LIBRARY,
            '<action-group-tag>' => '\@actiongroup',
            '<action-rights-level>' => 7,
            '<secret>' => Model::generateRandomString(32, true, true, true, false),
        );
    }

    /**
     * @param  InstallationData $data
     *
     * @return array A list of variables that will be used in installers.
     */
    protected function getInstallerData(InstallationData $data)
    {
        return array(
            'default_language' => $data->getDefaultLanguage(),
            'default_interface_language' => $data->getDefaultInterfaceLanguage(),
            'spoon_debug_email' => $data->getEmail(),
            'api_email' => $data->getEmail(),
            'site_domain' => (isset($_SERVER['HTTP_HOST'])) ?
                $_SERVER['HTTP_HOST'] :
                'fork.local',
            'site_title' => 'Fork CMS',
            'smtp_server' => '',
            'smtp_port' => '',
            'smtp_username' => '',
            'smtp_password' => '',
            'email' => $data->getEmail(),
            'password' => $data->getPassword(),
        );
    }
}

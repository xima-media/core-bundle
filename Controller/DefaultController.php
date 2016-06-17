<?php

namespace Xima\CoreBundle\Controller;

use Michelf\MarkdownExtra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Template()
     */
    public function releaseNotesAction()
    {
        $adminPool = null;
        if ($this->container->has('sonata.admin.pool')) {
            $adminPool = $this->get('sonata.admin.pool');
        }

        $dbVersion = 'No database versioning enabled.';
        if ($this->container->hasParameter('doctrine_migrations.dir_name')) {
            $dir = $this->container->getParameter('doctrine_migrations.dir_name');
            $connection = $this->get('doctrine')->getConnection();
            $configuration = new \Doctrine\DBAL\Migrations\Configuration\Configuration($connection);
            $configuration->setMigrationsNamespace($this->container->getParameter('doctrine_migrations.namespace'));
            $configuration->setMigrationsDirectory($dir);
            $configuration->setName($this->container->getParameter('doctrine_migrations.name'));
            $configuration->setMigrationsTableName($this->container->getParameter('doctrine_migrations.table_name'));

            $dbVersion = $configuration->formatVersion($configuration->getCurrentVersion());
        }

        $releaseNotes = 'No release notes.';
        $file = $this->get('kernel')->getRootDir() . '/../RELEASENOTES.md';
        if (is_readable($file)) {
            $mdReleaseNotes = file_get_contents($file);
            $releaseNotes = MarkdownExtra::defaultTransform($mdReleaseNotes);
        }

        return array(
            'admin_pool' => $adminPool,
            'dbVersion' => $dbVersion,
            'releaseNotes' => $releaseNotes
        );
    }
}

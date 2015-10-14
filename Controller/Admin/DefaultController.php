<?php

namespace Xima\CoreBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use dflydev\markdown\MarkdownExtraParser;

use Doctrine\DBAL\Migrations\Configuration\Configuration;

class DefaultController extends Controller
{
    /**
     * @Route("/release-notes")
     * @Template()
     */
    public function releaseNotesAction()
    {
        $container = $this->container;

        $admin_pool = $this->get('sonata.admin.pool');

        /* get release notes */
        $mdReleaseNotes = file_get_contents($this->get('kernel')->getRootDir() . '/../RELEASENOTES.md');

        $markdownParser = new MarkdownExtraParser();
        $releaseNotes = $markdownParser->transformMarkdown($mdReleaseNotes);

        $dir = $container->getParameter('doctrine_migrations.dir_name');
        $connection = $this->get('doctrine')->getConnection();
        $configuration  = new Configuration($connection);
        $configuration->setMigrationsNamespace($container->getParameter('doctrine_migrations.namespace'));
        $configuration->setMigrationsDirectory($dir);
        $configuration->setName($container->getParameter('doctrine_migrations.name'));
        $configuration->setMigrationsTableName($container->getParameter('doctrine_migrations.table_name'));

        $dbVersion = $configuration->formatVersion ($configuration->getCurrentVersion());

        return array(
            'admin_pool' => $admin_pool,
            'dbVersion' => $dbVersion,
            'releaseNotes' => $releaseNotes
        );
    }
}
